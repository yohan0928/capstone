<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CustomerAccountsSeeder extends Seeder
{
    public function run()
    {
        // Path to your CSV file
        $csvFile = storage_path('app/customer_accounts.csv');
        
        $this->command->info("Looking for CSV file at: {$csvFile}");
        
        if (!file_exists($csvFile)) {
            $this->command->error("❌ CSV file not found! Please ensure the file exists at: {$csvFile}");
            return;
        }
        
        $file = fopen($csvFile, 'r');
        if (!$file) {
            $this->command->error("❌ Could not open CSV file!");
            return;
        }
        
        $header = fgetcsv($file); // Skip header row
        
        $data = [];
        $count = 0;
        $skipped = 0;
        $now = Carbon::now();
        $batchSize = 100;
        
        $this->command->info("Starting CSV import...");
        
        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty($row[0]) || empty($row[1])) {
                $skipped++;
                continue;
            }
            
            // Ensure row has at least 19 columns
            $row = array_pad($row, 19, '');
            
            // Convert empty strings to null
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $row[$key] = null;
                }
            }
            
            // Generate unique email
            $firstName = strtolower(preg_replace('/[^a-zA-Z]/', '', $row[0]));
            $lastName = strtolower(preg_replace('/[^a-zA-Z]/', '', $row[1]));
            $email = $firstName . '.' . $lastName . ($count + 1) . '@example.com';
            
            // Check for duplicates
            $existingEmail = DB::table('customer_accounts')
                ->where('email', $email)
                ->exists();
            
            $emailCounter = 1;
            while ($existingEmail) {
                $email = $firstName . '.' . $lastName . ($count + 1) . $emailCounter . '@example.com';
                $existingEmail = DB::table('customer_accounts')
                    ->where('email', $email)
                    ->exists();
                $emailCounter++;
            }
            
            // Prepare data with correct mapping based on your migration
            $data[] = [
                'uuid' => (string) Str::uuid(),
                'first_name' => $row[0],
                'last_name' => $row[1],
                'contact_no' => $row[2], // Column 3
                'address' => $row[3], // Column 4
                'email' => $email,
                'password' => Hash::make('Password123!'),
                'google_id' => $row[6], // Column 7
                'email_verified_at' => $row[7] ?? $now,
                'two_factor_enabled' => $row[8] ?? 0,
                'two_factor_secret' => $row[9], // Column 10
                'two_factor_backup_codes' => $row[10], // Column 11
                'two_factor_enabled_at' => $row[11], // Column 12
                'role' => $row[12] ?? 3, // Column 13 - '3' from CSV goes here
                'regular' => $row[13] ?? null, // Column 14 - empty in CSV
                'date_joined' => $row[14] ?? $now,
                'date_deactivated' => $row[15],
                'reasons' => $row[16],
                'account_status' => $row[17] ?? 1, // Column 18 - '1' from CSV
                'active' => $row[18] ?? 1, // Column 19 - '1' from CSV
            ];
            
            $count++;
            
            // Insert in batches
            if ($count % $batchSize === 0) {
                try {
                    DB::table('customer_accounts')->insert($data);
                    $this->command->info("✓ Inserted {$count} records...");
                } catch (\Exception $e) {
                    $this->command->error("Error inserting batch: " . $e->getMessage());
                    break;
                }
                $data = [];
            }
        }
        
        // Insert any remaining records
        if (!empty($data)) {
            try {
                DB::table('customer_accounts')->insert($data);
            } catch (\Exception $e) {
                $this->command->error("Error inserting final batch: " . $e->getMessage());
            }
        }
        
        fclose($file);
        
        $this->command->info("\n✅ CSV Import Complete!");
        $this->command->info("📊 Records processed: {$count}");
        
        if ($skipped > 0) {
            $this->command->info("⚠️  Skipped empty rows: {$skipped}");
        }
        
        // Show imported data statistics
        $this->showImportStatistics();
    }
    
    private function showImportStatistics()
    {
        $this->command->info("\n📈 Import Statistics:");
        
        // Get total count
        $totalCount = DB::table('customer_accounts')->count();
        $this->command->info("Total records in table: {$totalCount}");
        
        // Show role distribution
        $this->command->info("\n👥 Role Distribution:");
        $roles = DB::table('customer_accounts')
            ->select('role', DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->get();
        
        foreach ($roles as $role) {
            $this->command->info("  Role {$role->role}: {$role->count} customers");
        }
        
        // Show regular status
        $this->command->info("\n⭐ Regular Status:");
        $regulars = DB::table('customer_accounts')
            ->select('regular', DB::raw('COUNT(*) as count'))
            ->groupBy('regular')
            ->get();
        
        foreach ($regulars as $reg) {
            $status = $reg->regular === null ? 'NULL' : $reg->regular;
            $this->command->info("  Regular {$status}: {$reg->count} customers");
        }
        
        // Show account status
        $this->command->info("\n✅ Account Status:");
        $statuses = DB::table('customer_accounts')
            ->select('account_status', DB::raw('COUNT(*) as count'))
            ->groupBy('account_status')
            ->get();
        
        foreach ($statuses as $status) {
            $this->command->info("  Status {$status->account_status}: {$status->count} customers");
        }
        
        // Show active status
        $this->command->info("\n🔔 Active Status:");
        $actives = DB::table('customer_accounts')
            ->select('active', DB::raw('COUNT(*) as count'))
            ->groupBy('active')
            ->get();
        
        foreach ($actives as $active) {
            $this->command->info("  Active {$active->active}: {$active->count} customers");
        }
        
        // Show sample records
        $this->command->info("\n👤 Sample Records (last 5):");
        $samples = DB::table('customer_accounts')
            ->select('id', 'first_name', 'last_name', 'email', 'role', 'regular', 'account_status', 'active')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($samples as $sample) {
            $this->command->info(sprintf(
                "#%d: %s %s (%s)",
                $sample->id,
                $sample->first_name,
                $sample->last_name,
                $sample->email
            ));
            $this->command->info("   Role: {$sample->role} | Regular: " . ($sample->regular ?? 'NULL') . 
                               " | Status: {$sample->account_status} | Active: {$sample->active}");
        }
        
        // Data mapping explanation
        $this->command->info("\n🔍 CSV to Database Mapping:");
        $this->command->info("✓ CSV column 13 (index 12) '3' → Database 'role' = 3");
        $this->command->info("✓ CSV column 14 (index 13) empty → Database 'regular' = NULL");
        $this->command->info("✓ CSV column 18 (index 17) '1' → Database 'account_status' = 1");
        $this->command->info("✓ CSV column 19 (index 18) '1' → Database 'active' = 1");
        $this->command->info("✓ UUID generated automatically");
        $this->command->info("✓ Email generated from name + number");
        $this->command->info("✓ Password hashed with bcrypt");
    }
}