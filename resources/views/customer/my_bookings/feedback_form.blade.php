@extends('layouts.app')

@section('title', 'Add Feedback')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-900">Add Feedback</h1>
            <p class="text-sm text-gray-600 mt-1">Share your experience for your completed booking</p>
        </div>

        <!-- Booking Info -->
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-700">Booking Reference</h3>
                    <p class="text-sm text-gray-900 font-semibold">{{ $booking->booking_ref_no }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700">Service</h3>
                    <p class="text-sm text-gray-900">{{ $booking->serviceName->service_name }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700">Branch</h3>
                    <p class="text-sm text-gray-900">{{ $booking->branch->branch_name }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700">Booking Date</h3>
                    <p class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($booking->booking_date)->format('M j, Y') }}</p>
                </div>
            </div>
        </div>

        @if($existingFeedback)
            <!-- Already Submitted Feedback -->
            <div class="p-6">
                <div class="text-center py-8">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Feedback Already Submitted</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        You have already submitted feedback for this booking. Thank you for sharing your experience!
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('sub_three.my_bookings.showMyBookings') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                            Back to My Bookings
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Feedback Form -->
            <form action="{{ route('sub_three.my_bookings.submitFeedback', $booking->uuid) }}" method="POST" class="p-6">
                @csrf
                
                <!-- Rating -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Overall Rating</label>
                    <div class="flex items-center space-x-1" x-data="{ rating: 0 }">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" 
                                    @click="rating = {{ $i }}; $refs.ratingInput.value = {{ $i }}"
                                    class="p-1 focus:outline-none"
                                    :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300'">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </button>
                        @endfor
                        <input type="hidden" name="rating" x-ref="ratingInput" value="0" required>
                    </div>
                    @error('rating')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Comment -->
                <div class="mb-6">
                    <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">Your Feedback</label>
                    <textarea id="comment" name="comment" rows="6" 
                              class="shadow-sm focus:ring-[#7F5539] focus:border-[#7F5539] block w-full sm:text-sm border border-gray-300 rounded-md p-3"
                              placeholder="Tell us about your experience... What did you like? What could be improved?"
                              required>{{ old('comment') }}</textarea>
                    @error('comment')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('sub_three.my_bookings.showMyBookings') }}" 
                       class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#7F5539] hover:bg-[#4A2C1D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7F5539]">
                        Submit Feedback
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('feedbackForm', () => ({
            rating: 0,
            init() {
                // Set initial rating from old input if exists
                const oldRating = {{ old('rating', 0) }};
                if (oldRating > 0) {
                    this.rating = oldRating;
                    this.$refs.ratingInput.value = oldRating;
                }
            }
        }));
    });
</script>
@endsection