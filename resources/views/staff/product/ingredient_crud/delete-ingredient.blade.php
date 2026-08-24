@extends('layouts.app')

@section('title', 'Ingredients')

@section('content')
    <div x-data="ingredientsPage()" x-init="init()" class="ingredients-wrapper p-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-[#4A2C1D]">Ingredients</h1>
                <p class="text-sm text-gray-500">Manage your ingredients here.</p>
            </div>
            <a href="{{ route('sub_two.ingredients.showIngredient') }}"
                class="text-sm font-medium text-[#7F5539] hover:underline mb-4 inline-block">
                Back to Ingredients
            </a>
        </div>

        <p class="p-4 bg-yellow-100 text-yellow-800 rounded mb-8 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <span class="flex-1 min-w-0 break-words">
                Ingredients archived, expired, or damaged in the last <b>30 days.</b>
            </span>
        </p>

        {{-- Tabs --}}
        <div class="flex space-x-4 mb-4 border-b border-gray-200">
            <button :class="tab === 'archived' ? 'border-b-2 border-[#7F5539] text-[#7F5539]' : 'text-gray-500'"
                class="py-2 px-4 font-semibold" @click="setTab('archived')">Archived</button>

            <button :class="tab === 'expired' ? 'border-b-2 border-[#7F5539] text-[#7F5539]' : 'text-gray-500'"
                class="py-2 px-4 font-semibold" @click="setTab('expired')">Expired</button>

            <button :class="tab === 'damaged' ? 'border-b-2 border-[#7F5539] text-[#7F5539]' : 'text-gray-500'"
                class="py-2 px-4 font-semibold" @click="setTab('damaged')">Damaged</button>
        </div>

        {{-- Tab Contents --}}
        <div>
            {{-- Archived --}}
            <div x-show="tab === 'archived'" x-cloak>
                @if ($archived_ingredients->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach ($archived_ingredients as $ingredient)
                            @include('staff.product.ingredient_crud.ingredient_card', [
                                'ingredient' => $ingredient,
                                'type' => 'archived',
                            ])
                        @endforeach
                    </div>
                    {{-- Archived --}}
                    {{ $archived_ingredients->appends(['tab' => request()->get('tab', 'archived')])->links() }}
                @else
                    <div class="p-4 bg-blue-100 text-blue-800 rounded">No archived ingredients found.</div>
                @endif
            </div>

            {{-- Expired --}}
            <div x-show="tab === 'expired'" x-cloak>
                @if ($expired_ingredients->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach ($expired_ingredients as $ingredient)
                            @include('staff.product.ingredient_crud.ingredient_card', [
                                'ingredient' => $ingredient,
                                'type' => 'expired',
                            ])
                        @endforeach
                    </div>
                    {{-- Expired --}}
                    {{ $expired_ingredients->appends(['tab' => request()->get('tab', 'expired')])->links() }}
                @else
                    <div class="p-4 bg-blue-100 text-blue-800 rounded">No expired ingredients found.</div>
                @endif
            </div>

            {{-- Damaged --}}
            <div x-show="tab === 'damaged'" x-cloak>
                @if ($damaged_ingredients->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach ($damaged_ingredients as $ingredient)
                            @include('staff.product.ingredient_crud.ingredient_card', [
                                'ingredient' => $ingredient,
                                'type' => 'damaged',
                            ])
                        @endforeach
                    </div>
                    {{-- Damaged --}}
                    {{ $damaged_ingredients->appends(['tab' => request()->get('tab', 'damaged')])->links() }}
                @else
                    <div class="p-4 bg-blue-100 text-blue-800 rounded">No damaged ingredients found.</div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function ingredientsPage() {
            return {
                tab: 'archived', // default tab

                init() {
                    const params = new URLSearchParams(window.location.search);
                    const urlTab = params.get('tab');

                    if (urlTab && ['archived', 'expired', 'damaged'].includes(urlTab)) {
                        this.tab = urlTab;
                    } else {
                        const savedTab = localStorage.getItem('ingredient_tab');
                        this.tab = savedTab || 'archived';
                    }
                },

                setTab(name) {
                    this.tab = name;
                    localStorage.setItem('ingredient_tab', name);

                    const url = new URL(window.location);
                    url.searchParams.set('tab', name);
                    url.searchParams.set(`${name}_page`, 1);
                    window.history.replaceState({}, '', url);
                }
            }
        }
    </script>

@endsection
