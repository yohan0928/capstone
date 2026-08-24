<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
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
        .summary-cards .card .value.green { color: #16a34a; }
        .summary-cards .card .value.blue { color: #2563eb; }
        .summary-cards .card .value.purple { color: #9333ea; }
        .summary-cards .card .value.emerald { color: #059669; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
            font-size: 9px;
        }
        table tr:nth-child(even) {
            background: #faf9f7;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        .grand-total {
            background: #f0ebe6 !important;
            font-weight: bold;
        }
        .grand-total td {
            border-top: 2px solid #7F5539;
            padding: 8px 6px;
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
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
        }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-emerald { background: #d1fae5; color: #065f46; }
        
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
        .page-break {
            page-break-after: always;
        }
        .report-meta {
            font-size: 8px;
            color: #888;
            text-align: right;
            margin-bottom: 10px;
        }
        .staff-badge {
            background: #7F5539;
            color: #fff;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 8px;
            display: inline-block;
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
    </style>
</head>
<body>

<div class="watermark">LINKUD HUB</div>

<div class="header">
    <h1>Sales Report</h1>
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
        <strong>Filter:</strong> Showing only transactions handled by you
    </div>
    <div class="date-range" style="font-size: 9px; color: #aaa; margin-top: 2px;">
        Generated: {{ $generated_at }}
    </div>
</div>

{{-- Summary Cards --}}
<div class="summary-cards">
    <div class="card">
        <div class="label">Total Revenue</div>
        <div class="value primary">₱{{ number_format($salesData['total_revenue'] ?? 0, 2) }}</div>
    </div>
    <div class="card">
        <div class="label">Total Bookings</div>
        <div class="value blue">{{ $salesData['total_bookings'] ?? 0 }}</div>
    </div>
    <div class="card">
        <div class="label">Total Orders</div>
        <div class="value green">{{ $salesData['total_orders'] ?? 0 }}</div>
    </div>
    <div class="card">
        <div class="label">Total Redemptions</div>
        <div class="value purple">{{ $salesData['total_redemptions'] ?? 0 }}</div>
    </div>
</div>

{{-- Sales by Branch Table --}}
<table>
    <thead>
        <tr>
            <th style="width: 18%;">Branch</th>
            <th style="width: 12%;" class="text-right">Booking Revenue</th>
            <th style="width: 12%;" class="text-right">Extension Revenue</th>
            <th style="width: 12%;" class="text-right">Order Revenue</th>
            <th style="width: 12%;" class="text-right">Reward Discount</th>
            <th style="width: 13%;" class="text-right">Total Revenue</th>
            <th style="width: 7%;" class="text-center">Bookings</th>
            <th style="width: 7%;" class="text-center">Orders</th>
            <th style="width: 7%;" class="text-center">Redemptions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($salesData['by_branch'] ?? [] as $branch)
            <tr>
                <td><span class="branch-name">{{ $branch['branch_name'] }}</span></td>
                <td class="text-right">₱{{ number_format($branch['booking_revenue'] ?? 0, 2) }}</td>
                <td class="text-right">₱{{ number_format($branch['extension_revenue'] ?? 0, 2) }}</td>
                <td class="text-right">₱{{ number_format($branch['order_revenue'] ?? 0, 2) }}</td>
                <td class="text-right" style="color: #059669;">₱{{ number_format($branch['reward_discount'] ?? 0, 2) }}</td>
                <td class="text-right" style="font-weight: bold;">₱{{ number_format($branch['total_revenue'] ?? 0, 2) }}</td>
                <td class="text-center"><span class="badge badge-blue">{{ $branch['total_bookings'] ?? 0 }}</span></td>
                <td class="text-center"><span class="badge badge-green">{{ $branch['total_orders'] ?? 0 }}</span></td>
                <td class="text-center"><span class="badge badge-purple">{{ $branch['total_redemptions'] ?? 0 }}</span></td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center text-muted" style="padding: 20px;">No data available for the selected period.</td>
            </tr>
        @endforelse
    </tbody>
    @if(count($salesData['by_branch'] ?? []) > 0)
        <tfoot>
            <tr class="grand-total">
                <td>GRAND TOTAL</td>
                <td class="text-right">₱{{ number_format($salesData['by_branch']->sum('booking_revenue'), 2) }}</td>
                <td class="text-right">₱{{ number_format($salesData['by_branch']->sum('extension_revenue'), 2) }}</td>
                <td class="text-right">₱{{ number_format($salesData['by_branch']->sum('order_revenue'), 2) }}</td>
                <td class="text-right" style="color: #059669;">₱{{ number_format($salesData['by_branch']->sum('reward_discount'), 2) }}</td>
                <td class="text-right" style="color: #7F5539; font-size: 11px;">₱{{ number_format($salesData['total_revenue'] ?? 0, 2) }}</td>
                <td class="text-center">{{ $salesData['total_bookings'] ?? 0 }}</td>
                <td class="text-center">{{ $salesData['total_orders'] ?? 0 }}</td>
                <td class="text-center">{{ $salesData['total_redemptions'] ?? 0 }}</td>
            </tr>
        </tfoot>
    @endif
</table>

<div class="footer">
    <p>This report is computer-generated and does not require a signature.</p>
    <div class="generated-info">
        Generated by: <strong>{{ $generated_by ?? 'System' }}</strong> 
        on {{ $generated_at }}
    </div>
    <p>&copy; {{ date('Y') }} {{ $company_name ?? 'Linkud Hub' }}. All rights reserved.</p>
</div>

</body>
</html>