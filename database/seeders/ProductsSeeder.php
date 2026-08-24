<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProductsSeeder extends Seeder
{
    public function run()
    {
        // Import products from CSV
        $this->importProducts();
    }
    
    private function importProducts()
    {
        $this->command->info("=== STARTING PRODUCTS IMPORT ===");
        
        // Path to your CSV file
        $csvFile = storage_path('app/products.csv');
        
        $this->command->info("Looking for CSV file at: {$csvFile}");
        
        if (!file_exists($csvFile)) {
            $this->command->error("❌ Products CSV file not found! Please ensure the file exists at: {$csvFile}");
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
            $tempFile = storage_path('app/products_utf8.csv');
            file_put_contents($tempFile, $fileContent);
            $csvFile = $tempFile;
            $this->command->info("Converted file to UTF-8");
        }
        
        $file = fopen($csvFile, 'r');
        if (!$file) {
            $this->command->error("❌ Could not open products CSV file!");
            return;
        }
        
        $header = fgetcsv($file); // Read header row
        $this->command->info("CSV Header columns: " . count($header));
        
        $data = [];
        $count = 0;
        $skipped = 0;
        $now = Carbon::now();
        $batchSize = 50;
        
        $this->command->info("Starting products CSV import...");
        
        // Process each row
        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows (check if first column is empty)
            if (empty($row[0])) {
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
            
            // Extract data based on CSV columns (0-based index)
            // Generate product batch number if not provided
            $productBatchNo = $row[2];
            if (!$productBatchNo) {
                $productBatchNo = null; // Set to null if empty
            }
            
            // Parse selling price
            $sellingPrice = $row[12];
            if ($sellingPrice) {
                // Remove any non-numeric characters except decimal point
                $sellingPrice = preg_replace('/[^0-9.]/', '', $sellingPrice);
                $sellingPrice = floatval($sellingPrice);
            } else {
                $sellingPrice = null;
            }
            
            // Parse dates - handle null values
            $dateStored = null;
            $dateExpiration = null;
            $dateCreated = null;
            $lastDateUpdated = null;
            $dateUpdated = null;
            
            // Clean and validate product name
            $productName = $row[5] ?? '';
            if ($productName) {
                // Clean special characters - replace special quotes and other characters
                $productName = trim($productName);
                $productName = preg_replace('/[^\x20-\x7E\xA0-\xFF\s]/u', '', $productName);
                // Replace common problematic characters
                $productName = str_replace(['é', 'É', 'è', 'È', 'ê', 'Ê', 'ë', 'Ë'], 'e', $productName);
                $productName = str_replace(['á', 'Á', 'à', 'À', 'â', 'Â', 'ä', 'Ä'], 'a', $productName);
                $productName = str_replace(['í', 'Í', 'ì', 'Ì', 'î', 'Î', 'ï', 'Ï'], 'i', $productName);
                $productName = str_replace(['ó', 'Ó', 'ò', 'Ò', 'ô', 'Ô', 'ö', 'Ö'], 'o', $productName);
                $productName = str_replace(['ú', 'Ú', 'ù', 'Ù', 'û', 'Û', 'ü', 'Ü'], 'u', $productName);
                $productName = str_replace(['ñ', 'Ñ'], 'n', $productName);
                $productName = str_replace(['ç', 'Ç'], 'c', $productName);
            }
            
            // Prepare product data based on your Product model fillable fields
            $productData = [
                'uuid' => (string) Str::uuid(),
                'owner_account_id' => $row[0] ? intval($row[0]) : null,
                'branch_id' => $row[1] ? intval($row[1]) : null,
                'product_batch_no' => $productBatchNo,
                'product_img' => $row[3],
                'product_type' => $row[4],
                'product_name' => $productName,
                'quantity_in' => $row[6] ? intval($row[6]) : null,
                'unit' => $row[7],
                'quantity_threshold' => $row[8] ? intval($row[8]) : null,
                'selling_price' => $sellingPrice,
                'date_stored' => $dateStored,
                'date_expiration' => $dateExpiration,
                'product_status' => $row[15] ? intval($row[15]) : null,
                'created_by' => $row[16] ? intval($row[16]) : null,
                'created_by_type' => $row[17],
                'date_created' => $dateCreated,
                'last_updated_by' => $row[19] ? intval($row[19]) : null,
                'last_updated_by_type' => $row[20],
                'last_date_updated' => $lastDateUpdated,
                'updated_by' => $row[22] ? intval($row[22]) : null,
                'updated_by_type' => $row[23],
                'date_updated' => $dateUpdated,
                'active' => $row[25] ? intval($row[25]) : null,
            ];
            
            // Ensure product_name is not empty (required field)
            if (empty($productData['product_name'])) {
                $skipped++;
                $this->command->warn("⚠️  Skipping row - product name is empty");
                continue;
            }
            
            $data[] = $productData;
            $count++;
            
            // Show progress for every 5 records
            if ($count % 5 === 0) {
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
        
        $this->command->info("\n✅ PRODUCTS IMPORT COMPLETE!");
        $this->command->info("📊 Products processed: {$count}");
        
        if ($skipped > 0) {
            $this->command->info("⚠️  Skipped rows: {$skipped}");
        }
        
        // Show products import statistics
        $this->showProductsImportStatistics();
    }
    
    /**
     * Insert a batch of records
     */
    private function insertBatch(array &$data)
    {
        try {
            DB::table('products')->insert($data);
            $this->command->info("✓ Inserted batch of " . count($data) . " product records...");
        } catch (\Exception $e) {
            $this->command->error("Error inserting product batch: " . $e->getMessage());
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
        
        foreach ($data as $index => $productData) {
            try {
                DB::table('products')->insert([$productData]);
                $successCount++;
                $this->command->info("✓ Inserted record {$index}: {$productData['product_name']}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->command->error("❌ Failed to insert record {$index}: {$productData['product_name']}");
                $this->command->error("   Error: " . $e->getMessage());
                
                // Try with additional cleaning
                $this->command->info("🔄 Attempting to clean and retry...");
                $cleanedData = $this->cleanProductDataForInsert($productData);
                try {
                    DB::table('products')->insert([$cleanedData]);
                    $this->command->info("✓ Inserted cleaned record {$index}: {$cleanedData['product_name']}");
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
     * Clean product data to ensure UTF-8 compatibility
     */
    private function cleanProductDataForInsert(array $data): array
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
            '“' => '"', '”' => '"', '–' => '-', '—' => '-',
        ];
        
        return strtr($text, $specialChars);
    }
    
    private function showProductsImportStatistics()
    {
        $this->command->info("\n📈 PRODUCTS IMPORT STATISTICS:");
        
        // Get total count
        $totalCount = DB::table('products')->count();
        $this->command->info("Total products in table: {$totalCount}");
        
        if ($totalCount === 0) {
            $this->command->error("No products were imported!");
            return;
        }
        
        // Show owner distribution
        $this->command->info("\n👑 Owner Distribution:");
        $owners = DB::table('products')
            ->select('owner_account_id', DB::raw('COUNT(*) as count'))
            ->groupBy('owner_account_id')
            ->orderBy('owner_account_id')
            ->get();
        
        foreach ($owners as $owner) {
            $this->command->info("  Owner {$owner->owner_account_id}: {$owner->count} products");
        }
        
        // Show branch distribution
        $this->command->info("\n🏬 Branch Distribution:");
        $branches = DB::table('products')
            ->select('branch_id', DB::raw('COUNT(*) as count'))
            ->groupBy('branch_id')
            ->orderBy('branch_id')
            ->get();
        
        foreach ($branches as $branch) {
            $branchId = $branch->branch_id === null ? 'NULL' : $branch->branch_id;
            $this->command->info("  Branch {$branchId}: {$branch->count} products");
        }
        
        // Show product status distribution
        $this->command->info("\n📦 Product Status:");
        $statuses = DB::table('products')
            ->select('product_status', DB::raw('COUNT(*) as count'))
            ->groupBy('product_status')
            ->orderBy('product_status')
            ->get();
        
        foreach ($statuses as $status) {
            $statusValue = $status->product_status === null ? 'NULL' : $status->product_status;
            $statusText = $statusValue == 1 ? 'Available' : ($statusValue == 0 ? 'Unavailable' : $statusValue);
            $this->command->info("  Status {$statusText}: {$status->count} products");
        }
        
        // Show active status
        $this->command->info("\n🔔 Active Status:");
        $actives = DB::table('products')
            ->select('active', DB::raw('COUNT(*) as count'))
            ->groupBy('active')
            ->orderBy('active')
            ->get();
        
        foreach ($actives as $active) {
            $activeValue = $active->active === null ? 'NULL' : $active->active;
            $activeText = $activeValue == 1 ? 'Active' : ($activeValue == 0 ? 'Inactive' : $activeValue);
            $this->command->info("  {$activeText}: {$active->count} products");
        }
        
        // Show price statistics
        $this->command->info("\n💰 Price Statistics:");
        $priceStats = DB::table('products')
            ->select(
                DB::raw('MIN(selling_price) as min_price'),
                DB::raw('MAX(selling_price) as max_price'),
                DB::raw('AVG(selling_price) as avg_price'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(selling_price) as total_value')
            )
            ->first();
        
        $this->command->info("  Min Price: ₱" . number_format($priceStats->min_price ?? 0, 2));
        $this->command->info("  Max Price: ₱" . number_format($priceStats->max_price ?? 0, 2));
        $this->command->info("  Avg Price: ₱" . number_format($priceStats->avg_price ?? 0, 2));
        $this->command->info("  Total Products: {$priceStats->total}");
        
        // Show sample product records
        $this->command->info("\n🛍️  Sample Product Records (last 5):");
        $samples = DB::table('products')
            ->select('id', 'product_name', 'product_type', 'quantity_in', 'unit', 'selling_price', 'product_status', 'active')
            ->whereNotNull('product_name')
            ->where('product_name', '!=', '')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($samples as $sample) {
            $this->command->info(sprintf(
                "#%d: %s",
                $sample->id,
                $sample->product_name
            ));
            $this->command->info("   Type: {$sample->product_type} | Qty: {$sample->quantity_in} {$sample->unit}");
            $this->command->info("   Price: ₱{$sample->selling_price} | Status: {$sample->product_status} | Active: {$sample->active}");
        }
        
        // Show top 10 products by name frequency
        $this->command->info("\n🏆 Top 10 Products:");
        $topProducts = DB::table('products')
            ->select('product_name', DB::raw('COUNT(*) as count'))
            ->whereNotNull('product_name')
            ->where('product_name', '!=', '')
            ->groupBy('product_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        $rank = 1;
        foreach ($topProducts as $product) {
            $this->command->info("  {$rank}. {$product->product_name}: {$product->count} records");
            $rank++;
        }
    }
}