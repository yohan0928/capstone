<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingsSeeder extends Seeder
{
    public function run()
    {
        // Path to your CSV file
        $csvFile = storage_path('app/bookings.csv');
        
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
        $invalidTimes = 0;
        
        $this->command->info("Starting Bookings CSV import...");
        
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
            
            // Generate booking reference number
            $bookingRefNo = 'BRN' . Carbon::now()->format('Ymd') . Str::upper(Str::random(4));
            
            // Check if customer_account_id exists
            if (!isset($csvData['customer_account_id']) || 
                !DB::table('customer_accounts')->where('id', $csvData['customer_account_id'])->exists()) {
                $skipped++;
                continue;
            }
            
            // Clean and format times
            $startTime = $this->formatTimeToHHMMSS($csvData['start_time'] ?? null);
            $endTime = $this->formatTimeToHHMMSS($csvData['end_time'] ?? null);
            $extendedStartTime = $this->formatTimeToHHMMSS($csvData['extended_start_time'] ?? null);
            $extendedEndTime = $this->formatTimeToHHMMSS($csvData['extended_end_time'] ?? null);
            
            // Track invalid times
            if (!$startTime && isset($csvData['start_time']) && $csvData['start_time']) $invalidTimes++;
            if (!$endTime && isset($csvData['end_time']) && $csvData['end_time']) $invalidTimes++;
            
            // Parse dates
            $dateStart = $this->formatDateToYMD($csvData['date_start'] ?? null);
            $dateEnd = $this->formatDateToYMD($csvData['date_end'] ?? null);
            $extendedDateStart = $this->formatDateToYMD($csvData['extended_date_start'] ?? null);
            $extendedDateEnd = $this->formatDateToYMD($csvData['extended_date_end'] ?? null);
            
            // Handle booking_date - use date_start if booking_date is null
            $bookingDate = isset($csvData['booking_date']) && $csvData['booking_date'] ? 
                          $this->formatDateTime($csvData['booking_date']) : 
                          ($dateStart ? Carbon::createFromFormat('Y-m-d', $dateStart)->startOfDay() : Carbon::now());
            
            // Prepare data for insertion
            $bookingData = [
                'uuid' => (string) Str::uuid(),
                'booking_ref_no' => $bookingRefNo,
                'customer_account_id' => $csvData['customer_account_id'] ?? null,
                'branch_id' => isset($csvData['branch_id']) && $csvData['branch_id'] ? $csvData['branch_id'] : null,
                'service_category_id' => isset($csvData['service_category_id']) && $csvData['service_category_id'] ? $csvData['service_category_id'] : null,
                'service_name_id' => isset($csvData['service_name_id']) && $csvData['service_name_id'] ? $csvData['service_name_id'] : null,
                'seat_id' => isset($csvData['seat_id']) && $csvData['seat_id'] ? $csvData['seat_id'] : null,
                
                // Base booking time and date - format to HH:MM:SS and YYYY-MM-DD
                'start_time' => $startTime,
                'end_time' => $endTime,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                
                // Extension fields
                'extended_start_time' => $extendedStartTime,
                'extended_end_time' => $extendedEndTime,
                'extended_date_start' => $extendedDateStart,
                'extended_date_end' => $extendedDateEnd,
                
                // Other fields
                'booking_date' => $bookingDate,
                'booking_type' => isset($csvData['booking_type']) ? (int)$csvData['booking_type'] : 0, // Default to walk-in
                'booking_status' => isset($csvData['booking_status']) ? (int)$csvData['booking_status'] : 4, // Default to completed
                
                // Reminder fields (check if they exist in CSV)
                'start_reminder_sent' => isset($csvData['start_reminder_sent']) ? (int)$csvData['start_reminder_sent'] : 0,
                'start_reminder_sent_at' => isset($csvData['start_reminder_sent_at']) && $csvData['start_reminder_sent_at'] ? 
                                           $this->formatDateTime($csvData['start_reminder_sent_at']) : null,
                'end_reminder_sent' => isset($csvData['end_reminder_sent']) ? (int)$csvData['end_reminder_sent'] : 0,
                'end_reminder_sent_at' => isset($csvData['end_reminder_sent_at']) && $csvData['end_reminder_sent_at'] ? 
                                         $this->formatDateTime($csvData['end_reminder_sent_at']) : null,
                
                // Audit fields (check if they exist in CSV)
                'created_by' => null, // Not in CSV
                'created_by_type' => isset($csvData['created_by_type']) && $csvData['created_by_type'] ? $csvData['created_by_type'] : null,
                'date_created' => isset($csvData['date_created']) && $csvData['date_created'] ? 
                                 $this->formatDateTime($csvData['date_created']) : Carbon::now(),
                'last_updated_by' => isset($csvData['last_updated_by']) && $csvData['last_updated_by'] ? $csvData['last_updated_by'] : null,
                'last_updated_by_type' => isset($csvData['last_updated_by_type']) && $csvData['last_updated_by_type'] ? $csvData['last_updated_by_type'] : null,
                'last_date_updated' => isset($csvData['last_date_updated']) && $csvData['last_date_updated'] ? 
                                      $this->formatDateTime($csvData['last_date_updated']) : Carbon::now(),
                'updated_by' => isset($csvData['update_by']) && $csvData['update_by'] ? $csvData['update_by'] : null, // Note: CSV has 'update_by' not 'updated_by'
                'updated_by_type' => isset($csvData['updated_by_type']) && $csvData['updated_by_type'] ? $csvData['updated_by_type'] : null,
                'date_updated' => isset($csvData['date_updated']) && $csvData['date_updated'] ? 
                                 $this->formatDateTime($csvData['date_updated']) : Carbon::now(),
                
                'active' => isset($csvData['active']) ? (int)$csvData['active'] : 1,
            ];
            
            $data[] = $bookingData;
            $count++;
            
            // Insert in batches
            if ($count % $batchSize === 0) {
                try {
                    DB::table('bookings')->insert($data);
                    $this->command->info("✓ Inserted {$count} booking records...");
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
                DB::table('bookings')->insert($data);
                $this->command->info("✓ Inserted final batch of " . count($data) . " booking records...");
            } catch (\Exception $e) {
                $this->command->error("Error inserting final batch: " . $e->getMessage());
            }
        }
        
        fclose($file);
        
        $this->command->info("\n✅ Bookings CSV Import Complete!");
        $this->command->info("📊 Total records processed: {$count}");
        $this->command->info("⚠️  Skipped rows: {$skipped}");
        $this->command->info("⚠️  Invalid time formats found: {$invalidTimes}");
        
        // Show import statistics
        $this->showImportStatistics();
    }
    
    /**
     * Format time to HH:MM:SS
     * Handles cases like "12:00", "12:00:00", "12:00 pm", "99:57 pm"
     */
    private function formatTimeToHHMMSS($timeString)
    {
        if (empty($timeString) || $timeString === '' || $timeString === null) {
            return null;
        }
        
        $timeString = trim(strtolower($timeString));
        
        // Remove any "pm", "am", "p.m.", "a.m." and whitespace
        $timeString = preg_replace('/\s*(am|pm|a\.m\.|p\.m\.)$/i', '', $timeString);
        
        // Check if time is already in HH:MM:SS format
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $timeString, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = (int)$matches[3];
            
            // Validate and fix if needed
            if ($hours >= 24) $hours = 23;
            if ($minutes >= 60) $minutes = 59;
            if ($seconds >= 60) $seconds = 59;
            
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }
        
        // Check if time is in HH:MM format
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $timeString, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            
            // Handle invalid times (like "99:57")
            if ($hours >= 24) {
                // If hours > 23, adjust to valid 24-hour format
                $hours = $hours % 24;
                if ($hours === 0) $hours = 23; // Cap at 23 if modulo gives 0
            }
            if ($minutes >= 60) $minutes = 59;
            
            return sprintf('%02d:%02d:00', $hours, $minutes);
        }
        
        // Try to parse with Carbon for other formats
        try {
            $time = Carbon::parse($timeString);
            return $time->format('H:i:s');
        } catch (\Exception $e) {
            $this->command->warn("Could not parse time: {$timeString}");
            return null;
        }
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
        $this->command->info("\n📈 Bookings Import Statistics:");
        
        $totalCount = DB::table('bookings')->count();
        $this->command->info("Total bookings in table: {$totalCount}");
        
        if ($totalCount === 0) {
            $this->command->error("❌ No data was imported!");
            return;
        }
        
        // Show sample of formatted data
        $this->command->info("\n📊 Sample of Formatted Data (first 5 records):");
        $samples = DB::table('bookings')
            ->select('booking_ref_no', 'date_start', 'start_time', 'end_time', 'booking_status', 'booking_type', 'active')
            ->orderBy('id')
            ->limit(5)
            ->get();
        
        foreach ($samples as $sample) {
            $this->command->info("Ref: {$sample->booking_ref_no} | Date: {$sample->date_start} | Time: {$sample->start_time} to {$sample->end_time}");
            $this->command->info("  Status: {$sample->booking_status} | Type: {$sample->booking_type} | Active: {$sample->active}");
        }
        
        // Show time format validation
        $this->command->info("\n🔍 Data Format Validation:");
        
        $validStartTimes = DB::table('bookings')
            ->whereNotNull('start_time')
            ->where('start_time', 'REGEXP', '^[0-9]{2}:[0-9]{2}:[0-9]{2}$')
            ->count();
        $this->command->info("✓ Valid HH:MM:SS formatted start times: {$validStartTimes}/{$totalCount}");
        
        $validDates = DB::table('bookings')
            ->whereNotNull('date_start')
            ->where('date_start', 'REGEXP', '^[0-9]{4}-[0-9]{2}-[0-9]{2}$')
            ->count();
        $this->command->info("✓ Valid YYYY-MM-DD formatted dates: {$validDates}/{$totalCount}");
        
        // Show data distribution
        $this->command->info("\n📊 Booking Status Distribution:");
        $statuses = DB::table('bookings')
            ->select('booking_status', DB::raw('COUNT(*) as count'))
            ->groupBy('booking_status')
            ->orderBy('booking_status')
            ->get();
        
        $statusMap = [
            0 => 'Cancelled',
            1 => 'Booked/Confirmed',
            2 => 'Pending',
            3 => 'No-show',
            4 => 'Completed',
        ];
        
        foreach ($statuses as $status) {
            $statusText = $statusMap[$status->booking_status] ?? "Unknown ({$status->booking_status})";
            $percentage = $totalCount > 0 ? round(($status->count / $totalCount) * 100, 1) : 0;
            $this->command->info("  {$statusText}: {$status->count} bookings ({$percentage}%)");
        }
        
        $this->command->info("\n🎉 Data import completed successfully!");
    }
}