<?php

namespace App\Observers;

use App\Jobs\SendInquiryNotifications;
use App\Models\Inquiry;

class InquiryObserver
{
    public function created(Inquiry $inquiry): void
    {
        dispatch(new SendInquiryNotifications($inquiry));
    }
}
