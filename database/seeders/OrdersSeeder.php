<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrdersSeeder extends Seeder
{
    public function run()
    {
        // Import orders from CSV
        $this->importOrders();
    }
    
    private function importOrders()
    {
        $this->command->info("=== STARTING ORDERS IMPORT ===");
        
        // Path to your CSV file
        $csvFile = storage_path('app/orders.csv');
        
        $this->command->info("Looking for CSV file at: {$csvFile}");
        
        if (!file_exists($csvFile)) {
            $this->command->error("❌ Orders CSV file not found! Please ensure the file exists at: {$csvFile}");
            return;
        }
        
        // Read the entire file to check encoding
        $fileContent = file_get_contents($csvFile);
        $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        $this->command->info("Detected encoding: " . ($encoding ?: 'Unknown'));
        
        // Convert to UTF-8 if needed
        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            // Write back to a temporary file
            $tempFile = storage_path('app/orders_utf8.csv');
            file_put_contents($tempFile, $fileContent);
            $csvFile = $tempFile;
            $this->command->info("Converted file to UTF-8");
        }
        
        $file = fopen($csvFile, 'r');
        if (!$file) {
            $this->command->error("❌ Could not open orders CSV file!");
            return;
        }
        
        $header = fgetcsv($file); // Read header row
        $this->command->info("CSV Header columns: " . count($header));
        
        $data = [];
        $count = 0;
        $skipped = 0;
        $now = Carbon::now();
        $batchSize = 50;
        
        $this->command->info("Starting orders CSV import...");
        
        // Process each row
        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows (check if all columns are empty)
            if (empty(array_filter($row))) {
                $skipped++;
                continue;
            }
            
            // Ensure row has enough columns
            $row = array_pad($row, count($header), '');
            
            // Convert empty strings to null and clean UTF-8
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $row[$key] = null;
                } elseif ($value !== null) {
                    // Clean and normalize UTF-8 characters
                    $row[$key] = mb_convert_encoding(trim($value), 'UTF-8', 'UTF-8');
                    // Remove any non-printable characters except spaces
                    $row[$key] = preg_replace('/[^\x20-\x7E\xA0-\xFF\s]/u', '', $row[$key]);
                }
            }
            
            // Generate order_ref_no if empty
            $orderRefNo = $row[0];
            if (!$orderRefNo) {
                // Generate a unique order reference number
                $orderRefNo = 'ORD-' . strtoupper(Str::random(8)) . '-' . time();
            }
            
            // Parse dates - handle null values
            $orderDate = $this->parseDate($row[4]);
            $dateCreated = $this->parseDate($row[8]);
            $lastDateUpdated = $this->parseDate($row[11]);
            $dateUpdated = $this->parseDate($row[14]);
            
            // Prepare order data based on your Order model fillable fields
            $orderData = [
                'uuid' => (string) Str::uuid(),
                'order_ref_no' => $orderRefNo,
                'customer_account_id' => $row[1] ? intval($row[1]) : null,
                'branch_id' => $row[2] ? intval($row[2]) : null,
                'booking_id' => $row[3] ? intval($row[3]) : null,
                'order_date' => $orderDate,
                'order_status' => $row[5] ? intval($row[5]) : 1, // Default to 1 (ordered)
                'created_by' => $row[6] ? intval($row[6]) : null,
                'created_by_type' => $row[7],
                'date_created' => $dateCreated,
                'last_updated_by' => $row[9] ? intval($row[9]) : null,
                'last_updated_by_type' => $row[10],
                'last_date_updated' => $lastDateUpdated,
                'updated_by' => $row[12] ? intval($row[12]) : null,
                'updated_by_type' => $row[13],
                'date_updated' => $dateUpdated,
                'active' => $row[15] ? intval($row[15]) : 1,
            ];
            
            // Check if required fields are present
            if (empty($orderData['customer_account_id']) || empty($orderData['branch_id'])) {
                $skipped++;
                $this->command->warn("⚠️  Skipping row - missing customer_account_id or branch_id");
                continue;
            }
            
            $data[] = $orderData;
            $count++;
            
            // Show progress for every 10 records
            if ($count % 10 === 0) {
                $this->command->info("✓ Processed {$count} records...");
            }
            
            // Insert in batches
            if ($count % $batchSize === 0) {
                $this->insertBatch($data);
                $data = [];
            }
        }
        
        // Insert any remaining records
        if (!empty($data)) {
            $this->insertBatch($data);
        }
        
        fclose($file);
        
        // Clean up temp file if created
        if (isset($tempFile) && file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        $this->command->info("\n✅ ORDERS IMPORT COMPLETE!");
        $this->command->info("📊 Orders processed: {$count}");
        
        if ($skipped > 0) {
            $this->command->info("⚠️  Skipped rows: {$skipped}");
        }
        
        // Show orders import statistics
        $this->showOrdersImportStatistics();
    }
    
    /**
     * Parse date string to Carbon instance
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date: {$dateString}");
            return null;
        }
    }
    
    /**
     * Insert a batch of records
     */
    private function insertBatch(array &$data)
    {
        try {
            DB::table('orders')->insert($data);
            $this->command->info("✓ Inserted batch of " . count($data) . " order records...");
        } catch (\Exception $e) {
            $this->command->error("Error inserting order batch: " . $e->getMessage());
            // Try inserting one by one
            $this->insertOneByOne($data);
        }
    }
    
    /**
     * Insert records one by one to identify problematic records
     */
    private function insertOneByOne(array $data)
    {
        $this->command->info("\n🔍 Attempting to insert records one by one...");
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($data as $index => $orderData) {
            try {
                DB::table('orders')->insert([$orderData]);
                $successCount++;
                $this->command->info("✓ Inserted order record {$index}: {$orderData['order_ref_no']}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->command->error("❌ Failed to insert order record {$index}: {$orderData['order_ref_no']}");
                $this->command->error("   Error: " . $e->getMessage());
                
                // Try with additional cleaning
                $this->command->info("🔄 Attempting to clean and retry...");
                $cleanedData = $this->cleanOrderDataForInsert($orderData);
                try {
                    DB::table('orders')->insert([$cleanedData]);
                    $this->command->info("✓ Inserted cleaned order record {$index}: {$cleanedData['order_ref_no']}");
                    $successCount++;
                } catch (\Exception $e2) {
                    $this->command->error("❌ Still failed after cleaning");
                }
            }
        }
        
        $this->command->info("\n📊 One-by-one insertion results:");
        $this->command->info("  Successful: {$successCount}");
        $this->command->info("  Failed: {$errorCount}");
    }
    
    /**
     * Clean order data to ensure UTF-8 compatibility
     */
    private function cleanOrderDataForInsert(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Convert to UTF-8
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                // Remove any invalid UTF-8 characters
                $value = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $value);
                // Replace special characters with ASCII equivalents
                $value = $this->replaceSpecialChars($value);
                // Trim
                $value = trim($value);
                $data[$key] = $value;
            }
        }
        return $data;
    }
    
    /**
     * Replace special characters with ASCII equivalents
     */
    private function replaceSpecialChars(string $text): string
    {
        $specialChars = [
            'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E',
            'ê' => 'e', 'Ê' => 'E', 'ë' => 'e', 'Ë' => 'E',
            'á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A',
            'â' => 'a', 'Â' => 'A', 'ä' => 'a', 'Ä' => 'A',
            'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I',
            'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I',
            'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O',
            'ô' => 'o', 'Ô' => 'O', 'ö' => 'o', 'Ö' => 'O',
            'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U',
            'û' => 'u', 'Û' => 'U', 'ü' => 'u', 'Ü' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ç' => 'c', 'Ç' => 'C',
            'ß' => 'ss', 'œ' => 'oe', 'æ' => 'ae',
            '«' => '"', '»' => '"', '‘' => "'", '’' => "'",
            '«' => '"', '»' => '"', '‘' => "'", '’' => "'",
            '“' => '"', '”' => '"', '–' => '-', '—' => '-',
        ];
        
        return strtr($text, $specialChars);
    }
    
    private function showOrdersImportStatistics()
    {
        $this->command->info("\n📈 ORDERS IMPORT STATISTICS:");
        
        // Get total count
        $totalCount = DB::table('orders')->count();
        $this->command->info("Total orders in table: {$totalCount}");
        
        if ($totalCount === 0) {
            $this->command->error("No orders were imported!");
            return;
        }
        
        // Show customer distribution
        $this->command->info("\n👥 Customer Distribution (Top 10):");
        $customers = DB::table('orders')
            ->select('customer_account_id', DB::raw('COUNT(*) as count'))
            ->groupBy('customer_account_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($customers as $customer) {
            $this->command->info("  Customer {$customer->customer_account_id}: {$customer->count} orders");
        }
        
        // Show branch distribution
        $this->command->info("\n🏬 Branch Distribution:");
        $branches = DB::table('orders')
            ->select('branch_id', DB::raw('COUNT(*) as count'))
            ->groupBy('branch_id')
            ->orderBy('branch_id')
            ->get();
        
        foreach ($branches as $branch) {
            $this->command->info("  Branch {$branch->branch_id}: {$branch->count} orders");
        }
        
        // Show order status distribution
        $this->command->info("\n📊 Order Status:");
        $statuses = DB::table('orders')
            ->select('order_status', DB::raw('COUNT(*) as count'))
            ->groupBy('order_status')
            ->orderBy('order_status')
            ->get();
        
        foreach ($statuses as $status) {
            $statusValue = $status->order_status === null ? 'NULL' : $status->order_status;
            $statusText = match($statusValue) {
                0 => 'Cancelled',
                1 => 'Ordered',
                2 => 'Pending',
                default => 'Unknown',
            };
            $this->command->info("  {$statusText}: {$status->count} orders");
        }
        
        // Show active status
        $this->command->info("\n🔔 Active Status:");
        $actives = DB::table('orders')
            ->select('active', DB::raw('COUNT(*) as count'))
            ->groupBy('active')
            ->orderBy('active')
            ->get();
        
        foreach ($actives as $active) {
            $activeValue = $active->active === null ? 'NULL' : $active->active;
            $activeText = $activeValue == 1 ? 'Active' : ($activeValue == 0 ? 'Inactive' : $activeValue);
            $this->command->info("  {$activeText}: {$active->count} orders");
        }
        
        // Show date range of orders
        $this->command->info("\n📅 Date Range:");
        $dateRange = DB::table('orders')
            ->select(
                DB::raw('MIN(order_date) as earliest_order'),
                DB::raw('MAX(order_date) as latest_order'),
                DB::raw('COUNT(DISTINCT DATE(order_date)) as distinct_days')
            )
            ->first();
        
        $earliest = $dateRange->earliest_order ? Carbon::parse($dateRange->earliest_order)->format('Y-m-d') : 'N/A';
        $latest = $dateRange->latest_order ? Carbon::parse($dateRange->latest_order)->format('Y-m-d') : 'N/A';
        $this->command->info("  Earliest Order: {$earliest}");
        $this->command->info("  Latest Order: {$latest}");
        $this->command->info("  Distinct Order Days: {$dateRange->distinct_days}");
        
        // Show monthly distribution
        $this->command->info("\n📆 Monthly Order Distribution:");
        $monthly = DB::table('orders')
            ->select(
                DB::raw("DATE_FORMAT(order_date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count')
            )
            ->whereNotNull('order_date')
            ->groupBy(DB::raw("DATE_FORMAT(order_date, '%Y-%m')"))
            ->orderBy('month')
            ->get();
        
        foreach ($monthly as $month) {
            $this->command->info("  {$month->month}: {$month->count} orders");
        }
        
        // Show daily distribution for the busiest days
        $this->command->info("\n📈 Top 10 Busiest Order Days:");
        $daily = DB::table('orders')
            ->select(
                DB::raw('DATE(order_date) as order_day'),
                DB::raw('COUNT(*) as count')
            )
            ->whereNotNull('order_date')
            ->groupBy(DB::raw('DATE(order_date)'))
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($daily as $day) {
            $this->command->info("  {$day->order_day}: {$day->count} orders");
        }
        
        // Show orders with and without bookings
        $this->command->info("\n📋 Booking Association:");
        $bookingStats = DB::table('orders')
            ->select(
                DB::raw('SUM(CASE WHEN booking_id IS NOT NULL THEN 1 ELSE 0 END) as with_booking'),
                DB::raw('SUM(CASE WHEN booking_id IS NULL THEN 1 ELSE 0 END) as without_booking'),
                DB::raw('COUNT(*) as total')
            )
            ->first();
        
        $this->command->info("  Orders with booking: {$bookingStats->with_booking}");
        $this->command->info("  Orders without booking: {$bookingStats->without_booking}");
        $this->command->info("  Total orders: {$bookingStats->total}");
        
        // Show sample order records
        $this->command->info("\n🛒 Sample Order Records (last 5):");
        $samples = DB::table('orders')
            ->select('id', 'order_ref_no', 'customer_account_id', 'branch_id', 'order_date', 'order_status', 'active')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($samples as $sample) {
            $statusText = match($sample->order_status) {
                0 => 'Cancelled',
                1 => 'Ordered',
                2 => 'Pending',
                default => 'Unknown',
            };
            
            $date = $sample->order_date ? Carbon::parse($sample->order_date)->format('Y-m-d') : 'N/A';
            
            $this->command->info(sprintf(
                "#%d: %s",
                $sample->id,
                $sample->order_ref_no
            ));
            $this->command->info("   Customer: {$sample->customer_account_id} | Branch: {$sample->branch_id}");
            $this->command->info("   Date: {$date} | Status: {$statusText} | Active: {$sample->active}");
        }
    }
}