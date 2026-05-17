<?php

namespace App\Helpers;

use App\Models\MemberNotificationModel;
use PDO;

/**
 * Self-signup zawodnika na trening + waitlist + auto-promote.
 *
 * Atomicznosc: SELECT COUNT(*) ... FOR UPDATE w transakcji aby
 * uniknac wyscigu na ostatnim wolnym miejscu.
 *
 * Status ENUM po migracji 109:
 *   signed_up | waitlist | attended | absent | cancelled
 *   + legacy: zapisany/obecny/nieobecny/spozniony/wypisany
 *
 * Source ENUM:
 *   admin | trainer | member_self | recurring
 */
class TrainingSignupService
{
    /** @return array{ok:bool,status:string,message:string,http?:int} */
    public static function signup(int $trainingId, int $memberId, int $memberClubId, string $source = 'member_self'): array
    {
        $db = Database::pdo();
        $training = self::fetchTraining($db, $trainingId);
        if (!$training) {
            return ['ok' => false, 'status' => 'not_found', 'message' => 'Trening nie istnieje.', 'http' => 404];
        }
        if ((int)$training['club_id'] !== $memberClubId) {
            return ['ok' => false, 'status' => 'forbidden', 'message' => 'Brak uprawnien do tego treningu.', 'http' => 403];
        }
        if ((int)($training['signup_enabled'] ?? 1) !== 1) {
            return ['ok' => false, 'status' => 'disabled', 'message' => 'Zapisy na ten trening sa wylaczone.', 'http' => 409];
        }
        if (!self::beforeDeadline($training)) {
            return ['ok' => false, 'status' => 'deadline_passed', 'message' => 'Termin zapisow juz minal.', 'http' => 409];
        }
        if (in_array(($training['status'] ?? ''), ['odwolany','zakonczony'], true)) {
            return ['ok' => false, 'status' => 'invalid_state', 'message' => 'Trening odwolany lub zakonczony.', 'http' => 409];
        }

        $max             = $training['max_participants'] !== null ? (int)$training['max_participants'] : 0;
        $waitlistEnabled = (int)($training['waitlist_enabled'] ?? 1) === 1;

        $db->beginTransaction();
        try {
            // Idempotency: jezeli member juz ma aktywny wpis — zwroc istniejacy.
            $stmt = $db->prepare(
                "SELECT id, status FROM training_attendees
                 WHERE training_id = ? AND member_id = ? FOR UPDATE"
            );
            $stmt->execute([$trainingId, $memberId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $cur = (string)$existing['status'];
                if (in_array($cur, ['signed_up','waitlist','attended','zapisany','obecny'], true)) {
                    $db->commit();
                    return ['ok' => true, 'status' => $cur, 'message' => 'Juz jestes zapisany.'];
                }
                // Re-signup po cancellation: zmien status z powrotem.
                $newStatus = 'signed_up';
                if ($max > 0) {
                    $cnt = self::countActive($db, $trainingId, true);
                    if ($cnt >= $max) {
                        if (!$waitlistEnabled) {
                            $db->rollBack();
                            return ['ok' => false, 'status' => 'full', 'message' => 'Trening pelny.', 'http' => 409];
                        }
                        $newStatus = 'waitlist';
                    }
                }
                $up = $db->prepare(
                    "UPDATE training_attendees
                     SET status = ?, signup_source = ?, signed_up_at = NOW(),
                         cancelled_at = NULL, cancellation_reason = NULL
                     WHERE id = ?"
                );
                $up->execute([$newStatus, $source, (int)$existing['id']]);
                $db->commit();
                self::notifySignup($memberId, $memberClubId, $training, $newStatus);
                return ['ok' => true, 'status' => $newStatus, 'message' => $newStatus === 'waitlist'
                    ? 'Dodano do listy rezerwowej.'
                    : 'Zapisales sie na trening.'];
            }

            // INSERT path
            $newStatus = 'signed_up';
            if ($max > 0) {
                $cnt = self::countActive($db, $trainingId, true);
                if ($cnt >= $max) {
                    if (!$waitlistEnabled) {
                        $db->rollBack();
                        return ['ok' => false, 'status' => 'full', 'message' => 'Trening pelny.', 'http' => 409];
                    }
                    $newStatus = 'waitlist';
                }
            }
            $ins = $db->prepare(
                "INSERT INTO training_attendees
                  (training_id, member_id, status, signup_source, signed_up_at, registered_at)
                 VALUES (?, ?, ?, ?, NOW(), NOW())"
            );
            $ins->execute([$trainingId, $memberId, $newStatus, $source]);
            $db->commit();
            self::notifySignup($memberId, $memberClubId, $training, $newStatus);
            return ['ok' => true, 'status' => $newStatus, 'message' => $newStatus === 'waitlist'
                ? 'Dodano do listy rezerwowej.'
                : 'Zapisales sie na trening.'];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('TrainingSignupService::signup failed: ' . $e->getMessage());
            return ['ok' => false, 'status' => 'error', 'message' => 'Blad serwera.', 'http' => 500];
        }
    }

    /** @return array{ok:bool,status:string,message:string,http?:int,promoted?:?array} */
    public static function cancel(int $trainingId, int $memberId, int $memberClubId, ?string $reason = null): array
    {
        $db = Database::pdo();
        $training = self::fetchTraining($db, $trainingId);
        if (!$training) {
            return ['ok' => false, 'status' => 'not_found', 'message' => 'Trening nie istnieje.', 'http' => 404];
        }
        if ((int)$training['club_id'] !== $memberClubId) {
            return ['ok' => false, 'status' => 'forbidden', 'message' => 'Brak uprawnien.', 'http' => 403];
        }
        if (!self::beforeDeadline($training)) {
            return ['ok' => false, 'status' => 'deadline_passed',
                    'message' => 'Termin anulowania minal — skontaktuj sie z trenerem.', 'http' => 409];
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "SELECT id, status FROM training_attendees
                 WHERE training_id = ? AND member_id = ? FOR UPDATE"
            );
            $stmt->execute([$trainingId, $memberId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $db->rollBack();
                return ['ok' => false, 'status' => 'not_registered',
                        'message' => 'Nie jestes zapisany na ten trening.', 'http' => 409];
            }
            $wasSignedUp = in_array((string)$row['status'], ['signed_up','zapisany'], true);

            $up = $db->prepare(
                "UPDATE training_attendees
                 SET status = 'cancelled', cancelled_at = NOW(), cancellation_reason = ?
                 WHERE id = ?"
            );
            $up->execute([$reason !== null ? mb_substr($reason, 0, 500) : null, (int)$row['id']]);

            $promoted = null;
            if ($wasSignedUp) {
                // Auto-promote pierwszy z waitlist (FIFO po signed_up_at / id).
                $pick = $db->prepare(
                    "SELECT id, member_id FROM training_attendees
                     WHERE training_id = ? AND status = 'waitlist'
                     ORDER BY COALESCE(signed_up_at, registered_at) ASC, id ASC
                     LIMIT 1 FOR UPDATE"
                );
                $pick->execute([$trainingId]);
                $waitRow = $pick->fetch(PDO::FETCH_ASSOC);
                if ($waitRow) {
                    $promote = $db->prepare(
                        "UPDATE training_attendees
                         SET status = 'signed_up', signed_up_at = NOW()
                         WHERE id = ?"
                    );
                    $promote->execute([(int)$waitRow['id']]);
                    $promoted = ['attendee_id' => (int)$waitRow['id'], 'member_id' => (int)$waitRow['member_id']];
                }
            }
            $db->commit();

            if ($promoted) {
                // Best-effort notifications (in-app + email).
                self::notifyPromotion((int)$promoted['member_id'], $memberClubId, $training);
            }

            return ['ok' => true, 'status' => 'cancelled',
                    'message' => 'Wypisales sie z treningu.',
                    'promoted' => $promoted];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('TrainingSignupService::cancel failed: ' . $e->getMessage());
            return ['ok' => false, 'status' => 'error', 'message' => 'Blad serwera.', 'http' => 500];
        }
    }

    /**
     * Sprawdzenie deadlinu z parametryzacja (do testow).
     * Zwraca true gdy NOW + deadline_hours <= start_time (mozna sie jeszcze (wy)pisac).
     */
    public static function isBeforeDeadline(array $training, ?\DateTimeImmutable $now = null): bool
    {
        return self::beforeDeadline($training, $now);
    }

    private static function beforeDeadline(array $training, ?\DateTimeImmutable $now = null): bool
    {
        if (empty($training['start_time'])) return false;
        try {
            $start = new \DateTimeImmutable((string)$training['start_time']);
        } catch (\Throwable) {
            return false;
        }
        $now    = $now ?? new \DateTimeImmutable();
        $hours  = (int)($training['signup_deadline_hours'] ?? 2);
        $cutoff = $start->modify('-' . max(0, $hours) . ' hours');
        return $now < $cutoff;
    }

    private static function fetchTraining(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare("SELECT * FROM trainings WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private static function countActive(PDO $db, int $trainingId, bool $forUpdate): int
    {
        $sql = "SELECT COUNT(*) FROM training_attendees
                WHERE training_id = ?
                  AND status IN ('signed_up','zapisany','obecny','attended')";
        if ($forUpdate) $sql .= " FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute([$trainingId]);
        return (int)$stmt->fetchColumn();
    }

    private static function notifySignup(int $memberId, int $clubId, array $training, string $status): void
    {
        try {
            $when  = !empty($training['start_time']) ? date('Y-m-d H:i', strtotime((string)$training['start_time'])) : '';
            $title = $status === 'waitlist'
                ? 'Dodano Cie do listy rezerwowej'
                : 'Zapisales sie na trening';
            $body  = sprintf('%s — %s', (string)($training['name'] ?? 'Trening'), $when);
            (new MemberNotificationModel())->notify(
                $memberId, $clubId, 'general', $title, $body, 'portal/schedule'
            );
        } catch (\Throwable $e) {
            error_log('notifySignup failed: ' . $e->getMessage());
        }
    }

    private static function notifyPromotion(int $memberId, int $clubId, array $training): void
    {
        // In-app
        try {
            $when  = !empty($training['start_time']) ? date('Y-m-d H:i', strtotime((string)$training['start_time'])) : '';
            $title = 'Dostales miejsce na trening';
            $body  = sprintf('Zwolnilo sie miejsce — jestes zapisany na %s (%s).',
                (string)($training['name'] ?? 'trening'), $when);
            (new MemberNotificationModel())->notify(
                $memberId, $clubId, 'general', $title, $body, 'portal/schedule'
            );
        } catch (\Throwable $e) {
            error_log('notifyPromotion (in-app) failed: ' . $e->getMessage());
        }

        // Email — best-effort.
        try {
            $db   = Database::pdo();
            $stmt = $db->prepare(
                "SELECT email, first_name, last_name FROM members
                 WHERE id = ? AND club_id = ? LIMIT 1"
            );
            $stmt->execute([$memberId, $clubId]);
            $m = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($m && !empty($m['email']) && class_exists(EmailService::class)) {
                $when = !empty($training['start_time'])
                    ? date('Y-m-d H:i', strtotime((string)$training['start_time']))
                    : '';
                $subject = 'Awansowales z listy rezerwowej — masz miejsce na trening';
                $body    = sprintf("Czesc %s,\n\nZwolnilo sie miejsce na treningu '%s' (%s). Jestes teraz zapisany.\n\nPlan: %sportal/schedule",
                    trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')),
                    (string)($training['name'] ?? 'Trening'),
                    $when,
                    rtrim(defined('BASE_URL') ? BASE_URL : '/', '/') . '/'
                );
                EmailService::send($clubId, (string)$m['email'], $subject, $body,
                    trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')));
            }
        } catch (\Throwable $e) {
            error_log('notifyPromotion (email) failed: ' . $e->getMessage());
        }
    }
}
