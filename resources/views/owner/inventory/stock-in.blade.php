@extends('layouts.app')

@section('title', 'Stock In')

@section('content')
    <div x-data="stockInData()" x-init="init()" class="p-4 max-w-3xl mx-auto">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('sub_one.inventory.index') }}"
                class="inline-flex items-center text-sm font-medium text-[#7F5539] hover:text-[#4A2C1D]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-4 h-4 mr-1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Inventory
            </a>
            <h1 class="text-2xl font-bold text-gray-900 text-center">Stock In</h1>
            <div class="w-32"></div> {{-- spacer to balance the back link --}}
        </div>

        {{-- Branch Toggle Tabs (no "All Branches" — defaults to Claveria) --}}
        <div class="mb-4 p-3 bg-white rounded-lg shadow-sm" style="border: 1px solid #e6ddd4;">
            <div class="flex rounded-lg p-1 w-full" style="background-color: #e6ddd4; border: 1px solid #d4c4b2;">
                <template x-for="branch in branches" :key="branch.id">
                    <button
                        @click="selectBranch(branch.id)"
                        class="flex-1 relative transition-all duration-200 py-2 px-4 rounded-md text-sm font-medium focus:outline-none truncate"
                        :style="selectedBranchId == branch.id
                            ? 'background-color: #9c6644; color: #fff;'
                            : 'background-color: transparent; color: #7f5539;'"
                        x-text="branch.branch_name">
                    </button>
                </template>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">New Stock In Transaction</h3>
                <p class="text-sm text-gray-500 mt-0.5">Add multiple items in one transaction</p>
            </div>

            <div class="px-6 py-3 bg-blue-50 border-b border-blue-100">
                <p class="text-sm text-blue-700">All items below will be saved under one transaction reference number.</p>
            </div>

            <div class="px-4 sm:px-6 py-4">
                <div class="space-y-3">
                    <template x-for="(item, index) in stockInItems" :key="index">
                        <div class="relative p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-3">

                            {{-- Remove --}}
                            <button @click.stop="removeStockInItem(index)"
                                :class="stockInItems.length > 1 ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
                                class="absolute top-3 right-3 z-10 text-red-400 hover:text-red-600 p-1.5 rounded-full hover:bg-red-50 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>

                            {{-- Item Type Toggle --}}
                            <div>
                                <label class="block text-sm font-medium text-[#4A2C1D] mb-1">Item Type</label>
                                <div class="flex rounded-lg border-2 border-[#7F5539] overflow-hidden">
                                    <button type="button"
                                        @click="item.item_type = 'product'; item.ingredient_id = '';"
                                        class="flex-1 py-2 text-sm font-medium transition-colors"
                                        :class="item.item_type === 'product' ? 'bg-[#7F5539] text-white' : 'bg-white text-[#7F5539] hover:bg-[#7F5539]/5'">
                                        Product
                                    </button>
                                    <button type="button"
                                        @click="item.item_type = 'ingredient'; item.product_id = '';"
                                        class="flex-1 py-2 text-sm font-medium transition-colors border-l-2 border-[#7F5539]"
                                        :class="item.item_type === 'ingredient' ? 'bg-[#7F5539] text-white' : 'bg-white text-[#7F5539] hover:bg-[#7F5539]/5'">
                                        Ingredient
                                    </button>
                                </div>
                            </div>

                            {{-- Product selector --}}
                            <template x-if="item.item_type === 'product'">
                                <div x-data="{
                                    open: false,
                                    get selectedName() {
                                        if (!item.product_id) return 'Select a product';
                                        const products = {{ Js::from($products->map->only(['id', 'product_name'])) }};
                                        const p = products.find(p => p.id == item.product_id);
                                        return p ? p.product_name : 'Select a product';
                                    },
                                    select(id) { item.product_id = id; this.open = false; }
                                }" class="relative">
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Product</label>
                                    <button @click="open = !open" @click.away="open = false" type="button"
                                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                        <span x-text="selectedName" :class="{ 'text-gray-500': !item.product_id }"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500" :class="{ 'rotate-180': open }"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                    </button>
                                    <div x-show="open" x-transition class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto" style="display:none;">
                                        @forelse ($products as $product)
                                            <a href="#" @click.prevent="select({{ $product->id }})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ $product->product_name }}</a>
                                        @empty
                                            <span class="block px-4 py-2 text-sm text-gray-500">No products available</span>
                                        @endforelse
                                    </div>
                                </div>
                            </template>

                            {{-- Ingredient selector --}}
                            <template x-if="item.item_type === 'ingredient'">
                                <div x-data="{
                                    open: false,
                                    get selectedName() {
                                        if (!item.ingredient_id) return 'Select an ingredient';
                                        const ingredients = {{ Js::from($ingredients->map->only(['id', 'ingredient_name', 'unit'])) }};
                                        const i = ingredients.find(i => i.id == item.ingredient_id);
                                        return i ? i.ingredient_name + ' (' + i.unit + ')' : 'Select an ingredient';
                                    },
                                    select(id) { item.ingredient_id = id; this.open = false; }
                                }" class="relative">
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Ingredient</label>
                                    <button @click="open = !open" @click.away="open = false" type="button"
                                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-left bg-white">
                                        <span x-text="selectedName" :class="{ 'text-gray-500': !item.ingredient_id }"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500" :class="{ 'rotate-180': open }"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                    </button>
                                    <div x-show="open" x-transition class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto" style="display:none;">
                                        @forelse ($ingredients as $ingredient)
                                            <a href="#" @click.prevent="select({{ $ingredient->id }})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                {{ $ingredient->ingredient_name }} <span class="text-gray-400 text-xs">({{ $ingredient->unit }})</span>
                                            </a>
                                        @empty
                                            <span class="block px-4 py-2 text-sm text-gray-500">No ingredients available</span>
                                        @endforelse
                                    </div>
                                </div>
                            </template>

                            {{-- Qty + Note --}}
                            <div class="flex gap-3 items-end">
                                <div class="w-32 flex-shrink-0">
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Qty received</label>
                                    <input type="number" x-model.number="item.quantity" min="1" class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-sm">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-sm font-medium text-[#4A2C1D]">Note (optional)</label>
                                    <input type="text" x-model="item.note" placeholder="e.g. batch A" class="w-full border-2 border-[#7F5539] rounded px-3 py-2 mt-1 text-sm">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <button @click="addStockInItem()"
                    class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2 border-2 border-dashed border-[#7F5539] rounded-lg text-sm font-medium text-[#7F5539] hover:bg-[#7F5539]/5 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add another item
                </button>
            </div>

            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-white">
                <a href="{{ route('sub_one.inventory.index') }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button @click="submitStockIn()" :disabled="isSubmitting"
                    class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-[#7F5539] hover:bg-[#4A2C1D] disabled:opacity-50 transition-colors">
                    <span x-text="isSubmitting ? 'Saving...' : 'Confirm'"></span>
                </button>
            </div>
        </div>

        {{-- TOAST NOTIFICATION --}}
        <div x-show="showToast" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed bottom-6 right-6 z-[10000] max-w-sm w-full sm:w-auto">
            <div class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium"
                :class="toastType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
                <svg x-show="toastType === 'success'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg x-show="toastType === 'error'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="toastMessage"></span>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('stockInData', () => ({

                branches: @json($branches->map->only(['id', 'branch_name'])->values() ?? []),
                defaultBranchId: {{ $defaultBranch->id ?? 'null' }},
                selectedBranchId: {{ $defaultBranch->id ?? 'null' }},

                isSubmitting: false,

                showToast:    false,
                toastMessage: '',
                toastType:    'success',
                toastTimer:   null,

                stockInItems: [{ item_type: 'product', branch_id: null, product_id: '', ingredient_id: '', quantity: 1, note: '' }],

                init() {
                    // Base row always uses the selected branch (defaults to Claveria)
                    this.stockInItems = [{
                        item_type:     'product',
                        branch_id:     this.selectedBranchId,
                        product_id:    '',
                        ingredient_id: '',
                        quantity:      1,
                        note:          '',
                    }];

                    // Pre-fill from a Restock link on the Stock Levels page
                    // (?item_type=product|ingredient&item_id=123)
                    const params   = new URLSearchParams(window.location.search);
                    const itemType = params.get('item_type');
                    const itemId   = params.get('item_id');

                    if (itemType && itemId && (itemType === 'product' || itemType === 'ingredient')) {
                        this.stockInItems[0].item_type = itemType;
                        if (itemType === 'product') {
                            this.stockInItems[0].product_id = Number(itemId);
                        } else {
                            this.stockInItems[0].ingredient_id = Number(itemId);
                        }
                    }
                },

                // Switching branch applies to all rows in this transaction —
                // a single Stock In transaction is scoped to one branch.
                selectBranch(id) {
                    this.selectedBranchId = id;
                    this.stockInItems.forEach(item => { item.branch_id = id; });
                },

                addStockInItem() {
                    this.stockInItems.push({
                        item_type:     'product',
                        branch_id:     this.selectedBranchId,
                        product_id:    '',
                        ingredient_id: '',
                        quantity:      1,
                        note:          '',
                    });
                },
                removeStockInItem(i) { this.stockInItems.splice(i, 1); },

                showToastMsg(message, type = 'success') {
                    this.toastMessage = message;
                    this.toastType = type;
                    this.showToast = true;
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => { this.showToast = false; }, 3500);
                },

                async submitStockIn() {
                    if (this.isSubmitting) return;

                    const invalid = this.stockInItems.some(i => {
                        if (!i.branch_id || i.quantity < 1) return true;
                        if (i.item_type === 'product'    && !i.product_id)    return true;
                        if (i.item_type === 'ingredient' && !i.ingredient_id) return true;
                        return false;
                    });
                    if (invalid) { alert('Please fill in all required fields for each item.'); return; }

                    this.isSubmitting = true;
                    try {
                        const response = await fetch('{{ route('sub_one.inventory.stockIn') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({
                                products: this.stockInItems.map(i => ({
                                    branch_id:     i.branch_id,
                                    item_type:     i.item_type,
                                    product_id:    i.item_type === 'product'    ? i.product_id    : null,
                                    ingredient_id: i.item_type === 'ingredient' ? i.ingredient_id : null,
                                    quantity:      i.quantity,
                                    note:          i.note,
                                }))
                            }),
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.showToastMsg('Stock in saved. Inventory updated.', 'success');
                            setTimeout(() => {
                                window.location.href = '{{ route('sub_one.inventory.index') }}';
                            }, 900);
                        }
                        else throw new Error(data.message || 'Failed to save stock in');
                    } catch (e) { this.showToastMsg(e.message || 'Failed to save. Please try again.', 'error');
                    } finally { this.isSubmitting = false; }
                },

            }));
        });
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
@endsection