<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ratings & Feedback Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #7F5539;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 22px;
            color: #7F5539;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 12px;
            color: #666;
        }
        .header .date-range {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
        }
        .header .generated-by {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px dashed #e5e7eb;
            display: inline-block;
            padding-left: 15px;
            padding-right: 15px;
        }
        .header .generated-by strong {
            color: #7F5539;
        }
        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .summary-cards .card {
            display: table-cell;
            text-align: center;
            padding: 10px;
            background: #f8f7f5;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .summary-cards .card .label {
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .summary-cards .card .value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 3px;
        }
        .summary-cards .card .value.primary { color: #7F5539; }
        .summary-cards .card .value.blue { color: #2563eb; }
        .summary-cards .card .value.green { color: #16a34a; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }
        table th {
            background: #7F5539;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 6px;
            text-align: left;
        }
        table td {
            padding: 6px 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        table tr:nth-child(even) {
            background: #faf9f7;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #999;
            text-align: center;
        }
        .footer .generated-info {
            font-size: 8px;
            color: #666;
            margin-top: 3px;
        }
        .branch-name {
            font-weight: bold;
            color: #7F5539;
        }
        .text-muted {
            color: #999;
        }
        .watermark {
            position: fixed;
            bottom: 50px;
            right: 50px;
            opacity: 0.05;
            font-size: 60px;
            font-weight: bold;
            color: #7F5539;
            transform: rotate(-20deg);
            pointer-events: none;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
        }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f3f4f6; color: #4b5563; }

        /*
         * Rating rows are built with display:table / table-cell rather than
         * flexbox. DomPDF's flexbox support is partial and does not honor
         * `gap` at all, so flex-based rows can render with overlapping or
         * missing spacing. Table-cell layout is fully supported and matches
         * what .summary-cards above already relies on.
         */
        .star-row {
            display: table;
            width: 100%;
            margin: 1px 0;
        }
        .star-row .star-label {
            display: table-cell;
            font-size: 8px;
            color: #666;
            width: 14px;
            vertical-align: middle;
        }
        .star-row .bar-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .star-row .bar-bg {
            background: #e5e7eb;
            border-radius: 3px;
            height: 6px;
            overflow: hidden;
        }
        .star-row .bar-fill {
            height: 100%;
            background: #fbbf24;
            border-radius: 3px;
        }
        .star-row .count {
            display: table-cell;
            font-size: 8px;
            color: #999;
            width: 22px;
            text-align: right;
            vertical-align: middle;
        }

        .rating-inline .stars {
            display: inline-block;
            vertical-align: middle;
        }
        .rating-inline .stars span {
            font-size: 12px;
        }
        .rating-inline .value {
            display: inline-block;
            vertical-align: middle;
            font-weight: bold;
            margin-left: 6px;
        }

        /* ── Category card (mirrors the on-screen report's card design) ── */
        .category-block {
            margin-top: 16px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #ffffff;
        }

        /* Header row: small tag icon + title/subtitle stack. Built with
           float rather than flexbox (see .star-row note above re: DomPDF's
           partial flex/gap support) and cleared via ::after. */
        .cat-header {
            margin-bottom: 10px;
        }
        .cat-header::after {
            content: "";
            display: block;
            clear: both;
        }
        .cat-icon {
            float: left;
            width: 28px;
            height: 28px;
            background: #f3ede7;
            border-radius: 6px;
            position: relative;
        }
        /*
         * Category icons are built from plain positioned <div>/<span>
         * boxes with a background or border color, NOT inline <svg>.
         * DomPDF's inline SVG support is inconsistent across installs
         * (some builds render nothing at all), whereas absolute
         * positioning + background-color is the same technique already
         * used successfully by .bar-fill above, so this is guaranteed to
         * render the same way in every DomPDF environment.
         */
        .icon-shape {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 16px;
            height: 16px;
        }
        .icon-shape span {
            position: absolute;
            display: block;
            background: #7F5539;
        }
        .icon-shape span.outline {
            background: none;
            border: 1.2px solid #7F5539;
        }
        .icon-shape span.circle {
            background: none;
            border: 1.2px solid #7F5539;
            border-radius: 50%;
        }
        .icon-fallback {
            position: absolute;
            top: 0;
            left: 0;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            font-size: 13px;
            color: #7F5539;
        }
        .cat-header-text {
            margin-left: 36px;
        }
        .cat-title {
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
        }
        .cat-subtitle {
            font-size: 9px;
            color: #9ca3af;
            margin-top: 1px;
        }

        .cat-rating-row {
            margin-bottom: 8px;
        }
        .cat-rating-row .stars span {
            font-size: 14px;
        }
        .cat-rating-row .value {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
            margin-left: 4px;
        }
        .cat-rating-row .reviews {
            font-size: 9px;
            color: #9ca3af;
            margin-left: 4px;
        }

        /*
         * Two-column layout using a REAL <table> element (not CSS
         * display:table/table-cell, and not float). DomPDF's float support
         * silently stacks adjacent floated divs when there's whitespace
         * between them in the markup (and interacts poorly with
         * page-break-inside:avoid on an ancestor), so a genuine <table> is
         * the most reliable way to guarantee true side-by-side columns.
         * table-layout:fixed locks each column to exactly 50% regardless
         * of content height/length on either side.
         */
        .category-block .cat-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .category-block .cat-table td {
            padding: 0;
            border: none;
            vertical-align: top;
            width: 50%;
        }
        .category-block .cat-table td.cat-col-left {
            padding-right: 12px;
        }
        .category-block .cat-table td.cat-col-right {
            padding-left: 12px;
        }

        /* AI summary box on the right: its own bordered card with a header
           strip, matching the on-screen report's "AI Feedback Summary"
           panel (minus the Regenerate button, since the PDF is static). */
        .ai-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .ai-box-header {
            background: #f8f7f5;
            padding: 6px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .ai-box-header .label {
            font-size: 9px;
            font-weight: bold;
            color: #444;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .ai-box-content {
            padding: 10px;
            font-size: 10px;
            color: #444;
            line-height: 1.5;
        }
        .no-comments {
            font-size: 9px;
            color: #b45309;
            font-style: italic;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }
        
        @media print {
            body { padding: 14px; }
            .category-block { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="watermark">LINKUD HUB</div>

<div class="header">
    <h1>Ratings &amp; Feedback Report</h1>
    <div class="subtitle">{{ $company_name ?? 'Linkud Hub' }}</div>
    <div class="date-range">
        Period: {{ $date_from }} - {{ $date_to }}
        @if($branch)
            | Branch: {{ $branch->branch_name }}
        @else
            | All Branches
        @endif
    </div>
    <div class="generated-by">
        <strong>Generated By:</strong> {{ $generated_by ?? 'System' }} 
        <span style="color: #999; font-weight: normal;">|</span> 
        <strong>Email:</strong> {{ $generated_by_email ?? 'system@linkudhub.com' }}
    </div>
    <div class="date-range" style="font-size: 9px; color: #aaa; margin-top: 2px;">
        Generated: {{ $generated_at }}
    </div>
</div>

{{-- Summary Cards --}}
@php
    // NOTE: $byCategory here is already deduped/merged by name in
    // buildReportData() (owners can otherwise end up with multiple
    // ServiceCategory rows sharing the same display name). No additional
    // grouping is needed in this template — each entry below is unique.
    $byBranch = $data['by_branch'] ?? [];
    $byCategory = $data['by_category'] ?? [];
    $totalFeedbacks = 0;
    $totalRatingSum = 0;
    $branchCount = count($byBranch);
    
    foreach ($byBranch as $branch) {
        $totalFeedbacks += $branch['total'] ?? 0;
        $totalRatingSum += ($branch['avg_rating'] ?? 0) * ($branch['total'] ?? 0);
    }
    $overallAvg = $totalFeedbacks > 0 ? round($totalRatingSum / $totalFeedbacks, 1) : 0;
@endphp

<div class="summary-cards">
    <div class="card">
        <div class="label">Overall Avg Rating</div>
        <div class="value primary">{{ number_format($overallAvg, 1) }} / 5.0</div>
    </div>
    <div class="card">
        <div class="label">Total Feedbacks</div>
        <div class="value blue">{{ $totalFeedbacks }}</div>
    </div>
    <div class="card">
        <div class="label">Branches Covered</div>
        <div class="value green">{{ $branchCount }}</div>
    </div>
</div>

{{-- Executive / Overall AI Summary --}}
@if(!empty($overall_summary))
    <div style="margin-bottom: 20px;">
        <h2 style="font-size: 14px; color: #7F5539; margin-bottom: 8px; border-bottom: 2px solid #7F5539; padding-bottom: 4px;">
            Executive Summary
        </h2>
        <div class="ai-box">
            <div class="ai-box-header"><span class="label">AI Feedback Summary</span></div>
            <div class="ai-box-content">{{ $overall_summary }}</div>
        </div>
    </div>
@endif

{{-- By Branch Table --}}
@if(count($byBranch) > 0)
    <div style="margin-bottom: 20px;">
        <h2 style="font-size: 14px; color: #7F5539; margin-bottom: 8px; border-bottom: 2px solid #7F5539; padding-bottom: 4px;">
            Rating Summary by Branch
        </h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Branch</th>
                    <th style="width: 20%;">Avg Rating</th>
                    <th style="width: 15%;">Total Feedbacks</th>
                    <th style="width: 40%;">Star Distribution</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byBranch as $branch)
                    <tr>
                        <td><span class="branch-name">{{ $branch['branch_name'] ?? 'Unknown' }}</span></td>
                        <td>
                            <div class="rating-inline">
                                <span class="stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span style="color: {{ $i <= round($branch['avg_rating'] ?? 0) ? '#fbbf24' : '#d1d5db' }};">★</span>
                                    @endfor
                                </span>
                                <span class="value">{{ number_format($branch['avg_rating'] ?? 0, 1) }}</span>
                            </div>
                        </td>
                        <td><span class="badge badge-blue">{{ $branch['total'] ?? 0 }} reviews</span></td>
                        <td>
                            @php
                                $starDist = $branch['star_distribution'] ?? [5=>0,4=>0,3=>0,2=>0,1=>0];
                                $total = $branch['total'] ?? 1;
                            @endphp
                            @foreach([5,4,3,2,1] as $star)
                                @php $pct = $total > 0 ? ($starDist[$star] / $total * 100) : 0; @endphp
                                <div class="star-row">
                                    <span class="star-label">{{ $star }}★</span>
                                    <span class="bar-cell">
                                        <div class="bar-bg">
                                            <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                                        </div>
                                    </span>
                                    <span class="count">{{ $starDist[$star] }}</span>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="no-data">No branch data available for the selected period.</div>
@endif

{{-- By Category Section --}}
@if(count($byCategory) > 0)
    <div>
        <h2 style="font-size: 14px; color: #7F5539; margin-bottom: 8px; border-bottom: 2px solid #7F5539; padding-bottom: 4px;">
            Rating Summary by Service Category
        </h2>
        
        @foreach($byCategory as $category)
            @php
                $catTotal = $category['total'] ?? 0;
                $catAvg = $category['avg_rating'] ?? 0;
                $starDist = $category['star_distribution'] ?? [5=>0,4=>0,3=>0,2=>0,1=>0];
                $comments = $category['comments'] ?? [];
                $aiSummary = $category['ai_summary'] ?? null;
            @endphp
            <div class="category-block">

                {{--
                    Header: tag icon + category name / feedback count.
                    Icons are plain CSS boxes (position:absolute divs with
                    a background or border), not SVG — see .icon-shape
                    comment in <style> above for why. Matched by keyword
                    against category_name; unrecognized categories fall
                    back to the star.
                --}}
                @php
                    $catNameLower = strtolower(trim($category['category_name'] ?? ''));
                @endphp
                <div class="cat-header">
                    <div class="cat-icon">
                        @if(str_contains($catNameLower, 'pod'))
                            {{-- Private Pod: L-shaped desk + pendant lamp with rays, above-left --}}
                            <div class="icon-shape">
                                <span style="top:3px;left:2px;width:2px;height:13px;"></span>
                                <span style="top:5px;left:2px;width:9px;height:2px;"></span>
                                <span style="top:10px;left:2px;width:9px;height:2px;"></span>
                                <span class="circle" style="top:0;left:1px;width:3px;height:3px;"></span>
                                <span style="top:-2px;left:2px;width:1px;height:1.5px;"></span>
                                <span style="top:0.5px;left:5px;width:1.5px;height:1px;"></span>
                            </div>
                        @elseif(str_contains($catNameLower, 'room'))
                            {{-- Private Room: table + TV with rabbit-ear antenna above it --}}
                            <div class="icon-shape">
                                <span style="top:11px;left:1px;width:2px;height:5px;"></span>
                                <span style="top:11px;left:12px;width:2px;height:5px;"></span>
                                <span style="top:11px;left:1px;width:13px;height:2px;"></span>
                                <span style="top:13px;left:6px;width:2px;height:3px;"></span>
                                <span style="top:13px;left:9px;width:2px;height:3px;"></span>
                                <span class="outline" style="top:0;left:3px;width:9px;height:5px;"></span>
                                <span style="top:-3px;left:4px;width:1.4px;height:4px;"></span>
                                <span style="top:-3px;left:10px;width:1.4px;height:4px;"></span>
                            </div>
                        @elseif(str_contains($catNameLower, 'common') || str_contains($catNameLower, 'area'))
                            {{-- Common Area: table with outer legs + crossbar + inner center legs --}}
                            <div class="icon-shape">
                                <span style="top:2px;left:1px;width:2px;height:13px;"></span>
                                <span style="top:2px;left:12px;width:2px;height:13px;"></span>
                                <span style="top:6px;left:1px;width:13px;height:2px;"></span>
                                <span style="top:8px;left:6px;width:2px;height:7px;"></span>
                                <span style="top:8px;left:9px;width:2px;height:7px;"></span>
                            </div>
                        @else
                            <div class="icon-fallback">&#9733;</div>
                        @endif
                    </div>
                    <div class="cat-header-text">
                        <div class="cat-title">{{ $category['category_name'] ?? 'Unknown' }}</div>
                        <div class="cat-subtitle">{{ $catTotal }} feedbacks</div>
                    </div>
                </div>

                {{--
                    Two-column layout via a real <table>, not float/CSS
                    display:table. See .cat-table comment in <style> above
                    for why: DomPDF's float support silently stacks these
                    columns depending on surrounding whitespace/markup, and
                    a genuine <table> avoids that failure mode entirely.
                --}}
                <table class="cat-table">
                    <tr>
                        {{-- Left column: rating info --}}
                        <td class="cat-col-left">
                            <div class="cat-rating-row">
                                <span class="stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span style="color: {{ $i <= round($catAvg) ? '#fbbf24' : '#d1d5db' }};">★</span>
                                    @endfor
                                </span>
                                <span class="value">{{ number_format($catAvg, 1) }} / 5.0</span>
                                <span class="reviews">({{ $catTotal }} reviews)</span>
                            </div>

                            <div>
                                @foreach([5,4,3,2,1] as $star)
                                    @php $pct = $catTotal > 0 ? ($starDist[$star] / $catTotal * 100) : 0; @endphp
                                    <div class="star-row">
                                        <span class="star-label">{{ $star }}★</span>
                                        <span class="bar-cell">
                                            <div class="bar-bg">
                                                <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                                            </div>
                                        </span>
                                        <span class="count">{{ $starDist[$star] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </td>

                        {{--
                            Right column: bordered "AI Feedback Summary" card,
                            matching the on-screen report's panel design.
                            $aiSummary is populated by exportFeedbackPdf() via
                            generateCategoryAISummary(); it is always a string
                            (falls back to a "no comments" / "unavailable"
                            message), so we only fall back to the raw-comment
                            list if it's somehow missing entirely (e.g. view
                            reused outside the PDF export flow).
                        --}}
                        <td class="cat-col-right">
                            <div class="ai-box">
                                <div class="ai-box-header"><span class="label">AI Feedback Summary</span></div>
                                <div class="ai-box-content">
                                    @if(!empty($aiSummary))
                                        {{ $aiSummary }}
                                    @elseif(count($comments) > 0)
                                        <ul style="padding-left: 15px; font-size: 9px; color: #555;">
                                            @foreach(array_slice($comments, 0, 5) as $comment)
                                                <li style="margin-bottom: 2px;">{{ $comment }}</li>
                                            @endforeach
                                            @if(count($comments) > 5)
                                                <li style="color: #999;">... and {{ count($comments) - 5 }} more comment(s)</li>
                                            @endif
                                        </ul>
                                    @else
                                        <span class="no-comments">No written comments available for this category.</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
@else
    <div class="no-data">No category data available for the selected period.</div>
@endif

<div class="footer">
    <p>This report is computer-generated and does not require a signature.</p>
    <div class="generated-info">
        Generated by: <strong>{{ $generated_by ?? 'System' }}</strong> 
        ({{ $generated_by_email ?? 'system@linkudhub.com' }}) 
        on {{ $generated_at }}
    </div>
    <p>&copy; {{ date('Y') }} {{ $company_name ?? 'Linkud Hub' }}. All rights reserved.</p>
</div>

</body>
</html>