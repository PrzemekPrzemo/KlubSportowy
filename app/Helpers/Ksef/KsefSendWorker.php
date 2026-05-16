<?php

declare(strict_types=1);

namespace App\Helpers\Ksef;

use App\Helpers\Database;
use App\Models\ClubInvoiceItemModel;
use App\Models\ClubInvoiceModel;
use App\Models\ClubKsefConfigModel;
use App\Models\KsefSendQueueModel;
use App\Models\KsefUpoArchiveModel;
use PDO;
use Throwable;

/**
 * Procesor kolejki KSeF — Phase 3.
 *
 * State machine per wpis w `ksef_send_queue`:
 *
 *   queued
 *     ↓ XAdES sign challenge
 *   signing
 *     ↓ init session + send invoice
 *   sending
 *     ↓ Invoice/Status until 200
 *   awaiting_upo
 *     ↓ Invoice/Upo + persist
 *   completed
 *     │
 *     └── (any error) → retrying (next_retry_at = +backoff)
 *                    → failed (po MAX_ATTEMPTS)
 *
 * Bezpieczenstwo: kazdy SELECT idzie z club_id (z wiersza kolejki, nie z
 * sesji). Worker dziala z CLI bez sesji uzytkownika.
 *
 * Idempotencja: SELECT ... FOR UPDATE SKIP LOCKED w lockBatchForProcessing()
 * gwarantuje ze jeden wiersz = jeden worker w jednym momencie.
 */
final class KsefSendWorker
{
    private KsefSendQueueModel  $queue;
    private ClubInvoiceModel    $invoices;
    private ClubInvoiceItemModel $items;
    private ClubKsefConfigModel $config;
    private KsefUpoArchiveModel $upoArchive;

    public function __construct()
    {
        $this->queue      = new KsefSendQueueModel();
        $this->invoices   = new ClubInvoiceModel();
        $this->items      = new ClubInvoiceItemModel();
        $this->config     = new ClubKsefConfigModel();
        $this->upoArchive = new KsefUpoArchiveModel();
    }

    // ── Public API ───────────────────────────────────────────────

