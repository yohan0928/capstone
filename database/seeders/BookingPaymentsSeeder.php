<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingPaymentsSeeder extends Seeder
{
    public function run()
    {
        // Path to your CSV file
        $csvFile = storage_path('app/booking_payments.csv');
        
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
        
        $header = fgetcsv($file); // Read header row
        
        $this->command->info("CSV columns found: " . count($header));
        $this->command->info("Columns: " . implode(', ', $header));
        
        $data = [];
        $count = 0;
        $skipped = 0;
        $batchSize = 100;
        
        $this->command->info("Starting Booking Payments CSV import...");
        
        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty($row[0]) && empty($row[1])) {
                $skipped++;
                continue;
            }
            
            // Map CSV columns to database fields
            $row = array_pad($row, count($header), null);
            $csvData = array_combine($header, $row);
            
            // Debug: Show first row structure
            if ($count === 0) {
                $this->command->info("First row data sample:");
                foreach ($csvData as $key => $value) {
                    $this->command->info("  {$key}: " . ($value === null ? 'NULL' : "'{$value}'"));
                }
            }
            
            // Check if customer_account_id exists
            if (!isset($csvData['customer_account_id']) || 
                !DB::table('customer_accounts')->where('id', $csvData['customer_account_id'])->exists()) {
                $skipped++;
                $this->command->warn("Skipped: Customer account {$csvData['customer_account_id']} does not exist");
                continue;
            }
            
            // Check if booking_id exists
            if (isset($csvData['booking_id']) && $csvData['booking_id'] && 
                !DB::table('bookings')->where('id', $csvData['booking_id'])->exists()) {
                $skipped++;
                $this->command->warn("Skipped: Booking {$csvData['booking_id']} does not exist");
                continue;
            }
            
            // Check if branch_id exists
            if (isset($csvData['branch_id']) && $csvData['branch_id'] && 
                !DB::table('branches')->where('id', $csvData['branch_id'])->exists()) {
                $skipped++;
                $this->command->warn("Skipped: Branch {$csvData['branch_id']} does not exist");
                continue;
            }
            
            // Parse dates
            $paymentDate = $this->formatDateToYMD($csvData['payment_date'] ?? null);
            $dateCreated = $this->formatDateTime($csvData['date_created'] ?? null);
            $lastDateUpdated = $this->formatDateTime($csvData['last_date_updated'] ?? null);
            $dateUpdated = $this->formatDateTime($csvData['date_updated'] ?? null);
            
            // Fix typo in CSV column name (gcas_receipt_img should be gcash_receipt_img)
            $gcashReceiptImg = $csvData['gcas_receipt_img'] ?? $csvData['gcash_receipt_img'] ?? null;
            
            // Handle payment_status - default to 1 (paid) based on CSV data
            $paymentStatus = isset($csvData['payment_status']) ? (int)$csvData['payment_status'] : 1;
            
            // Handle payment_method (0=cash, 1=gcash based on CSV data)
            $paymentMethod = isset($csvData['payment_method']) ? (int)$csvData['payment_method'] : 0;
            
            // Handle payment_category - default to 1 (main payment) based on CSV data
            $paymentCategory = isset($csvData['payment_category']) ? (int)$csvData['payment_category'] : 1;
            
            // Generate UUID
            $uuid = (string) Str::uuid();
            
            // Prepare data for insertion
            $paymentData = [
                'uuid' => $uuid,
                'customer_account_id' => $csvData['customer_account_id'] ?? null,
                'branch_id' => isset($csvData['branch_id']) && $csvData['branch_id'] ? $csvData['branch_id'] : null,
                'booking_id' => isset($csvData['booking_id']) && $csvData['booking_id'] ? $csvData['booking_id'] : null,
                
                // Payment details
                'payment_date' => $paymentDate ? Carbon::parse($paymentDate) : Carbon::now(),
                'payment_category' => $paymentCategory,
                'payment_method' => $paymentMethod,
                
                // Amount fields - default to 0 for CSV
                'total_amount' => isset($csvData['total_amount']) && $csvData['total_amount'] ? (float)$csvData['total_amount'] : 0.00,
                'amount_paid' => isset($csvData['amount_paid']) && $csvData['amount_paid'] ? (float)$csvData['amount_paid'] : 0.00,
                'change' => isset($csvData['change']) && $csvData['change'] ? (float)$csvData['change'] : 0.00,
                
                // GCash fields
                'gcash_ref_no' => $csvData['gcash_ref_no'] ?? null,
                'gcash_receipt_img' => $gcashReceiptImg,
                
                // Notes - store as JSON if needed
                'notes' => isset($csvData['notes']) && $csvData['notes'] ? json_encode(['note' => $csvData['notes']]) : null,
                
                // Payment status
                'payment_status' => $paymentStatus,
                
                // Audit fields
                'created_by' => isset($csvData['created_by']) && $csvData['created_by'] ? $csvData['created_by'] : null,
                'created_by_type' => $csvData['created_by_type'] ?? null,
                'date_created' => $dateCreated ?? Carbon::now(),
                
                'last_updated_by' => isset($csvData['last_updated_by']) && $csvData['last_updated_by'] ? $csvData['last_updated_by'] : null,
                'last_updated_by_type' => $csvData['last_updated_by_type'] ?? null,
                'last_date_updated' => $lastDateUpdated ?? Carbon::now(),
                
                'updated_by' => isset($csvData['update_by']) && $csvData['update_by'] ? $csvData['update_by'] : null,
                'updated_by_type' => $csvData['updated_by_type'] ?? null,
                'date_updated' => $dateUpdated ?? Carbon::now(),
                
                // Active status
                'active' => isset($csvData['active']) ? (int)$csvData['active'] : 1,
            ];
            
            $data[] = $paymentData;
            $count++;
            
            // Insert in batches
            if ($count % $batchSize === 0) {
                try {
                    DB::table('booking_payments')->insert($data);
                    $this->command->info("✓ Inserted {$count} booking payment records...");
                    $data = [];
                } catch (\Exception $e) {
                    $this->command->error("Error inserting batch: " . $e->getMessage());
                    // Show first error data for debugging
                    if (!empty($data)) {
                        $this->command->error("First record in failed batch:");
                        foreach ($data[0] as $key => $value) {
                            $this->command->error("  {$key}: " . ($value === null ? 'NULL' : (is_object($value) ? get_class($value) : $value)));
                        }
                    }
                    break;
                }
            }
        }
        
        // Insert any remaining records
        if (!empty($data)) {
            try {
                DB::table('booking_payments')->insert($data);
                $this->command->info("✓ Inserted final batch of " . count($data) . " booking payment records...");
            } catch (\Exception $e) {
                $this->command->error("Error inserting final batch: " . $e->getMessage());
            }
        }
        
        fclose($file);
        
        $this->command->info("\n✅ Booking Payments CSV Import Complete!");
        $this->command->info("📊 Total records processed: {$count}");
        $this->command->info("⚠️  Skipped rows: {$skipped}");
        
        // Show import statistics
        $this->showImportStatistics();
    }
    
    /**
     * Format date to YYYY-MM-DD
     */
    private function formatDateToYMD($dateString)
    {
        if (empty($dateString) || $dateString === '' || $dateString === null) {
            return null;
        }
        
        // Check if already in YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }
        
        // Check for DD/MM/YYYY format
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateString, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }
        
        // Try to parse with Carbon
        try {
            $date = Carbon::parse($dateString);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date: {$dateString}");
            return null;
        }
    }
    
    /**
     * Format datetime string to Carbon instance
     */
    private function formatDateTime($datetimeString)
    {
        if (empty($datetimeString) || $datetimeString === '' || $datetimeString === null) {
            return null;
        }
        
        try {
            return Carbon::parse($datetimeString);
        } catch (\Exception $e) {
            $this->command->warn("Could not parse datetime: {$datetimeString}");
            return null;
        }
    }
    
    private function showImportStatistics()
    {
        $this->command->info("\n📈 Booking Payments Import Statistics:");
        
        $totalCount = DB::table('booking_payments')->count();
        $this->command->info("Total booking payments in table: {$totalCount}");
        
        if ($totalCount === 0) {
            $this->command->error("❌ No data was imported!");
            return;
        }
        
        // Show sample of formatted data
        $this->command->info("\n📊 Sample of Formatted Data (first 5 records):");
        $samples = DB::table('booking_payments')
            ->select('id', 'customer_account_id', 'booking_id', 'payment_date', 'payment_method', 'payment_status', 'active')
            ->orderBy('id')
            ->limit(5)
            ->get();
        
        foreach ($samples as $sample) {
            $paymentMethod = $this->getPaymentMethodText($sample->payment_method);
            $paymentStatus = $this->getPaymentStatusText($sample->payment_status);
            $this->command->info("ID: {$sample->id} | Customer: {$sample->customer_account_id} | Booking: {$sample->booking_id}");
            $this->command->info("  Date: {$sample->payment_date} | Method: {$paymentMethod} | Status: {$paymentStatus} | Active: {$sample->active}");
        }
        
        // Show data distribution
        $this->command->info("\n📊 Payment Method Distribution:");
        $methods = DB::table('booking_payments')
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get();
        
        foreach ($methods as $method) {
            $methodText = $this->getPaymentMethodText($method->payment_method);
            $percentage = $totalCount > 0 ? round(($method->count / $totalCount) * 100, 1) : 0;
            $this->command->info("  {$methodText}: {$method->count} payments ({$percentage}%)");
        }
        
        $this->command->info("\n📊 Payment Status Distribution:");
        $statuses = DB::table('booking_payments')
            ->select('payment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_status')
            ->orderBy('payment_status')
            ->get();
        
        foreach ($statuses as $status) {
            $statusText = $this->getPaymentStatusText($status->payment_status);
            $percentage = $totalCount > 0 ? round(($status->count / $totalCount) * 100, 1) : 0;
            $this->command->info("  {$statusText}: {$status->count} payments ({$percentage}%)");
        }
        
        $this->command->info("\n📊 Payment Category Distribution:");
        $categories = DB::table('booking_payments')
            ->select('payment_category', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_category')
            ->orderBy('payment_category')
            ->get();
        
        foreach ($categories as $category) {
            $categoryText = $this->getPaymentCategoryText($category->payment_category);
            $percentage = $totalCount > 0 ? round(($category->count / $totalCount) * 100, 1) : 0;
            $this->command->info("  {$categoryText}: {$category->count} payments ({$percentage}%)");
        }
        
        $this->command->info("\n📊 Active Status Distribution:");
        $activeStatus = DB::table('booking_payments')
            ->select('active', DB::raw('COUNT(*) as count'))
            ->groupBy('active')
            ->orderBy('active')
            ->get();
        
        foreach ($activeStatus as $status) {
            $statusText = $status->active == 1 ? 'Active' : 'Inactive';
            $percentage = $totalCount > 0 ? round(($status->count / $totalCount) * 100, 1) : 0;
            $this->command->info("  {$statusText}: {$status->count} payments ({$percentage}%)");
        }
        
        // Show date range
        $dateRange = DB::table('booking_payments')
            ->select(
                DB::raw('MIN(payment_date) as earliest_date'),
                DB::raw('MAX(payment_date) as latest_date')
            )
            ->first();
        
        $this->command->info("\n📅 Payment Date Range:");
        $this->command->info("  Earliest: {$dateRange->earliest_date}");
        $this->command->info("  Latest: {$dateRange->latest_date}");
        
        $this->command->info("\n🎉 Booking payments import completed successfully!");
    }
    
    private function getPaymentMethodText($method)
    {
        return match((int)$method) {
            0 => 'Cash',
            1 => 'GCash',
            2 => 'Debit Card',
            3 => 'Pay Later',
            default => "Unknown ({$method})"
        };
    }
    
    private function getPaymentStatusText($status)
    {
        return match((int)$status) {
            0 => 'Invalid',
            1 => 'Paid',
            2 => 'Unpaid',
            default => "Unknown ({$status})"
        };
    }
    
    private function getPaymentCategoryText($category)
    {
        return match((int)$category) {
            0 => 'Extension',
            1 => 'Main Payment',
            default => "Unknown ({$category})"
        };
    }
}