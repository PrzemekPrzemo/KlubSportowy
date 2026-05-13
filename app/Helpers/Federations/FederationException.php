<?php

namespace App\Helpers\Federations;

/**
 * Wyjątek FederationExporter — adaptery rzucają zamiast return null/false.
 *
 * Zalecane kategorie błędów (subklasy do dodania w razie potrzeby):
 *   - FederationConfigException     → brak/niepoprawne credentials
 *   - FederationApiException        → błąd komunikacji z API
 *   - FederationValidationException → nieprawidłowe dane członka (PESEL, etc.)
 */
class FederationException extends \RuntimeException
{
}
