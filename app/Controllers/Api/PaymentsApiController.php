<?php

namespace App\Controllers\Api;

use App\Helpers\Gateway\CheckoutRequest;
use App\Helpers\Gateway\GatewayException;
use App\Helpers\Gateway\GatewayFactory;
use App\Models\ClubPaymentGatewayModel;
use App\Models\MemberModel;
use App\Models\OnlinePaymentModel;
use App\Models\PaymentModel;

class PaymentsApiController extends BaseApiController
{
    public function index(): void
    {
        // Member tokens: implicit scope to own data. Api keys: explicit scope.
        if ($this->memberId === null) {
            $this->requireScope('payments:read');
        }

        $page     = max(1, (int)($_GET['page'] ?? 1));
        $memberId = $this->memberId ?? (isset($_GET['member_id']) ? (int)$_GET['member_id'] : null);
        $year     = isset($_GET['year']) ? (int)$_GET['year'] : null;
        $result   = (new PaymentModel())->listForClub($memberId, $year, $page, 50);
        $this->paginated($result);
    }

    public function summary(): void
    {
        if ($this->memberId === null) {
            $this->requireScope('payments:read');
            $total = (new PaymentModel())->totalForClubThisYear();
        } else {
            $total = (new PaymentModel())->totalForMemberThisYear($this->memberId);
        }
        $this->json(['data' => ['year' => (int)date('Y'), 'total' => $total]]);
    }

    public function pending(): void
    {
        $this->requireMember();
        $rows = (new OnlinePaymentModel())->pendingForMember($this->memberId);
        $this->json([
            'data' => array_map([$this, 'serializePayment'], $rows),
        ]);
    }

    /**
     * POST /api/v1/payments/init
     * Body: { amount: float, description?: string, fee_rate_id?: int,
     *         period_year?: int, period_month?: int }
     * Returns: { online_payment_id, provider, redirect_url, transaction_id? }
     */
    public function initPayment(): void
    {
        $this->requireMember();

        $body = $this->jsonBody();
        $amount = (float)($body['amount'] ?? 0);
        if ($amount <= 0) {
            $this->error('Kwota musi być większa od zera.', 422, 'invalid_amount');
        }
        $feeRateId = !empty($body['fee_rate_id']) ? (int)$body['fee_rate_id'] : null;
        $year      = (int)($body['period_year'] ?? date('Y'));
        $month     = !empty($body['period_month']) ? (int)$body['period_month'] : null;
        $desc      = trim((string)($body['description'] ?? 'Składka klubowa'));

        $gateway = (new ClubPaymentGatewayModel())->activeGateway();
        if (!$gateway || $gateway['provider'] === 'manual') {
            $this->error(
                'Klub nie udostępnia płatności online.',
                409,
                'gateway_not_configured'
            );
        }

        $opModel = new OnlinePaymentModel();
        $opId    = $opModel->createPayment([
            'club_id'      => $this->clubId,
            'member_id'    => $this->memberId,
            'fee_rate_id'  => $feeRateId,
            'amount'       => $amount,
            'description'  => $desc,
            'period_year'  => $year,
            'period_month' => $month,
            'provider'     => $gateway['provider'],
            'status'       => 'pending',
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $deepReturn = 'clubdesk://payment/return?op_id=' . $opId;
        $notifyUrl  = url('api/v1/payment/webhook/' . $gateway['provider'] . '?club_id=' . $this->clubId);

        $adapter = GatewayFactory::forProvider($gateway['provider'], $gateway);
        if ($adapter === null) {
            $this->error('Bramka płatności nieobsługiwana.', 500, 'gateway_unavailable');
        }

        $member = (new MemberModel())->findById($this->memberId);

        try {
            $req = new CheckoutRequest(
                clubId:            $this->clubId,
                memberId:          $this->memberId,
                amount:            $amount,
                currency:          $gateway['currency'] ?? 'PLN',
                description:       $desc,
                successUrl:        $deepReturn,
                cancelUrl:         $deepReturn . '&cancelled=1',
                notifyUrl:         $notifyUrl,
                internalReference: 'mobile-op#' . $opId,
                customerEmail:     $member['email'] ?? null,
            );
            $result = $adapter->createCheckout($req);
            $opModel->update($opId, [
                'checkout_url' => $result->redirectUrl,
                'provider_id'  => $result->externalId,
            ]);
            $this->json([
                'online_payment_id' => $opId,
                'provider'          => $gateway['provider'],
                'redirect_url'      => $result->redirectUrl,
                'transaction_id'    => $result->externalId,
                'return_scheme'     => 'clubdesk://payment/return',
            ]);
        } catch (GatewayException $e) {
            error_log('Gateway checkout failed (' . $gateway['provider'] . '): ' . $e->getMessage());
            $opModel->update($opId, ['status' => 'failed']);
            $this->error('Inicjacja płatności nie powiodła się.', 502, 'gateway_error');
        }
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function serializePayment(array $row): array
    {
        return [
            'id'           => (int)$row['id'],
            'amount'       => (float)$row['amount'],
            'currency'     => $row['currency'] ?? 'PLN',
            'description'  => $row['description'] ?? null,
            'provider'     => $row['provider'] ?? null,
            'status'       => $row['status'] ?? null,
            'period_year'  => isset($row['period_year']) ? (int)$row['period_year'] : null,
            'period_month' => isset($row['period_month']) ? (int)$row['period_month'] : null,
            'fee_name'     => $row['fee_name'] ?? null,
            'created_at'   => $row['created_at'] ?? null,
            'paid_at'      => $row['paid_at'] ?? null,
            'checkout_url' => $row['checkout_url'] ?? null,
        ];
    }
}
