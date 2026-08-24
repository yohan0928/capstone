@extends('layouts.app')

@section('title', 'Products')

@section('content')

    <div class="p-4">


        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-[#4A2C1D]">Products</h1>
                <p class="text-sm text-gray-500">Manage your Products here.</p>
            </div>
            <a href="{{ route('sub_two.products.showProduct') }}"
                class="text-sm font-medium text-[#7F5539] hover:underline mb-4 inline-block">
                Back to Products
            </a>
        </div>

        <p class="p-4 bg-yellow-100 text-yellow-800 rounded mb-8 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                class="w-6 h-6 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <span class="flex-1 min-w-0 break-words">
                Products archived, expired, or damaged in the last <b>30 days.</b>
            </span>
        </p>


        {{-- Image Modal Alpine Wrapper --}}
        <div x-data="imageModal()" x-init="init()"
            @open-image-modal.window="open($event.detail.images, $event.detail.index)">
            <div x-data="{ tab: 'archived' }">
                {{-- Tabs --}}
                <div class="flex space-x-4 mb-4 border-b border-gray-200">
                    <button :class="tab === 'archived' ? 'border-b-2 border-[#7F5539] text-[#7F5539]' : 'text-gray-500'"
                        class="py-2 px-4 font-semibold" @click="tab = 'archived'">Archived</button>
                    <button :class="tab === 'expired' ? 'border-b-2 border-[#7F5539] text-[#7F5539]' : 'text-gray-500'"
                        class="py-2 px-4 font-semibold" @click="tab = 'expired'">Expired</button>
                    <button :class="tab === 'damaged' ? 'border-b-2 border-[#7F5539] text-[#7F5539]' : 'text-gray-500'"
                        class="py-2 px-4 font-semibold" @click="tab = 'damaged'">Damaged</button>
                </div>

                {{-- Tab Contents --}}
                <div>
                    {{-- Archived --}}
                    <div x-show="tab === 'archived'" x-cloak>
                        @if ($archived_products->count())
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                                @foreach ($archived_products as $product)
                                    @include('staff.product.product_crud.product_card', [
                                        'product' => $product,
                                        'type' => 'archived',
                                    ])
                                @endforeach
                            </div>
                            <div class="mt-4">{{ $archived_products->links() }}</div>
                        @else
                            <div class="p-4 bg-blue-100 text-blue-800 rounded">No archived products found.</div>
                        @endif
                    </div>

                    {{-- Expired --}}
                    <div x-show="tab === 'expired'" x-cloak>
                        @if ($expired_products->count())
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                                @foreach ($expired_products as $product)
                                    @include('staff.product.product_crud.product_card', [
                                        'product' => $product,
                                        'type' => 'expired',
                                    ])
                                @endforeach
                            </div>
                            <div class="mt-4">{{ $expired_products->links() }}</div>
                        @else
                            <div class="p-4 bg-blue-100 text-blue-800 rounded">No expired products found.</div>
                        @endif
                    </div>

                    {{-- Damaged --}}
                    <div x-show="tab === 'damaged'" x-cloak>
                        @if ($damaged_products->count())
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                                @foreach ($damaged_products as $product)
                                    @include('staff.product.product_crud.product_card', [
                                        'product' => $product,
                                        'type' => 'damaged',
                                    ])
                                @endforeach
                            </div>
                            <div class="mt-4">{{ $damaged_products->links() }}</div>
                        @else
                            <div class="p-4 bg-blue-100 text-blue-800 rounded">No damaged products found.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Image Modal --}}
            <div x-show="show" x-cloak @click.self="close"
                class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 p-4">
                <img :src="images[currentIndex]" class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg shadow-lg">
                <button id="closeModal" @click="close"
                    class="absolute top-4 right-4 p-2 bg-white/80 backdrop-blur-sm rounded-full hover:bg-white transition-colors z-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        function imageModal() {
            return {
                show: false,
                images: [],
                currentIndex: 0,
                open(imageList, index) {
                    if (!imageList || imageList.length === 0) return;
                    this.images = imageList.map(i => i.includes('http') ? i : '/storage/' + i);
                    this.currentIndex = index;
                    this.show = true;
                    document.body.style.overflow = 'hidden';
                },
                close() {
                    this.show = false;
                    document.body.style.overflow = 'auto';
                },
            }
        }
    </script>
@endsection
