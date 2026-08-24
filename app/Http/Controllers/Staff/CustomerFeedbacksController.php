<?php

namespace App\Http\Controllers\Staff;

use App\Models\Feedback;
use App\Models\Branch;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class CustomerFeedbacksController extends Controller
{
    public function index(Request $request)
    {
        // Get the currently authenticated staff member
        $staff = Auth::guard('staff')->user();
        $staffId = $staff->id;
        $branchId = $staff->branch_id; // Staff belongs to a specific branch

        // Get the staff's branch
        $branch = Branch::where('id', $branchId)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->first();

        // Get DISTINCT service categories from feedbacks for this branch
        $serviceCategoriesQuery = ServiceCategory::where('owner_account_id', $staff->owner_account_id)
            ->where('active', 1)
            ->where('service_category_status', 1)
            ->whereHas('feedbacks', function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->where('approved', 1)
                      ->where('active', 1);
            });

        // Get all service categories first
        $allServiceCategories = $serviceCategoriesQuery
            ->select('id', 'service_category')
            ->orderBy('service_category')
            ->get();

        // Filter to get unique service categories (case-insensitive)
        $uniqueServiceCategories = [];
        $seenCategories = [];

        foreach ($allServiceCategories as $category) {
            $normalizedName = strtolower(trim($category->service_category));
            if (!in_array($normalizedName, $seenCategories)) {
                $seenCategories[] = $normalizedName;
                $uniqueServiceCategories[] = $category;
            }
        }

        // Get DISTINCT service names from feedbacks for this branch
        $serviceNamesQuery = ServiceName::where('owner_account_id', $staff->owner_account_id)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->whereHas('feedbacks', function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->where('approved', 1)
                      ->where('active', 1);
            });

        // Get all service names first
        $allServiceNames = $serviceNamesQuery
            ->select('id', 'service_name')
            ->orderBy('service_name')
            ->get();

        // Filter to get unique service names (case-insensitive)
        $uniqueServiceNames = [];
        $seenServiceNames = [];

        foreach ($allServiceNames as $service) {
            $normalizedName = strtolower(trim($service->service_name));
            if (!in_array($normalizedName, $seenServiceNames)) {
                $seenServiceNames[] = $normalizedName;
                $uniqueServiceNames[] = $service;
            }
        }

        // Build query for feedbacks - only show approved feedbacks for this specific branch
        $query = Feedback::with([
            'serviceName' => function ($q) {
                $q->select('id', 'service_name');
            },
            'branch' => function ($q) {
                $q->select('id', 'branch_name');
            },
            'serviceCategory' => function ($q) {
                $q->select('id', 'service_category');
            },
            'customerAccount' => function ($q) {
                $q->select('id', 'first_name', 'last_name');
            }
        ])
        ->where('branch_id', $branchId) // Only show feedbacks for staff's branch
        ->where('approved', 1) // Only show approved feedbacks
        ->where('active', 1)
        ->latest('created_at');

        // Apply filters if provided
        if ($request->filled('service_category_id')) {
            $query->where('service_category_id', (int) $request->service_category_id);
        }

        if ($request->filled('service_name_id')) {
            $query->where('service_name_id', (int) $request->service_name_id);
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('comment', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('customerAccount', function ($q) use ($searchTerm) {
                      $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Paginate results
        $feedbacks = $query->paginate(50);

        // Calculate average rating for this branch
        $branchRating = Feedback::where('branch_id', $branchId)
            ->where('approved', 1)
            ->where('active', 1)
            ->avg('rating') ?? 0;

        // Count 5-star feedbacks for this branch
        $fiveStarCount = Feedback::where('branch_id', $branchId)
            ->where('approved', 1)
            ->where('active', 1)
            ->where('rating', 5)
            ->count();

        // Count total feedbacks for this branch
        $totalFeedbacks = $feedbacks->total();

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $feedbacks->items(),
                'pagination' => [
                    'current_page' => $feedbacks->currentPage(),
                    'last_page' => $feedbacks->lastPage(),
                    'per_page' => $feedbacks->perPage(),
                    'total' => $feedbacks->total(),
                    'from' => $feedbacks->firstItem(),
                    'to' => $feedbacks->lastItem(),
                ],
                'branch' => $branch,
                'service_categories' => $uniqueServiceCategories,
                'service_names' => $uniqueServiceNames,
                'branch_rating' => round($branchRating, 1),
                'five_star_count' => $fiveStarCount,
                'total_feedbacks' => $totalFeedbacks
            ]);
        }

        return view('staff.feedback_n_reviews.feedback_n_reviews', compact(
            'feedbacks',
            'branch',
            'uniqueServiceCategories',
            'uniqueServiceNames',
            'branchRating',
            'fiveStarCount',
            'totalFeedbacks'
        ));
    }
}