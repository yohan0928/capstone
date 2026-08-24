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
        .header .staff-info {
            font-size: 10px;
            color: #7F5539;
            margin-top: 5px;
            background: #f8f7f5;
            padding: 4px 15px;
            border-radius: 4px;
            display: inline-block;
        }
        .header .staff-info strong {
            color: #4A2C1D;
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
            font-size: 9px;
        }
        table th {
            background: #7F5539;
            color: #fff;
            font-size: 8px;
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
        
        .star-row {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 1px 0;
        }
        .star-row .star-label {
            font-size: 8px;
            color: #666;
            width: 12px;
        }
        .star-row .bar-bg {
            flex: 1;
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
            font-size: 8px;
            color: #999;
            width: 20px;
            text-align: right;
        }
        
        .category-block {
            margin-top: 16px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: #fafaf8;
        }
        .category-block .cat-title {
            font-size: 12px;
            font-weight: bold;
            color: #7F5539;
            margin-bottom: 6px;
        }
        .category-block .cat-meta {
            font-size: 9px;
            color: #666;
            margin-bottom: 8px;
        }
        .category-block .cat-summary {
            font-size: 10px;
            color: #444;
            background: #f3f0eb;
            padding: 8px 10px;
            border-radius: 4px;
            margin-top: 6px;
            border-left: 3px solid #7F5539;
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
        
        .filter-note {
            font-size: 8px;
            color: #888;
            margin-top: 5px;
            padding: 4px 10px;
            background: #f5f5f4;
            border-radius: 4px;
            display: inline-block;
        }
        .filter-note strong {
            color: #7F5539;
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
        @endif
    </div>
    <div class="staff-info">
        <strong>Staff:</strong> {{ $generated_by ?? 'System' }} 
        <span style="color: #999; margin: 0 6px;">|</span>
        <strong>Branch:</strong> {{ $branch->branch_name ?? 'N/A' }}
    </div>
    <div class="generated-by">
        <strong>Generated By:</strong> {{ $generated_by ?? 'System' }} 
        <span style="color: #999; font-weight: normal;">|</span> 
        <strong>Email:</strong> {{ $generated_by_email ?? 'system@linkudhub.com' }}
    </div>
    <div class="filter-note">
        <strong>Filter:</strong> Showing only feedback handled by you
    </div>
    <div class="date-range" style="font-size: 9px; color: #aaa; margin-top: 2px;">
        Generated: {{ $generated_at }}
    </div>
</div>

{{-- Summary Cards --}}
@php
    $byBranch = $feedbackData['by_branch'] ?? [];
    $byCategory = $feedbackData['by_category'] ?? [];
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
        <div class="label">Your Branch</div>
        <div class="value green">{{ $branch->branch_name ?? 'N/A' }}</div>
    </div>
</div>

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
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <div style="display: flex;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span style="font-size: 12px; color: {{ $i <= round($branch['avg_rating'] ?? 0) ? '#fbbf24' : '#d1d5db' }};">★</span>
                                    @endfor
                                </div>
                                <span style="font-weight: bold;">{{ number_format($branch['avg_rating'] ?? 0, 1) }}</span>
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
                                    <div class="bar-bg">
                                        <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                                    </div>
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
            @endphp
            <div class="category-block">
                <div class="cat-title">{{ $category['category_name'] ?? 'Unknown' }}</div>
                <div class="cat-meta">
                    <span style="font-weight: bold;">{{ number_format($catAvg, 1) }} / 5.0</span>
                    &nbsp;·&nbsp; {{ $catTotal }} feedbacks
                </div>
                
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px;">
                    <div style="display: flex; gap: 2px;">
                        @for($i = 1; $i <= 5; $i++)
                            <span style="font-size: 14px; color: {{ $i <= round($catAvg) ? '#fbbf24' : '#d1d5db' }};">★</span>
                        @endfor
                    </div>
                </div>
                
                <div style="max-width: 250px; margin-top: 6px;">
                    @foreach([5,4,3,2,1] as $star)
                        @php $pct = $catTotal > 0 ? ($starDist[$star] / $catTotal * 100) : 0; @endphp
                        <div class="star-row">
                            <span class="star-label">{{ $star }}★</span>
                            <div class="bar-bg">
                                <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                            </div>
                            <span class="count">{{ $starDist[$star] }}</span>
                        </div>
                    @endforeach
                </div>
                
                @if(count($comments) > 0)
                    <div class="cat-summary">
                        <strong style="font-size: 9px; color: #7F5539;">Customer Comments:</strong>
                        <ul style="margin-top: 4px; padding-left: 15px; font-size: 9px; color: #555;">
                            @foreach(array_slice($comments, 0, 5) as $comment)
                                <li style="margin-bottom: 2px;">{{ $comment }}</li>
                            @endforeach
                            @if(count($comments) > 5)
                                <li style="color: #999;">... and {{ count($comments) - 5 }} more comment(s)</li>
                            @endif
                        </ul>
                    </div>
                @else
                    <div class="no-comments">No written comments available for this category.</div>
                @endif
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