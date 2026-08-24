<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.35;
            padding: 10mm 8mm;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #7F5539;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 15pt;
            color: #7F5539;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header .subtitle {
            font-size: 10pt;
            color: #666;
        }
        .header .date-range {
            font-size: 8.5pt;
            color: #888;
            margin-top: 2px;
        }
        .header .generated-by {
            font-size: 8pt;
            color: #666;
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px dashed #e5e7eb;
            display: inline-block;
            padding-left: 12px;
            padding-right: 12px;
        }
        .header .generated-by strong {
            color: #7F5539;
        }
        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .summary-cards .card {
            display: table-cell;
            text-align: left;
            padding: 6px;
            background: #f8f7f5;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .summary-cards .card .label {
            font-size: 10pt;
            color: #888;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .summary-cards .card .value {
            font-size: 12pt;
            font-weight: bold;
            color: #333;
            margin-top: 2px;
        }
        .summary-cards .card .value.primary { color: #7F5539; }
        .summary-cards .card .value.green { color: #16a34a; }
        .summary-cards .card .value.red { color: #dc2626; }
        .summary-cards .card .value.blue { color: #2563eb; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10pt;
            table-layout: fixed;
            border-radius: 6px;
            overflow: hidden;
        }
        table th {
            background: #7F5539;
            color: #fff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.15px;
            padding: 3px 4px;
            text-align: left;
            word-wrap: break-word;
        }
        table th:first-child { border-radius: 4px 0 0 0; }
        table th:last-child { border-radius: 0 4px 0 0; }
        table td {
            padding: 3px 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            word-wrap: break-word;
            text-align: left;
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
            padding: 5px 4px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: left; }
        .text-left { text-align: left; }
        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 6.5pt;
            color: #999;
            text-align: left;
        }
        .footer .generated-info {
            font-size: 6.5pt;
            color: #666;
            margin-top: 2px;
        }
        .branch-name {
            font-weight: bold;
            color: #7F5539;
            white-space: nowrap;
        }
        .text-muted { color: #999; }
        .watermark {
            position: fixed;
            bottom: 40px;
            right: 40px;
            opacity: 0.05;
            font-size: 48px;
            font-weight: bold;
            color: #7F5539;
            transform: rotate(-20deg);
            pointer-events: none;
        }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 7px;
            font-size: 8pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .badge-green   { background: #d1fae5; color: #065f46; }
        .badge-red     { background: #fee2e2; color: #991b1b; }
        .badge-orange  { background: #fef3c7; color: #92400e; }
        .badge-purple  { background: #ede9fe; color: #5b21b6; }
        .badge-blue    { background: #dbeafe; color: #1e40af; }
        .badge-gray    { background: #f3f4f6; color: #4b5563; }
        .badge-emerald { background: #d1fae5; color: #065f46; }

        /* ── Section title for table groupings ── */
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #fff;
            padding: 4px 7px;
            border-radius: 4px 4px 0 0;
            margin-top: 10px;
        }
        .section-title.brown { background: #7F5539; }
        .section-title.green { background: #166534; }
        .section-title.red   { background: #991b1b; }

        /* ── Flat transaction ledger table (one row per item) ── */
        .ledger-table,
        .summary-table {
            table-layout: auto;
        }
        .ledger-table td { font-size: 8pt; }
        .ledger-txn-no {
            font-weight: 700;
            color: #7F5539;
            font-size: 7.5pt;
            white-space: nowrap;
            word-wrap: normal;
            overflow-wrap: normal;
            overflow: hidden;
            text-overflow: clip;
        }
        .ledger-out {
            color: #dc2626;
            font-weight: 700;
        }
        .ledger-in {
            color: #16a34a;
            font-weight: 700;
        }
        .ledger-dash {
            color: #ccc;
        }
        .mto-subrow td {
            background: #faf5ff;
        }
        .no-data {
            padding: 10px;
            text-align: center;
            color: #aaa;
            font-style: italic;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 4px 4px;
            background: #fafafa;
        }

        /* ── Branch group ── */
        .branch-group {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .branch-group-title {
            font-size: 12pt;
            font-weight: 700;
            color: #7F5539;
            padding: 5px 0 6px;
            border-bottom: 2px solid #7F5539;
            margin-bottom: 10px;
        }
        .txn-block { margin-bottom: 10px; }

        @media print {
            body { padding: 0; }
            .branch-group { page-break-inside: avoid; }
            .txn-block { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="watermark">LINKUD HUB</div>

<div class="header">
    <h1>Inventory Report</h1>
    <div class="subtitle">{{ $company_name ?? 'Linkud Hub' }}</div>
    @php
        $headerBranchName = null;
        if (!empty($branch)) {
            $headerBranchName = is_array($branch) ? ($branch['branch_name'] ?? null) : ($branch->branch_name ?? null);
        }
    @endphp
    <div class="date-range">
        Period: {{ $date_from }} - {{ $date_to }}
        @if($headerBranchName)
            | Branch: {{ $headerBranchName }}
        @else
            | All Branches
        @endif
    </div>
    <div class="generated-by">
        <strong>Generated By:</strong> {{ $generated_by ?? 'System' }}
        <span style="color: #999; font-weight: normal;">|</span>
        <strong>Email:</strong> {{ $generated_by_email ?? 'system@linkudhub.com' }}
    </div>
    <div class="date-range" style="font-size: 8pt; color: #aaa; margin-top: 2px;">
        Generated: {{ $generated_at }}
    </div>
</div>

{{-- Summary Cards --}}
@php
    $totalBeginning = $inventoryData['total_beginning_balance'] ?? 0;
    $totalStockIn   = $inventoryData['total_stock_in'] ?? 0;
    $totalStockOut  = $inventoryData['total_stock_out'] ?? 0;
    $totalEnding    = $inventoryData['total_ending_balance'] ?? 0;
    $netMovement    = $totalStockIn - $totalStockOut;
    $netColor       = $netMovement >= 0 ? '#16a34a' : '#dc2626';
@endphp

<div class="summary-cards">
    <div class="card">
        <div class="label">Net Movement</div>
        <div class="value" style="color: {{ $netColor }};">
            {{ ($netMovement >= 0 ? '+' : '-') . number_format(abs($netMovement)) }}
        </div>
    </div>
    <div class="card">
        <div class="label">Total Stock In</div>
        <div class="value" style="color: #16a34a;">+{{ number_format($totalStockIn) }}</div>
    </div>
    <div class="card">
        <div class="label">Total Stock Out</div>
        <div class="value" style="color: #dc2626;">-{{ number_format($totalStockOut) }}</div>
    </div>
    <div class="card">
        <div class="label">Ending Balance</div>
        <div class="value" style="color: #2563eb;">{{ number_format($totalEnding) }}</div>
    </div>
</div>

{{-- Inventory by Branch Table --}}
<div style="margin-bottom: 16px;">
    <div class="section-title brown">Branch Summary</div>
    <table class="summary-table">
        <colgroup>
            <col style="width: 23%;">
            <col style="width: 9%;">
            <col style="width: 7%;">
            <col style="width: 5%;">
            <col style="width: 9%;">
            <col style="width: 7%;">
            <col style="width: 11%;">
            <col style="width: 10%;">
            <col style="width: 8%;">
            <col style="width: 11%;">
        </colgroup>
        <thead>
            <tr>
                <th>Branch</th>
                <th>Beginning</th>
                <th>Stock In</th>
                <th>Sold</th>
                <th>Damaged</th>
                <th>Expired</th>
                <th>Pulled Out</th>
                <th>Total Out</th>
                <th>Ending</th>
                <th>Net</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventoryData['by_branch'] ?? [] as $branchItem)
                @php
                    $net = ($branchItem['total_stock_in'] ?? 0) - ($branchItem['total_stock_out'] ?? 0);
                    $netClass = $net >= 0 ? 'style="color: #16a34a;"' : 'style="color: #dc2626;"';
                @endphp
                <tr>
                    <td class="text-left"><span class="branch-name">{{ $branchItem['branch_name'] }}</span></td>
                    <td>{{ number_format($branchItem['beginning_balance'] ?? 0) }}</td>
                    <td style="color: #16a34a;">+{{ number_format($branchItem['total_stock_in'] ?? 0) }}</td>
                    <td>{{ number_format($branchItem['total_sold'] ?? 0) }}</td>
                    <td style="color: #d97706;">{{ number_format($branchItem['total_damaged'] ?? 0) }}</td>
                    <td style="color: #dc2626;">{{ number_format($branchItem['total_expired'] ?? 0) }}</td>
                    <td style="color: #9333ea;">{{ number_format($branchItem['total_pulled_out'] ?? 0) }}</td>
                    <td style="color: #dc2626; font-weight: bold;">-{{ number_format($branchItem['total_stock_out'] ?? 0) }}</td>
                    <td style="color: #2563eb; font-weight: bold;">{{ number_format($branchItem['ending_balance'] ?? 0) }}</td>
                    <td {!! $netClass !!}>{{ ($net >= 0 ? '+' : '-') . number_format(abs($net)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted" style="padding: 16px;">No data available for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($inventoryData['by_branch'] ?? []) > 0)
            <tfoot>
                <tr class="grand-total">
                    <td class="text-left">GRAND TOTAL</td>
                    <td>{{ number_format($inventoryData['total_beginning_balance'] ?? 0) }}</td>
                    <td style="color: #16a34a;">+{{ number_format($inventoryData['total_stock_in'] ?? 0) }}</td>
                    <td>{{ number_format($inventoryData['by_branch']->sum('total_sold')) }}</td>
                    <td style="color: #d97706;">{{ number_format($inventoryData['by_branch']->sum('total_damaged')) }}</td>
                    <td style="color: #dc2626;">{{ number_format($inventoryData['by_branch']->sum('total_expired')) }}</td>
                    <td style="color: #9333ea;">{{ number_format($inventoryData['by_branch']->sum('total_pulled_out')) }}</td>
                    <td style="color: #dc2626; font-weight: bold;">-{{ number_format($inventoryData['total_stock_out'] ?? 0) }}</td>
                    <td style="color: #2563eb; font-weight: bold;">{{ number_format($inventoryData['total_ending_balance'] ?? 0) }}</td>
                    <td style="color: {{ $netMovement >= 0 ? '#16a34a' : '#dc2626' }}; font-weight: bold;">
                        {{ ($netMovement >= 0 ? '+' : '-') . number_format(abs($netMovement)) }}
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

    // Human-readable labels for stock-out reasons (used_in_mto -> "MTO Ingredient")
    $reasonLabels = [
        'expired'     => 'Expired',
        'damaged'     => 'Damaged',
        'pulled_out'  => 'Pulled out',
        'sold'        => 'Sold',
        'used_in_mto' => 'MTO Ingredient',
    ];
@endphp

@foreach($branchesArray as $branchRow)
    @php
        $branchName = $branchRow['branch_name'] ?? '';

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
                        <colgroup>
                            <col style="width: 30%;">
                            <col style="width: 10%;">
                            <col style="width: 6%;">
                            <col style="width: 12%;">
                            <col style="width: 8%;">
                            <col style="width: 18%;">
                            <col style="width: 8%;">
                            <col style="width: 8%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Transaction No.</th>
                                <th>Date and Time</th>
                                <th>Total Qty</th>
                                <th>Processed By</th>
                                <th>Item Type</th>
                                <th>Item Name</th>
                                <th>Qty</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchTxnsIn as $txn)
                                @php
                                    $txnItems = array_values(array_filter($branchItemsIn, function($i) use ($txn) {
                                        return ($i['transaction_no'] ?? '') === $txn['transaction_no'];
                                    }));
                                @endphp
                                @if(count($txnItems) > 0)
                                    @foreach($txnItems as $idx => $item)
                                        @php $isFirstRow = $idx === 0; @endphp
                                        <tr>
                                            <td class="ledger-txn-no">{{ $isFirstRow ? $txn['transaction_no'] : '-' }}</td>
                                            <td class="{{ $isFirstRow ? '' : 'ledger-dash' }}">
                                                @if($isFirstRow)
                                                    {{ \Carbon\Carbon::parse($txn['created_at'])->format('M d, Y') }}<br>{{ \Carbon\Carbon::parse($txn['created_at'])->format('h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="{{ $isFirstRow ? 'ledger-in' : 'ledger-dash' }}">{{ $isFirstRow ? '+' . ($txn['total_quantity'] ?? 0) : '-' }}</td>
                                            <td class="{{ $isFirstRow ? '' : 'ledger-dash' }}">{{ $isFirstRow ? ($txn['processed_by'] ?? '-') : '-' }}</td>
                                            <td>
                                                <span class="badge {{ $item['item_type'] === 'ingredient' ? 'badge-purple' : 'badge-blue' }}">
                                                    {{ $item['item_type'] === 'ingredient' ? 'Ingredient' : 'Product' }}
                                                </span>
                                            </td>
                                            <td>{{ $item['item_name'] ?? '-' }}</td>
                                            <td class="ledger-in">+{{ $item['quantity'] ?? 0 }}{{ $item['unit'] ? ' ' . $item['unit'] : '' }}</td>
                                            <td>-</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="ledger-txn-no">{{ $txn['transaction_no'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($txn['created_at'])->format('M d, Y') }}<br>{{ \Carbon\Carbon::parse($txn['created_at'])->format('h:i A') }}</td>
                                        <td class="ledger-in">+{{ $txn['total_quantity'] ?? 0 }}</td>
                                        <td>{{ $txn['processed_by'] ?? '-' }}</td>
                                        <td colspan="4" class="text-muted">No item details available</td>
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
                        <colgroup>
                            <col style="width: 30%;">
                            <col style="width: 10%;">
                            <col style="width: 6%;">
                            <col style="width: 12%;">
                            <col style="width: 8%;">
                            <col style="width: 14%;">
                            <col style="width: 8%;">
                            <col style="width: 12%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Transaction No.</th>
                                <th>Date and Time</th>
                                <th>Total Qty</th>
                                <th>Processed By</th>
                                <th>Item Type</th>
                                <th>Item Name</th>
                                <th>Qty</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchTxnsOut as $txn)
                                @php
                                    $txnItems = array_values(array_filter($branchItemsOut, function($i) use ($txn) {
                                        return ($i['transaction_no'] ?? '') === $txn['transaction_no'];
                                    }));

                                    // MTO ingredient consumption rows are pulled out and re-appended
                                    // as sub-rows underneath the product/other rows of the same transaction.
                                    $mtoIngredientItems = array_values(array_filter($txnItems, function($i) {
                                        return ($i['item_type'] ?? '') === 'ingredient' && ($i['reason'] ?? '') === 'used_in_mto';
                                    }));
                                    $mainItems = array_values(array_filter($txnItems, function($i) {
                                        return !(($i['item_type'] ?? '') === 'ingredient' && ($i['reason'] ?? '') === 'used_in_mto');
                                    }));
                                    $isMtoTxn = count($mtoIngredientItems) > 0;
                                    $orderedItems = array_merge($mainItems, $mtoIngredientItems);

                                    // Recompute the displayed Total Qty for this transaction as a COUNT
                                    // of line items, not a sum of quantities — ingredients are measured
                                    // in mixed units (g, pcs, etc.) so their quantity values aren't
                                    // meaningful to add together. Each included line (regular RTD/packaged
                                    // product, any ingredient, or an MTO-consumed ingredient) counts as 1
                                    // "used" item, e.g. Plastic Cup -1, Coffee Powder -1, Plastic Straw -1.
                                    // MTO drinks (product rows measured in "cup") are excluded — they're
                                    // what triggered the ingredient consumption, not a distinct
                                    // stocked-out item. (MTO products still carry a normal reason like
                                    // "Sold", so a missing-reason check won't catch them — unit is the
                                    // reliable signal.)
                                    $displayTotalQty = count(array_filter($orderedItems, function ($i) {
                                        $isMtoProductLine = ($i['item_type'] ?? '') === 'product' && ($i['unit'] ?? '') === 'cup';
                                        return !$isMtoProductLine;
                                    }));
                                @endphp
                                @if(count($orderedItems) > 0)
                                    @foreach($orderedItems as $idx => $item)
                                        @php
                                            $isFirstRow = $idx === 0;
                                            $isMtoIngredientRow = ($item['item_type'] ?? '') === 'ingredient' && ($item['reason'] ?? '') === 'used_in_mto';
                                            // Only the actual MTO drink row (product measured in "cup")
                                            // gets its reason hidden — other products in the same
                                            // transaction (e.g. plain RTD items) keep their real reason.
                                            $isMtoProductRow = ($item['item_type'] ?? '') === 'product' && ($item['unit'] ?? '') === 'cup';
                                            $reasonLabel = $reasonLabels[$item['reason'] ?? ''] ?? ucfirst(str_replace('_', ' ', $item['reason'] ?? ''));
                                        @endphp
                                        <tr @if($isMtoIngredientRow) class="mto-subrow" @endif>
                                            <td class="ledger-txn-no">{{ $isFirstRow ? $txn['transaction_no'] : '-' }}</td>
                                            <td class="{{ $isFirstRow ? '' : 'ledger-dash' }}">
                                                @if($isFirstRow)
                                                    {{ \Carbon\Carbon::parse($txn['created_at'])->format('M d, Y') }}<br>{{ \Carbon\Carbon::parse($txn['created_at'])->format('h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="{{ $isFirstRow ? 'ledger-out' : 'ledger-dash' }}">{{ $isFirstRow ? '-' . $displayTotalQty : '-' }}</td>
                                            <td class="{{ $isFirstRow ? '' : 'ledger-dash' }}">{{ $isFirstRow ? ($txn['processed_by'] ?? '-') : '-' }}</td>
                                            <td>
                                                <span class="badge {{ $item['item_type'] === 'ingredient' ? 'badge-purple' : 'badge-blue' }}">
                                                    {{ $item['item_type'] === 'ingredient' ? 'Ingredient' : 'Product' }}
                                                </span>
                                            </td>
                                            <td>{{ $isMtoIngredientRow ? ($item['item_name'] ?? '-') : ($item['item_name'] ?? '-') }}</td>
                                            <td class="ledger-out">-{{ $item['quantity'] ?? 0 }}{{ $item['unit'] ? ' ' . $item['unit'] : '' }}</td>
                                            <td>
                                                @if($isMtoProductRow)
                                                    -
                                                @elseif($isMtoIngredientRow)
                                                    <span class="badge badge-purple">{{ $reasonLabel }}</span>
                                                @elseif(!empty($item['reason']))
                                                    <span class="badge badge-gray">{{ $reasonLabel }}</span>
                                                @else
                                                    <span style="color: #b45309; font-weight: 600;">Not specified</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="ledger-txn-no">{{ $txn['transaction_no'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($txn['created_at'])->format('M d, Y') }}<br>{{ \Carbon\Carbon::parse($txn['created_at'])->format('h:i A') }}</td>
                                        <td class="ledger-out">-{{ $txn['total_quantity'] ?? 0 }}</td>
                                        <td>{{ $txn['processed_by'] ?? '-' }}</td>
                                        <td colspan="4" class="text-muted">No item details available</td>
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