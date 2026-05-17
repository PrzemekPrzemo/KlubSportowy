<?php

namespace App\Sports\Studio;

/**
 * Rzucane, gdy zawodnik probuje uzyc karnetu z `classes_remaining = 0`
 * lub karnetu w statusie != active (exhausted/expired/refunded).
 */
class PassExhaustedException extends \RuntimeException
{
}
