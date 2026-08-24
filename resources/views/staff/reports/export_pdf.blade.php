<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Staff Performance Report</title>
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

        .staff-info {
            background: #f8f7f5;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .staff-info .name {
            font-size: 14px;
            font-weight: bold;
            color: #7F5539;
        }
        .staff-info .details {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
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
        .summary-cards .card .value.gold { color: #d97706; }

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
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
        }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #7F5539;
            margin-top: 18px;
            padding-bottom: 4px;
            border-bottom: 2px solid #7F5539;
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
        .no-data {
            text-align: center;
            padding: 15px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="watermark">LINKUD HUB</div>

<div class="header">
    <h1>Staff Performance Report</h1>
    <div class="subtitle">{{ $company_name ?? 'Linkud Hub' }}</div>
    <div class="date-range">
        Period: {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
        @if($branch)
            | Branch: {{ $branch->branch_name }}
        @endif
    </div>
    <div class="generated-by">
        <strong>Generated By:</strong> {{ $generated_by ?? 'System' }} 
        <span style="color: #999; font-weight: normal;">|</span> 
        <strong>Staff:</strong> {{ $staff->first_name }} {{ $staff->last_name }}
    </div>
    <div class="date-range" style="font-size: 9px; color: #aaa; margin-top: 2px;">
        Generated: {{ $generated_at }}
    </div>
</div>

<div class="staff-info">
    <div class="name">{{ $staff->first_name }} {{ $staff->last_name }}</div>
    <div class="details">
        Position: {{ $staff->position ?? 'Staff' }} | 
        Branch: {{ $branch->branch_name ?? 'N/A' }} | 
        Staff ID: {{ $staff->uuid ?? 'N/A' }}
    </div>
</div>

{{-- Summary Cards --}}
<div class="summary-cards">
    <div class="card">
        <div class="label">Total Bookings</div>
        <div class="value primary">{{ $stats['total_bookings'] ?? 0 }}</div>
    </div>
    <div class="card">
        <div class="label">Customers Served</div>
        <div class="value blue">{{ $stats['total_customers'] ?? 0 }}</div>
    </div>
    <div class="card">
        <div class="label">Hours Used</div>
        <div class="value green">{{ $stats['total_hours_used'] ?? '0m' }}</div>
    </div>
    <div class="card">
        <div class="label">Total Revenue</div>
        <div class="value gold">₱{{ number_format($stats['total_revenue'] ?? 0, 2) }}</div>
    </div>
</div>

{{-- Revenue Breakdown --}}
@if(($stats['booking_revenue'] ?? 0) > 0 || ($stats['order_revenue'] ?? 0) > 0)
    <div class="section-title">Revenue Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Revenue Source</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Booking Revenue</td>
                <td class="text-right">₱{{ number_format($stats['booking_revenue'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Order Revenue</td>
                <td class="text-right">₱{{ number_format($stats['order_revenue'] ?? 0, 2) }}</td>
            </tr>
            <tr style="font-weight: bold; background: #f0ebe6;">
                <td>Total Revenue</td>
                <td class="text-right">₱{{ number_format($stats['total_revenue'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>
@endif

{{-- Services Breakdown --}}
@if(count($stats['service_breakdown'] ?? []) > 0)
    <div class="section-title">Services Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Service</th>
                <th>Category</th>
                <th class="text-center">Bookings</th>
                <th class="text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['service_breakdown'] as $service)
                <tr>
                    <td>{{ $service['service_name'] }}</td>
                    <td>{{ $service['service_category'] }}</td>
                    <td class="text-center">{{ $service['total_bookings'] }}</td>
                    <td class="text-right">₱{{ number_format($service['total_revenue'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Products Sold --}}
@if(count($stats['order_items_breakdown'] ?? []) > 0)
    <div class="section-title">Products Sold</div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Type</th>
                <th class="text-center">Quantity</th>
                <th class="text-center">Orders</th>
                <th class="text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['order_items_breakdown'] as $item)
                <tr>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['product_type'] }}</td>
                    <td class="text-center">{{ $item['total_quantity_sold'] }}</td>
                    <td class="text-center">{{ $item['order_count'] }}</td>
                    <td class="text-right">₱{{ number_format($item['total_revenue'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Inventory Deduction --}}
@if(count($stats['inventory_deduction']['product_deduction'] ?? []) > 0)
    <div class="section-title">Inventory Deduction</div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Type</th>
                <th class="text-center">Quantity Deducted</th>
                <th class="text-right">Value Deducted</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['inventory_deduction']['product_deduction'] as $product)
                <tr>
                    <td>{{ $product['product_name'] }}</td>
                    <td>{{ $product['product_type'] }}</td>
                    <td class="text-center">{{ $product['total_quantity_deducted'] }}</td>
                    <td class="text-right">₱{{ number_format($product['total_value_deducted'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Inventory Statistics --}}
@if(isset($stats['inventory_stats']))
    <div class="section-title">Inventory Statistics</div>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Total Items</th>
                <th>Available</th>
                <th>Low Stock</th>
                <th>Total Quantity</th>
            </tr>
        </thead>
        <tbody>
            @php
                $productStats = $stats['inventory_stats']['product_stats'] ?? null;
                $ingredientStats = $stats['inventory_stats']['ingredient_stats'] ?? null;
            @endphp
            @if($productStats)
                <tr>
                    <td>Products</td>
                    <td class="text-center">{{ $productStats->total_products ?? 0 }}</td>
                    <td class="text-center">{{ $productStats->available_products ?? 0 }}</td>
                    <td class="text-center">
                        <span class="badge badge-red">{{ $productStats->low_stock_products ?? 0 }}</span>
                    </td>
                    <td class="text-center">{{ $productStats->total_quantity ?? 0 }}</td>
                </tr>
            @endif
            @if($ingredientStats)
                <tr>
                    <td>Ingredients</td>
                    <td class="text-center">{{ $ingredientStats->total_ingredients ?? 0 }}</td>
                    <td class="text-center">{{ $ingredientStats->available_ingredients ?? 0 }}</td>
                    <td class="text-center">
                        <span class="badge badge-red">{{ $ingredientStats->low_stock_ingredients ?? 0 }}</span>
                    </td>
                    <td class="text-center">{{ $ingredientStats->total_quantity ?? 0 }}</td>
                </tr>
            @endif
        </tbody>
    </table>
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