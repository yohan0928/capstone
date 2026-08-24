@props(['ingredient', 'type'])

<div x-data="{ deleteModal: false }" class="relative bg-white border border-[#4A2C1D] rounded-2xl p-4 flex flex-col">

    {{-- ========== IMAGE SECTION ========== --}}
    <div class="relative w-full h-48 bg-gray-100 rounded-xl overflow-hidden mb-4">
        <button
            @click.prevent="$dispatch('open-image-modal', {
                images: ['{{ $ingredient->ingredient?->ingredient_img ?? $ingredient->ingredient_img }}'],
                index: 0
            })"
            class="w-full h-full relative z-20 group/image">

            {{-- Safe image with fallback --}}
            <img src="{{ $ingredient->ingredient?->ingredient_img
                ? asset('/storage/app/public/' . $ingredient->ingredient->ingredient_img)
                : ($ingredient->ingredient_img
                    ? asset('/storage/app/public/' . $ingredient->ingredient_img)
                    : 'https://ui-avatars.com/api/?name=' .
                        urlencode($ingredient->ingredient?->ingredient_name ?? ($ingredient->ingredient_name ?? 'N/A')) .
                        '&background=7F5539&color=FFFFFF') }}"
                alt="{{ $ingredient->ingredient?->ingredient_name ?? ($ingredient->ingredient_name ?? 'N/A') }}"
                class="w-full h-full object-cover rounded-xl" />

            @if (!empty($ingredient->ingredient?->ingredient_img ?? $ingredient->ingredient_img))
                <div
                    class="absolute inset-0 bg-black bg-opacity-0 group-hover/image:bg-opacity-40
                            transition-all duration-300 flex items-center justify-center opacity-0
                            group-hover/image:opacity-100 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-10 h-10 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                    </svg>
                </div>
            @endif
        </button>

        {{-- Reactivate button (archived only) --}}
        @if ($type === 'archived')
            <div class="absolute top-3 right-3 bg-white/80 backdrop-blur-sm rounded-full p-1 shadow z-20">
                <button @click.prevent="deleteModal = true"
                    class="relative group p-1.5 text-[#4A2C1D]
                               hover:text-white hover:bg-green-600 rounded-full transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0
                                 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1
                                 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span
                        class="absolute right-full top-1/2 -translate-y-1/2 mr-2 bg-gray-800 text-white
                                 text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100
                                 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                        Reactivate Ingredient
                    </span>
                </button>
            </div>
        @endif
    </div>

    {{-- ========== INFO SECTION ========== --}}
    <div class="flex flex-col space-y-2 mb-4">
        <p class="text-xs font-mono text-gray-500" title="Branch Name">
            {{ $ingredient->branch->branch_name ?? ($ingredient->ingredient?->branch->branch_name ?? 'N/A') }}
        </p>

        {{-- ✅ Show the correct PBN depending on context --}}
        <p class="text-xs font-mono text-gray-500" title="Batch Number">
            {{ $type === 'damaged'
                ? $ingredient->ingredient?->ingredient_batch_no ?? 'N/A'
                : $ingredient->ingredient_batch_no ?? 'N/A' }}
        </p>

        <div class="mb-4">
            <div class="flex items-start justify-between gap-x-4">
                <h2 class="flex-1 min-w-0 text-xl font-bold text-[#4A2C1D] break-words">
                    {{ $ingredient->ingredient?->ingredient_name ?? ($ingredient->ingredient_name ?? 'N/A') }}
                </h2>

                @if ($type === 'archived')
                    <span
                        class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full
                                 {{ $ingredient->ingredient_status == 1 ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                        {{ $ingredient->ingredient_status == 1 ? 'Available' : 'Unavailable' }}
                    </span>
                @elseif ($type === 'expired')
                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full bg-red-200 text-red-800">
                        Expired
                    </span>
                @elseif ($type === 'damaged')
                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full bg-red-200 text-red-800">
                        Damaged
                    </span>
                @endif
            </div>

            <span
                class="inline-flex items-center justify-center bg-[#7F5539] text-white
                         text-xs font-medium px-2.5 py-1 rounded-full mt-2">
                {{ $ingredient->ingredient?->ingredient_type ?? ($ingredient->ingredient_type ?? 'N/A') }}
            </span>
        </div>

        {{-- Quantity & extra info --}}
        @if ($type === 'archived')
            <div class="flex items-center gap-2 text-gray-600">
                <span class="font-semibold text-[#4A2C1D]">Stock Quantity:</span>
                <span class="text-[#7F5539] font-mono">
                    {{ number_format($ingredient->stock_quantity_in ?? 0) }} {{ $ingredient->unit ?? '' }}
                </span>
                @if (($ingredient->stock_quantity_in ?? 0) <= ($ingredient->stock_quantity_threshold ?? 0))
                    <span class="inline-block bg-red-600 text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                        Low Level
                    </span>
                @else
                    <span class="text-sm text-[#7F5539]">
                        (Threshold: {{ number_format($ingredient->stock_quantity_threshold ?? 0) }}
                        {{ $ingredient->unit ?? '' }})
                    </span>
                @endif
            </div>
            @if ($ingredient->date_expiration)
                <div class="flex items-center gap-2 text-gray-600">
                    <span class="font-semibold text-[#4A2C1D]">Expiration Date:</span>
                    <span class="text-[#7F5539] font-mono">
                        {{ \Carbon\Carbon::parse($ingredient->date_expiration)->format('M d, Y, g:i A') }}
                    </span>
                </div>
            @endif
        @elseif ($type === 'expired')
            <div class="flex items-center gap-2 text-gray-600">
                <span class="font-semibold text-[#4A2C1D]">Date Expired:</span>
                <span class="text-[#7F5539] font-mono">
                    {{ \Carbon\Carbon::parse($ingredient->date_expiration)->format('M d, Y, g:i A') }}
                </span>
            </div>
        @elseif ($type === 'damaged')
            <div class="flex items-center gap-2 text-gray-600">
                <span class="font-semibold text-[#4A2C1D]">Quantity Out:</span>
                <span class="text-[#7F5539] font-mono">
                    {{ number_format($ingredient->stock_quantity_out ?? 0) }}
                </span>
            </div>
            <div class="flex items-center gap-2 text-gray-600">
                <span class="font-semibold text-[#4A2C1D]">Reason:</span>
                <span class="text-[#7F5539] font-mono">{{ $ingredient->reasons ?? 'N/A' }}</span>
            </div>
            <div class="flex items-center gap-2 text-gray-600">
                <span class="font-semibold text-[#4A2C1D]">Date Damaged:</span>
                <span class="text-[#7F5539] font-mono">
                    {{ \Carbon\Carbon::parse($ingredient->date_damaged)->format('M d, Y, g:i A') }}
                </span>
            </div>
        @endif
    </div>

    {{-- ======= FOOTER ======= --}}
    <div class="border-t border-gray-100 pt-2 text-xs text-gray-500 space-y-1">
        @if ($ingredient->created_by && $ingredient->date_created)
            <div class="flex justify-between">
                <span>Created By: {{ $ingredient->creator->first_name ?? '' }}
                    {{ $ingredient->creator->last_name ?? '' }}</span>
                <span>{{ \Carbon\Carbon::parse($ingredient->date_created)->format('M d, Y, g:i A') }}</span>
            </div>
        @endif
        @if ($ingredient->last_updated_by && $ingredient->last_date_updated)
            <div class="flex justify-between">
                <span>Last Updated By: {{ $ingredient->last_updator->first_name ?? '' }}
                    {{ $ingredient->last_updator->last_name ?? '' }}</span>
                <span>{{ \Carbon\Carbon::parse($ingredient->last_date_updated)->format('M d, Y, g:i A') }}</span>
            </div>
        @endif
    </div>

    {{-- Reactivate Modal --}}
    @if ($type === 'archived')
        <div x-show="deleteModal" x-transition
            class="absolute inset-0 bg-white/90 backdrop-blur-sm z-40 rounded-2xl flex flex-col p-4"
            style="display: none;">
            <form action="{{ route('sub_two.ingredients.reactivateIngredient', $ingredient->uuid) }}" method="POST"
                class="w-full flex flex-col h-full">
                @csrf
                @method('PATCH')
                <div class="flex-grow flex flex-col justify-center items-center text-center">
                    <h5 class="text-lg font-bold text-[#4A2C1D]">Confirm Reactivate</h5>
                    <p class="mt-2 text-sm text-gray-600">
                        Reactivate <strong class="text-[#4A2C1D]">
                            "{{ $ingredient->ingredient?->ingredient_name ?? ($ingredient->ingredient_name ?? 'N/A') }}"
                        </strong>?
                    </p>
                </div>
                <div class="flex justify-center gap-3 mt-4">
                    <button type="button" @click="deleteModal = false"
                        class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 w-full">
                        Cancel
                    </button>
                    <button type="submit"
                        class="w-full px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
