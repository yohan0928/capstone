<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
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
        .summary-cards .card .value.red { color: #dc2626; }
        .summary-cards .card .value.blue { color: #2563eb; }
        
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
            padding: 8px 5px;
            text-align: left;
        }
        table td {
            padding: 5px 5px;
            border-bottom: 1px solid #e5e7eb;
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
            padding: 8px 5px;
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
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-orange { background: #fef3c7; color: #92400e; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-gray { background: #f3f4f6; color: #4b5563; }
        .badge-emerald { background: #d1fae5; color: #065f46; }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px 4px 0 0;
            margin-top: 16px;
        }
        .section-title.brown { background: #7F5539; }
        .section-title.green { background: #166534; }
        .section-title.red { background: #991b1b; }
        
        .ledger-table th {
            background: #f5f5f4;
            color: #444;
            border-bottom-color: #ccc;
        }
        .ledger-header-row td {
            background: #f3ece6;
            font-weight: 700;
            color: #4A2C1D;
            padding: 6px 10px;
            border-top: 2px solid #7F5539;
            border-bottom: 1px solid #e5d8ca;
            font-size: 10px;
        }
        .ledger-txn-no { 
            font-weight: 700;
            color: #7F5539;
        }
        .ledger-meta { 
            font-weight: 500; 
            color: #7F5539; 
            font-size: 9px; 
        }
        .ledger-item-row td {
            padding: 4px 10px 4px 22px;
            border-bottom: 1px solid #f3f3f3;
            color: #444;
            font-size: 9.5px;
        }
        .ledger-item-row.flag-row td:last-child {
            color: #b45309;
            font-weight: 600;
        }
        .no-data {
            padding: 12px;
            text-align: center;
            color: #aaa;
            font-style: italic;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 4px 4px;
            background: #fafafa;
        }
        
        .branch-group {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .branch-group-title {
            font-size: 14px;
            font-weight: 700;
            color: #7F5539;
            padding: 6px 0 8px;
            border-bottom: 2.5px solid #7F5539;
            margin-bottom: 12px;
        }
        .txn-block { margin-bottom: 14px; }
        
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
            .branch-group { page-break-inside: avoid; }
            .txn-block { page-break-inside: avoid; }
            .ledger-header-row { page-break-after: avoid; }
        }
    </style>
</head>
<body>

<div class="watermark">LINKUD HUB</div>

<div class="header">
    <h1>Inventory Report</h1>
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
        <strong>Filter:</strong> Showing only inventory transactions handled by you
    </div>
    <div class="date-range" style="font-size: 9px; color: #aaa; margin-top: 2px;">
        Generated: {{ $generated_at }}
    </div>
</div>

{{-- Summary Cards --}}
@php
    $totalBeginning = $inventoryData['total_beginning_balance'] ?? 0;
    $totalStockIn = $inventoryData['total_stock_in'] ?? 0;
    $totalStockOut = $inventoryData['total_stock_out'] ?? 0;
    $totalEnding = $inventoryData['total_ending_balance'] ?? 0;
    $netMovement = $totalStockIn - $totalStockOut;
    $netColor = $netMovement >= 0 ? '#16a34a' : '#dc2626';
@endphp

<div class="summary-cards">
    <div class="card">
        <div class="label">Net Movement</div>
        <div class="value" style="color: {{ $netColor }};">
            {{ ($netMovement >= 0 ? '+' : '') . number_format($netMovement) }}
        </div>
    </div>
    <div class="card">
        <div class="label">Total Stock In</div>
        <div class="value" style="color: #16a34a;">+{{ number_format($totalStockIn) }}</div>
    </div>
    <div class="card">
        <div class="label">Total Stock Out</div>
        <div class="value" style="color: #dc2626;">−{{ number_format($totalStockOut) }}</div>
    </div>
    <div class="card">
        <div class="label">Ending Balance</div>
        <div class="value" style="color: #2563eb;">{{ number_format($totalEnding) }}</div>
    </div>
</div>

{{-- Inventory by Branch Table --}}
<div style="margin-bottom: 20px;">
    <div class="section-title brown">Branch Summary</div>
    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Branch</th>
                <th style="width: 10%;" class="text-right">Beginning</th>
                <th style="width: 10%;" class="text-right">Stock In</th>
                <th style="width: 9%;" class="text-right">Sold</th>
                <th style="width: 9%;" class="text-right">Damaged</th>
                <th style="width: 9%;" class="text-right">Expired</th>
                <th style="width: 9%;" class="text-right">Pulled Out</th>
                <th style="width: 10%;" class="text-right">Total Out</th>
                <th style="width: 10%;" class="text-right">Ending</th>
                <th style="width: 10%;" class="text-right">Net</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventoryData['by_branch'] ?? [] as $branch)
                @php
                    $net = ($branch['total_stock_in'] ?? 0) - ($branch['total_stock_out'] ?? 0);
                    $netClass = $net >= 0 ? 'style="color: #16a34a;"' : 'style="color: #dc2626;"';
                @endphp
                <tr>
                    <td><span class="branch-name">{{ $branch['branch_name'] }}</span></td>
                    <td class="text-right">{{ number_format($branch['beginning_balance'] ?? 0) }}</td>
                    <td class="text-right" style="color: #16a34a;">+{{ number_format($branch['total_stock_in'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($branch['total_sold'] ?? 0) }}</td>
                    <td class="text-right" style="color: #d97706;">{{ number_format($branch['total_damaged'] ?? 0) }}</td>
                    <td class="text-right" style="color: #dc2626;">{{ number_format($branch['total_expired'] ?? 0) }}</td>
                    <td class="text-right" style="color: #9333ea;">{{ number_format($branch['total_pulled_out'] ?? 0) }}</td>
                    <td class="text-right" style="color: #dc2626; font-weight: bold;">−{{ number_format($branch['total_stock_out'] ?? 0) }}</td>
                    <td class="text-right" style="color: #2563eb; font-weight: bold;">{{ number_format($branch['ending_balance'] ?? 0) }}</td>
                    <td class="text-right" {!! $netClass !!}>{{ ($net >= 0 ? '+' : '') . number_format($net) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted" style="padding: 20px;">No data available for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($inventoryData['by_branch'] ?? []) > 0)
            <tfoot>
                <tr class="grand-total">
                    <td>GRAND TOTAL</td>
                    <td class="text-right">{{ number_format($inventoryData['total_beginning_balance'] ?? 0) }}</td>
                    <td class="text-right" style="color: #16a34a;">+{{ number_format($inventoryData['total_stock_in'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($inventoryData['by_branch']->sum('total_sold')) }}</td>
                    <td class="text-right" style="color: #d97706;">{{ number_format($inventoryData['by_branch']->sum('total_damaged')) }}</td>
                    <td class="text-right" style="color: #dc2626;">{{ number_format($inventoryData['by_branch']->sum('total_expired')) }}</td>
                    <td class="text-right" style="color: #9333ea;">{{ number_format($inventoryData['by_branch']->sum('total_pulled_out')) }}</td>
                    <td class="text-right" style="color: #dc2626; font-weight: bold;">−{{ number_format($inventoryData['total_stock_out'] ?? 0) }}</td>
                    <td class="text-right" style="color: #2563eb; font-weight: bold;">{{ number_format($inventoryData['total_ending_balance'] ?? 0) }}</td>
                    <td class="text-right" style="color: {{ $netMovement >= 0 ? '#16a34a' : '#dc2626' }}; font-weight: bold;">
                        {{ ($netMovement >= 0 ? '+' : '') . number_format($netMovement) }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

{{-- Per-Branch Transaction Details --}}
@php
    $transactionsArray = isset($inventoryData['transactions']) && $inventoryData['transactions'] instanceof \Illuminate\Support\Collection 
        ? $inventoryData['transactions']->toArray() 
        : (isset($inventoryData['transactions']) ? $inventoryData['transactions'] : []);
    
    $itemsArray = isset($inventoryData['items']) && $inventoryData['items'] instanceof \Illuminate\Support\Collection 
        ? $inventoryData['items']->toArray() 
        : (isset($inventoryData['items']) ? $inventoryData['items'] : []);
    
    $branchesArray = isset($inventoryData['by_branch']) && $inventoryData['by_branch'] instanceof \Illuminate\Support\Collection 
        ? $inventoryData['by_branch']->toArray() 
        : (isset($inventoryData['by_branch']) ? $inventoryData['by_branch'] : []);
@endphp

@foreach($branchesArray as $branch)
    @php
        $branchName = $branch['branch_name'] ?? '';
        
        $branchTxnsIn = array_filter($transactionsArray, function($t) use ($branchName) {
            return ($t['branch_name'] ?? '') === $branchName && $t['type'] === 'stock_in';
        });
        
        $branchTxnsOut = array_filter($transactionsArray, function($t) use ($branchName) {
            return ($t['branch_name'] ?? '') === $branchName && $t['type'] === 'stock_out';
        });
        
        $branchItemsIn = array_filter($itemsArray, function($i) use ($branchName) {
            return ($i['branch_name'] ?? '') === $branchName && $i['txn_type'] === 'stock_in';
        });
        
        $branchItemsOut = array_filter($itemsArray, function($i) use ($branchName) {
            return ($i['branch_name'] ?? '') === $branchName && $i['txn_type'] === 'stock_out';
        });
    @endphp
    
    @if(count($branchTxnsIn) > 0 || count($branchTxnsOut) > 0)
        <div class="branch-group">
            <div class="branch-group-title">{{ $branchName }}</div>
            
            {{-- Stock In Transactions --}}
            @if(count($branchTxnsIn) > 0)
                <div class="txn-block">
                    <div class="section-title green">Stock In</div>
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Transaction No.</th>
                                <th style="width: 20%;">Date</th>
                                <th style="width: 15%;" class="text-right">Total Qty</th>
                                <th style="width: 15%;">Processed By</th>
                                <th style="width: 15%;">Item Type</th>
                                <th style="width: 15%;">Item Name / Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchTxnsIn as $txn)
                                <tr class="ledger-header-row">
                                    <td colspan="6">
                                        <span class="ledger-txn-no">{{ $txn['transaction_no'] }}</span>
                                        <span class="ledger-meta">
                                            {{ \Carbon\Carbon::parse($txn['created_at'])->format('M d, Y h:i A') }}
                                            &nbsp;·&nbsp; Qty +{{ $txn['total_quantity'] ?? 0 }}
                                            &nbsp;·&nbsp; Processed: {{ $txn['processed_by'] ?? '—' }}
                                        </span>
                                    </td>
                                </tr>
                                @php
                                    $txnItems = array_filter($branchItemsIn, function($i) use ($txn) {
                                        return ($i['transaction_no'] ?? '') === $txn['transaction_no'];
                                    });
                                @endphp
                                @if(count($txnItems) > 0)
                                    @foreach($txnItems as $item)
                                        <tr class="ledger-item-row">
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <span class="badge {{ $item['item_type'] === 'ingredient' ? 'badge-purple' : 'badge-blue' }}">
                                                    {{ $item['item_type'] === 'ingredient' ? 'Ingredient' : 'Product' }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $item['item_name'] ?? '—' }}
                                                <span style="color: #16a34a; font-weight: bold;">
                                                    (+{{ $item['quantity'] ?? 0 }}{{ $item['unit'] ? ' ' . $item['unit'] : '' }})
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="ledger-item-row">
                                        <td colspan="6" style="text-align: center; color: #999; padding: 6px;">
                                            No item details available
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            
            {{-- Stock Out Transactions --}}
            @if(count($branchTxnsOut) > 0)
                <div class="txn-block">
                    <div class="section-title red">Stock Out</div>
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th style="width: 18%;">Transaction No.</th>
                                <th style="width: 18%;">Date</th>
                                <th style="width: 14%;" class="text-right">Total Qty</th>
                                <th style="width: 14%;">Processed By</th>
                                <th style="width: 12%;">Item Type</th>
                                <th style="width: 12%;">Item Name</th>
                                <th style="width: 12%;">Qty</th>
                                <th style="width: 12%;">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchTxnsOut as $txn)
                                <tr class="ledger-header-row">
                                    <td colspan="8">
                                        <span class="ledger-txn-no">{{ $txn['transaction_no'] }}</span>
                                        <span class="ledger-meta">
                                            {{ \Carbon\Carbon::parse($txn['created_at'])->format('M d, Y h:i A') }}
                                            &nbsp;·&nbsp; Qty −{{ $txn['total_quantity'] ?? 0 }}
                                            &nbsp;·&nbsp; Processed: {{ $txn['processed_by'] ?? '—' }}
                                            @if($txn['reason'])
                                                &nbsp;·&nbsp; Reason: {{ ucfirst($txn['reason']) }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                                @php
                                    $txnItems = array_filter($branchItemsOut, function($i) use ($txn) {
                                        return ($i['transaction_no'] ?? '') === $txn['transaction_no'];
                                    });
                                @endphp
                                @if(count($txnItems) > 0)
                                    @foreach($txnItems as $item)
                                        <tr class="ledger-item-row {{ empty($item['reason']) ? 'flag-row' : '' }}">
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <span class="badge {{ $item['item_type'] === 'ingredient' ? 'badge-purple' : 'badge-blue' }}">
                                                    {{ $item['item_type'] === 'ingredient' ? 'Ingredient' : 'Product' }}
                                                </span>
                                            </td>
                                            <td>{{ $item['item_name'] ?? '—' }}</td>
                                            <td style="color: #dc2626; font-weight: bold;">
                                                −{{ $item['quantity'] ?? 0 }}{{ $item['unit'] ? ' ' . $item['unit'] : '' }}
                                            </td>
                                            <td>
                                                @if($item['reason'])
                                                    <span class="badge badge-gray">{{ ucfirst($item['reason']) }}</span>
                                                @else
                                                    <span style="color: #b45309; font-weight: 600;">⚠ Not specified</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="ledger-item-row">
                                        <td colspan="8" style="text-align: center; color: #999; padding: 6px;">
                                            No item details available
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
@endforeach

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