<?php

namespace App\Exceptions;

use Exception;

class Timeslotoverlapexception extends Exception
{
    protected $message = 'فيه موعد اتاني متعارض مع الوقت ده في نفس العيادة.';

}
