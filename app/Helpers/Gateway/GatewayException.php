<?php

namespace App\Helpers\Gateway;

/**
 * Wyjątek bramki płatności — adaptery rzucają zamiast return null/false.
 *
 * Subklasy wskazują kategorię błędu:
 *   - GatewayConfigException     → brak/niepoprawne creds
 *   - GatewaySignatureException  → webhook signature mismatch
 *   - GatewayApiException        → błąd komunikacji z API
 */
class GatewayException extends \RuntimeException
{
}
