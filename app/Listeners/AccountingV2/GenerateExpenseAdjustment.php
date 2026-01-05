<?php

namespace App\Listeners\AccountingV2;

use App\Events\ExpenseUpdated;
use App\Listeners\AccountingV2\Concerns\SkipsWhenManual;
use App\Services\AccountingV2\AccountingHelper;

class GenerateExpenseAdjustment
{
    use SkipsWhenManual;

    public function handle(ExpenseUpdated $event): void
    {
        if ($this->shouldSkipAutomation()) {
            return;
        }

        AccountingHelper::fromExpenseAdjustment($event->expense);
    }
}










