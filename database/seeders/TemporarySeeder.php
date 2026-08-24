<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\OwnerAccount;
use App\Models\SuperAdminAccount;
use Illuminate\Support\Str;

class TemporarySeeder extends Seeder
{
    public function run(): void
    {
        // Create Owner Account
        OwnerAccount::create([
            'uuid' => (string) Str::uuid(),
            'first_name' => 'Owner',
            'last_name' => 'Admin',
            'email' => 'owner@example.com',
            'password' => Hash::make('Owneraccount0000!'),
            'role' => 1, // owner
            'account_status' => 1, // verified
            'active' => 1,
            'date_joined' => now(), // Add date_joined
        ]);

        // Create Super Admin Account
        SuperAdminAccount::create([
            'uuid' => (string) Str::uuid(),
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'linkudhub@gmail.com',
            'password' => Hash::make('Linkudhub0000!'),
            'role' => 0, // super admin
            'account_status' => 1, // verified
            'active' => 1,
            'date_joined' => now(), // Add date_joined
        ]);

        // Optional: Create additional test accounts
        $this->createAdditionalTestAccounts();
    }

    /**
     * Create additional test accounts for different roles
     */
    private function createAdditionalTestAccounts(): void
    {
        // Create Staff Account (if you have StaffAccount model)
        // StaffAccount::create([
        //     'uuid' => (string) Str::uuid(),
        //     'owner_account_id' => 1, // Reference to the owner above
        //     'branch_id' => 1, // Reference to an existing branch
        //     'first_name' => 'Staff',
        //     'last_name' => 'Member',
        //     'email' => 'staff@example.com',
        //     'password' => Hash::make('password123'),
        //     'role' => 2, // staff role
        //     'account_status' => 1,
        //     'active' => 1,
        //     'remember_token' => Str::random(60),
        //     'date_joined' => now(),
        // ]);

        // Create Customer Account (if you have CustomerAccount model)
        // CustomerAccount::create([
        //     'uuid' => (string) Str::uuid(),
        //     'first_name' => 'John',
        //     'last_name' => 'Customer',
        //     'email' => 'customer@example.com',
        //     'password' => Hash::make('password123'),
        //     'role' => 3, // customer role
        //     'account_status' => 1,
        //     'active' => 1,
        //     'remember_token' => Str::random(60),
        //     'date_joined' => now(),
        // ]);
    }
}