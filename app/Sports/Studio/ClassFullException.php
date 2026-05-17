<?php

namespace App\Sports\Studio;

/**
 * Rzucane gdy klasa jest pelna i waitlist nie jest dozwolony
 * (lub bookForMember zwraca status='waitlist' — wtedy nie throw).
 */
class ClassFullException extends \RuntimeException
{
}
