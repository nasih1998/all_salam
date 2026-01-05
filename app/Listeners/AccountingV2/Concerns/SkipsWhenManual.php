<?php

namespace App\Listeners\AccountingV2\Concerns;

use Illuminate\Support\Facades\Config;

trait SkipsWhenManual
{
    protected function shouldSkipAutomation(): bool
    {
        return !Config::get('accounting_v2.auto_generate_journals', false);
    }
}


