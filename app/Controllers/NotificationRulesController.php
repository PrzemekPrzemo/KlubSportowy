<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\ValidatesRequest;
use App\Models\NotificationLogModel;
use App\Models\NotificationRuleModel;

/**
 * Faza S.1 — admin UI do zarządzania regułami przypomnień.
 *
 * Klub konfiguruje:
 *   - kiedy słać (np. fee_reminder dla overdue: 3, 7, 14 dni)
 *   - jakim kanałem (email/sms/both)
 *   - max ile razy per target (anti-spam)
 *
 * Plus widok logów wysłanych powiadomień (audit + dashboard).
 */
class NotificationRulesController extends BaseController
{
    use ValidatesRequest;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
    }

    public function index(): void
    {
        $rules = (new NotificationRuleModel())->listForClub();
        $log   = (new NotificationLogModel())->listForClub([], 50);
        $stats = (new NotificationLogModel())->todayStats();

        // Pobierz dostępne template_types z email_templates (per klub + globalne)
        $clubId = $this->currentClub();
        $stmt = Database::pdo()->prepare(
            "SELECT DISTINCT template_type FROM email_templates
             WHERE club_id IS NULL OR club_id = ?
             ORDER BY template_type"
        );
        $stmt->execute([$clubId]);
        $availableTemplates = array_column($stmt->fetchAll(), 'template_type');

        $this->render('notifications/index', [
            'title'              => 'Powiadomienia',
            'rules'              => $rules,
            'log'                => $log,
            'stats'              => $stats,
            'triggerEvents'      => NotificationRuleModel::$TRIGGER_EVENTS,
            'channels'           => NotificationRuleModel::$CHANNELS,
            'availableTemplates' => $availableTemplates,
        ]);
    }

    public function storeRule(): void
    {
        Csrf::verify();
        $back = 'club/notifications';

        $templateType = $this->validateString($_POST['template_type'] ?? '', 'template_type', 1, 80, $back);
        $triggerEvent = $this->validateInList(
            $_POST['trigger_event'] ?? '',
            NotificationRuleModel::$TRIGGER_EVENTS,
            'trigger_event',
            $back
        );
        $daysOffset = $this->validateInt($_POST['days_offset'] ?? '0', 'days_offset', -365, 365, $back);
        $channel    = $this->validateInList(
            $_POST['channel'] ?? '',
            NotificationRuleModel::$CHANNELS,
            'channel',
            $back
        );
        $maxPer = $this->validateInt($_POST['max_per_target'] ?? '1', 'max_per_target', 1, 20, $back);

        $data = [
            'template_type'  => $templateType,
            'trigger_event'  => $triggerEvent,
            'days_offset'    => $daysOffset,
            'channel'        => $channel,
            'max_per_target' => $maxPer,
            'is_active'      => isset($_POST['is_active']) ? 1 : 1, // default ON
            'notes'          => $this->validateOptionalString($_POST['notes'] ?? null, 5000, $back),
        ];

        try {
            (new NotificationRuleModel())->insert($data);
            Session::flash('success', 'Reguła dodana.');
        } catch (\Throwable $e) {
            // UNIQUE collision (taka kombinacja już istnieje)
            Session::flash('error', 'Reguła z tymi parametrami już istnieje (template + event + offset).');
        }
        $this->redirect($back);
    }

    public function updateRule(string $id): void
    {
        Csrf::verify();
        $back = 'club/notifications';
        $idInt = (int)$id;

        $existing = (new NotificationRuleModel())->findById($idInt);
        if (!$existing) {
            Session::flash('error', 'Nie znaleziono reguły.');
            $this->redirect($back);
        }

        $data = [
            'channel'        => $this->validateInList(
                $_POST['channel'] ?? '',
                NotificationRuleModel::$CHANNELS,
                'channel',
                $back
            ),
            'days_offset'    => $this->validateInt($_POST['days_offset'] ?? '0', 'days_offset', -365, 365, $back),
            'max_per_target' => $this->validateInt($_POST['max_per_target'] ?? '1', 'max_per_target', 1, 20, $back),
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
            'notes'          => $this->validateOptionalString($_POST['notes'] ?? null, 5000, $back),
        ];

        (new NotificationRuleModel())->update($idInt, $data);
        Session::flash('success', 'Reguła zaktualizowana.');
        $this->redirect($back);
    }

    public function toggleRule(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        $rule = (new NotificationRuleModel())->findById($idInt);
        if (!$rule) {
            $this->redirect('club/notifications');
        }
        $newVal = empty($rule['is_active']) ? 1 : 0;
        (new NotificationRuleModel())->update($idInt, ['is_active' => $newVal]);
        Session::flash('success', $newVal ? 'Reguła aktywowana.' : 'Reguła dezaktywowana.');
        $this->redirect('club/notifications');
    }

    public function deleteRule(string $id): void
    {
        Csrf::verify();
        (new NotificationRuleModel())->delete((int)$id);
        Session::flash('success', 'Reguła usunięta.');
        $this->redirect('club/notifications');
    }
}
