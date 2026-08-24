<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrderPaymentsSeeder extends Seeder
{
    public function run()
    {
        // Import order payments from CSV
        $this->importOrderPayments();
    }
    
    private function importOrderPayments()
    {
        $this->command->info("=== STARTING ORDER PAYMENTS IMPORT ===");
        
        // Path to your CSV file
        $csvFile = storage_path('app/order_payments.csv');
        
        $this->command->info("Looking for CSV file at: {$csvFile}");
        
        if (!file_exists($csvFile)) {
            $this->command->error("❌ Order Payments CSV file not found! Please ensure the file exists at: {$csvFile}");
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
            $tempFile = storage_path('app/order_payments_utf8.csv');
            file_put_contents($tempFile, $fileContent);
            $csvFile = $tempFile;
            $this->command->info("Converted file to UTF-8");
        }
        
        $file = fopen($csvFile, 'r');
        if (!$file) {
            $this->command->error("❌ Could not open order payments CSV file!");
            return;
        }
        
        $header = fgetcsv($file); // Read header row
        $this->command->info("CSV Header columns: " . count($header));
        
        $data = [];
        $count = 0;
        $skipped = 0;
        $now = Carbon::now();
        $batchSize = 50;
        
        $this->command->info("Starting order payments CSV import...");
        
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
            
            // Parse numeric values - handle spaces and formatting
            $totalAmount = $this->parseDecimal($row[5]);
            $discount = $this->parseDecimal($row[6]);
            $vatSales = $this->parseDecimal($row[7]);
            $vatAmount = $this->parseDecimal($row[8]);
            $amountPaid = $this->parseDecimal($row[9]);
            $change = $this->parseDecimal($row[10]);
            
            // Parse dates - handle null values
            $paymentDate = $this->parseDate($row[3]);
            $dateCreated = $this->parseDate($row[16]);
            $lastDateUpdated = $this->parseDate($row[19]);
            $dateUpdated = $this->parseDate($row[22]);
            
            // Prepare order payment data based on your OrderPayment model fillable fields
            $orderPaymentData = [
                'uuid' => (string) Str::uuid(),
                'customer_account_id' => $row[0] ? intval($row[0]) : null,
                'branch_id' => $row[1] ? intval($row[1]) : null,
                'order_id' => $row[2] ? intval($row[2]) : null,
                'payment_date' => $paymentDate,
                'payment_method' => $row[4] ? intval($row[4]) : 0, // Default to 0 (cash)
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'vat_sales' => $vatSales,
                'vat_amount' => $vatAmount,
                'amount_paid' => $amountPaid,
                'change' => $change,
                'notes' => $row[11],
                'gcash_ref_no' => $row[12],
                'order_payment_status' => $row[13] ? intval($row[13]) : 1, // Default to 1 (paid)
                'created_by' => $row[14] ? intval($row[14]) : null,
                'created_by_type' => $row[15],
                'date_created' => $dateCreated,
                'last_updated_by' => $row[17] ? intval($row[17]) : null,
                'last_updated_by_type' => $row[18],
                'last_date_updated' => $lastDateUpdated,
                'updated_by' => $row[20] ? intval($row[20]) : null,
                'updated_by_type' => $row[21],
                'date_updated' => $dateUpdated,
                'active' => $row[23] ? intval($row[23]) : 1,
            ];
            
            // Check if required fields are present
            $missingFields = [];
            if (empty($orderPaymentData['customer_account_id'])) {
                $missingFields[] = 'customer_account_id';
            }
            if (empty($orderPaymentData['branch_id'])) {
                $missingFields[] = 'branch_id';
            }
            if (empty($orderPaymentData['order_id'])) {
                $missingFields[] = 'order_id';
            }
            
            if (!empty($missingFields)) {
                $skipped++;
                $this->command->warn("⚠️  Skipping row - missing required fields: " . implode(', ', $missingFields));
                continue;
            }
            
            // Verify order exists
            if (!DB::table('orders')->where('id', $orderPaymentData['order_id'])->exists()) {
                $skipped++;
                $this->command->warn("⚠️  Skipping row - order_id {$orderPaymentData['order_id']} does not exist in orders table");
                continue;
            }
            
            $data[] = $orderPaymentData;
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
        
        $this->command->info("\n✅ ORDER PAYMENTS IMPORT COMPLETE!");
        $this->command->info("📊 Order payments processed: {$count}");
        
        if ($skipped > 0) {
            $this->command->info("⚠️  Skipped rows: {$skipped}");
        }
        
        // Show order payments import statistics
        $this->showOrderPaymentsImportStatistics();
    }
    
    /**
     * Parse decimal string to float
     */
    private function parseDecimal($value)
    {
        if (empty($value)) {
            return null;
        }
        
        // Remove any non-numeric characters except decimal point and spaces
        $cleaned = preg_replace('/[^0-9.]/', '', trim($value));
        
        if ($cleaned === '') {
            return null;
        }
        
        return floatval($cleaned);
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
            DB::table('order_payments')->insert($data);
            $this->command->info("✓ Inserted batch of " . count($data) . " order payment records...");
        } catch (\Exception $e) {
            $this->command->error("Error inserting order payment batch: " . $e->getMessage());
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
        
        foreach ($data as $index => $orderPaymentData) {
            try {
                DB::table('order_payments')->insert([$orderPaymentData]);
                $successCount++;
                $this->command->info("✓ Inserted order payment record {$index}: Order #{$orderPaymentData['order_id']}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->command->error("❌ Failed to insert order payment record {$index}: Order #{$orderPaymentData['order_id']}");
                $this->command->error("   Error: " . $e->getMessage());
                
                // Try with additional cleaning
                $this->command->info("🔄 Attempting to clean and retry...");
                $cleanedData = $this->cleanOrderPaymentDataForInsert($orderPaymentData);
                try {
                    DB::table('order_payments')->insert([$cleanedData]);
                    $this->command->info("✓ Inserted cleaned order payment record {$index}: Order #{$cleanedData['order_id']}");
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
     * Clean order payment data to ensure UTF-8 compatibility
     */
    private function cleanOrderPaymentDataForInsert(array $data): array
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
            '"' => '"', '”' => '"', '–' => '-', '—' => '-',
        ];
        
        return strtr($text, $specialChars);
    }
    
    private function showOrderPaymentsImportStatistics()
    {
        $this->command->info("\n📈 ORDER PAYMENTS IMPORT STATISTICS:");
        
        // Get total count
        $totalCount = DB::table('order_payments')->count();
        $this->command->info("Total order payments in table: {$totalCount}");
        
        if ($totalCount === 0) {
            $this->command->error("No order payments were imported!");
            return;
        }
        
        // Show payment method distribution
        $this->command->info("\n💳 Payment Method Distribution:");
        $methods = DB::table('order_payments')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total_amount'))
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get();
        
        foreach ($methods as $method) {
            $methodValue = $method->payment_method === null ? 'NULL' : $method->payment_method;
            $methodText = match($methodValue) {
                0 => 'Cash',
                1 => 'GCash',
                2 => 'Debit Card',
                3 => 'Pay Later',
                default => 'Unknown',
            };
            $this->command->info(sprintf(
                "  %s: %d payments, ₱%s total",
                $methodText,
                $method->count,
                number_format($method->total_amount, 2)
            ));
        }
        
        // Show payment status distribution
        $this->command->info("\n📊 Payment Status:");
        $statuses = DB::table('order_payments')
            ->select('order_payment_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total_amount'))
            ->groupBy('order_payment_status')
            ->orderBy('order_payment_status')
            ->get();
        
        foreach ($statuses as $status) {
            $statusValue = $status->order_payment_status === null ? 'NULL' : $status->order_payment_status;
            $statusText = match($statusValue) {
                0 => 'Unpaid',
                1 => 'Paid',
                2 => 'Pending',
                3 => 'Cancelled',
                default => 'Unknown',
            };
            $this->command->info(sprintf(
                "  %s: %d payments, ₱%s total",
                $statusText,
                $status->count,
                number_format($status->total_amount, 2)
            ));
        }
        
        // Show active status
        $this->command->info("\n🔔 Active Status:");
        $actives = DB::table('order_payments')
            ->select('active', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total_amount'))
            ->groupBy('active')
            ->orderBy('active')
            ->get();
        
        foreach ($actives as $active) {
            $activeValue = $active->active === null ? 'NULL' : $active->active;
            $activeText = $activeValue == 1 ? 'Active' : ($activeValue == 0 ? 'Inactive' : $activeValue);
            $this->command->info(sprintf(
                "  %s: %d payments, ₱%s total",
                $activeText,
                $active->count,
                number_format($active->total_amount, 2)
            ));
        }
        
        // Show financial statistics
        $this->command->info("\n💰 Financial Statistics:");
        $financialStats = DB::table('order_payments')
            ->select(
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('AVG(total_amount) as avg_payment'),
                DB::raw('MIN(total_amount) as min_payment'),
                DB::raw('MAX(total_amount) as max_payment'),
                DB::raw('SUM(discount) as total_discounts'),
                DB::raw('SUM(vat_amount) as total_vat'),
                DB::raw('SUM(amount_paid) as total_paid'),
                DB::raw('SUM(change) as total_change'),
                DB::raw('COUNT(DISTINCT customer_account_id) as unique_customers'),
                DB::raw('COUNT(DISTINCT order_id) as orders_paid')
            )
            ->first();
        
        $this->command->info("  Total Revenue: ₱" . number_format($financialStats->total_revenue, 2));
        $this->command->info("  Average Payment: ₱" . number_format($financialStats->avg_payment, 2));
        $this->command->info("  Smallest Payment: ₱" . number_format($financialStats->min_payment, 2));
        $this->command->info("  Largest Payment: ₱" . number_format($financialStats->max_payment, 2));
        $this->command->info("  Total Discounts: ₱" . number_format($financialStats->total_discounts, 2));
        $this->command->info("  Total VAT: ₱" . number_format($financialStats->total_vat, 2));
        $this->command->info("  Total Amount Paid: ₱" . number_format($financialStats->total_paid, 2));
        $this->command->info("  Total Change Given: ₱" . number_format($financialStats->total_change, 2));
        $this->command->info("  Unique Customers: " . $financialStats->unique_customers);
        $this->command->info("  Orders Paid: " . $financialStats->orders_paid);
        
        // Show date range of payments
        $this->command->info("\n📅 Payment Date Range:");
        $dateRange = DB::table('order_payments')
            ->select(
                DB::raw('MIN(payment_date) as earliest_payment'),
                DB::raw('MAX(payment_date) as latest_payment'),
                DB::raw('COUNT(DISTINCT DATE(payment_date)) as distinct_days')
            )
            ->first();
        
        $earliest = $dateRange->earliest_payment ? Carbon::parse($dateRange->earliest_payment)->format('Y-m-d') : 'N/A';
        $latest = $dateRange->latest_payment ? Carbon::parse($dateRange->latest_payment)->format('Y-m-d') : 'N/A';
        $this->command->info("  Earliest Payment: {$earliest}");
        $this->command->info("  Latest Payment: {$latest}");
        $this->command->info("  Distinct Payment Days: {$dateRange->distinct_days}");
        
        // Show monthly revenue
        $this->command->info("\n📆 Monthly Revenue:");
        $monthly = DB::table('order_payments')
            ->select(
                DB::raw("DATE_FORMAT(payment_date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('AVG(total_amount) as avg_payment')
            )
            ->whereNotNull('payment_date')
            ->groupBy(DB::raw("DATE_FORMAT(payment_date, '%Y-%m')"))
            ->orderBy('month')
            ->get();
        
        foreach ($monthly as $month) {
            $this->command->info(sprintf(
                "  %s: %d payments, ₱%s revenue (avg: ₱%s)",
                $month->month,
                $month->payment_count,
                number_format($month->total_revenue, 2),
                number_format($month->avg_payment, 2)
            ));
        }
        
        // Show top customers by spending
        $this->command->info("\n👑 Top 10 Customers by Spending:");
        $topCustomers = DB::table('order_payments')
            ->select(
                'customer_account_id',
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('AVG(total_amount) as avg_payment')
            )
            ->whereNotNull('customer_account_id')
            ->groupBy('customer_account_id')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get();
        
        $rank = 1;
        foreach ($topCustomers as $customer) {
            $this->command->info(sprintf(
                "  %d. Customer #%d: %d payments, ₱%s total (avg: ₱%s)",
                $rank,
                $customer->customer_account_id,
                $customer->payment_count,
                number_format($customer->total_spent, 2),
                number_format($customer->avg_payment, 2)
            ));
            $rank++;
        }
        
        // Show branch distribution
        $this->command->info("\n🏬 Branch Revenue Distribution:");
        $branches = DB::table('order_payments')
            ->select(
                'branch_id',
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('AVG(total_amount) as avg_payment')
            )
            ->groupBy('branch_id')
            ->orderBy('branch_id')
            ->get();
        
        foreach ($branches as $branch) {
            $this->command->info(sprintf(
                "  Branch #%d: %d payments, ₱%s revenue (avg: ₱%s)",
                $branch->branch_id,
                $branch->payment_count,
                number_format($branch->total_revenue, 2),
                number_format($branch->avg_payment, 2)
            ));
        }
        
        // Show payment amount distribution
        $this->command->info("\n💵 Payment Amount Distribution:");
        $amountRanges = [
            ['min' => 0, 'max' => 20, 'label' => '₱0-20'],
            ['min' => 20.01, 'max' => 50, 'label' => '₱20-50'],
            ['min' => 50.01, 'max' => 100, 'label' => '₱50-100'],
            ['min' => 100.01, 'max' => 200, 'label' => '₱100-200'],
            ['min' => 200.01, 'max' => 1000, 'label' => '₱200+'],
        ];
        
        foreach ($amountRanges as $range) {
            $count = DB::table('order_payments')
                ->where('total_amount', '>=', $range['min'])
                ->where('total_amount', '<=', $range['max'])
                ->count();
            
            $total = DB::table('order_payments')
                ->where('total_amount', '>=', $range['min'])
                ->where('total_amount', '<=', $range['max'])
                ->sum('total_amount');
            
            $percentage = $totalCount > 0 ? ($count / $totalCount) * 100 : 0;
            
            $this->command->info(sprintf(
                "  %s: %d payments (%.1f%%), ₱%s total",
                $range['label'],
                $count,
                $percentage,
                number_format($total, 2)
            ));
        }
        
        // Show sample order payment records
        $this->command->info("\n🧾 Sample Order Payment Records (last 5):");
        $samples = DB::table('order_payments')
            ->select('id', 'order_id', 'payment_method', 'total_amount', 'payment_date', 'order_payment_status', 'active')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($samples as $sample) {
            $methodText = match($sample->payment_method) {
                0 => 'Cash',
                1 => 'GCash',
                2 => 'Debit Card',
                3 => 'Pay Later',
                default => 'Unknown',
            };
            
            $statusText = match($sample->order_payment_status) {
                0 => 'Unpaid',
                1 => 'Paid',
                2 => 'Pending',
                3 => 'Cancelled',
                default => 'Unknown',
            };
            
            $date = $sample->payment_date ? Carbon::parse($sample->payment_date)->format('Y-m-d') : 'N/A';
            
            $this->command->info(sprintf(
                "#%d: Order #%d",
                $sample->id,
                $sample->order_id
            ));
            $this->command->info(sprintf(
                "   Amount: ₱%s | Method: %s",
                number_format($sample->total_amount, 2),
                $methodText
            ));
            $this->command->info("   Date: {$date} | Status: {$statusText} | Active: {$sample->active}");
        }
        
        // Verify data integrity
        $this->command->info("\n🔍 Data Integrity Check:");
        
        // Check for payments with amount_paid != total_amount
        $mismatchedPayments = DB::table('order_payments')
            ->whereRaw('ROUND(amount_paid, 2) != ROUND(total_amount, 2)')
            ->count();
        
        if ($mismatchedPayments > 0) {
            $this->command->warn("⚠️  Found {$mismatchedPayments} payments where amount_paid ≠ total_amount");
        } else {
            $this->command->info("✓ All payments have matching amounts");
        }
        
        // Check for payments with non-zero change
        $withChange = DB::table('order_payments')
            ->where('change', '>', 0)
            ->count();
        
        if ($withChange > 0) {
            $this->command->info("✓ Found {$withChange} payments with change given");
        }
        
        // Check for payments with discounts or VAT
        $withDiscounts = DB::table('order_payments')
            ->where('discount', '>', 0)
            ->count();
        
        $withVAT = DB::table('order_payments')
            ->where('vat_amount', '>', 0)
            ->count();
        
        if ($withDiscounts > 0) {
            $this->command->info("✓ Found {$withDiscounts} payments with discounts applied");
        } else {
            $this->command->info("✓ No payments have discounts applied");
        }
        
        if ($withVAT > 0) {
            $this->command->info("✓ Found {$withVAT} payments with VAT");
        } else {
            $this->command->info("✓ No payments have VAT applied");
        }
        
        // Check for orphaned order references
        $orphanedOrders = DB::table('order_payments as op')
            ->leftJoin('orders as o', 'op.order_id', '=', 'o.id')
            ->whereNull('o.id')
            ->count();
        
        if ($orphanedOrders > 0) {
            $this->command->warn("⚠️  Found {$orphanedOrders} payments referencing non-existent orders");
        } else {
            $this->command->info("✓ All order references are valid");
        }
        
        // Compare with order items totals
        $this->command->info("\n📊 Cross-Table Verification:");
        
        // Get total from order_payments
        $paymentsTotal = DB::table('order_payments')->sum('total_amount');
        
        // Get total from order_items (should match)
        $itemsTotal = DB::table('order_items')->sum('sub_total');
        
        $this->command->info("  Order Payments Total: ₱" . number_format($paymentsTotal, 2));
        $this->command->info("  Order Items Total: ₱" . number_format($itemsTotal, 2));
        
        if (abs($paymentsTotal - $itemsTotal) < 0.01) {
            $this->command->info("✅ Payment totals match order item totals!");
        } else {
            $this->command->warn("⚠️  Payment totals do not match order item totals");
            $this->command->warn("  Difference: ₱" . number_format($paymentsTotal - $itemsTotal, 2));
        }
    }
}