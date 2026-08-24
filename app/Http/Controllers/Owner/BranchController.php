<?php

namespace App\Http\Controllers\Owner;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\OwnerAccount;
use App\Models\StaffAccount;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Owner\BranchNotification;
use App\Notifications\Staff\BranchStaffNotification;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    protected $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    // Show Branches - Updated for modal support
    public function showBranch(Request $request)
    {
        // Logged in as Owner
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $query = Branch::with('owner')
            ->where('active', 1)
            ->where('owner_account_id', $ownerId);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('branch_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('location', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('features', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('open_time', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('close_time', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('open_days', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('address', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by branch status
        if ($request->filled('branch_status') && $request->branch_status !== '') {
            $query->where('branch_status', $request->branch_status);
        }

        // Filter by geocoding status
        if ($request->filled('has_coordinates')) {
            if ($request->has_coordinates == '1') {
                $query->whereNotNull('latitude')->whereNotNull('longitude');
            } elseif ($request->has_coordinates == '0') {
                $query->whereNull('latitude')->orWhereNull('longitude');
            }
        }

        $branches = $query->orderBy('date_created', 'desc')->paginate(10);

        $this->runCleanup();

        // Statistics
        $statsQuery = Branch::where('owner_account_id', $ownerId)->where('active', 1);

        $totalBranches = $statsQuery->count();
        $openBranches = (clone $statsQuery)->where('branch_status', 1)->count();
        $closedBranches = (clone $statsQuery)->where('branch_status', 0)->count();
        $comingSoonBranches = (clone $statsQuery)->where('branch_status', 2)->count();
        $branchesWithCoordinates = (clone $statsQuery)->whereNotNull('latitude')->whereNotNull('longitude')->count();
        $branchesWithoutCoordinates = $totalBranches - $branchesWithCoordinates;

        $stats = [
            'total_branches' => $totalBranches,
            'open_branches' => $openBranches,
            'closed_branches' => $closedBranches,
            'coming_soon_branches' => $comingSoonBranches,
            'has_coordinates' => $branchesWithCoordinates,
            'no_coordinates' => $branchesWithoutCoordinates,
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $branches->items(),
                'pagination' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'per_page' => $branches->perPage(),
                    'total' => $branches->total(),
                    'from' => $branches->firstItem(),
                    'to' => $branches->lastItem(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('owner.branch.branch', compact('branches', 'stats'));
    }

    /**
     * ================================================================
     * AUTO-GEOCODE ADDRESS
     * ================================================================
     * This method automatically converts the address to coordinates
     * using the geocoding service.
     */
    private function autoGeocodeAddress($address)
    {
        try {
            $result = $this->geocodingService->geocodeAddress($address);
            
            if ($result) {
                return [
                    'latitude' => $result['latitude'],
                    'longitude' => $result['longitude'],
                    'formatted_address' => $result['formatted_address'] ?? null,
                    'success' => true
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Could not geocode this address. Please enter coordinates manually.'
            ];
        } catch (\Exception $e) {
            Log::error('Geocoding error in BranchController: ' . $e->getMessage(), [
                'address' => $address
            ]);
            
            return [
                'success' => false,
                'message' => 'Geocoding service error. Please enter coordinates manually.'
            ];
        }
    }

    // Store Branch - UPDATED with geocoding
    public function storeBranch(Request $request)
    {
        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'google_map_url' => 'required|url|max:500',
            'features' => 'required|string|max:255',
            'open_hour' => 'required',
            'open_minute' => 'required',
            'open_ampm' => 'required|in:AM,PM',
            'close_hour' => 'required',
            'close_minute' => 'required',
            'close_ampm' => 'required|in:AM,PM',
            'open_days' => 'required|string|max:255',
            'branch_profile' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // Get the authenticated owner
        $owner = Auth::guard('owner')->user();

        // Convert 12-hour input to 24-hour time for storage
        $open_time = Carbon::createFromFormat('h:i A', "{$validated['open_hour']}:{$validated['open_minute']} {$validated['open_ampm']}")->format('H:i:s');
        $close_time = Carbon::createFromFormat('h:i A', "{$validated['close_hour']}:{$validated['close_minute']} {$validated['close_ampm']}")->format('H:i:s');

        // Handle single image upload
        $branchProfile = null;
        if ($request->hasFile('branch_profile')) {
            $branchProfile = $request->file('branch_profile')->store('branch_profiles', 'public');
        }

        // ============================================================
        // AUTO-GEOCODE THE ADDRESS
        // ============================================================
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $geocodingMessage = null;

        // Only auto-geocode if coordinates weren't manually provided
        if (empty($latitude) || empty($longitude)) {
            $geocodeResult = $this->autoGeocodeAddress($validated['address']);
            
            if ($geocodeResult['success']) {
                $latitude = $geocodeResult['latitude'];
                $longitude = $geocodeResult['longitude'];
                $geocodingMessage = 'Coordinates automatically found from address.';
            } else {
                $geocodingMessage = $geocodeResult['message'];
                // Keep latitude/longitude as null, they'll need to be added manually
            }
        } else {
            $geocodingMessage = 'Coordinates manually provided.';
        }

        $branch = Branch::create([
            'owner_account_id' => $owner->id,
            'branch_profile' => $branchProfile,
            'branch_name' => $validated['branch_name'],
            'location' => $validated['location'],
            'address' => $validated['address'],
            'google_map_url' => $validated['google_map_url'],
            'features' => $validated['features'],
            'open_time' => $open_time,
            'close_time' => $close_time,
            'open_days' => $validated['open_days'],
            'branch_status' => 1,  // open
            'latitude' => $latitude,
            'longitude' => $longitude,
            'date_created' => now(),
            'active' => 1,
        ]);

        // Flash message with geocoding status
        $statusMessage = 'Branch created successfully!';
        if ($geocodingMessage) {
            $statusMessage .= ' ' . $geocodingMessage;
        }

        return redirect()->route('sub_one.branches.showBranch')
            ->with('toast', [
                'type' => $latitude && $longitude ? 'success' : 'warning',
                'message' => $statusMessage
            ]);
    }

    // Get Branch Data for Edit Modal
    public function getBranchData($branch_uuid)
    {
        try {
            $branch = Branch::where('uuid', $branch_uuid)
                ->where('owner_account_id', Auth::guard('owner')->id())
                ->where('active', 1)
                ->firstOrFail();

            // Parse the times properly
            if ($branch->open_time) {
                $openTime = Carbon::parse($branch->open_time);
                $branch->formatted_open_time = $openTime->format('h:i A');
                $branch->open_hour = $openTime->format('h');
                $branch->open_minute = $openTime->format('i');
                $branch->open_ampm = $openTime->format('A');
            }
            
            if ($branch->close_time) {
                $closeTime = Carbon::parse($branch->close_time);
                $branch->formatted_close_time = $closeTime->format('h:i A');
                $branch->close_hour = $closeTime->format('h');
                $branch->close_minute = $closeTime->format('i');
                $branch->close_ampm = $closeTime->format('A');
            }

            return response()->json([
                'success' => true,
                'branch' => $branch
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or you do not have permission to edit it.'
            ], 404);
        }
    }

    // Update Branch Details - UPDATED with geocoding
    public function updateBranch(Request $request, $branch_uuid)
    {
        $branch = Branch::where('uuid', $branch_uuid)->firstOrFail();

        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'google_map_url' => 'required|url|max:500',
            'features' => 'required|string|max:255',
            'open_hour' => 'required',
            'open_minute' => 'required',
            'open_ampm' => 'required|in:AM,PM',
            'close_hour' => 'required',
            'close_minute' => 'required',
            'close_ampm' => 'required|in:AM,PM',
            'open_days' => 'required|string|max:255',
            'branch_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // Get the authenticated owner
        $owner = Auth::guard('owner')->user();

        $open_time = Carbon::createFromFormat('h:i A', "{$validated['open_hour']}:{$validated['open_minute']} {$validated['open_ampm']}")->format('H:i:s');
        $close_time = Carbon::createFromFormat('h:i A', "{$validated['close_hour']}:{$validated['close_minute']} {$validated['close_ampm']}")->format('H:i:s');

        // Get current image from the branch
        $currentImage = $branch->branch_profile;

        // Handle new image upload
        if ($request->hasFile('branch_profile')) {
            // Delete old image if exists
            if ($currentImage && Storage::disk('public')->exists($currentImage)) {
                Storage::disk('public')->delete($currentImage);
            }

            // Store new image
            $imagePath = $request->file('branch_profile')->store('branch_profiles', 'public');
            $currentImage = $imagePath;
        }

        // ============================================================
        // AUTO-GEOCODE THE ADDRESS (only if address changed)
        // ============================================================
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $geocodingMessage = null;

        // Only auto-geocode if address changed and coordinates weren't manually provided
        if ($branch->address !== $validated['address'] && (empty($latitude) || empty($longitude))) {
            $geocodeResult = $this->autoGeocodeAddress($validated['address']);
            
            if ($geocodeResult['success']) {
                $latitude = $geocodeResult['latitude'];
                $longitude = $geocodeResult['longitude'];
                $geocodingMessage = 'Coordinates automatically updated from new address.';
            } else {
                $geocodingMessage = $geocodeResult['message'];
                // Keep existing coordinates if they exist
                $latitude = $branch->latitude;
                $longitude = $branch->longitude;
            }
        } elseif (empty($latitude) || empty($longitude)) {
            // Keep existing coordinates
            $latitude = $branch->latitude;
            $longitude = $branch->longitude;
        } else {
            $geocodingMessage = 'Coordinates manually updated.';
        }

        $branch->update([
            'owner_account_id' => $owner->id,
            'branch_profile' => $currentImage,
            'branch_name' => $validated['branch_name'],
            'location' => $validated['location'],
            'address' => $validated['address'],
            'google_map_url' => $validated['google_map_url'],
            'features' => $validated['features'],
            'open_time' => $open_time,
            'close_time' => $close_time,
            'open_days' => $validated['open_days'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'date_updated' => now(),
        ]);

        // Flash message with geocoding status
        $statusMessage = 'Branch updated successfully!';
        if ($geocodingMessage) {
            $statusMessage .= ' ' . $geocodingMessage;
        }

        return redirect()->route('sub_one.branches.showBranch')
            ->with('toast', [
                'type' => $latitude && $longitude ? 'success' : 'warning',
                'message' => $statusMessage
            ]);
    }

    /**
     * ================================================================
     * AJAX Geocode Address Endpoint
     * ================================================================
     * This endpoint is called from the frontend to geocode an address
     * and return the coordinates in JSON format.
     */
    public function geocodeAddress(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:500'
        ]);

        try {
            $result = $this->geocodingService->geocodeAddress($request->address);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'latitude' => $result['latitude'],
                        'longitude' => $result['longitude'],
                        'formatted_address' => $result['formatted_address'] ?? $request->address,
                        'provider' => $result['provider'] ?? 'unknown'
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Could not find coordinates for this address. Please check the address or enter coordinates manually.'
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('AJAX Geocoding error: ' . $e->getMessage(), [
                'address' => $request->address
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Geocoding service error. Please enter coordinates manually.'
            ], 500);
        }
    }

    /**
     * ================================================================
     * Batch Geocode All Branches
     * ================================================================
     * This endpoint geocodes all branches that don't have coordinates.
     */
    public function batchGeocode()
    {
        $owner = Auth::guard('owner')->user();
        
        $branches = Branch::where('owner_account_id', $owner->id)
            ->where('active', 1)
            ->where(function($query) {
                $query->whereNull('latitude')
                      ->orWhereNull('longitude');
            })
            ->get();

        if ($branches->isEmpty()) {
            return redirect()->route('sub_one.branches.showBranch')
                ->with('toast', [
                    'type' => 'info',
                    'message' => 'All branches already have coordinates!'
                ]);
        }

        $successCount = 0;
        $failedCount = 0;
        $failedBranches = [];

        foreach ($branches as $branch) {
            if (empty($branch->address)) {
                $failedCount++;
                $failedBranches[] = $branch->branch_name . ' (no address)';
                continue;
            }

            $result = $this->autoGeocodeAddress($branch->address);
            
            if ($result['success']) {
                $branch->latitude = $result['latitude'];
                $branch->longitude = $result['longitude'];
                $branch->save();
                $successCount++;
            } else {
                $failedCount++;
                $failedBranches[] = $branch->branch_name . ' (' . ($result['message'] ?? 'unknown error') . ')';
            }

            // Sleep to respect rate limits
            usleep(500000); // 0.5 seconds
        }

        $message = "Batch geocoding complete: {$successCount} branches updated, {$failedCount} failed.";
        
        if (!empty($failedBranches)) {
            $message .= " Failed: " . implode('; ', array_slice($failedBranches, 0, 5));
            if (count($failedBranches) > 5) {
                $message .= ' and ' . (count($failedBranches) - 5) . ' more...';
            }
        }

        return redirect()->route('sub_one.branches.showBranch')
            ->with('toast', [
                'type' => $failedCount > 0 ? 'warning' : 'success',
                'message' => $message
            ]);
    }

    // Update Branch Status
    public function updateBranchStatus(Request $request, $branch_uuid)
    {
        $validated = $request->validate([
            'branch_status' => 'required|in:0,1,2',  // 0=Closed, 1=Open, 2=Soon
        ]);

        $branch = Branch::where('uuid', $branch_uuid)->firstOrFail();

        if ($branch->branch_status == $validated['branch_status']) {
            return back()->with('info', 'No changes detected.');
        }

        $oldStatus = $branch->branch_status;
        $statusLabels = [
            0 => 'Closed',
            1 => 'Open',
            2 => 'Coming Soon'
        ];

        $oldStatusLabel = $statusLabels[$oldStatus];
        $newStatusLabel = $statusLabels[$validated['branch_status']];

        // Update status
        $branch->branch_status = $validated['branch_status'];
        $branch->date_updated = now();
        $branch->save();

        // Send notification for status change
        $actor = Auth::guard('owner')->user();
        $owner = Auth::guard('owner')->user();
        $owners = OwnerAccount::where('id', $owner->id)->get();

        Notification::send($owners, new BranchNotification($branch, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        // Notify all staff members under this specific branch
        $staffs = StaffAccount::where('branch_id', $branch->id)
            ->where('owner_account_id', $owner->id)
            ->get();

        Notification::send($staffs, new BranchStaffNotification($branch, $actor, 'status_changed', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]));

        return redirect()
            ->route('sub_one.branches.showBranch')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Branch Status updated successfully.'
            ]);
    }

    // Show Deactivated Branches
    public function showDeactivatedBranch(Request $request)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $today = now();

        // Get deactivated branches (only those within last 30 days)
        $query = Branch::with('owner')
            ->where('active', 0)
            ->where('owner_account_id', $ownerId)
            ->where('date_updated', '>=', $today->copy()->subDays(30));  // hide expired branches

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q
                    ->where('branch_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('location', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('features', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by branch status
        if ($request->filled('branch_status') && $request->branch_status !== '') {
            $query->where('branch_status', $request->branch_status);
        }

        // Filter by days left category
        if ($request->filled('days_left')) {
            switch ($request->days_left) {
                case 'critical':
                    $query->whereBetween('date_updated', [
                        $today->copy()->subDays(30),
                        $today->copy()->subDays(21)
                    ]);
                    break;

                case 'warning':
                    $query->whereBetween('date_updated', [
                        $today->copy()->subDays(20),
                        $today->copy()->subDays(11)
                    ]);
                    break;

                case 'normal':
                    $query->where('date_updated', '>=', $today->copy()->subDays(10));
                    break;
            }
        }

        // Get paginated branches
        $branches = $query->orderBy('date_updated', 'desc')->paginate(10);

        // Calculate stats
        $totalArchived = (clone $query)->count();
        $archivedOpen = (clone $query)->where('branch_status', 1)->count();
        $archivedClosed = (clone $query)->where('branch_status', 0)->count();

        // Calculate average days left
        $avgDaysLeft = 30;
        $archivedBranches = (clone $query)->get();

        if ($archivedBranches->count() > 0) {
            $totalDaysLeft = 0;
            foreach ($archivedBranches as $branch) {
                /** @var \App\Models\Branch $branch */
                $archivedDate = $branch->date_updated ?: $branch->date_created;
                $archivedDate = Carbon::parse($archivedDate);
                $daysSinceArchived = $archivedDate->diffInDays(now());
                $daysLeft = max(0, 30 - $daysSinceArchived);
                $totalDaysLeft += $daysLeft;
            }
            $avgDaysLeft = round($totalDaysLeft / $archivedBranches->count());
        }

        $stats = [
            'total_archived' => $totalArchived,
            'archived_open' => $archivedOpen,
            'archived_closed' => $archivedClosed,
            'avg_days_left' => $avgDaysLeft,
        ];

        // AJAX response (used for filtering/search)
        if ($request->ajax()) {
            $branchesData = $branches->getCollection()->map(function ($branch) {
                $archivedDate = $branch->date_updated ?: $branch->date_created;
                $archivedDate = Carbon::parse($archivedDate);
                $daysSinceArchived = $archivedDate->diffInDays(now());
                $branch->days_left = max(0, 30 - $daysSinceArchived);

                // Determine status label
                if ($branch->days_left <= 10) {
                    $branch->status_label = 'Critical';
                } elseif ($branch->days_left <= 20) {
                    $branch->status_label = 'Warning';
                } else {
                    $branch->status_label = 'Normal';
                }

                return $branch;
            });

            return response()->json([
                'success' => true,
                'data' => $branchesData,
                'pagination' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'per_page' => $branches->perPage(),
                    'total' => $branches->total(),
                    'from' => $branches->firstItem(),
                    'to' => $branches->lastItem(),
                ],
                'stats' => $stats,
            ]);
        }

        // Normal page view
        return view('owner.branch.archives.delete-branch', compact('branches', 'stats'));
    }

    // Deactivate Branch
    public function deactivateBranch($branch_uuid)
    {
        $branch = Branch::where('uuid', $branch_uuid)->firstOrFail();

        if ($branch->active === 0) {
            return back()->with('info', 'Branch is already deactivated.');
        }

        $branch->active = 0;
        $branch->branch_status = 0;  // 0=closed
        $branch->date_updated = now();  // Set archive date

        $branch->save();

        return redirect()->route('sub_one.branches.showBranch');
    }

    // Reactivate Branch
    public function reactivateBranch($branch_uuid)
    {
        $branch = Branch::where('uuid', $branch_uuid)->firstOrFail();

        if ($branch->active === 1) {
            return back()->with('info', 'Branch is already active.');
        }

        $branch->active = 1;
        $branch->branch_status = 1;  // 1=open
        $branch->date_updated = now();  // Update timestamp

        $branch->save();

        return redirect()->route('sub_one.branches.showDeactivatedBranch');
    }

    /**
     * ===========================================================
     * REMOVE FILES THAT ARE NOT EXISTING IN THE DATABASE
     * ===========================================================
     */
    private function cleanOrphanedFilesDynamic(array $folders): array
    {
        $allDeletedFiles = [];

        foreach ($folders as $folder => $config) {
            $modelClass = $config['model'];
            $column = $config['column'];

            /** @var \Illuminate\Database\Eloquent\Model $modelInstance */
            $modelInstance = new $modelClass;

            if (!Schema::hasTable($modelInstance->getTable()) || !Schema::hasColumn($modelInstance->getTable(), $column)) {
                continue;
            }

            $allFiles = $modelClass::pluck($column)->filter()->toArray();
            $existingFiles = [];

            foreach ($allFiles as $fileEntry) {
                if (empty($fileEntry))
                    continue;

                $decoded = json_decode($fileEntry, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $existingFiles = array_merge($existingFiles, $decoded);
                } else {
                    $existingFiles[] = $fileEntry;
                }
            }

            $existingFiles = array_map(fn($f) => ltrim($f, '/'), $existingFiles);
            $storageFiles = array_map(fn($f) => ltrim($f, '/'), Storage::disk('public')->files($folder));

            foreach ($storageFiles as $file) {
                if (!in_array($file, $existingFiles)) {
                    Storage::disk('public')->delete($file);
                    $allDeletedFiles[] = $file;
                }
            }
        }

        return $allDeletedFiles;
    }

    public function runCleanup(): array
    {
        return $this->cleanOrphanedFilesDynamic([
            'branch_profiles' => ['model' => Branch::class, 'column' => 'branch_profile'],
        ]);
    }

    // Optional: AJAX Store Branch for better UX
    public function storeBranchAjax(Request $request)
    {
        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'google_map_url' => 'required|url|max:500',
            'features' => 'required|string|max:255',
            'open_hour' => 'required',
            'open_minute' => 'required',
            'open_ampm' => 'required|in:AM,PM',
            'close_hour' => 'required',
            'close_minute' => 'required',
            'close_ampm' => 'required|in:AM,PM',
            'open_days' => 'required|string|max:255',
            'branch_profile' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        try {
            $owner = Auth::guard('owner')->user();

            // Convert 12-hour input to 24-hour time for storage
            $open_time = Carbon::createFromFormat('h:i A', "{$validated['open_hour']}:{$validated['open_minute']} {$validated['open_ampm']}")->format('H:i:s');
            $close_time = Carbon::createFromFormat('h:i A', "{$validated['close_hour']}:{$validated['close_minute']} {$validated['close_ampm']}")->format('H:i:s');

            // Handle single image upload
            $branchProfile = null;
            if ($request->hasFile('branch_profile')) {
                $branchProfile = $request->file('branch_profile')->store('branch_profiles', 'public');
            }

            // Auto-geocode
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            if (empty($latitude) || empty($longitude)) {
                $geocodeResult = $this->autoGeocodeAddress($validated['address']);
                if ($geocodeResult['success']) {
                    $latitude = $geocodeResult['latitude'];
                    $longitude = $geocodeResult['longitude'];
                }
            }

            $branch = Branch::create([
                'owner_account_id' => $owner->id,
                'branch_profile' => $branchProfile,
                'branch_name' => $validated['branch_name'],
                'location' => $validated['location'],
                'address' => $validated['address'],
                'google_map_url' => $validated['google_map_url'],
                'features' => $validated['features'],
                'open_time' => $open_time,
                'close_time' => $close_time,
                'open_days' => $validated['open_days'],
                'branch_status' => 1,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'date_created' => now(),
                'active' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Branch created successfully.' . ($latitude && $longitude ? ' Coordinates automatically geocoded.' : ' Please add coordinates manually.'),
                'branch' => $branch
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create branch: ' . $e->getMessage()
            ], 500);
        }
    }

    // Optional: AJAX Update Branch for better UX
    public function updateBranchAjax(Request $request, $branch_uuid)
    {
        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'google_map_url' => 'required|url|max:500',
            'features' => 'required|string|max:255',
            'open_hour' => 'required',
            'open_minute' => 'required',
            'open_ampm' => 'required|in:AM,PM',
            'close_hour' => 'required',
            'close_minute' => 'required',
            'close_ampm' => 'required|in:AM,PM',
            'open_days' => 'required|string|max:255',
            'branch_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        try {
            $branch = Branch::where('uuid', $branch_uuid)
                ->where('owner_account_id', Auth::guard('owner')->id())
                ->firstOrFail();

            $owner = Auth::guard('owner')->user();

            $open_time = Carbon::createFromFormat('h:i A', "{$validated['open_hour']}:{$validated['open_minute']} {$validated['open_ampm']}")->format('H:i:s');
            $close_time = Carbon::createFromFormat('h:i A', "{$validated['close_hour']}:{$validated['close_minute']} {$validated['close_ampm']}")->format('H:i:s');

            // Get current image from the branch
            $currentImage = $branch->branch_profile;

            // Handle new image upload
            if ($request->hasFile('branch_profile')) {
                if ($currentImage && Storage::disk('public')->exists($currentImage)) {
                    Storage::disk('public')->delete($currentImage);
                }
                $imagePath = $request->file('branch_profile')->store('branch_profiles', 'public');
                $currentImage = $imagePath;
            }

            // Auto-geocode if address changed
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            if ($branch->address !== $validated['address'] && (empty($latitude) || empty($longitude))) {
                $geocodeResult = $this->autoGeocodeAddress($validated['address']);
                if ($geocodeResult['success']) {
                    $latitude = $geocodeResult['latitude'];
                    $longitude = $geocodeResult['longitude'];
                } else {
                    $latitude = $branch->latitude;
                    $longitude = $branch->longitude;
                }
            } elseif (empty($latitude) || empty($longitude)) {
                $latitude = $branch->latitude;
                $longitude = $branch->longitude;
            }

            $branch->update([
                'owner_account_id' => $owner->id,
                'branch_profile' => $currentImage,
                'branch_name' => $validated['branch_name'],
                'location' => $validated['location'],
                'address' => $validated['address'],
                'google_map_url' => $validated['google_map_url'],
                'features' => $validated['features'],
                'open_time' => $open_time,
                'close_time' => $close_time,
                'open_days' => $validated['open_days'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'date_updated' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Branch updated successfully.' . ($latitude && $longitude ? ' Coordinates updated.' : ' Please add coordinates manually.'),
                'branch' => $branch->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get Discount Data for Modal
    public function getDiscountData($branch_uuid)
    {
        try {
            \Log::info('Getting discount data for branch: ' . $branch_uuid);
            
            $owner = Auth::guard('owner')->user();
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login again.'
                ], 401);
            }

            $branch = Branch::where('uuid', $branch_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or you do not have permission to manage discounts.'
                ], 404);
            }

            // Get all service categories with their services under this branch
            $categories = ServiceCategory::with(['serviceNames' => function($query) use ($branch) {
                    $query->where('branch_id', $branch->id)
                          ->where('active', 1)
                          ->select('id', 'service_name', 'price', 'old_price', 'discount', 'discount_type', 'service_category_id');
                }])
                ->where('branch_id', $branch->id)
                ->where('active', 1)
                ->get()
                ->map(function ($category) {
                    if (empty($category->category_name) && !empty($category->name)) {
                        $category->category_name = $category->name;
                    }
                    return $category;
                });

            return response()->json([
                'success' => true,
                'branch' => [
                    'uuid' => $branch->uuid,
                    'branch_name' => $branch->branch_name,
                    'id' => $branch->id
                ],
                'categories' => $categories,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getDiscountData: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Apply Discount
    public function applyDiscount(Request $request, $branch_uuid)
    {
        try {
            $validated = $request->validate([
                'discount_type' => 'required|in:amount,percentage',
                'discount_value' => 'required|numeric|min:0',
                'selected_services' => 'required|array',
                'selected_services.*' => 'exists:service_names,id'
            ]);

            $owner = Auth::guard('owner')->user();
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login again.'
                ], 401);
            }

            $branch = Branch::where('uuid', $branch_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or you do not have permission to manage discounts.'
                ], 404);
            }

            // Get all services if "all" is selected
            if (in_array('all', $validated['selected_services'])) {
                $services = ServiceName::where('branch_id', $branch->id)
                    ->where('active', 1)
                    ->get();
            } else {
                $services = ServiceName::whereIn('id', $validated['selected_services'])
                    ->where('branch_id', $branch->id)
                    ->where('active', 1)
                    ->get();
            }

            $updatedCount = 0;
            $errors = [];

            foreach ($services as $service) {
                try {
                    if (!$service->old_price) {
                        $service->old_price = $service->price;
                    }

                    $discount = $validated['discount_value'];
                    
                    if ($validated['discount_type'] === 'percentage') {
                        $discount = min($discount, 100);
                        $discountAmount = ($service->old_price * $discount) / 100;
                        $newPrice = $service->old_price - $discountAmount;
                    } else {
                        $discountAmount = min($discount, $service->old_price);
                        $newPrice = $service->old_price - $discountAmount;
                    }

                    $service->update([
                        'price' => max($newPrice, 0),
                        'discount' => $discount,
                        'discount_type' => $validated['discount_type'],
                        'date_updated' => now()
                    ]);

                    $updatedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to update service '{$service->service_name}': " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Discount applied to {$updatedCount} services successfully.",
                'updated_count' => $updatedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply discount: ' . $e->getMessage()
            ], 500);
        }
    }

    // Remove Discount
    public function removeDiscount(Request $request, $branch_uuid)
    {
        try {
            $validated = $request->validate([
                'selected_services' => 'required|array',
                'selected_services.*' => 'exists:service_names,id'
            ]);

            $owner = Auth::guard('owner')->user();
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login again.'
                ], 401);
            }

            $branch = Branch::where('uuid', $branch_uuid)
                ->where('owner_account_id', $owner->id)
                ->where('active', 1)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or you do not have permission to manage discounts.'
                ], 404);
            }

            if (in_array('all', $validated['selected_services'])) {
                $services = ServiceName::where('branch_id', $branch->id)
                    ->where('active', 1)
                    ->whereNotNull('old_price')
                    ->get();
            } else {
                $services = ServiceName::whereIn('id', $validated['selected_services'])
                    ->where('branch_id', $branch->id)
                    ->where('active', 1)
                    ->whereNotNull('old_price')
                    ->get();
            }

            $updatedCount = 0;

            foreach ($services as $service) {
                $service->update([
                    'price' => $service->old_price,
                    'old_price' => null,
                    'discount' => null,
                    'discount_type' => null,
                    'date_updated' => now()
                ]);
                $updatedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Discount removed from {$updatedCount} services. Original prices restored.",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove discount: ' . $e->getMessage()
            ], 500);
        }
    }
}