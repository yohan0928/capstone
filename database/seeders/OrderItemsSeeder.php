<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrderItemsSeeder extends Seeder
{
    public function run()
    {
        // Import order items from CSV
        $this->importOrderItems();
    }
    
    private function importOrderItems()
    {
        $this->command->info("=== STARTING ORDER ITEMS IMPORT ===");
        
        // Path to your CSV file
        $csvFile = storage_path('app/order_items.csv');
        
        $this->command->info("Looking for CSV file at: {$csvFile}");
        
        if (!file_exists($csvFile)) {
            $this->command->error("❌ Order Items CSV file not found! Please ensure the file exists at: {$csvFile}");
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
            $tempFile = storage_path('app/order_items_utf8.csv');
            file_put_contents($tempFile, $fileContent);
            $csvFile = $tempFile;
            $this->command->info("Converted file to UTF-8");
        }
        
        $file = fopen($csvFile, 'r');
        if (!$file) {
            $this->command->error("❌ Could not open order items CSV file!");
            return;
        }
        
        $header = fgetcsv($file); // Read header row
        $this->command->info("CSV Header columns: " . count($header));
        
        $data = [];
        $count = 0;
        $skipped = 0;
        $now = Carbon::now();
        $batchSize = 50;
        
        $this->command->info("Starting order items CSV import...");
        
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
            $sellingPrice = $this->parseDecimal($row[4]);
            $quantity = $row[5] ? intval($row[5]) : null;
            $subTotal = $this->parseDecimal($row[6]);
            
            // Parse dates - handle null values
            $dateCreated = $this->parseDate($row[10]);
            $lastDateUpdated = $this->parseDate($row[13]);
            $dateUpdated = $this->parseDate($row[16]);
            
            // Prepare order item data based on your OrderItem model fillable fields
            $orderItemData = [
                'uuid' => (string) Str::uuid(),
                'customer_account_id' => $row[0] ? intval($row[0]) : null,
                'branch_id' => $row[1] ? intval($row[1]) : null,
                'order_id' => $row[2] ? intval($row[2]) : null,
                'product_id' => $row[3] ? intval($row[3]) : null,
                'selling_price' => $sellingPrice,
                'quantity' => $quantity,
                'sub_total' => $subTotal,
                'order_item_status' => $row[7] ? intval($row[7]) : 1, // Default to 1 (bought)
                'created_by' => $row[8] ? intval($row[8]) : null,
                'created_by_type' => $row[9],
                'date_created' => $dateCreated,
                'last_updated_by' => $row[11] ? intval($row[11]) : null,
                'last_updated_by_type' => $row[12],
                'last_date_updated' => $lastDateUpdated,
                'updated_by' => $row[14] ? intval($row[14]) : null,
                'updated_by_type' => $row[15],
                'date_updated' => $dateUpdated,
                'active' => $row[17] ? intval($row[17]) : 1,
            ];
            
            // Check if required fields are present
            $missingFields = [];
            if (empty($orderItemData['customer_account_id'])) {
                $missingFields[] = 'customer_account_id';
            }
            if (empty($orderItemData['branch_id'])) {
                $missingFields[] = 'branch_id';
            }
            if (empty($orderItemData['order_id'])) {
                $missingFields[] = 'order_id';
            }
            if (empty($orderItemData['product_id'])) {
                $missingFields[] = 'product_id';
            }
            
            if (!empty($missingFields)) {
                $skipped++;
                $this->command->warn("⚠️  Skipping row - missing required fields: " . implode(', ', $missingFields));
                continue;
            }
            
            // Verify order exists
            if (!DB::table('orders')->where('id', $orderItemData['order_id'])->exists()) {
                $skipped++;
                $this->command->warn("⚠️  Skipping row - order_id {$orderItemData['order_id']} does not exist in orders table");
                continue;
            }
            
            $data[] = $orderItemData;
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
        
        $this->command->info("\n✅ ORDER ITEMS IMPORT COMPLETE!");
        $this->command->info("📊 Order items processed: {$count}");
        
        if ($skipped > 0) {
            $this->command->info("⚠️  Skipped rows: {$skipped}");
        }
        
        // Show order items import statistics
        $this->showOrderItemsImportStatistics();
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
            DB::table('order_items')->insert($data);
            $this->command->info("✓ Inserted batch of " . count($data) . " order item records...");
        } catch (\Exception $e) {
            $this->command->error("Error inserting order item batch: " . $e->getMessage());
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
        
        foreach ($data as $index => $orderItemData) {
            try {
                DB::table('order_items')->insert([$orderItemData]);
                $successCount++;
                $this->command->info("✓ Inserted order item record {$index}: Order #{$orderItemData['order_id']}, Product #{$orderItemData['product_id']}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->command->error("❌ Failed to insert order item record {$index}: Order #{$orderItemData['order_id']}, Product #{$orderItemData['product_id']}");
                $this->command->error("   Error: " . $e->getMessage());
                
                // Try with additional cleaning
                $this->command->info("🔄 Attempting to clean and retry...");
                $cleanedData = $this->cleanOrderItemDataForInsert($orderItemData);
                try {
                    DB::table('order_items')->insert([$cleanedData]);
                    $this->command->info("✓ Inserted cleaned order item record {$index}: Order #{$cleanedData['order_id']}, Product #{$cleanedData['product_id']}");
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
     * Clean order item data to ensure UTF-8 compatibility
     */
    private function cleanOrderItemDataForInsert(array $data): array
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
    
    private function showOrderItemsImportStatistics()
    {
        $this->command->info("\n📈 ORDER ITEMS IMPORT STATISTICS:");
        
        // Get total count
        $totalCount = DB::table('order_items')->count();
        $this->command->info("Total order items in table: {$totalCount}");
        
        if ($totalCount === 0) {
            $this->command->error("No order items were imported!");
            return;
        }
        
        // Show order distribution
        $this->command->info("\n🛒 Order Distribution (Top 10):");
        $orders = DB::table('order_items')
            ->select('order_id', DB::raw('COUNT(*) as item_count'), DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(sub_total) as total_value'))
            ->groupBy('order_id')
            ->orderBy('item_count', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($orders as $order) {
            $this->command->info(sprintf(
                "  Order #%d: %d items, %d units, ₱%s",
                $order->order_id,
                $order->item_count,
                $order->total_quantity,
                number_format($order->total_value, 2)
            ));
        }
        
        // Show product distribution
        $this->command->info("\n📦 Product Distribution (Top 10):");
        $products = DB::table('order_items')
            ->select('product_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(sub_total) as total_value'))
            ->groupBy('product_id')
            ->orderBy('order_count', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($products as $product) {
            $this->command->info(sprintf(
                "  Product #%d: %d orders, %d units sold, ₱%s revenue",
                $product->product_id,
                $product->order_count,
                $product->total_quantity,
                number_format($product->total_value, 2)
            ));
        }
        
        // Show customer distribution
        $this->command->info("\n👥 Customer Distribution (Top 10):");
        $customers = DB::table('order_items')
            ->select('customer_account_id', DB::raw('COUNT(DISTINCT order_id) as order_count'), DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(sub_total) as total_spent'))
            ->groupBy('customer_account_id')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($customers as $customer) {
            $this->command->info(sprintf(
                "  Customer #%d: %d orders, %d units, ₱%s spent",
                $customer->customer_account_id,
                $customer->order_count,
                $customer->total_quantity,
                number_format($customer->total_spent, 2)
            ));
        }
        
        // Show branch distribution
        $this->command->info("\n🏬 Branch Distribution:");
        $branches = DB::table('order_items')
            ->select('branch_id', DB::raw('COUNT(*) as item_count'), DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(sub_total) as total_value'))
            ->groupBy('branch_id')
            ->orderBy('branch_id')
            ->get();
        
        foreach ($branches as $branch) {
            $this->command->info(sprintf(
                "  Branch #%d: %d items, %d units, ₱%s total value",
                $branch->branch_id,
                $branch->item_count,
                $branch->total_quantity,
                number_format($branch->total_value, 2)
            ));
        }
        
        // Show order item status distribution
        $this->command->info("\n📊 Order Item Status:");
        $statuses = DB::table('order_items')
            ->select('order_item_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(sub_total) as total_value'))
            ->groupBy('order_item_status')
            ->orderBy('order_item_status')
            ->get();
        
        foreach ($statuses as $status) {
            $statusValue = $status->order_item_status === null ? 'NULL' : $status->order_item_status;
            $statusText = match($statusValue) {
                0 => 'Cancelled',
                1 => 'Bought',
                2 => 'Pending',
                default => 'Unknown',
            };
            $this->command->info(sprintf(
                "  %s: %d items, ₱%s value",
                $statusText,
                $status->count,
                number_format($status->total_value, 2)
            ));
        }
        
        // Show active status
        $this->command->info("\n🔔 Active Status:");
        $actives = DB::table('order_items')
            ->select('active', DB::raw('COUNT(*) as count'), DB::raw('SUM(sub_total) as total_value'))
            ->groupBy('active')
            ->orderBy('active')
            ->get();
        
        foreach ($actives as $active) {
            $activeValue = $active->active === null ? 'NULL' : $active->active;
            $activeText = $activeValue == 1 ? 'Active' : ($activeValue == 0 ? 'Inactive' : $activeValue);
            $this->command->info(sprintf(
                "  %s: %d items, ₱%s value",
                $activeText,
                $active->count,
                number_format($active->total_value, 2)
            ));
        }
        
        // Show price statistics
        $this->command->info("\n💰 Price Statistics:");
        $priceStats = DB::table('order_items')
            ->select(
                DB::raw('MIN(selling_price) as min_price'),
                DB::raw('MAX(selling_price) as max_price'),
                DB::raw('AVG(selling_price) as avg_price'),
                DB::raw('MIN(quantity) as min_quantity'),
                DB::raw('MAX(quantity) as max_quantity'),
                DB::raw('AVG(quantity) as avg_quantity'),
                DB::raw('SUM(sub_total) as grand_total'),
                DB::raw('SUM(quantity) as total_units')
            )
            ->first();
        
        $this->command->info("  Selling Price Range: ₱" . number_format($priceStats->min_price, 2) . " - ₱" . number_format($priceStats->max_price, 2));
        $this->command->info("  Average Selling Price: ₱" . number_format($priceStats->avg_price, 2));
        $this->command->info("  Quantity Range: " . $priceStats->min_quantity . " - " . $priceStats->max_quantity . " units");
        $this->command->info("  Average Quantity: " . number_format($priceStats->avg_quantity, 2) . " units");
        $this->command->info("  Grand Total Value: ₱" . number_format($priceStats->grand_total, 2));
        $this->command->info("  Total Units Sold: " . $priceStats->total_units);
        
        // Show most popular price points
        $this->command->info("\n🏷️  Most Common Price Points:");
        $pricePoints = DB::table('order_items')
            ->select('selling_price', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as total_quantity'))
            ->whereNotNull('selling_price')
            ->groupBy('selling_price')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($pricePoints as $price) {
            $this->command->info(sprintf(
                "  ₱%s: %d orders, %d units",
                number_format($price->selling_price, 2),
                $price->count,
                $price->total_quantity
            ));
        }
        
        // Show quantity distribution
        $this->command->info("\n📦 Quantity Distribution:");
        $quantities = DB::table('order_items')
            ->select('quantity', DB::raw('COUNT(*) as count'), DB::raw('SUM(sub_total) as total_value'))
            ->whereNotNull('quantity')
            ->groupBy('quantity')
            ->orderBy('quantity')
            ->get();
        
        foreach ($quantities as $qty) {
            $this->command->info(sprintf(
                "  %d unit(s): %d items, ₱%s total",
                $qty->quantity,
                $qty->count,
                number_format($qty->total_value, 2)
            ));
        }
        
        // Show sample order item records
        $this->command->info("\n🛍️  Sample Order Item Records (last 5):");
        $samples = DB::table('order_items')
            ->select('id', 'order_id', 'product_id', 'selling_price', 'quantity', 'sub_total', 'order_item_status', 'active')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($samples as $sample) {
            $statusText = match($sample->order_item_status) {
                0 => 'Cancelled',
                1 => 'Bought',
                2 => 'Pending',
                default => 'Unknown',
            };
            
            $this->command->info(sprintf(
                "#%d: Order #%d, Product #%d",
                $sample->id,
                $sample->order_id,
                $sample->product_id
            ));
            $this->command->info(sprintf(
                "   Price: ₱%s | Qty: %d | Subtotal: ₱%s",
                number_format($sample->selling_price, 2),
                $sample->quantity,
                number_format($sample->sub_total, 2)
            ));
            $this->command->info("   Status: {$statusText} | Active: {$sample->active}");
        }
        
        // Verify data integrity
        $this->command->info("\n🔍 Data Integrity Check:");
        
        // Check for items with mismatched calculation
        $mismatched = DB::table('order_items')
            ->whereRaw('ROUND(selling_price * quantity, 2) != ROUND(sub_total, 2)')
            ->count();
        
        if ($mismatched > 0) {
            $this->command->warn("⚠️  Found {$mismatched} items with mismatched price calculations");
        } else {
            $this->command->info("✓ All price calculations are correct");
        }
        
        // Check for items with null values in required fields
        $nullRequired = DB::table('order_items')
            ->whereNull('order_id')
            ->orWhereNull('product_id')
            ->orWhereNull('branch_id')
            ->count();
        
        if ($nullRequired > 0) {
            $this->command->warn("⚠️  Found {$nullRequired} items with null values in required fields");
        } else {
            $this->command->info("✓ All required fields are populated");
        }
        
        // Check for orphaned order references
        $orphanedOrders = DB::table('order_items as oi')
            ->leftJoin('orders as o', 'oi.order_id', '=', 'o.id')
            ->whereNull('o.id')
            ->count();
        
        if ($orphanedOrders > 0) {
            $this->command->warn("⚠️  Found {$orphanedOrders} items referencing non-existent orders");
        } else {
            $this->command->info("✓ All order references are valid");
        }
    }
}