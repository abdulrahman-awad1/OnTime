<?php

namespace App\Exceptions;

/**
 * The client_uuid already exists but belongs to a different parent
 * (appointment / medical record). Mapped to HTTP 409.
 */
class ClientUuidConflictException extends \Exception
{
}
