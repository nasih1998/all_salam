<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TestDbController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use App\Models\Role;
use App\Models\Setting;
use App\Models\sms_gateway;
use App\Models\Product;
use DB;
use Auth;
use App\Models\Permission;

class UpdateController extends Controller
{


    
    public function viewStep1(Request $request)
    {
        $role = Auth::user()->roles()->first();
        $permission = Role::findOrFail($role->id)->inRole('setting_system');
        if($permission){
            return view('update.viewStep1');
        }
    }
    
    public function lastStep(Request $request)
    {
        $role = Auth::user()->roles()->first();
        $permission = Role::findOrFail($role->id)->inRole('setting_system');

        if($permission){
            ini_set('max_execution_time', 2000);

            try {
            
                Artisan::call('config:cache');
                Artisan::call('config:clear');

                Artisan::call('migrate --force');

                $role = Role::findOrFail(1);
                $role->permissions()->detach();

                $permissions = array(
                    0 => 'view_employee',
                    1 => 'add_employee',
                    2 => 'edit_employee',
                    3 => 'delete_employee',
                    4 => 'company',
                    5 => 'department',
                    6 => 'designation',
                    7 => 'office_shift',
                    8 => 'attendance',
                    9 => 'leave',
                    10 => 'holiday',
                    11 => 'Top_products',
                    12 => 'Top_customers',
                    13 => 'shipment',
                    14 => 'users_report',
                    15 => 'stock_report',
                    16 => 'sms_settings',
                    17 => 'pos_settings',
                    18 => 'payment_gateway',
                    19 => 'mail_settings',
                    20 => 'dashboard',
                    21 => 'pay_due',
                    22 => 'pay_sale_return_due',
                    23 => 'pay_supplier_due',
                    24 => 'pay_purchase_return_due',
                    25 => 'product_report',
                    26 => 'product_sales_report',
                    27 => 'product_purchases_report',
                    28 => 'notification_template',
                    29 => 'edit_product_sale',
                    30 => 'edit_product_purchase',
                    31 => 'edit_product_quotation',
                    32 => 'edit_tax_discount_shipping_sale',
                    33 => 'edit_tax_discount_shipping_purchase',
                    34 => 'edit_tax_discount_shipping_quotation',
                    35 => 'module_settings',
                    36 => 'count_stock',
                    37 => 'deposit_add',
                    38 => 'deposit_delete',
                    39 => 'deposit_edit',
                    40 => 'deposit_view',
                    41 => 'account',
                    42 => 'inventory_valuation',
                    43 => 'expenses_report',
                    44 => 'deposits_report',
                    45 => 'transfer_money',
                    46 => 'payroll',
                    47 => 'projects',
                    48 => 'tasks',
                    49 => 'appearance_settings',
                    50 => 'translations_settings',
                    51 => 'subscription_product',
                    52 => 'report_error_logs',
                    53 => 'payment_methods',
                    54 => 'report_transactions',
                    55 => 'report_sales_by_category',
                    56 => 'report_sales_by_brand',
                    57 => 'opening_stock_import',
                    58 => 'seller_report',
                    59 => 'Store_settings_view',
                    60 => 'Orders_view',
                    61 => 'Collections_view',
                    62 => 'Banners_view',
                    63 => 'inactive_customers_report',
                    64 => 'zeroSalesProducts',
                    65 => 'Dead_Stock_Report',
                    66 => 'draft_invoices_report',
                    67 => 'discount_summary_report',
                    68 => 'tax_summary_report',
                    69 => 'Stock_Aging_Report',
                    70 => 'Stock_Transfer_Report',
                    71 => 'Stock_Adjustment_Report',
                    72 => 'Top_Suppliers_Report',
                    73 => 'Subscribers_view',
                    74 => 'Messages_view',
                    75 => 'cash_register_report',
                    76 => 'woocommerce_settings',
                    77 => 'customer_display_screen_setup',
                    78 => 'quickbooks_settings',
                    79 => 'customer_loyalty_points_report',
                    80 => 'assets',
                    81 => 'damage_view',
                    82 => 'cash_flow_report',
                    83 => 'report_attendance_summary',
                    84 => 'return_ratio_report',
                    85 => 'negative_stock_report',
                    86 => 'accounting_dashboard',
                    87 => 'chart_of_accounts',
                    88 => 'journal_entries',
                    89 => 'trial_balance',
                    90 => 'accounting_profit_loss',
                    91 => 'balance_sheet',
                    92 => 'accounting_tax_report',
                    

                );

                foreach ($permissions as $permission_slug) {
                    $perm = Permission::firstOrCreate(['name' => $permission_slug]);
                }

                $permissions_data = Permission::pluck('id')->toArray();
                $role->permissions()->attach($permissions_data);

                //create new sms gateway infobip
                sms_gateway::firstOrCreate(['title' => 'infobip']);
                sms_gateway::firstOrCreate(['title' => 'termii']); // ✅ Create "termii" gateway

                // Check if the SMS gateway with the title 'nexmo' exists
                $nexmoGateway = sms_gateway::where('title', 'nexmo')->first();

                if ($nexmoGateway) {
                    // If it exists, delete it
                    $nexmoGateway->delete();
                }

                // Insert SMS message template: subscription_reminder
                DB::table('sms_messages')->updateOrInsert(
                    [
                        'name' => 'subscription_reminder',
                        'text' => "Hello {client_name},\nThis is a reminder from {business_name} that your subscription will renew automatically on {next_billing_date}. \nPlease ensure your payment method is up-to-date to avoid interruptions.\nThank you!",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // ✅ Run TranslationSeeder automatically
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\TranslationSeeder',
                    '--force' => true
                ]);

                // ✅ Run StoreSettingSeeder automatically
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\StoreSettingSeeder',
                    '--force' => true
                ]);

                //----------------------------------------------------
                // ✅ Clean product names containing < or >
                //----------------------------------------------------
                $cleaned = 0;
                \App\Models\Product::where('name', 'REGEXP', '<|>')
                ->chunkById(200, function ($products) use (&$cleaned) {
                    foreach ($products as $p) {
                        $old = $p->name;
                        // Replace dangerous characters or strip HTML
                        $clean = str_replace(['<', '>'], ['‹', '›'], strip_tags($old));
                        if ($clean !== $old) {
                            $p->name = $clean;
                            $p->save();
                            $cleaned++;
                        }
                    }
                });


                // ✅ Clear caches so translations are picked up immediately
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('view:clear');
                Artisan::call('route:clear');

        
                
            } catch (\Exception $e) {
                
                return $e->getMessage();
                
                return 'Something went wrong';
            }
            
            return view('update.finishedUpdate');
        }
    }

}
