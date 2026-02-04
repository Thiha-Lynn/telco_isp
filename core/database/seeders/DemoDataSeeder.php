<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedNewsletters();
        $this->seedLanguages();
        $this->seedCurrencies();
        $this->seedRoles();
        $this->seedAppBanners();
        $this->seedNotifications();
        $this->seedMaintenanceSettings();
        $this->seedPaymentQuery();
        $this->seedUserQuery();
        $this->seedExtraMonths();
        
        $this->command->info('Demo data seeded successfully!');
    }

    private function seedNewsletters(): void
    {
        if (DB::table('newsletters')->count() == 0) {
            $emails = [
                'subscriber1@example.com',
                'subscriber2@example.com',
                'demo.user@example.com',
                'test.subscriber@example.com',
                'newsletter.fan@example.com',
            ];
            
            foreach ($emails as $email) {
                DB::table('newsletters')->insert([
                    'email' => $email,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('Newsletters seeded: 5 records');
        }
    }

    private function seedLanguages(): void
    {
        if (DB::table('languages')->count() < 2) {
            $languages = [
                ['name' => 'Myanmar', 'code' => 'mm', 'is_default' => 0, 'direction' => 1],
                ['name' => 'Chinese', 'code' => 'zh', 'is_default' => 0, 'direction' => 1],
            ];
            
            foreach ($languages as $lang) {
                if (!DB::table('languages')->where('code', $lang['code'])->exists()) {
                    DB::table('languages')->insert(array_merge($lang, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
            $this->command->info('Languages seeded');
        }
    }

    private function seedCurrencies(): void
    {
        if (DB::table('currencies')->count() < 2) {
            $currencies = [
                ['name' => 'Myanmar Kyat', 'sign' => 'MMK', 'value' => 1, 'is_default' => 1],
                ['name' => 'US Dollar', 'sign' => 'USD', 'value' => 0.00047, 'is_default' => 0],
            ];
            
            foreach ($currencies as $currency) {
                if (!DB::table('currencies')->where('sign', $currency['sign'])->exists()) {
                    DB::table('currencies')->insert(array_merge($currency, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
            $this->command->info('Currencies seeded');
        }
    }

    private function seedRoles(): void
    {
        if (DB::table('role')->count() < 3) {
            $roles = [
                ['name' => 'Super Admin', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Manager', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Support Staff', 'created_at' => now(), 'updated_at' => now()],
            ];
            
            foreach ($roles as $role) {
                if (!DB::table('role')->where('name', $role['name'])->exists()) {
                    DB::table('role')->insert($role);
                }
            }
            $this->command->info('Roles seeded');
        }
    }

    private function seedAppBanners(): void
    {
        // Check if table exists first
        if (!\Illuminate\Support\Facades\Schema::hasTable('app_banner')) {
            \Illuminate\Support\Facades\Schema::create('app_banner', function ($table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('image')->nullable();
                $table->string('link')->nullable();
                $table->integer('status')->default(1);
                $table->integer('order')->default(0);
                $table->timestamps();
            });
            $this->command->info('Created app_banner table');
        }
        
        if (DB::table('app_banner')->count() == 0) {
            $banners = [
                ['title' => 'Welcome Banner', 'image' => 'banner1.jpg', 'link' => '#', 'status' => 1, 'order' => 1],
                ['title' => 'Special Offer', 'image' => 'banner2.jpg', 'link' => '#packages', 'status' => 1, 'order' => 2],
                ['title' => 'New Package', 'image' => 'banner3.jpg', 'link' => '#services', 'status' => 1, 'order' => 3],
            ];
            
            foreach ($banners as $banner) {
                DB::table('app_banner')->insert(array_merge($banner, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
            $this->command->info('App banners seeded: 3 records');
        }
    }

    private function seedNotifications(): void
    {
        // Check if table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
            \Illuminate\Support\Facades\Schema::create('notifications', function ($table) {
                $table->id();
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('type')->default('info');
                $table->integer('user_id')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
            $this->command->info('Created notifications table');
        }
        
        if (DB::table('notifications')->count() == 0) {
            $notifications = [
                ['title' => 'Welcome to ISP Admin', 'message' => 'Your admin account has been set up successfully.', 'type' => 'info'],
                ['title' => 'System Update', 'message' => 'The system has been upgraded to Laravel 12.', 'type' => 'success'],
                ['title' => 'New Feature', 'message' => 'Mobile payment integration is now available.', 'type' => 'info'],
            ];
            
            foreach ($notifications as $notification) {
                DB::table('notifications')->insert(array_merge($notification, [
                    'user_id' => null,
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
            $this->command->info('Notifications seeded: 3 records');
        }
    }

    private function seedMaintenanceSettings(): void
    {
        // Check if table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('maintainance')) {
            \Illuminate\Support\Facades\Schema::create('maintainance', function ($table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->datetime('start_time')->nullable();
                $table->datetime('end_time')->nullable();
                $table->boolean('is_active')->default(0);
                $table->timestamps();
            });
            $this->command->info('Created maintainance table');
        }
        
        if (DB::table('maintainance')->count() == 0) {
            DB::table('maintainance')->insert([
                'title' => 'Scheduled Maintenance',
                'description' => 'Regular system maintenance for performance optimization.',
                'start_time' => now()->addDays(7),
                'end_time' => now()->addDays(7)->addHours(2),
                'is_active' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('Maintenance settings seeded');
        }
    }

    private function seedPaymentQuery(): void
    {
        if (DB::table('payment_query')->count() == 0) {
            // Get a user if exists
            $user = DB::table('users')->first();
            $userId = $user ? $user->id : 1;
            
            $payments = [
                [
                    'user_id' => $userId,
                    'query_data' => json_encode([
                        'transaction_id' => 'TXN' . date('YmdHis') . '001',
                        'amount' => 50000,
                        'method' => 'cbpay',
                        'status' => 'success',
                    ]),
                    'status' => 1,
                ],
                [
                    'user_id' => $userId,
                    'query_data' => json_encode([
                        'transaction_id' => 'TXN' . date('YmdHis') . '002',
                        'amount' => 75000,
                        'method' => 'kbzpay',
                        'status' => 'success',
                    ]),
                    'status' => 1,
                ],
                [
                    'user_id' => $userId,
                    'query_data' => json_encode([
                        'transaction_id' => 'TXN' . date('YmdHis') . '003',
                        'amount' => 100000,
                        'method' => 'wavepay',
                        'status' => 'pending',
                    ]),
                    'status' => 0,
                ],
            ];
            
            foreach ($payments as $payment) {
                try {
                    DB::table('payment_query')->insert(array_merge($payment, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                } catch (\Exception $e) {
                    // Column might not exist, skip
                }
            }
            $this->command->info('Payment query seeded: 3 records');
        }
    }

    private function seedUserQuery(): void
    {
        if (DB::table('user_query')->count() == 0) {
            $queries = [
                [
                    'user_id' => 1,
                    'user_number' => '09123456789',
                    'contact_name' => 'John Doe',
                    'address' => '123 Main Street, Yangon',
                    'reporting_time' => now(),
                    'fault_number' => 'FLT-001',
                    'fault_status' => 'pending',
                    'query_type' => 'speed_issue',
                    'query_data' => json_encode(['subject' => 'Internet Speed Issue', 'message' => 'My internet speed is slower than expected.']),
                    'status' => 0,
                ],
                [
                    'user_id' => 1,
                    'user_number' => '09987654321',
                    'contact_name' => 'Jane Smith',
                    'address' => '456 Second Street, Mandalay',
                    'reporting_time' => now()->subDays(2),
                    'fault_number' => 'FLT-002',
                    'fault_status' => 'resolved',
                    'query_type' => 'billing',
                    'query_data' => json_encode(['subject' => 'Billing Inquiry', 'message' => 'Question about my latest bill.']),
                    'status' => 1,
                ],
            ];
            
            foreach ($queries as $query) {
                try {
                    DB::table('user_query')->insert(array_merge($query, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                } catch (\Exception $e) {
                    $this->command->warn('User query insert error: ' . $e->getMessage());
                }
            }
            $this->command->info('User queries seeded: 2 records');
        }
    }

    private function seedExtraMonths(): void
    {
        if (DB::table('extra_months')->count() < 3) {
            $months = [
                ['user_id' => 1, 'months' => 1, 'reason' => 'Promotional offer'],
                ['user_id' => 1, 'months' => 3, 'reason' => 'Loyalty bonus'],
                ['user_id' => 1, 'months' => 6, 'reason' => 'Annual subscription bonus'],
            ];
            
            foreach ($months as $month) {
                try {
                    DB::table('extra_months')->insert(array_merge($month, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                } catch (\Exception $e) {
                    // Skip if exists
                }
            }
            $this->command->info('Extra months seeded');
        }
    }
}
