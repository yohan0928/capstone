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

        // ── Attach AI-generated summaries ───────────────────────────────
        // The PDF is generated synchronously (no browser round-trip like the
        // report page's AJAX summary buttons), so we call Groq directly here
        // per category, plus once more for the executive/overall summary.
        $byCategory = $reportData['byCategory'] ?? [];
        foreach ($byCategory as &$category) {
            $category['ai_summary'] = $this->generateCategoryAISummary(
                $category,
                $dateFrom,
                $dateTo,
                $branchId
            );
        }
        unset($category);
        $reportData['byCategory'] = $byCategory;

        $byBranch       = $reportData['byBranch'] ?? [];
        $totalFeedbacks = (int) collect($byBranch)->sum('total');
        $totalRatingSum = collect($byBranch)->sum(
            fn($b) => ($b['avg_rating'] ?? 0) * ($b['total'] ?? 0)
        );
        $overallAvg = $totalFeedbacks > 0 ? round($totalRatingSum / $totalFeedbacks, 1) : 0;

        $overallSummary = $this->generateOverallAISummaryForPdf(
            $byCategory,
            $overallAvg,
            $totalFeedbacks,
            $dateFrom,
            $dateTo,
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
                'by_branch' => $byBranch,
                'by_category' => $byCategory,
            ],
            'overall_summary' => $overallSummary,
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
        $pdf->setPaper('A4', 'landscape');

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
        // Group by NORMALIZED name (case-insensitive, trimmed) rather than
        // raw service_category_id. Owners can end up with duplicate
        // ServiceCategory rows that share the same display name (the same
        // issue index() already works around for the filter dropdown) — if
        // left grouped by id, two groups can surface with an identical
        // `category_name`, which the Blade view uses as an Alpine `:key`.
        // A duplicate key breaks that x-for entirely (nothing renders, and
        // no placeholder shows either, since the array isn't actually
        // empty) — so this dedupe is required, not just cosmetic.
        $byCategory = $feedbacks
            ->groupBy(function ($feedback) {
                $name = $feedback->serviceCategory?->service_category ?? 'N/A';
                return strtolower(trim($name));
            })
            ->map(function ($group) {
                $category    = $group->first()->serviceCategory;
                $displayName = $category?->service_category ?? 'N/A';

                return [
                    'id'                => $group->first()->service_category_id,
                    'category_name'     => $displayName,
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
    // AI SUMMARY FRAMING HELPERS (Shared by per-category & overall summaries)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a note describing how the AI should frame the given date range.
     * Short ranges get "recent snapshot" framing, ranges over a month get
     * "trend over months" framing, and ranges over a year get "long-term /
     * seasonal trend" framing.
     */
    private function buildTimeframeNote(Carbon $dateFrom, Carbon $dateTo): string
    {
        $days = $dateFrom->diffInDays($dateTo) + 1;

        if ($days > 365) {
            $years = round($days / 365, 1);
            return "This period spans approximately {$years} year(s) ({$dateFrom->format('M d, Y')} to {$dateTo->format('M d, Y')}). "
                . "Frame the summary around long-term or seasonal trends and sustained patterns rather than isolated incidents, "
                . "and explicitly acknowledge that this reflects a multi-year view.";
        }

        if ($days > 31) {
            $months = round($days / 30);
            return "This period spans approximately {$months} month(s) ({$dateFrom->format('M d, Y')} to {$dateTo->format('M d, Y')}). "
                . "Frame the summary around trends that developed or shifted across these months, "
                . "and explicitly acknowledge that this reflects a multi-month view rather than a single day.";
        }

        return "This period spans {$days} day(s) ({$dateFrom->format('M d, Y')} to {$dateTo->format('M d, Y')}). "
            . "Frame the summary as a short-term, recent snapshot of customer sentiment.";
    }

    /**
     * Pick the correct scope note (all-branches vs single-branch) depending
     * on whether a specific branch is currently selected.
     */
    private function buildScopeNote(?int $branchId, string $allBranchesNote, string $singleBranchNote): string
    {
        return $branchId ? $singleBranchNote : $allBranchesNote;
    }

    /**
     * Normalize/validate an incoming date range from a request, defaulting
     * to the last 7 days and guarding against a reversed range.
     *
     * @return array{0: Carbon, 1: Carbon} [$dateFrom, $dateTo]
     */
    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [$dateFrom, $dateTo];
    }

    /**
     * Call the Groq chat completions endpoint with a prepared prompt and
     * return the trimmed summary text. Shared by the AJAX summary endpoints
     * and the PDF export, which generates summaries synchronously at
     * download time. On failure, returns a short human-readable fallback
     * string rather than throwing, so a single bad category summary never
     * breaks the whole report/PDF.
     */
    private function callGroqApiSummary(string $prompt, int $maxTokens, string $logContext = ''): string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'      => 'llama-3.1-8b-instant',
                    'max_tokens' => $maxTokens,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->failed()) {
                throw new \Exception('Groq API error: ' . $response->status() . ' - ' . $response->body());
            }

            $data = $response->json();

            return trim($data['choices'][0]['message']['content'] ?? 'Unable to generate summary.');

        } catch (\Exception $e) {
            Log::error('AI Summary generation failed' . ($logContext ? " [{$logContext}]" : '') . ': ' . $e->getMessage());
            return 'AI summary unavailable for this section.';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AI FEEDBACK SUMMARY (Groq API) — PER SERVICE CATEGORY
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate an AI-powered summary of feedback comments for a service category.
     * Called via AJAX POST from the report page.
     *
     * Now timeframe- and branch-scope-aware: when "All Branches" is selected,
     * the prompt explicitly tells the model to synthesize across all branches
     * for this category, and the date range is framed the same way as the
     * overall summary (short-term snapshot / multi-month / multi-year).
     */
    public function generateAISummary(Request $request)
    {
        $request->validate([
            'comments'      => 'required|array|min:1',
            'comments.*'    => 'string',
            'context'       => 'required|string|max:255',
            'avg_rating'    => 'nullable|numeric|min:1|max:5',
            'total'         => 'nullable|integer|min:0',
            'date_from'     => 'nullable|date',
            'date_to'       => 'nullable|date',
            'branch_id'     => 'nullable|integer',
        ]);

        $owner   = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        // Security: make sure this owner actually owns the branch being
        // referenced. The comments themselves are already scoped by
        // buildReportData(), but branch_id comes from the client, so we
        // re-validate ownership here (same pattern as generateOverallSummary).
        $ownerBranchIds = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->pluck('id')
            ->toArray();

        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        if ($branchId && !in_array($branchId, $ownerBranchIds)) {
            $branchId = null;
        }

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

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

        $spanNote  = $this->buildTimeframeNote($dateFrom, $dateTo);
        $scopeNote = $this->buildScopeNote(
            $branchId,
            "This summary must synthesize feedback for the \"{$context}\" service category across ALL branches of the business — do not focus on any single branch.",
            "This summary reflects feedback for the \"{$context}\" service category at the currently selected branch only."
        );

        $prompt = <<<PROMPT
You are analyzing customer feedback for a study hub and co-working space business.

Service Category : {$context}
{$scopeNote}
{$spanNote}
Average Rating    : {$avgRating} / 5.0
Total Feedbacks   : {$total}

Customer comments:
{$commentList}

Write a concise 2–3 sentence summary of the overall customer experience for this service category.
- Highlight the most commonly praised aspects.
- Mention any recurring complaints or suggestions if they exist.
- Naturally acknowledge the timeframe and branch scope covered, per the framing instructions above.
- Keep the tone professional and factual.
- Write as a single plain paragraph — no bullet points, no numbering.
PROMPT;

        $summary = $this->callGroqApiSummary($prompt, 300, $context);

        return response()->json(['summary' => $summary]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AI FEEDBACK SUMMARY (Groq API) — OVERALL / ALL BRANCHES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate an AI-powered executive summary across ALL service categories
     * (and, when no single branch is selected, across ALL branches) for the
     * currently applied date range.
     *
     * The prompt is adapted based on how long the selected date range is —
     * short ranges get "recent snapshot" framing, ranges over a month get
     * "trend over months" framing, and ranges over a year get "long-term /
     * seasonal trend" framing — so the generated text actually reflects the
     * timeframe being reported on, not just the raw comments.
     *
     * Called via AJAX POST from the report page when the owner has "All
     * Branches" selected (or explicitly requests the overall summary).
     */
    public function generateOverallSummary(Request $request)
    {
        $request->validate([
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date',
            'branch_id'    => 'nullable|integer',
        ]);

        $owner   = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $branches = Branch::where('owner_account_id', $ownerId)
            ->where('active', 1)
            ->where('branch_status', 1)
            ->pluck('id')
            ->toArray();

        // Validate the requested branch actually belongs to this owner
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        if ($branchId && !in_array($branchId, $branches)) {
            $branchId = null;
        }

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        // Query feedback directly — independent of the by-category grouping,
        // so this still works even if some feedback rows have no
        // service_category_id (older records, data-quality gaps, etc.)
        $feedbackQuery = Feedback::whereIn('branch_id', $branches)
            ->where('approved', 1)
            ->where('active', 1)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($branchId) {
            $feedbackQuery->where('branch_id', $branchId);
        }

        $feedbacks = $feedbackQuery->get(['rating', 'comment']);

        $total     = $feedbacks->count();
        $avgRating = $total ? round($feedbacks->avg('rating'), 1) : '—';

        $comments = $feedbacks
            ->pluck('comment')
            ->filter(fn($c) => !empty(trim($c ?? '')))
            ->values();

        if ($total === 0) {
            return response()->json([
                'summary' => 'No feedback is available for the selected date range and branch scope.',
            ]);
        }

        if ($comments->isEmpty()) {
            return response()->json([
                'summary' => "No written comments are available for this period ({$total} rating".($total === 1 ? '' : 's')." with no accompanying text).",
            ]);
        }

        // ── Determine how the AI should frame the timeframe / scope ────────
        $spanNote  = $this->buildTimeframeNote($dateFrom, $dateTo);
        $scopeNote = $this->buildScopeNote(
            $branchId,
            'This summary must synthesize feedback across ALL branches of the business as a whole — do not focus on any single branch.',
            'This summary reflects feedback for the currently selected branch only.'
        );

        // Cap at 80 comments to keep the prompt within a safe token range
        $commentList = $comments
            ->take(80)
            ->map(fn($c, $i) => ($i + 1) . '. ' . $c)
            ->join("\n");

        $prompt = <<<PROMPT
You are writing an executive summary of overall customer feedback for a study hub and co-working space business.

{$scopeNote}
{$spanNote}

Average Rating   : {$avgRating} / 5.0
Total Feedbacks  : {$total}

Customer comments (aggregated across all service categories):
{$commentList}

Write a concise 3–4 sentence executive summary of the overall customer experience.
- Highlight the most commonly praised aspects across the business.
- Mention any recurring complaints or suggestions if they exist.
- Naturally acknowledge the timeframe covered, per the framing instructions above.
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
                    'max_tokens' => 350,
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
            Log::error('Overall AI Summary generation failed: ' . $e->getMessage());
            return response()->json(
                ['summary' => 'Failed to generate summary. Please try again later.'],
                500
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AI SUMMARY GENERATION FOR PDF EXPORT (synchronous, server-side)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate an AI summary for a single category's comments, using the
     * same prompt framing as generateAISummary(), but built directly from
     * already-aggregated data (from buildReportData()) instead of an
     * incoming HTTP request. Used exclusively by exportFeedbackPdf() since
     * the PDF is rendered synchronously and can't rely on the report page's
     * client-side AJAX summary buttons.
     */
    private function generateCategoryAISummary(
        array $category,
        Carbon $dateFrom,
        Carbon $dateTo,
        ?int $branchId
    ): string {
        $comments = $category['comments'] ?? [];

        if (empty($comments)) {
            return 'No written comments are available for this service category.';
        }

        $context   = $category['category_name'] ?? 'Unknown';
        $avgRating = $category['avg_rating'] ?? '—';
        $total     = $category['total'] ?? count($comments);

        $commentList = collect($comments)
            ->take(50)
            ->values()
            ->map(fn($c, $i) => ($i + 1) . '. ' . $c)
            ->join("\n");

        $spanNote  = $this->buildTimeframeNote($dateFrom, $dateTo);
        $scopeNote = $this->buildScopeNote(
            $branchId,
            "This summary must synthesize feedback for the \"{$context}\" service category across ALL branches of the business — do not focus on any single branch.",
            "This summary reflects feedback for the \"{$context}\" service category at the currently selected branch only."
        );

        $prompt = <<<PROMPT
You are analyzing customer feedback for a study hub and co-working space business.

Service Category : {$context}
{$scopeNote}
{$spanNote}
Average Rating    : {$avgRating} / 5.0
Total Feedbacks   : {$total}

Customer comments:
{$commentList}

Write a concise 2–3 sentence summary of the overall customer experience for this service category.
- Highlight the most commonly praised aspects.
- Mention any recurring complaints or suggestions if they exist.
- Naturally acknowledge the timeframe and branch scope covered, per the framing instructions above.
- Keep the tone professional and factual.
- Write as a single plain paragraph — no bullet points, no numbering.
PROMPT;

        return $this->callGroqApiSummary($prompt, 300, $context);
    }

    /**
     * Generate the executive/overall AI summary for the PDF, built from the
     * already-aggregated by-category data rather than issuing a fresh
     * feedback query. Mirrors generateOverallSummary()'s prompt, but is
     * synchronous and self-contained for use inside exportFeedbackPdf().
     */
    private function generateOverallAISummaryForPdf(
        array $byCategory,
        float|int $overallAvg,
        int $totalFeedbacks,
        Carbon $dateFrom,
        Carbon $dateTo,
        ?int $branchId
    ): string {
        if ($totalFeedbacks === 0) {
            return 'No feedback is available for the selected date range and branch scope.';
        }

        $allComments = collect($byCategory)
            ->flatMap(fn($cat) => $cat['comments'] ?? [])
            ->filter()
            ->values();

        if ($allComments->isEmpty()) {
            return "No written comments are available for this period ({$totalFeedbacks} rating"
                . ($totalFeedbacks === 1 ? '' : 's') . " with no accompanying text).";
        }

        $spanNote  = $this->buildTimeframeNote($dateFrom, $dateTo);
        $scopeNote = $this->buildScopeNote(
            $branchId,
            'This summary must synthesize feedback across ALL branches of the business as a whole — do not focus on any single branch.',
            'This summary reflects feedback for the currently selected branch only.'
        );

        $commentList = $allComments
            ->take(80)
            ->values()
            ->map(fn($c, $i) => ($i + 1) . '. ' . $c)
            ->join("\n");

        $prompt = <<<PROMPT
You are writing an executive summary of overall customer feedback for a study hub and co-working space business.

{$scopeNote}
{$spanNote}

Average Rating   : {$overallAvg} / 5.0
Total Feedbacks  : {$totalFeedbacks}

Customer comments (aggregated across all service categories):
{$commentList}

Write a concise 3–4 sentence executive summary of the overall customer experience.
- Highlight the most commonly praised aspects across the business.
- Mention any recurring complaints or suggestions if they exist.
- Naturally acknowledge the timeframe covered, per the framing instructions above.
- Keep the tone professional and factual.
- Write as a single plain paragraph — no bullet points, no numbering.
PROMPT;

        return $this->callGroqApiSummary($prompt, 350, 'overall-pdf');
    }
}