    /**
     * Przetwarza batch zadan z kolejki. Zwraca liczbe przetworzonych pozycji.
     *
     * Lock: SELECT ... FOR UPDATE SKIP LOCKED w transakcji + szybkie
     * `updateState(... = 'signing')` aby zwolnic lock przed dlugotrwala IO.
     */
    public function processBatch(int $limit = 10): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $rows = $this->queue->lockBatchForProcessing($limit);
            // Przejscie do pol "in-flight" w tej samej transakcji — zwalnia
            // wiersze do innych workerow po commit (bo status zmienia sie
            // na inny niz queued/retrying).
            foreach ($rows as $row) {
                $newStatus = match ((string)$row['status']) {
                    'queued', 'retrying' => 'signing',
                    'sending'           => 'sending',     // kontynuuj wysylke
                    'awaiting_upo'      => 'awaiting_upo',// poll UPO
                    default             => (string)$row['status'],
                };
                if ($newStatus !== (string)$row['status']) {
                    $this->queue->updateState((int)$row['id'], ['status' => $newStatus]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        $count = 0;
        foreach ($rows as $row) {
            try {
                $this->processOne((int)$row['id']);
                $count++;
            } catch (Throwable $e) {
                // Bledy lapie processOne → markRetry; tu logujemy tylko
                // ostatecznego catch-alla.
                error_log('[KsefSendWorker] uncaught: ' . $e->getMessage());
            }
        }
        return $count;
    }

    /**
     * Wykonuje jedna iteracje state machine dla danego queue id.
     * Nie loopuje — kolejny krok zostanie podjety w kolejnym ticku cron.
     */
    public function processOne(int $queueId): void
    {
        $row = $this->queue->findById($queueId);
        if (!$row) {
            return;
        }

        $clubId    = (int)$row['club_id'];
        $invoiceId = (int)$row['invoice_id'];
        $attempts  = (int)$row['attempts'];

        try {
            switch ((string)$row['status']) {
                case 'signing':
                    $this->stepSignAndInit($row);
                    $this->stepSendInvoice($this->queue->findById($queueId) ?? $row);
                    break;

                case 'sending':
                    // np. po restarcie: invoice juz w trakcie — sprobuj
                    // pobrac status w tym samym tasku.
                    $this->stepPollStatus($row);
                    break;

                case 'awaiting_upo':
                    $this->stepFetchUpo($row);
                    break;

                case 'queued':
                case 'retrying':
                    // batch przelozyl na 'signing' — ale gdyby cos poszlo
                    // nie tak, zlap to tutaj.
                    $this->queue->updateState($queueId, ['status' => 'signing']);
                    $this->stepSignAndInit($this->queue->findById($queueId) ?? $row);
                    $this->stepSendInvoice($this->queue->findById($queueId) ?? $row);
                    break;
            }
        } catch (Throwable $e) {
            $code = method_exists($e, 'getCode') ? (string)$e->getCode() : null;
            $this->queue->markRetry($queueId, $attempts, $e->getMessage(), $code);
            $this->config->audit(
                $clubId,
                'queue_retry',
                'invoice_id=' . $invoiceId . ' err=' . mb_substr($e->getMessage(), 0, 500)
            );
            return;
        }
    }

    // ── State machine steps ──────────────────────────────────────

    /**
     * Krok 1: sign + init session.
     *
     * @param array<string,mixed> $row
     */
    private function stepSignAndInit(array $row): void
    {
        $clubId = (int)$row['club_id'];
        $cfg    = $this->config->findForClub($clubId);
        if (!$cfg || (int)($cfg['enabled'] ?? 0) !== 1) {
            throw new \RuntimeException('KSeF nie jest wlaczony dla klubu.');
        }
        $nip = (string)($cfg['nip'] ?? '');
        if (!ClubKsefConfigModel::validateNip($nip)) {
            throw new \RuntimeException('NIP klubu nieprawidlowy lub brak.');
        }

        $api = new KsefApiClient((string)($cfg['mode'] ?? KsefApiClient::MODE_TEST));

        $challenge = $api->authChallenge($nip);

        $signer = new XAdESSigner();
        $signed = $signer->signChallenge($challenge['challenge'], $nip, $clubId);

        $resp  = $api->initSessionXAdES($signed);
        $token = (string)($resp['sessionToken']['token'] ?? $resp['token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('KSeF nie zwrocil session token.');
        }

        $this->queue->updateState((int)$row['id'], [
            'status'             => 'sending',
            'ksef_session_token' => $token,
            'signed_at'          => date('Y-m-d H:i:s'),
        ]);
        $this->config->audit($clubId, 'queue_signed', 'invoice_id=' . (int)$row['invoice_id']);
    }

    /**
     * Krok 2: generuj XML + wyslij.
     *
     * @param array<string,mixed> $row
     */
    private function stepSendInvoice(array $row): void
    {
        $clubId    = (int)$row['club_id'];
        $invoiceId = (int)$row['invoice_id'];
        $token     = (string)($row['ksef_session_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Brak session token (krok poprzedni nie wykonal init).');
        }

        $invoice = $this->invoices->findForClub($invoiceId, $clubId);
        if (!$invoice) {
            throw new \RuntimeException('Faktura nie istnieje lub poza klubem.');
        }
        $items = $this->items->listForInvoice($invoiceId);
        if (empty($items)) {
            throw new \RuntimeException('Faktura nie ma pozycji.');
        }

        $xml    = FA2XmlGenerator::generate($invoice, $items);
        $b64    = base64_encode($xml);

        // Init API z dobrym mode (config klubu)
        $cfg = $this->config->findForClub($clubId);
        $api = new KsefApiClient((string)($cfg['mode'] ?? KsefApiClient::MODE_TEST));

        $resp = $api->sendInvoice($token, $b64);
        $ref  = (string)($resp['elementReferenceNumber'] ?? $resp['referenceNumber'] ?? '');
        if ($ref === '') {
            throw new \RuntimeException('KSeF nie zwrocil reference number.');
        }

        $this->queue->updateState((int)$row['id'], [
            'status'                 => 'awaiting_upo',
            'sent_at'                => date('Y-m-d H:i:s'),
            'ksef_reference'         => mb_substr((string)($resp['referenceNumber'] ?? $ref), 0, 100),
            'ksef_element_reference' => mb_substr($ref, 0, 100),
            'next_retry_at'          => date('Y-m-d H:i:s', time() + 60), // poll za minute
        ]);
        // Synchronizuj invoice
        $pdo = Database::pdo();
        $pdo->prepare(
            "UPDATE club_invoices SET status = 'sent_ksef', ksef_session_id = ? WHERE id = ? AND club_id = ?"
        )->execute([mb_substr($token, 0, 100), $invoiceId, $clubId]);

        $this->config->audit($clubId, 'queue_sent', 'invoice_id=' . $invoiceId . ' ref=' . $ref);
    }

    /**
     * Krok 3: poll statusu wysylki — jesli OK → przejdz do UPO.
     *
     * @param array<string,mixed> $row
     */
    private function stepPollStatus(array $row): void
    {
        $clubId = (int)$row['club_id'];
        $token  = (string)($row['ksef_session_token'] ?? '');
        $ref    = (string)($row['ksef_element_reference'] ?? $row['ksef_reference'] ?? '');
        if ($token === '' || $ref === '') {
            throw new \RuntimeException('Brak tokenu lub reference do pollowania statusu.');
        }

        $cfg  = $this->config->findForClub($clubId);
        $api  = new KsefApiClient((string)($cfg['mode'] ?? KsefApiClient::MODE_TEST));
        $st   = $api->getInvoiceStatus($token, $ref);
        $code = (int)($st['processingCode'] ?? 0);

        if ($code === 200 || $code === 0) {
            // Approved → przejdz do UPO
            $this->queue->updateState((int)$row['id'], [
                'status'        => 'awaiting_upo',
                'next_retry_at' => date('Y-m-d H:i:s', time() + 30),
            ]);
            $this->stepFetchUpo($this->queue->findById((int)$row['id']) ?? $row);
            return;
        }
        // 4xx — pending; przelozyc na +1m
        $this->queue->updateState((int)$row['id'], [
            'status'        => 'sending',
            'next_retry_at' => date('Y-m-d H:i:s', time() + 60),
        ]);
    }

    /**
     * Krok 4: pobierz UPO i zarchiwizuj.
     *
     * @param array<string,mixed> $row
     */
    private function stepFetchUpo(array $row): void
    {
        $clubId    = (int)$row['club_id'];
        $invoiceId = (int)$row['invoice_id'];
        $token     = (string)($row['ksef_session_token'] ?? '');
        $ref       = (string)($row['ksef_element_reference'] ?? $row['ksef_reference'] ?? '');
        if ($token === '' || $ref === '') {
            throw new \RuntimeException('Brak tokenu / reference do pobrania UPO.');
        }

        $cfg = $this->config->findForClub($clubId);
        $api = new KsefApiClient((string)($cfg['mode'] ?? KsefApiClient::MODE_TEST));

        $upoXml = $api->getUpo($token, $ref);

        // Zapisz do storage/ksef/upo/{club_id}/{invoice_id}.xml
        $dir = ROOT_PATH . '/storage/ksef/upo/' . $clubId;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new \RuntimeException('Nie mozna utworzyc katalogu UPO: ' . $dir);
        }
        $path = $dir . '/' . $invoiceId . '.xml';
        $put  = @file_put_contents($path, $upoXml);
        if ($put === false) {
            throw new \RuntimeException('Zapis UPO nie powiodl sie.');
        }
        @chmod($path, 0640);

        // Wylicz hash dokumentu faktury (z aktualnego XML, ten sam ktory poszedl)
        $invoice = $this->invoices->findForClub($invoiceId, $clubId);
        $items   = $this->items->listForInvoice($invoiceId);
        $invXml  = ($invoice && !empty($items)) ? FA2XmlGenerator::generate($invoice, $items) : '';
        $docHash = $invXml !== '' ? hash('sha256', $invXml) : hash('sha256', $upoXml);

        $this->upoArchive->archive(
            $invoiceId,
            $clubId,
            $path,
            $ref,
            date('Y-m-d H:i:s'),
            $docHash
        );

        // Update invoice → accepted_ksef
        $relativePath = 'storage/ksef/upo/' . $clubId . '/' . $invoiceId . '.xml';
        $pdo = Database::pdo();
        $pdo->prepare(
            "UPDATE club_invoices
                SET status = 'accepted_ksef',
                    ksef_reference_number = ?,
                    ksef_upo_path = ?
              WHERE id = ? AND club_id = ?"
        )->execute([$ref, $relativePath, $invoiceId, $clubId]);

        // Cleanup: terminate session + wyzeruj token
        try {
            $api->terminateSession($token);
        } catch (Throwable) {
            // best-effort — nawet jak terminate sie nie uda, sesja wygasnie sama
        }

        $this->queue->updateState((int)$row['id'], [
            'status'             => 'completed',
            'upo_received_at'    => date('Y-m-d H:i:s'),
            'ksef_session_token' => null,
            'next_retry_at'      => null,
            'last_error_code'    => null,
            'last_error_message' => null,
        ]);

        $this->config->audit($clubId, 'upo_archived', 'invoice_id=' . $invoiceId . ' ref=' . $ref);
        $this->config->audit($clubId, 'queue_completed', 'invoice_id=' . $invoiceId);
    }
}
