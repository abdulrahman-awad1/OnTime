<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('clinic.{clinicId}', function () {
    return true;
});
