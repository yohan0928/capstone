@extends('layouts.app')

@section('title', 'Booking Calendar')

@section('content')
    <style>
        /* --- Your existing calendar styles here --- */
        .calendar-day {
            transition: all 0.2s ease-in-out;
            border-radius: 0.375rem;
            background-color: #faf5f0;
            border: 1px solid #f1f5f9;
            position: relative;
        }

        .calendar-day:hover {
            background-color: #f3e9e1;
        }

        /* Past dates - lighter shade */
        .calendar-day.is-past {
            background-color: #fdf8f3;
            color: #9ca3af;
            border-color: #f8f1e9;
        }

        .calendar-day.is-past:hover {
            background-color: #f8f1e9;
        }

        /* Today - main color */
        .calendar-day.is-today {
            background-color: #4A2C1D !important;
            color: white !important;
            font-weight: bold;
            border-color: #4A2C1D;
        }

        .calendar-day.is-today:hover {
            background-color: #3a2317 !important;
        }

        /* Fully booked dates - reddish brown tint */
        .calendar-day.is-fully-booked {
            background-color: #f9ece8;
            color: #8b4513;
            border-color: #e8d5cd;
        }

        .calendar-day.is-fully-booked:hover {
            background-color: #f5e2da;
        }

        /* Non-current month dates - different brown tint */
        .calendar-day:not(.is-current-month) {
            background-color: #f8f4f0;
            color: #a8a29e;
            border-color: #e7e5e4;
        }

        .calendar-day:not(.is-current-month):hover {
            background-color: #f1eeea;
        }

        /* Single booking badge */
        .booking-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #ef4444;
            color: white;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        /* Jumping animation for new bookings */
        @keyframes jump-gentle {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        .jump-badge {
            animation: jump-gentle 1.2s ease-in-out infinite;
        }

        /* Static badge (after jump stops) */
        .static-badge {
            transform: translateY(0);
        }

        [x-cloak] {
            display: none !important;
        }

        /* --- End of Calendar Styles --- */
    </style>

    <!-- Add Branch Button -->
    <a href="{{ route('sub_two.book_now.create') }}" class="fixed bottom-6 right-6 z-50">
        <span class="relative flex items-center justify-center w-12 h-12">
            <!-- Pulsing circle behind -->
            <span class="absolute flex items-center justify-center">
                <span class="w-16 h-16 rounded-full bg-[#7F5539] opacity-40 animate-pulse-slow"></span>
            </span>

            <!-- Foreground button wrapped in a group -->
            <span
                class="relative group flex items-center justify-center w-12 h-12 bg-[#7F5539] text-white rounded-full shadow-lg hover:bg-[#4A2C1D] transition duration-300 ease-in-out cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>

                <!-- Tooltip label on the left -->
                <span
                    class="absolute right-full top-1/2 -translate-y-1/2 mr-2 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                    Book Now
                </span>
            </span>
        </span>
    </a>

    <div x-data="bookingPage(@js($bookings), {{ $totalMaxDailyBookings ?? 0 }})" x-init="init()"
        data-list-url="{{ route('sub_two.booking_lists.showBookingList') }}"
        class="max-w-2xl lg:max-w-none mx-auto p-4 sm:p-6">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-extrabold text-[#4A2C1D]">
                Calendar
            </h1>
        </div>

        <div class="space-y-4 text-[#4A2C1D]">
            <div class="space-y-4">

                <div class="p-4 border border-[#7F5539]/50 rounded-lg bg-white shadow-inner">
                    <div class="flex justify-between items-center text-base font-semibold mb-3">
                        <button @click="changeMonth(-1)"
                            class="text-gray-500 hover:text-[#4A2C1D] transition p-1 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="flex items-center space-x-2">
                            <div class="relative">
                                <button
                                    @click="showMonthPicker = !showMonthPicker; showYearPicker = false; $nextTick(() => scrollToMonth())"
                                    class="hover:bg-gray-200/60 px-2 py-1 rounded-md transition"
                                    x-text="monthNames[currentMonth]"></button>
                                <div x-show="showMonthPicker" @click.away="showMonthPicker = false" x-transition
                                    class="absolute bg-white border rounded-md shadow-lg z-50 top-full mt-2 max-h-60 overflow-y-auto w-32"
                                    style="display: none;" x-ref="monthPicker">
                                    <template x-for="(month, index) in monthNames" :key="index">
                                        <a href="#" @click.prevent="selectMonth(index)"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            :class="{ 'bg-gray-200 font-semibold': index === currentMonth }" x-text="month"
                                            :data-month-index="index"></a>
                                    </template>
                                </div>
                            </div>
                            <div class="relative">
                                <button
                                    @click="showYearPicker = !showYearPicker; showMonthPicker = false; $nextTick(() => scrollToYear())"
                                    class="hover:bg-gray-200/60 px-2 py-1 rounded-md transition" x-text="currentYear">
                                </button>
                                <div x-show="showYearPicker" @click.away="showYearPicker = false" x-transition
                                    class="absolute bg-white border rounded-md shadow-lg z-50 top-full mt-2 max-h-60 overflow-y-auto w-24"
                                    style="display: none;" x-ref="yearPicker">
                                    <template x-for="year in yearRange" :key="year">
                                        <a href="#" @click.prevent="selectYear(year)"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            :class="{ 'bg-gray-200 font-semibold': year === currentYear }" x-text="year"
                                            :data-year="year">
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <button @click="changeMonth(1)"
                            class="text-gray-500 hover:text-[#4A2C1D] transition p-1 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-2 text-center text-sm">
                        <!-- Fixed: Added unique keys for day headers -->
                        <template x-for="(day, index) in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="index">
                            <span class="font-bold p-2" x-text="day"></span>
                        </template>

                        <!-- Fixed: Ensure each day has a unique key -->
                        <template x-for="(day, dayIndex) in calendarDays" :key="day.key + '-' + dayIndex">
                            <div class="relative">
                                <template x-if="day.isCurrentMonth">
                                    <div class="relative">
                                        <button @click="openDateModal(day.date)" 
        class="p-3 w-full flex items-center justify-center calendar-day"
        :class="{
            'is-today': isToday(day.date),
            'is-past': isDateInPast(day.date) && !isToday(day.date),
            'is-fully-booked': isDateFullyBooked(day.date) && !isToday(day.date)
        }">
    <span class="text-lg" x-text="day.day"></span>
</button>

                                        <!-- Single booking badge showing total count -->
                                        <template x-if="getBookingCountForDate(day.date) > 0">
                                            <div class="booking-badge"
                                                :class="{ 'jump-badge': shouldBounce(day.date), 'static-badge': !shouldBounce(
                                                        day.date) }"
                                                :title="getBookingCountForDate(day.date) + ' booking(s)'"
                                                x-text="getBookingCountForDate(day.date)">
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!day.isCurrentMonth">
                                    <div class="p-3 w-full flex items-center justify-center text-gray-400">
                                        <span class="text-lg" x-text="day.day"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        
        <div x-show="showDateModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50 backdrop-blur-sm">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <!-- Header -->
            <div class="border-b border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 text-center">Date Options</h3>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Option 1: View Bookings -->
                    <button @click="redirectToBookingList()" 
                            class="w-full p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors text-left">
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">View Bookings</h4>
                                <p class="text-sm text-gray-500">Show all bookings for this date</p>
                            </div>
                        </div>
                    </button>
                    
                    <!-- Option 2: Check Space Availability -->
                    <button @click="redirectToSpaceAvailability()" 
                            class="w-full p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors text-left">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Check Space Availability</h4>
                                <p class="text-sm text-gray-500">See available seats and services</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="border-t border-gray-200 p-4 flex justify-center">
                <button @click="closeDateModal()" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
    </div>

    <script>
    function bookingPage(bookings, maxBookings) {
        return {
            showMonthPicker: false,
            showYearPicker: false,
            showDateModal: false,
            selectedDate: null,
            listUrl: null,
            maxDailyBookings: maxBookings || 0,

            today: null,
            currentMonth: null,
            currentYear: null,
            yearRange: [],
            calendarDays: [],
            monthNames: ["January", "February", "March", "April", "May", "June", "July", "August",
                "September", "October", "November", "December"
            ],

            allBookings: bookings || [],
            viewedDates: new Set(),

            init() {
                const localToday = new Date();
                this.today = new Date(Date.UTC(localToday.getFullYear(), localToday.getMonth(),
                    localToday.getDate()));

                this.currentMonth = this.today.getUTCMonth();
                this.currentYear = this.today.getUTCFullYear();

                this.listUrl = this.$el.dataset.listUrl;

                // Generate years from 2000 to current year + 5 years buffer
                const startYear = 2000;
                const endYear = this.currentYear + 5;
                this.yearRange = [];
                for (let y = startYear; y <= endYear; y++) {
                    this.yearRange.push(y);
                }

                // Load viewed dates from localStorage
                this.loadViewedDates();

                this.generateCalendar();
            },

            // Load viewed dates from localStorage
            loadViewedDates() {
                try {
                    const stored = localStorage.getItem('bookingCalendar_viewedDates');
                    if (stored) {
                        const datesArray = JSON.parse(stored);
                        this.viewedDates = new Set(datesArray);
                    }
                } catch (e) {
                    console.warn('Failed to load viewed dates from localStorage:', e);
                    this.viewedDates = new Set();
                }
            },

            // Save viewed dates to localStorage
            saveViewedDates() {
                try {
                    const datesArray = Array.from(this.viewedDates);
                    localStorage.setItem('bookingCalendar_viewedDates', JSON.stringify(datesArray));
                } catch (e) {
                    console.warn('Failed to save viewed dates to localStorage:', e);
                }
            },

            // Mark a date as viewed (stop bounce)
            markDateAsViewed(date) {
                if (!date) return;

                const dateKey = this.getDateKey(date);
                if (!this.viewedDates.has(dateKey)) {
                    this.viewedDates.add(dateKey);
                    this.saveViewedDates();
                }
            },

            // Check if date should bounce (has bookings and hasn't been viewed AND is not past date)
            shouldBounce(date) {
                if (!date) return false;

                // Don't bounce if it's a past date
                if (this.isDateInPast(date)) {
                    return false;
                }

                const hasBookings = this.getBookingCountForDate(date) > 0;
                const dateKey = this.getDateKey(date);
                const hasBeenViewed = this.viewedDates.has(dateKey);

                return hasBookings && !hasBeenViewed;
            },

            // Generate unique key for date
            getDateKey(date) {
                return date.toISOString().split('T')[0];
            },

            getBookingCountForDate(date) {
                if (!date) return 0;
                return this.allBookings.filter(booking => {
                    const bookingStartDate = new Date(booking.date_start);
                    const bookingStartDay = new Date(Date.UTC(
                        bookingStartDate.getUTCFullYear(),
                        bookingStartDate.getUTCMonth(),
                        bookingStartDate.getUTCDate()
                    ));
                    return bookingStartDay.getTime() === date.getTime();
                }).length;
            },

            isDateFullyBooked(date) {
                if (!date) return false;
                if (this.maxDailyBookings <= 0) return false;
                const bookingsOnDate = this.getBookingCountForDate(date);
                return bookingsOnDate >= this.maxDailyBookings;
            },

            // Open date modal
            openDateModal(date) {
                if (!date) return;
                
                // Mark this date as viewed before showing modal
                this.markDateAsViewed(date);
                
                this.selectedDate = date;
                this.showDateModal = true;
                document.body.classList.add('modal-open');
            },

            // Close date modal
            closeDateModal() {
                this.showDateModal = false;
                this.selectedDate = null;
                document.body.classList.remove('modal-open');
            },

            // Redirect to booking list
            redirectToBookingList() {
                if (!this.selectedDate || !this.listUrl) {
                    console.error('Cannot redirect: date or listUrl is missing.');
                    return;
                }

                const year = this.selectedDate.getUTCFullYear();
                const month = (this.selectedDate.getUTCMonth() + 1).toString().padStart(2, '0');
                const day = this.selectedDate.getUTCDate().toString().padStart(2, '0');
                const dateString = `${year}-${month}-${day}`;

                // Redirect with date filter
                window.location.href = `${this.listUrl}?date_start=${dateString}&date_end=${dateString}`;
            },

            // Redirect to space availability page
            redirectToSpaceAvailability() {
                if (!this.selectedDate) {
                    console.error('Cannot redirect: date is missing.');
                    return;
                }

                const year = this.selectedDate.getUTCFullYear();
                const month = (this.selectedDate.getUTCMonth() + 1).toString().padStart(2, '0');
                const day = this.selectedDate.getUTCDate().toString().padStart(2, '0');
                const dateString = `${year}-${month}-${day}`;

                // Redirect to space availability page
                window.location.href = `/sub_two/booking_calendar/space-availability?date=${dateString}`;
            },

            scrollToMonth() {
                this.$nextTick(() => {
                    const monthPicker = this.$refs.monthPicker;
                    if (monthPicker) {
                        const currentMonthElement = monthPicker.querySelector(
                            `[data-month-index="${this.currentMonth}"]`);
                        if (currentMonthElement) {
                            currentMonthElement.scrollIntoView({
                                block: 'center',
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            },

            scrollToYear() {
                this.$nextTick(() => {
                    const yearPicker = this.$refs.yearPicker;
                    if (yearPicker) {
                        const currentYearElement = yearPicker.querySelector(
                            `[data-year="${this.currentYear}"]`);
                        if (currentYearElement) {
                            currentYearElement.scrollIntoView({
                                block: 'center',
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            },

            selectMonth(monthIndex) {
                this.currentMonth = monthIndex;
                this.generateCalendar();
                this.showMonthPicker = false;
            },

            selectYear(year) {
                this.currentYear = year;
                this.generateCalendar();
                this.showYearPicker = false;
            },

            generateCalendar() {
                const firstDayOfMonth = new Date(Date.UTC(this.currentYear, this.currentMonth, 1));
                const lastDayOfMonth = new Date(Date.UTC(this.currentYear, this.currentMonth + 1, 0));

                const startDayOfWeek = firstDayOfMonth.getUTCDay();
                const totalDaysInMonth = lastDayOfMonth.getUTCDate();

                const prevMonthLastDate = new Date(Date.UTC(this.currentYear, this.currentMonth, 0));
                const prevMonthTotalDays = prevMonthLastDate.getUTCDate();

                let days = [];

                // Previous month days
                for (let i = startDayOfWeek; i > 0; i--) {
                    const dayNum = prevMonthTotalDays - i + 1;
                    days.push({
                        key: `prev-${this.currentYear}-${this.currentMonth}-${dayNum}`,
                        day: dayNum,
                        date: null,
                        isCurrentMonth: false
                    });
                }

                // Current month days
                for (let i = 1; i <= totalDaysInMonth; i++) {
                    days.push({
                        key: `current-${this.currentYear}-${this.currentMonth}-${i}`,
                        day: i,
                        date: new Date(Date.UTC(this.currentYear, this.currentMonth, i)),
                        isCurrentMonth: true
                    });
                }

                // Next month days
                const gridCells = 42;
                let nextMonthDay = 1;
                while (days.length < gridCells) {
                    days.push({
                        key: `next-${this.currentYear}-${this.currentMonth}-${nextMonthDay}`,
                        day: nextMonthDay,
                        date: null,
                        isCurrentMonth: false
                    });
                    nextMonthDay++;
                }

                this.calendarDays = days;
            },

            changeMonth(direction) {
                const d = new Date(Date.UTC(this.currentYear, this.currentMonth, 1));
                d.setUTCMonth(d.getUTCMonth() + direction);
                this.currentMonth = d.getUTCMonth();
                this.currentYear = d.getUTCFullYear();
                this.generateCalendar();
            },

            isToday(date) {
                if (!date) return false;
                return date.getTime() === this.today.getTime();
            },

            isDateInPast(date) {
                if (!date) return false;
                return date.getTime() < this.today.getTime();
            },

            // Format date for display (optional helper)
            formatDate(date) {
                if (!date) return '';
                return date.toLocaleDateString('en-US', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }
        }
    }
</script>
@endsection
