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
        /* Rounded-corner card wrapper for standalone tables (Sales by
           Branch, Payment Methods, Popular Services, Products Sold),
           matching the branch-group card style used in Orders Breakdown. */
        .table-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 10px;
        }
        .table-card table {
            margin-top: 0;
        }
        .table-card table th:first-child,
        .table-card table td:first-child {
            padding-left: 12px;
        }
        .table-card table th:last-child,
        .table-card table td:last-child {
            padding-right: 12px;
        }
        table th {
            background: #7F5539;
            color: #fff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 6px;
            text-align: left;
        }
        table td {
            padding: 6px 6px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
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
            font-size: 9px;
            color: #999;
            text-align: center;
        }
        .footer .generated-info {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-emerald { background: #d1fae5; color: #065f46; }
        .badge-gray { background: #f3f4f6; color: #4b5563; }

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
            font-size: 9px;
            color: #888;
            text-align: right;
            margin-bottom: 10px;
        }
        .section {
            margin-top: 20px;
        }
        .section h2 {
            font-size: 14px;
            color: #7F5539;
            margin-bottom: 8px;
            border-bottom: 2px solid #7F5539;
            padding-bottom: 4px;
            page-break-after: avoid;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
        /* Order Breakdown ledger: flat one-row-per-item table, styled like
           the inventory report's ledger table. Repeated order info
           (ref/date/payment/total qty) collapses to a dash on subsequent
           item rows for the same order. */
        .ledger-dash {
            color: #ccc;
        }
        .order-total-row td {
            background: #faf9f7;
            border-top: 1px dashed #d8cec3;
            font-size: 10px;
            font-weight: bold;
            color: #7F5539;
        }
        /* Per-branch grouping for Orders Breakdown, rounded-corner card
           style to match the inventory report's per-branch cards. */
        .branch-group {
            margin-top: 16px;
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .branch-group:first-child {
            margin-top: 0;
        }
        .branch-group-title {
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            background: #7F5539;
            padding: 8px 12px;
            margin-bottom: 0;
            border-bottom: none;
        }
        .branch-group table {
            margin-top: 0;
        }
        .branch-group table th:first-child,
        .branch-group table td:first-child {
            padding-left: 12px;
        }
        .branch-group table th:last-child,
        .branch-group table td:last-child {
            padding-right: 12px;
        }
        @media print {
            .section { page-break-inside: avoid; }
            .branch-group { page-break-inside: avoid; }
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
        @else
            | All Branches
        @endif
    </div>
    <div class="generated-by">
        <strong>Generated By:</strong> {{ $generated_by ?? 'System' }}
    </div>
    <div class="date-range" style="font-size: 10px; color: #aaa; margin-top: 2px;">
        Generated: {{ $generated_at }}
    </div>
</div>

{{-- Summary Cards --}}
<div class="summary-cards">
    <div class="card">
        <div class="label">Total Revenue</div>
        <div class="value primary">₱{{ number_format($salesData['total_revenue'], 2) }}</div>
    </div>
    <div class="card">
        <div class="label">Total Bookings</div>
        <div class="value blue">{{ $salesData['total_bookings'] }}</div>
    </div>
    <div class="card">
        <div class="label">Total Orders</div>
        <div class="value green">{{ $salesData['total_orders'] }}</div>
    </div>
    <div class="card">
        <div class="label">Total Redemptions</div>
        <div class="value purple">{{ $salesData['total_redemptions'] }}</div>
    </div>
</div>

{{-- Sales by Branch Table --}}
<div class="table-card">
<table>
    <thead>
        <tr>
            <th style="width: 18%;">Branch</th>
            <th style="width: 13%;" class="text-right">Booking Revenue</th>
            <th style="width: 13%;" class="text-right">Order Revenue</th>
            <th style="width: 13%;" class="text-right">Reward Discount</th>
            <th style="width: 14%;" class="text-right">Total Revenue</th>
            <th style="width: 9%;" class="text-center">Bookings</th>
            <th style="width: 9%;" class="text-center">Orders</th>
            <th style="width: 11%;" class="text-center">Redemptions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($salesData['by_branch'] as $branch)
            <tr>
                <td><span class="branch-name">{{ $branch['branch_name'] }}</span></td>
                <td class="text-right">₱{{ number_format($branch['booking_revenue'], 2) }}</td>
                <td class="text-right">₱{{ number_format($branch['order_revenue'], 2) }}</td>
                <td class="text-right" style="color: #059669;">₱{{ number_format($branch['reward_discount'], 2) }}</td>
                <td class="text-right" style="font-weight: bold;">₱{{ number_format($branch['total_revenue'], 2) }}</td>
                <td class="text-center"><span class="badge badge-blue">{{ $branch['total_bookings'] }}</span></td>
                <td class="text-center"><span class="badge badge-green">{{ $branch['total_orders'] }}</span></td>
                <td class="text-center"><span class="badge badge-purple">{{ $branch['total_redemptions'] }}</span></td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted" style="padding: 20px;">No data available for the selected period.</td>
            </tr>
        @endforelse
    </tbody>
    @if(count($salesData['by_branch']) > 0)
        <tfoot>
            <tr class="grand-total">
                <td>GRAND TOTAL</td>
                <td class="text-right">₱{{ number_format($salesData['by_branch']->sum('booking_revenue'), 2) }}</td>
                <td class="text-right">₱{{ number_format($salesData['by_branch']->sum('order_revenue'), 2) }}</td>
                <td class="text-right" style="color: #059669;">₱{{ number_format($salesData['by_branch']->sum('reward_discount'), 2) }}</td>
                <td class="text-right" style="color: #7F5539; font-size: 11px;">₱{{ number_format($salesData['total_revenue'], 2) }}</td>
                <td class="text-center">{{ $salesData['total_bookings'] }}</td>
                <td class="text-center">{{ $salesData['total_orders'] }}</td>
                <td class="text-center">{{ $salesData['total_redemptions'] }}</td>
            </tr>
        </tfoot>
    @endif
</table>
</div>

{{-- ══════════════════════════════════════════
     Payment Method Breakdown
══════════════════════════════════════════════ --}}
@php $paymentMethods = $salesData['payment_methods'] ?? []; @endphp
<div class="section">
    <h2>Payment Method Breakdown</h2>
    @if(count($paymentMethods) > 0)
        <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Payment Method</th>
                    <th style="width: 20%;" class="text-center">Payments</th>
                    <th style="width: 30%;" class="text-right">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paymentMethods as $pm)
                    <tr>
                        <td>{{ $pm['method'] ?? 'Unknown' }}</td>
                        <td class="text-center"><span class="badge badge-gray">{{ $pm['payments'] ?? 0 }}</span></td>
                        <td class="text-right" style="font-weight: bold;">₱{{ number_format($pm['total_amount'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="no-data">No payment data available for the selected period.</div>
    @endif
</div>

{{-- ══════════════════════════════════════════
     Most Popular Services
══════════════════════════════════════════════ --}}
@php $serviceBreakdown = $salesData['service_breakdown'] ?? []; @endphp
<div class="section">
    <h2>Most Popular Services</h2>
    @if(count($serviceBreakdown) > 0)
        <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Category</th>
                    <th style="width: 30%;">Service</th>
                    <th style="width: 20%;" class="text-right">Total Hours Spent</th>
                    <th style="width: 25%;" class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serviceBreakdown as $s)
                    <tr>
                        <td>{{ $s['category'] ?? 'Uncategorized' }}</td>
                        <td style="font-weight: bold;">{{ $s['service'] ?? 'Unknown' }}</td>
                        <td class="text-right">{{ $s['hours'] ?? 0 }} hr(s)</td>
                        <td class="text-right" style="font-weight: bold;">₱{{ number_format($s['revenue'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="no-data">No service booking data available for the selected period.</div>
    @endif
</div>

{{-- ══════════════════════════════════════════
     Products Sold (RTD/Package + MTO)
══════════════════════════════════════════════ --}}
@php
    $productsSold = $salesData['products_sold'] ?? [];
    $productTypeLabels = ['rtd' => 'RTD', 'package' => 'Package', 'mto' => 'MTO'];
@endphp
<div class="section">
    <h2>Products Sold</h2>
    @if(count($productsSold) > 0)
        <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Product</th>
                    <th style="width: 20%;">Type</th>
                    <th style="width: 15%;" class="text-center">Quantity Sold</th>
                    <th style="width: 25%;" class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productsSold as $p)
                    @php
                        $type = $p['type'] ?? 'unknown';
                        $typeLabel = $productTypeLabels[$type] ?? ucfirst($type);
                        $badgeClass = $type === 'mto' ? 'badge-purple' : 'badge-blue';
                    @endphp
                    <tr>
                        <td style="font-weight: bold;">{{ $p['product'] ?? 'Unknown' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span></td>
                        <td class="text-center">{{ $p['quantity'] ?? 0 }}</td>
                        <td class="text-right" style="font-weight: bold;">₱{{ number_format($p['revenue'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="no-data">No product sales data available for the selected period.</div>
    @endif
</div>

{{-- ══════════════════════════════════════════
     Orders Breakdown (grouped by branch, flat ledger with dash-repeat)
     Flows naturally after Products Sold; heading won't be orphaned
     alone at the bottom of a page (page-break-after: avoid on h2),
     and each branch card stays intact (page-break-inside: avoid).
══════════════════════════════════════════════ --}}
@php
    $orders = $salesData['orders'] ?? [];
    $ordersByBranch = collect($orders)->groupBy('branch_name')->sortKeys();
@endphp
<div class="section">
    <h2>Orders Breakdown</h2>
    @if(count($orders) > 0)
        @foreach($ordersByBranch as $branchName => $branchOrders)
            <div class="branch-group">
                <div class="branch-group-title">{{ $branchName }}</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 16%;">Order Ref.</th>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 14%;">Payment Method</th>
                            <th style="width: 8%;" class="text-center">Total Items</th>
                            <th style="width: 22%;">Product</th>
                            <th style="width: 8%;" class="text-center">Qty</th>
                            <th style="width: 10%;" class="text-right">Price</th>
                            <th style="width: 10%;" class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branchOrders as $order)
                            @php
                                $orderItems = $order['items'] ?? [];
                                $orderDate  = null;
                                try {
                                    $orderDate = \Carbon\Carbon::parse($order['date'] ?? null);
                                } catch (\Exception $e) {
                                    $orderDate = null;
                                }
                            @endphp

                            @if(count($orderItems) > 0)
                                @foreach($orderItems as $item)
                                    <tr>
                                        @if($loop->first)
                                            <td style="font-weight: bold; color: #7F5539;">{{ $order['order_ref_no'] ?? '—' }}</td>
                                            <td>
                                                @if($orderDate)
                                                    <div>{{ $orderDate->format('M d, Y') }}</div>
                                                    <div class="text-muted" style="font-size: 9px;">{{ $orderDate->format('h:i A') }}</div>
                                                @else
                                                    <div>{{ $order['date'] ?? '—' }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $order['payment_method'] ?? '—' }}</td>
                                            <td class="text-center">{{ $order['items_qty'] ?? 0 }}</td>
                                        @else
                                            <td class="ledger-dash text-center">—</td>
                                            <td class="ledger-dash text-center">—</td>
                                            <td class="ledger-dash text-center">—</td>
                                            <td class="ledger-dash text-center">—</td>
                                        @endif
                                        <td>{{ $item['product_name'] ?? '—' }}</td>
                                        <td class="text-center">{{ $item['quantity'] ?? 0 }}</td>
                                        <td class="text-right">₱{{ number_format($item['selling_price'] ?? 0, 2) }}</td>
                                        <td class="text-right">₱{{ number_format($item['sub_total'] ?? 0, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="order-total-row">
                                    <td colspan="7" class="text-right">TOTAL AMOUNT</td>
                                    <td class="text-right">₱{{ number_format($order['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td style="font-weight: bold; color: #7F5539;">{{ $order['order_ref_no'] ?? '—' }}</td>
                                    <td>
                                        @if($orderDate)
                                            <div>{{ $orderDate->format('M d, Y') }}</div>
                                            <div class="text-muted" style="font-size: 9px;">{{ $orderDate->format('h:i A') }}</div>
                                        @else
                                            <div>{{ $order['date'] ?? '—' }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $order['payment_method'] ?? '—' }}</td>
                                    <td class="text-center">{{ $order['items_qty'] ?? 0 }}</td>
                                    <td colspan="3" class="text-center text-muted">No items</td>
                                    <td class="text-right">₱{{ number_format($order['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="no-data">No orders found for the selected period.</div>
    @endif
</div>

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