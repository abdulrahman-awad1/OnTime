<?php

namespace App\Exceptions;

use Exception;

class SlotNotAvailableException extends Exception
{
    protected $message = 'الميعاد غير متاح .';
}
