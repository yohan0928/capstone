<?php

namespace App\Http\Controllers\Owner;

use App\Models\Feedback;
use App\Models\Branch;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CustomerFeedbacksController extends Controller
{
    public function index(Request $request)
    {
        // Get the currently authenticated owner
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get owner's branches for filter dropdown
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->select('id', 'branch_name')
            ->orderBy('branch_name')
            ->get();

        // Get branch IDs owned by this owner
        $branchIds = $branches->pluck('id')->toArray();

        // Get DISTINCT service categories from feedbacks using raw query to avoid duplicates
        $serviceCategoriesQuery = ServiceCategory::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('service_category_status', 1)
            ->whereHas('feedbacks', function($query) use ($branchIds) {
                $query->whereIn('branch_id', $branchIds)
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

        // Get DISTINCT service names from feedbacks
        $serviceNamesQuery = ServiceName::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('service_name_status', 1)
            ->whereHas('feedbacks', function($query) use ($branchIds) {
                $query->whereIn('branch_id', $branchIds)
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

        // Build query for feedbacks - only show approved feedbacks from owner's branches
        $query = Feedback::with([
            'serviceName' => function ($q) {
                $q->select('id', 'service_name');
            },
            'branch' => function ($q) {
                $q->select('id', 'branch_name');
            },
            'serviceCategory' => function ($q) {
                $q->select('id', 'service_category');
            }
        ])
        ->whereIn('branch_id', $branchIds) // Filter by owner's branches
        ->where('approved', 1) // Only show approved feedbacks
        ->where('active', 1)
        ->latest('created_at');

        // Apply filters if provided
        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;
            if (in_array($branchId, $branchIds)) {
                $query->where('branch_id', $branchId);
            }
        }

        if ($request->filled('service_category_id')) {
            $query->where('service_category_id', (int) $request->service_category_id);
        }

        if ($request->filled('service_name_id')) {
            $query->where('service_name_id', (int) $request->service_name_id);
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            // Guard: ignore date_to entirely if it is before date_from
            if (!$request->filled('date_from') || $request->date_to >= $request->date_from) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
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

        // Calculate overall average rating — mirrors the same filters so it stays in sync
        $ratingQuery = Feedback::whereIn('branch_id', $branchIds)
            ->where('approved', 1)
            ->where('active', 1);
        
        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;
            if (in_array($branchId, $branchIds)) {
                $ratingQuery->where('branch_id', $branchId);
            }
        }
        if ($request->filled('service_category_id')) {
            $ratingQuery->where('service_category_id', (int) $request->service_category_id);
        }
        if ($request->filled('service_name_id')) {
            $ratingQuery->where('service_name_id', (int) $request->service_name_id);
        }
        if ($request->filled('rating')) {
            $ratingQuery->where('rating', (int) $request->rating);
        }
        if ($request->filled('date_from')) {
            $ratingQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $ratingQuery->whereDate('created_at', '<=', $request->date_to);
        }
        
        $overallRating = $ratingQuery->avg('rating') ?? 0;
        
        // Count total feedbacks
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
                'branches' => $branches,
                'service_categories' => $uniqueServiceCategories,
                'service_names' => $uniqueServiceNames,
                'overall_rating' => round($overallRating, 1),
                'total_feedbacks' => $totalFeedbacks
            ]);
        }

        return view('owner.feedback_n_reviews.feedback_n_reviews', compact(
            'feedbacks',
            'branches',
            'uniqueServiceCategories',
            'uniqueServiceNames',
            'overallRating',
            'totalFeedbacks'
        ));
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // PDF EXPORT
    // ─────────────────────────────────────────────────────────────────────────
    
    public function exportFeedbackPdf(Request $request)
{
    $owner = Auth::guard('owner')->user();
    $ownerId = $owner->id;

    // Get owner's branches
    $branches = Branch::where('owner_account_id', $ownerId)
        ->where('active', 1)
        ->where('branch_status', 1)
        ->select('id', 'branch_name')
        ->orderBy('branch_name')
        ->get();

    $branchIds = $branches->pluck('id')->toArray();

    // Parse dates
    $dateFrom = $request->date_from
        ? Carbon::parse($request->date_from)->startOfDay()
        : Carbon::now()->subDays(6)->startOfDay();

    $dateTo = $request->date_to
        ? Carbon::parse($request->date_to)->endOfDay()
        : Carbon::now()->endOfDay();

    $branchId = $request->branch_id ?: null;

    // Validate branch belongs to owner
    if ($branchId && !in_array((int)$branchId, $branchIds)) {
        $branchId = null;
    }

    // Get the report data using buildReportData
    $reportData = $this->buildReportData(
        $branchIds,
        $dateFrom->format('Y-m-d'),
        $dateTo->format('Y-m-d'),
        $branchId
    );
    
    $branch = null;
    if ($branchId) {
        $branch = Branch::find($branchId);
    }

    // Prepare data for PDF view - ensure correct structure
    $data = [
        'date_from' => $dateFrom->format('M d, Y'),
        'date_to' => $dateTo->format('M d, Y'),
        'branch' => $branch,
        'data' => [
            'by_branch' => $reportData['byBranch'] ?? [],
            'by_category' => $reportData['byCategory'] ?? [],
        ],
        'generated_at' => now()->format('M d, Y h:i A'),
        'company_name' => 'Linkud Hub',
        'generated_by' => $owner ? $owner->first_name . ' ' . $owner->last_name : 'System',
        'generated_by_email' => $owner ? $owner->email : 'system@linkudhub.com',
    ];

    // Debug: Log the data to check if it's being passed correctly
    Log::info('Feedback PDF Data:', [
        'by_branch_count' => count($data['data']['by_branch']),
        'by_category_count' => count($data['data']['by_category']),
        'date_from' => $data['date_from'],
        'date_to' => $data['date_to'],
    ]);

    $pdf = Pdf::loadView('owner.reports.pdf.feedback_report', $data);
    $pdf->setPaper('A4', 'portrait');
    
    $filename = 'feedback_report_' . date('Y-m-d_His') . '.pdf';
    return $pdf->download($filename);
}

    // ─────────────────────────────────────────────────────────────────────────
    // FEEDBACK SUMMARY REPORT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show the feedback summary report page.
     */
    public function report(Request $request)
    {
        $owner    = Auth::guard('owner')->user();
        $ownerId  = $owner->id;

        // Only owner's active branches
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->select('id', 'branch_name')
            ->orderBy('branch_name')
            ->get();

        $branchIds = $branches->pluck('id')->toArray();

        // Default date range: last 7 days
        $dateFrom = $request->date_from ?? now()->subDays(7)->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

        // Security: make sure the requested branch belongs to this owner
        if ($branchId && !in_array($branchId, $branchIds)) {
            $branchId = null;
        }

        // AJAX call → return JSON only
        if ($request->ajax() || $request->wantsJson()) {
            $data = $this->buildReportData($branchIds, $dateFrom, $dateTo, $branchId);

            return response()->json([
                'success'     => true,
                'by_branch'   => $data['byBranch'],
                'by_category' => $data['byCategory'],
            ]);
        }
        
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // Initial page load → pass data to view
        $data = $this->buildReportData($branchIds, $dateFrom, $dateTo, $branchId);

        return view('owner.reports.feedback_report', [
            'branches'    => $branches,
            'byBranch'    => $data['byBranch'],
            'byCategory'  => $data['byCategory'],
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
            'branchId'    => $branchId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET FEEDBACK REPORT DATA (For PDF Export)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get feedback report data for PDF export
     * This is a wrapper around buildReportData that ensures consistent data structure
     */
    private function getFeedbackReportData($dateFrom, $dateTo, $branchId = null)
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Get owner's branches
        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->select('id', 'branch_name')
            ->orderBy('branch_name')
            ->get();

        $branchIds = $branches->pluck('id')->toArray();

        // If a specific branch is requested, validate it belongs to the owner
        if ($branchId && !in_array($branchId, $branchIds)) {
            $branchId = null;
        }

        // Build the report data using the existing method
        return $this->buildReportData(
            $branchIds,
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d'),
            $branchId
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BUILD REPORT DATA (Reusable)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the by-branch and by-category aggregated arrays.
     * Reused by both the initial page load and AJAX refresh.
     */
    private function buildReportData(
        array   $branchIds,
        string  $dateFrom,
        string  $dateTo,
        ?int    $branchId
    ): array {
        $query = Feedback::with([
            'branch'          => fn($q) => $q->select('id', 'branch_name'),
            'serviceCategory' => fn($q) => $q->select('id', 'service_category'),
        ])
        ->whereIn('branch_id', $branchIds)
        ->where('approved', 1)
        ->where('active', 1)
        ->whereBetween('created_at', [
            $dateFrom . ' 00:00:00',
            $dateTo   . ' 23:59:59',
        ]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $feedbacks = $query->get();

        // ── By Branch ──────────────────────────────────────────────────────
        $byBranch = $feedbacks
            ->groupBy('branch_id')
            ->map(function ($group) {
                $branch = $group->first()->branch;

                return [
                    'branch_name'       => $branch?->branch_name ?? 'N/A',
                    'avg_rating'        => round($group->avg('rating'), 1),
                    'total'             => $group->count(),
                    'star_distribution' => [
                        5 => $group->where('rating', 5)->count(),
                        4 => $group->where('rating', 4)->count(),
                        3 => $group->where('rating', 3)->count(),
                        2 => $group->where('rating', 2)->count(),
                        1 => $group->where('rating', 1)->count(),
                    ],
                ];
            })
            ->values()
            ->toArray();

        // ── By Service Category ────────────────────────────────────────────
        $byCategory = $feedbacks
            ->groupBy('service_category_id')
            ->map(function ($group) {
                $category = $group->first()->serviceCategory;

                return [
                    'id'                => $group->first()->service_category_id,
                    'category_name'     => $category?->service_category ?? 'N/A',
                    'avg_rating'        => round($group->avg('rating'), 1),
                    'total'             => $group->count(),
                    'star_distribution' => [
                        5 => $group->where('rating', 5)->count(),
                        4 => $group->where('rating', 4)->count(),
                        3 => $group->where('rating', 3)->count(),
                        2 => $group->where('rating', 2)->count(),
                        1 => $group->where('rating', 1)->count(),
                    ],
                    // Non-empty comments only (used for AI summary)
                    'comments' => $group
                        ->pluck('comment')
                        ->filter(fn($c) => !empty(trim($c ?? '')))
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return compact('byBranch', 'byCategory');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AI FEEDBACK SUMMARY (Groq API)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate an AI-powered summary of feedback comments for a service category.
     * Called via AJAX POST from the report page.
     */
    public function generateAISummary(Request $request)
    {
        $request->validate([
            'comments'      => 'required|array|min:1',
            'comments.*'    => 'string',
            'context'       => 'required|string|max:255',
            'avg_rating'    => 'nullable|numeric|min:1|max:5',
            'total'         => 'nullable|integer|min:0',
        ]);

        // Security: make sure this owner actually owns the branch(es) the
        // comments came from. Since we already filtered by branchIds in
        // buildReportData, the comments array is already scoped. No extra
        // check needed here — but we do re-validate ownership on report().

        $comments  = collect($request->comments)->filter()->values();
        $context   = $request->context;
        $avgRating = $request->avg_rating ?? '—';
        $total     = $request->total ?? $comments->count();

        if ($comments->isEmpty()) {
            return response()->json([
                'summary' => 'No written comments are available for this service category.',
            ]);
        }

        // Cap at 50 comments so the prompt stays within a safe token range
        $commentList = $comments
            ->take(50)
            ->map(fn($c, $i) => ($i + 1) . '. ' . $c)
            ->join("\n");

        $prompt = <<<PROMPT
You are analyzing customer feedback for a study hub and co-working space business.

Service Category : {$context}
Average Rating   : {$avgRating} / 5.0
Total Feedbacks  : {$total}

Customer comments:
{$commentList}

Write a concise 2–3 sentence summary of the overall customer experience for this service category.
- Highlight the most commonly praised aspects.
- Mention any recurring complaints or suggestions if they exist.
- Keep the tone professional and factual.
- Write as a single plain paragraph — no bullet points, no numbering.
PROMPT;

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'      => 'llama-3.1-8b-instant',
                    'max_tokens' => 300,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        
            if ($response->failed()) {
                throw new \Exception('Groq API error: ' . $response->status() . ' - ' . $response->body());
            }
        
            $data    = $response->json();
            $summary = $data['choices'][0]['message']['content'] ?? 'Unable to generate summary.';
        
            return response()->json(['summary' => trim($summary)]);
        
        } catch (\Exception $e) {
            Log::error('AI Summary generation failed: ' . $e->getMessage());
            return response()->json(
                ['summary' => 'Failed to generate summary. Please try again later.'],
                500
            );
        }
    }
}