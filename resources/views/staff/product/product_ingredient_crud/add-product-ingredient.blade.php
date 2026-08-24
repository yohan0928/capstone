@extends('layouts.app')

@section('title', 'Add Ingredient')

@section('content')
    <div class="flex items-center justify-center p-4">
        <div class="relative bg-white shadow-md rounded-lg w-full max-w-2xl p-8 border border-[#4A2C1D]">

            <a href="javascript:history.back()"
                class="absolute top-3 right-3 text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1 z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                    class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>

            <h1 class="text-2xl font-bold text-[#4A2C1D] text-center mb-6">
                Add Ingredient
            </h1>

            @if ($errors->any())
                <div class="mb-6 text-red-700 bg-red-100 border border-red-300 rounded p-3">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('sub_two.product_ingredients.storeProductIngredient') }}" method="POST"
                enctype="multipart/form-data" class="space-y-6" x-data="ingredientCalculator({{ Js::from($ingredients) }})" x-init="computeConversion()">

                @csrf

                <input type="hidden" name="branch_id" value="{{ $branches }}">
                <input type="hidden" name="product_id" value="{{ $products->id }}">
                <input type="hidden" name="quantity_in_base_unit" :value="convertedQuantity">
                <input type="hidden" name="base_unit" value="g">

                <!-- Ingredient Dropdown -->
                <div class="relative">
                    <label class="block text-sm font-medium text-[#4A2C1D] mb-1">Select Ingredient</label>
                    <input type="hidden" name="ingredient_id" x-model="selectedIngredientId">
                    <button @click="openIngredient = !openIngredient; openUnit = false" @click.away="openIngredient = false"
                        type="button"
                        class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 text-left bg-white">
                        <span x-text="selectedIngredientName" :class="{ 'text-gray-500': !selectedIngredientId }"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-4 h-4 transition-transform duration-200 text-gray-500"
                            :class="{ 'rotate-180': openIngredient }">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div x-show="openIngredient" x-transition
                        class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                        style="display:none;">
                        @forelse($ingredients as $ingredient)
                            <a href="#" @click.prevent="selectIngredient({{ $ingredient->id }}); computeConversion()"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ $ingredient->ingredient_name }}</a>
                        @empty
                            <span class="block px-4 py-2 text-sm text-gray-500">No ingredients available</span>
                        @endforelse
                    </div>
                </div>

                <!-- Unit Dropdown -->
                <div class="flex gap-4 mt-4">
                    <div class="flex-1 relative">
                        <label class="block text-sm font-medium text-[#4A2C1D] mb-1">Unit</label>
                        <input type="hidden" name="unit" x-model="selectedUnit">
                        <button @click="openUnit=!openUnit; openIngredient=false" @click.away="openUnit=false"
                            type="button"
                            class="w-full flex justify-between items-center border-2 border-[#7F5539] rounded px-3 py-2 text-left bg-white">
                            <span x-text="selectedUnitName" :class="{ 'text-gray-500': !selectedUnit }"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-4 h-4 transition-transform duration-200 text-gray-500"
                                :class="{ 'rotate-180': openUnit }">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="openUnit" x-transition
                            class="absolute mt-1 w-full bg-white rounded-md shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto"
                            style="display:none;">
                            <template x-for="unit in availableUnits" :key="unit">
                                <a href="#" @click.prevent="selectUnit(unit); computeConversion()"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" x-text="unit"></a>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Quantity Needed -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-[#4A2C1D] mb-1">Quantity Needed</label>
                    <input type="number" name="quantity_needed" min="0" x-model.number="quantityNeeded"
                        @input="computeConversion()" class="w-full border-2 border-[#7F5539] rounded px-3 py-2"
                        placeholder="Enter quantity needed for this product">
                </div>

                <!-- Converted Quantity Display -->
                <div class="mt-4 p-4 border border-gray-200 rounded" x-show="convertedQuantity !== null">
                    <h2 class="font-bold mb-2" x-text="selectedIngredientName"></h2>
                    <p>Available: <span x-text="totalStockInBase + ' ' + baseUnit"></span></p>
                    <p>Quantity entered: <span x-text="quantityNeeded + ' ' + selectedUnit"></span></p>
                    <p>Converted to base unit: <span x-text="convertedQuantity + ' ' + baseUnit"></span></p>
                </div>

                <div class="mt-6">
                    <button type="submit"
                        class="bg-[#7F5539] text-white w-full px-4 py-2 rounded hover:bg-[#4A2C1D] transition-colors">
                        Add Ingredient
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function ingredientCalculator(ingredients) {
            return {
                selectedIngredientId: '',
                selectedUnit: '',
                quantityNeeded: 0,
                convertedQuantity: null,
                totalStockInBase: null,
                baseUnit: 'g',
                openIngredient: false,
                openUnit: false,
                units: ['g', 'kg', 'mg', 'lb', 'oz', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'pcs', 'pack'],

                get selectedIngredient() {
                    return ingredients.find(i => i.id == this.selectedIngredientId);
                },

                get selectedIngredientName() {
                    return this.selectedIngredient ? this.selectedIngredient.ingredient_name : 'Select Ingredient';
                },

                get selectedUnitName() {
                    return this.selectedUnit || 'Select Unit';
                },

                get availableUnits() {
                    // Use ingredient-specific conversion table if exists
                    return this.selectedIngredient && this.selectedIngredient.unit_conversions ?
                        Object.keys(this.selectedIngredient.unit_conversions) :
                        this.units;
                },

                selectIngredient(id) {
                    this.selectedIngredientId = id;
                    this.selectedUnit = '';
                    this.computeConversion();
                },

                selectUnit(unit) {
                    this.selectedUnit = unit;
                    this.computeConversion();
                },

                computeConversion() {
                    const ingredient = this.selectedIngredient;
                    if (!ingredient || !this.selectedUnit || !this.quantityNeeded) {
                        this.convertedQuantity = null;
                        this.totalStockInBase = null;
                        this.baseUnit = null;
                        return;
                    }

                    const conversions = ingredient.unit_conversions || {};

                    // Use ingredient.unit as the base unit
                    const baseUnit = ingredient.converted_unit ?? ingredient.unit; // fallback
                    this.baseUnit = baseUnit;

                    // Conversion factor: only if both selectedUnit and baseUnit exist in conversions
                    const factor = conversions[this.selectedUnit] ?? 1;

                    // Converted quantity
                    this.convertedQuantity = this.quantityNeeded * factor;

                    // Total stock in base unit: prefer converted_stock_quantity_in, fallback to stock_quantity_in
                    const stockQty = ingredient.converted_stock_quantity_in ?? ingredient.stock_quantity_in ?? 0;
                    this.totalStockInBase = stockQty;
                }
            }
        }
    </script>
@endsection
