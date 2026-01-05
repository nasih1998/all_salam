<?php

namespace App\Listeners\AccountingV2;

use App\Events\PurchaseDeleted;
use App\Listeners\AccountingV2\Concerns\SkipsWhenManual;
use App\Services\AccountingV2\AccountingHelper;

class GeneratePurchaseReversal
{
    use SkipsWhenManual;

    public function handle(PurchaseDeleted $event): void
    {
        if ($this->shouldSkipAutomation()) {
            return;
        }

        AccountingHelper::reversePurchase($event->purchase);
    }
}










