<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * NEW FEATURE - SAFE ADDITION
 *
 * Modular provider for Advanced Accounting (V2). Registers observers and console commands.
 * Does not modify legacy modules.
 */
class AccountingV2ServiceProvider extends ServiceProvider
{
	public function register()
	{
		// Merge default config
		$this->mergeConfigFrom(
			base_path('config/accounting_v2.php'), 'accounting_v2'
		);

		// Register commands here to avoid touching Console Kernel
		$this->commands([
			\App\Console\Commands\AccountingCloseYear::class,
		]);
	}

	public function boot()
	{
		if (!Config::get('accounting_v2.enabled', true)) {
			return; // allow disabling without impacting legacy modules
		}

		$this->registerModelObservers();
	}

	protected function registerModelObservers(): void
	{
		// Softly dispatch domain events from model lifecycle without touching controllers
		try {
			if (class_exists(\App\Models\Sale::class)) {
				\App\Models\Sale::created(function ($sale) {
					try { event(new \App\Events\SaleCreated($sale)); } catch (\Throwable $e) { Log::warning('[AccountingV2] SaleCreated dispatch failed: '.$e->getMessage()); }
				});
				\App\Models\Sale::updated(function ($sale) {
					try {
						if (method_exists($sale, 'wasChanged') && $sale->wasChanged('GrandTotal')) {
							event(new \App\Events\SaleUpdated($sale));
						}
					} catch (\Throwable $e) { Log::warning('[AccountingV2] SaleUpdated dispatch failed: '.$e->getMessage()); }
				});
				\App\Models\Sale::deleted(function ($sale) {
					try { event(new \App\Events\SaleDeleted($sale)); } catch (\Throwable $e) { Log::warning('[AccountingV2] SaleDeleted dispatch failed: '.$e->getMessage()); }
				});
			}
			if (class_exists(\App\Models\Purchase::class)) {
				\App\Models\Purchase::created(function ($purchase) {
					try { event(new \App\Events\PurchaseCreated($purchase)); } catch (\Throwable $e) { Log::warning('[AccountingV2] PurchaseCreated dispatch failed: '.$e->getMessage()); }
				});
				\App\Models\Purchase::updated(function ($purchase) {
					try {
						if (method_exists($purchase, 'wasChanged') && $purchase->wasChanged('GrandTotal')) {
							event(new \App\Events\PurchaseUpdated($purchase));
						}
					} catch (\Throwable $e) { Log::warning('[AccountingV2] PurchaseUpdated dispatch failed: '.$e->getMessage()); }
				});
				\App\Models\Purchase::deleted(function ($purchase) {
					try { event(new \App\Events\PurchaseDeleted($purchase)); } catch (\Throwable $e) { Log::warning('[AccountingV2] PurchaseDeleted dispatch failed: '.$e->getMessage()); }
				});
			}
			if (class_exists(\App\Models\Expense::class)) {
				\App\Models\Expense::created(function ($expense) {
					try { event(new \App\Events\ExpenseCreated($expense)); } catch (\Throwable $e) { Log::warning('[AccountingV2] ExpenseCreated dispatch failed: '.$e->getMessage()); }
				});
				\App\Models\Expense::updated(function ($expense) {
					try {
						if (method_exists($expense, 'wasChanged') && $expense->wasChanged('amount')) {
							event(new \App\Events\ExpenseUpdated($expense));
						}
					} catch (\Throwable $e) { Log::warning('[AccountingV2] ExpenseUpdated dispatch failed: '.$e->getMessage()); }
				});
				\App\Models\Expense::deleted(function ($expense) {
					try { event(new \App\Events\ExpenseDeleted($expense)); } catch (\Throwable $e) { Log::warning('[AccountingV2] ExpenseDeleted dispatch failed: '.$e->getMessage()); }
				});
			}
			if (class_exists(\App\Models\SaleReturn::class)) {
				\App\Models\SaleReturn::created(function ($saleReturn) {
					try { event(new \App\Events\SaleReturned($saleReturn)); } catch (\Throwable $e) { Log::warning('[AccountingV2] SaleReturned dispatch failed: '.$e->getMessage()); }
				});
			}
			// Dispatch PaymentCreated when a PaymentSale is created
			if (class_exists(\App\Models\PaymentSale::class)) {
				\App\Models\PaymentSale::created(function ($payment) {
					try { event(new \App\Events\PaymentCreated($payment)); } catch (\Throwable $e) { Log::warning('[AccountingV2] PaymentCreated dispatch failed: '.$e->getMessage()); }
				});
				\App\Models\PaymentSale::updated(function ($payment) {
					try {
						if (method_exists($payment,'wasChanged') && $payment->wasChanged('deleted_at') && !is_null($payment->deleted_at)) {
							event(new \App\Events\PaymentDeleted($payment));
						}
					} catch (\Throwable $e) { Log::warning('[AccountingV2] PaymentDeleted dispatch failed: '.$e->getMessage()); }
				});
			}
			if (class_exists(\App\Models\PaymentPurchase::class)) {
				\App\Models\PaymentPurchase::created(function ($payment) {
					try { event(new \App\Events\PaymentPurchaseCreated($payment)); } catch (\Throwable $e) { Log::warning('[AccountingV2] PaymentPurchaseCreated dispatch failed: '.$e->getMessage()); }
				});
				\App\Models\PaymentPurchase::updated(function ($payment) {
					try {
						if (method_exists($payment,'wasChanged') && $payment->wasChanged('deleted_at') && !is_null($payment->deleted_at)) {
							event(new \App\Events\PaymentPurchaseDeleted($payment));
						}
					} catch (\Throwable $e) { Log::warning('[AccountingV2] PaymentPurchaseDeleted dispatch failed: '.$e->getMessage()); }
				});
			}
		} catch (\Throwable $e) {
			Log::warning('[AccountingV2] Observer registration failed: '.$e->getMessage());
		}
	}
}


