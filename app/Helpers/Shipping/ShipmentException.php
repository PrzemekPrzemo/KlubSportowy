<?php

namespace App\Helpers\Shipping;

/**
 * Wyjątek adapterów wysyłki — zamiast return null/false.
 */
class ShipmentException extends \RuntimeException
{
}
