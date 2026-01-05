<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\SaleCreated::class => [
            \App\Listeners\AccountingV2\GenerateSaleJournal::class,
        ],
        \App\Events\PurchaseCreated::class => [
            \App\Listeners\AccountingV2\GeneratePurchaseJournal::class,
        ],
        \App\Events\ExpenseCreated::class => [
            \App\Listeners\AccountingV2\GenerateExpenseJournal::class,
        ],
        \App\Events\SaleReturned::class => [
            \App\Listeners\AccountingV2\GenerateSaleReturnJournal::class,
        ],
        \App\Events\SaleUpdated::class => [
            \App\Listeners\AccountingV2\GenerateSaleAdjustment::class,
        ],
        \App\Events\PurchaseUpdated::class => [
            \App\Listeners\AccountingV2\GeneratePurchaseAdjustment::class,
        ],
        \App\Events\ExpenseUpdated::class => [
            \App\Listeners\AccountingV2\GenerateExpenseAdjustment::class,
        ],
        \App\Events\SaleDeleted::class => [
            \App\Listeners\AccountingV2\GenerateSaleReversal::class,
        ],
        \App\Events\PurchaseDeleted::class => [
            \App\Listeners\AccountingV2\GeneratePurchaseReversal::class,
        ],
        \App\Events\ExpenseDeleted::class => [
            \App\Listeners\AccountingV2\GenerateExpenseReversal::class,
        ],
        \App\Events\PaymentCreated::class => [
            \App\Listeners\AccountingV2\GeneratePaymentSaleJournal::class,
        ],
        \App\Events\PaymentPurchaseCreated::class => [
            \App\Listeners\AccountingV2\GeneratePaymentPurchaseJournal::class,
        ],
        \App\Events\PaymentDeleted::class => [
            \App\Listeners\AccountingV2\GeneratePaymentSaleReversal::class,
        ],
        \App\Events\PaymentPurchaseDeleted::class => [
            \App\Listeners\AccountingV2\GeneratePaymentPurchaseReversal::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
