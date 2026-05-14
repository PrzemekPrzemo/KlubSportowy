<?php

namespace App\Helpers\Federations;

/**
 * Wynik exportMember/updateMember — standardowy DTO dla wszystkich adapterów.
 *
 * Pole $externalId pozwala kontrolerowi zapisać ID nadane przez federację
 * (np. PZPN player_id) do mapping table — kolejne update'y wymagają tego ID.
 */
final class ExportResult
{
    public function __construct(
        public readonly bool    $ok,
        public readonly ?string $externalId  = null,
        public readonly string  $message     = '',
        public readonly array   $rawResponse = [],
    ) {
    }

    public static function success(string $externalId = '', string $message = '', array $raw = []): self
    {
        return new self(true, $externalId !== '' ? $externalId : null, $message, $raw);
    }

    public static function failure(string $message, array $raw = []): self
    {
        return new self(false, null, $message, $raw);
    }

    public function toArray(): array
    {
        return [
            'ok'           => $this->ok,
            'external_id'  => $this->externalId,
            'message'      => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}
