<style>
    /* Desktop Active Link Style: Dark Brown Text, Solid Bottom Border */
    .active-link-desktop {
        color: #4A2C1D !important;
        border-bottom-color: #4A2C1D !important;
        font-weight: 700 !important;
    }

    /* Desktop Sub-navlinks Active Style */
    .desktop-sub-link.active-link-desktop {
        background-color: #4A2C1D !important;
        color: white !important;
    }

    /* Mobile Dropdown Toggle Button Styles (MODIFIED: Removed default background) */
    .mobile-dropdown-toggle {
        /* Ensuring the toggle button is the right size and has visual separation */
        border-left: 1px solid #7F553930;
        background-color: transparent;
        /* REMOVED BACKGROUND */
        transition: background-color 0.2s;
    }

    .mobile-dropdown-toggle:hover {
        background-color: #f0f0f0;
        /* New subtle hover effect */
    }

    /* MODIFIED: Mobile dropdown content is hidden by default */
    .mobile-dropdown-content {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, opacity 0.2s ease, padding 0.3s ease, margin 0.3s ease;
        padding-top: 0;
        padding-bottom: 0;
        margin-top: 0;
    }

    /* MODIFIED: Show dropdown content when active */
    .mobile-dropdown.active .mobile-dropdown-content {
        max-height: 500px;
        opacity: 1;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        margin-top: 0.5rem;
    }

    .mobile-dropdown.active .mobile-dropdown-arrow {
        transform: rotate(180deg);
    }

    /* Mobile Navlinks Button Design (Main Links) */
    .mobile-link-container {
        display: flex;
        align-items: center;
        width: 100%;
        border-radius: 0.5rem;
        background-color: #f7f7f7;
        /* Light button background */
        margin-bottom: 0.5rem;
        transition: all 0.2s ease-in-out;
        border: 1px solid transparent;
        overflow: hidden;
        /* Ensures child elements respect border-radius */
    }

    .mobile-link-container:hover {
        border-color: #7F553930;
    }

    .mobile-dropdown:not(.active) .mobile-dropdown-content {
        display: none !important;
    }

    /* Styling for the actual link part inside the button container */
    .mobile-link-text {
        flex: 1;
        display: block;
        padding: 0.75rem 1rem;
        font-weight: 600;
        color: #4A2C1D;
        transition: background-color 0.2s;
    }

    /* 2. Mobile Active Link Style (Applied to the container/link) */
    .active-link-mobile {
        background-color: #4A2C1D !important;
        color: white !important;
        font-weight: 700 !important;
    }

    /* 2. Mobile Sub-Navlinks Button Design (MODIFIED: Padding for full width - Request 3) */
    .mobile-sub-link-button {
        display: block;
        width: 100%;
        /* Adjusted left padding to create indentation, now that pl-4 is removed from the parent UL */
        padding: 0.5rem 0.75rem 0.5rem 2.5rem;
        margin-bottom: 0.25rem;
        border-radius: 0.375rem;
        /* rounded-md */
        background-color: #F8F4F2;
        /* Slightly darker background than the main button for differentiation */
        color: #7F5539;
        font-weight: 500;
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .mobile-sub-link-button:hover {
        background-color: #EFEBE9;
        border-color: #7F553930;
    }

    /* Active style for sub-links */
    .mobile-sub-link-button.active-link-mobile {
        background-color: #7F5539 !important;
        color: white !important;
        font-weight: 600 !important;
    }

    /* Fix notification modal position */
    #notif-modal {
        margin-top: 0.5rem;
        max-height: 80vh;
    }

    /* Ensure modal doesn't overflow screen */
    @media (max-height: 800px) {
        #notif-modal {
            max-height: 70vh;
        }
    }

    @media (max-height: 600px) {
        #notif-modal {
            max-height: 60vh;
        }
    }

    /* Make badge pill-shaped for 9+ */
    #notification-badge[data-count="9+"] {
        min-width: 28px;
        padding-left: 6px;
        padding-right: 6px;
    }
</style>

<!-- Add user meta information in head -->
@auth
    <meta name="current-user-id" content="{{ Auth::user()->id }}">
    <meta name="current-user-type" content="{{ get_class(Auth::user()) }}">
@endauth

<nav class="bg-white shadow-md px-6 py-4 flex items-center justify-between sticky top-0 z-[9999] h-16">
    <div class="flex items-center space-x-4">
        {{-- Hamburger Menu Button (visible on large and smaller screens) --}}
        @if (auth('owner')->check() || auth('staff')->check() || auth('customer')->check())
            <div class="xl:hidden">
                <button id="menu-toggle" class="text-gray-800 focus:outline-none">
                    <!-- Hamburger Icon -->
                    <svg id="icon-hamburger" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2.5" stroke="currentColor"
                        class="w-8 h-8 text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>

                    <!-- X Icon (hidden by default) -->
                    <svg id="icon-close" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2.5" stroke="currentColor"
                        class="w-8 h-8 hidden text-[#7F5539] hover:bg-[#4A2C1D] hover:text-white rounded p-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- Logo (Clickable - Back to Home per Role, No Underline) --}}
        <div class="flex items-center">
            @guest
                <span class="text-xl font-bold text-[#4A2C1D] select-none cursor-default">
                    LinkudHub
                </span>
            @endguest

            @auth('owner')
                <a href="{{ route('sub_one.dashboard.showDashboard') }}"
                    class="text-xl font-bold text-[#4A2C1D] no-underline">
                    LinkudHub
                </a>
            @endauth

            @auth('staff')
                <a href="{{ route('sub_two.my_shift_schedules.showMyShift') }}"
                    class="text-xl font-bold text-[#4A2C1D] no-underline">
                    LinkudHub
                </a>
            @endauth

            @auth('customer')
                <a href="{{ route('sub_three.home.showHome') }}" class="text-xl font-bold text-[#4A2C1D] no-underline">
                    LinkudHub
                </a>
            @endauth
        </div>
    </div>

    {{-- Nav Links Middle (hidden on large and smaller screens) --}}
    <div class="hidden xl:flex flex-1 mx-6 justify-center">
        <ul id="desktop-nav-links" class="flex space-x-4">
            @auth('owner')
                <li>
                    <a href="{{ route('sub_one.dashboard.showDashboard') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="{{ route('sub_one.branches.showBranch') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Branches
                    </a>
                </li>

                <li class="relative group" data-dropdown-group="book-now">
                    <!-- Main nav link with bottom border and arrow -->
                    <a href="{{ route('sub_one.booking_calendar.showBookingCalendar') }}"
                        class="desktop-link desktop-dropdown-link flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Calendar
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-b-lg w-max left-0 top-full mt-0 p-2 space-y-1 z-50">

                        <li>
                            <a href="{{ route('sub_one.scan_qr_code_bookings.showQrCodeBookingScanner') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Scan Qr-Code Booking
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('sub_one.customer_checkins.index') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Customer Check-ins
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="relative group" data-dropdown-group="inventory">
                    <a href="{{ route('sub_one.inventory.index') }}"
                        class="flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Inventory
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-lg w-max top-full left-0 p-2 space-y-1">
                        <li>
                            <a href="{{ route('sub_one.products.showProduct') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Products
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sub_one.ingredients.showIngredient') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Ingredients
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sub_one.reports.staff_report') }}"
                                class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                                Staff
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="relative group" data-dropdown-group="pos">
                    <a href="{{ route('sub_one.pos.index') }}"
                        class="desktop-link desktop-dropdown-link flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        POS
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-lg w-max top-full left-0 p-2 space-y-1">
                        <li>
                            <a href="{{ route('sub_one.pos.history') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Order Lists
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="{{ route('sub_one.staff.showStaffList') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Staff
                    </a>
                </li>

                <li class="relative group" data-dropdown-group="reward-tiers">
                    <a href="{{ route('sub_one.loyalty_tiers.index') }}"
                        class="desktop-link desktop-dropdown-link flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Reward Tiers
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-lg w-max top-full left-0 p-2 space-y-1">
                        <li>
                            <a href="{{ route('sub_one.customer_rewards.index') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Customer Rewards
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="relative group" data-dropdown-group="reports">
                    <a href="{{ route('sub_one.reports.branch_report') }}"
                        class="desktop-link flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Reports
                    </a>
                </li>
                
                <!-- 
                <li class="relative group" data-dropdown-group="reports">
                    <a href="{{ route('sub_one.reports.staff_report') }}"
                        class="desktop-link desktop-dropdown-link flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Reports
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <!-- 
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-lg w-max top-full left-0 p-2 space-y-1">
                        <li>
                            <a href="{{ route('sub_one.reports.feedback_report') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Ratings
                            </a>
                        </li>
                        
                        <li>
                            <a href="{{ route('sub_one.reports.inventory_report') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Inventory
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <a href="{{ route('sub_one.reports.staff_report') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Reports
                    </a>
                </li>
                -->

                <li>
                    <a href="{{ route('sub_one.feedback.index') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Feedback
                    </a>
                </li>
            @endauth

            @auth('staff')
                <li>
                    <a href="{{ route('sub_two.my_shift_schedules.showMyShift') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Shifts
                    </a>
                </li>

                <li>
                    <a href="{{ route('sub_two.branches.showBranch') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Branch
                    </a>
                </li>

                <li class="relative group" data-dropdown-group="book-now">
                    <!-- Main nav link with bottom border and arrow -->
                    <a href="{{ route('sub_two.booking_calendar.showBookingCalendar') }}"
                        class="desktop-link desktop-dropdown-link flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Calendar
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-b-lg w-max left-0 top-full mt-0 p-2 space-y-1 z-50">

                        <li>
                            <a href="{{ route('sub_two.scan_qr_code_bookings.showQrCodeBookingScanner') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Scan Qr-Code Booking
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('sub_two.customer_checkins.index') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Customer Check-ins
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="relative group" data-dropdown-group="inventory">
                    <a href="{{ route('sub_two.inventory.index') }}"
                        class="flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Inventory
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-lg w-max top-full left-0 p-2 space-y-1">
                        <li>
                            <a href="{{ route('sub_two.products.showProduct') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Products
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('sub_two.ingredients.showIngredient') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Ingredients
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="relative group" data-dropdown-group="pos">
                    <a href="{{ route('sub_two.pos.index') }}"
                        class="desktop-link desktop-dropdown-link flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        POS
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-lg w-max top-full left-0 p-2 space-y-1">
                        <li>
                            <a href="{{ route('sub_two.pos.history') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Order Lists
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="relative group" data-dropdown-group="reward-tiers">
                    <a href="{{ route('sub_two.loyalty_tiers.index') }}"
                        class="desktop-link desktop-dropdown-link flex items-center text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Reward Tiers
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Dropdown menu -->
                    <ul
                        class="absolute hidden group-hover:block bg-white shadow-lg rounded-lg w-max top-full left-0 p-2 space-y-1">
                        <li>
                            <a href="{{ route('sub_two.customer_rewards.index') }}"
                                class="desktop-link desktop-sub-link block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white">
                                Customer Rewards
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="{{ route('sub_two.reports.my_report') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Reports
                    </a>
                </li>

                <li>
                    <a href="{{ route('sub_two.feedback.index') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Feedback
                    </a>
                </li>
            @endauth

            @auth('customer')
                <li>
                    <a href="{{ route('sub_three.home.showHome') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Home
                    </a>
                </li>

                <li>
                    <a href="{{ route('sub_three.my_bookings.showMyBookings') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Bookings
                    </a>
                </li>

                <li>
                    <a href="{{ route('sub_three.my_rewards.showMyRewards') }}"
                        class="desktop-link text-[#7F5539] hover:text-[#4A2C1D] font-semibold border-b-2 border-transparent pb-2 transform transition-all duration-300 hover:border-[#4A2C1D]">
                        Rewards
                    </a>
                </li>
            @endauth
        </ul>
    </div>


    {{-- Right Icons / Login Button --}}
    <div class="flex items-center space-x-1 relative">
        @php
            // Proper authentication detection
            $owner = auth('owner')->user();
            $staff = auth('staff')->user();
            $customer = auth('customer')->user();
            $user = $owner ?? ($staff ?? $customer);
            $userType = $owner ? 'owner' : ($staff ? 'staff' : ($customer ? 'customer' : null));

            // --- START FIX: Dynamically determine the route name based on user type ---
            $accountRoutes = [
                'owner' => 'sub_one.accounts.user_accounts',
                'staff' => 'sub_two.accounts.user_accounts',
                'customer' => 'sub_three.accounts.user_accounts',
            ];

            // Select the correct route name based on the user type
            $routeNameToUse = $userType ? $accountRoutes[$userType] ?? null : null;

            // Call the route function only if a valid user is logged in and a route is found
            $accountRoute = $routeNameToUse ? route($routeNameToUse) : '#';
            // --- END FIX ---
        @endphp

        @if (!$user)
            {{-- Guest view --}}
            <a href="{{ route('showLoginForm') }}"
                class="px-4 py-2 bg-[#7F5539] text-white font-semibold rounded hover:bg-[#4A2C1D]">
                Login
            </a>
        @else
            {{-- Logged-in view with notifications --}}
            <div class="relative inline-block">
                <button id="notif-btn"
                    class="relative p-2 rounded-full focus:outline-none hover:bg-[#4A2C1D] group transition-colors">
                    <svg class="w-6 h-6 text-[#7F5539] group-hover:text-white" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V5a1 1 0 10-2 0v.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    {{-- Dynamic Badge: Added 'hidden' class to disable it visually --}}
                    <span id="notification-badge"
                        class="notification-badge absolute top-0 right-0 inline-flex items-center justify-center text-xs font-bold leading-none text-white transform translate-x-[25%] -translate-y-[25%] bg-red-600 rounded-full min-w-[20px] h-5 px-1 hidden">
                        <!-- Content updated by JS (functionality removed) -->
                    </span>
                </button>

                {{-- Notification Modal: Added Tabs (Point 1) and removed count from header (Point 4) --}}
                <div id="notif-modal"
                    class="absolute hidden bg-white border rounded-lg shadow-lg right-0 top-full w-80 z-[9999]">

                    {{-- Tabs Navigation (Point 1) --}}
                    <div class="flex border-b">
                        <button id="tab-all" data-tab="all"
                            class="flex-1 py-3 text-sm font-semibold border-b-2 border-[#7F5539] text-[#4A2C1D] transition-colors hover:bg-gray-50 focus:outline-none">All</button>
                        <button id="tab-unread" data-tab="unread"
                            class="flex-1 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-600 transition-colors hover:bg-gray-50 focus:outline-none">Unread</button>
                        <button id="tab-read" data-tab="read"
                            class="flex-1 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-600 transition-colors hover:bg-gray-50 focus:outline-none">Read</button>
                    </div>

                    {{-- Header with action buttons --}}
                    <div class="p-4 font-bold text-gray-800 flex justify-between items-center">
                        <span id="notification-header">Notifications</span> {{-- Removed count text (Point 4) --}}
                        <div class="flex space-x-2">
                            <button id="mark-all-read"
                                class="text-xs text-[#7F5539] hover:text-[#4A2C1D] font-normal hover:underline">Mark
                                All Read</button>
                            <button id="mark-all-unread"
                                class="text-xs text-[#7F5539] hover:text-[#4A2C1D] font-normal hover:underline">Mark
                                All Unread</button>
                        </div>
                    </div>

                    <div class="max-h-64 overflow-y-auto">
                        <ul id="notification-list" class="p-2 space-y-2">
                            <li id="notification-loading" class="p-4 text-center text-gray-500">
                                Loading notifications...
                            </li>
                        </ul>
                    </div>

                    <div class="p-3 text-center border-t border-gray-200">
                    </div>
                </div>
            </div>

            {{-- User dropdown --}}
            <div class="relative inline-block">
                <button id="user-btn"
                    class="flex items-center space-x-2 p-2 rounded-full focus:outline-none group transition-colors
                                    hover:bg-[#4A2C1D] hover:text-white"
                    data-user-id="{{ $user->id ?? '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-6 h-6 text-[#7F5539] group-hover:text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </button>

                <ul id="user-modal"
                    class="absolute hidden bg-white border rounded-lg shadow-lg right-0 top-full p-2 space-y-2 w-max z-[9999]">

                    @if ($user)
                        <li>
                            <a href="{{ $accountRoute }}"
                                class="block font-semibold transition-colors px-4 py-2 rounded hover:bg-[#4A2C1D] hover:text-white text-center">
                                {{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}
                            </a>
                        </li>
                    @endif
                    <hr class="border-t-2 border-[#4A2C1D] border-opacity-30">
                    <li>
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="block px-4 py-2 text-black font-semibold rounded hover:bg-[#4A2C1D] hover:text-white text-center">
                            Logout
                        </a>
                    </li>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </ul>
            </div>
        @endif
    </div>
</nav>

<!-- Mobile Menu Sidebar -->
<div id="mobile-menu"
    class="fixed top-16 left-0 h-[calc(100vh-4rem)] bg-white shadow-lg p-6 transform -translate-x-full xl:hidden transition-transform duration-300 ease-in-out z-[99] overflow-y-auto">
    <!-- Mobile Nav Links -->
    <ul class="flex flex-col space-y-4">
        @auth('owner')
            <li>
                <a href="{{ route('sub_one.dashboard.showDashboard') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Dashboard
                </a>
            </li>

            <li>
                <a href="{{ route('sub_one.branches.showBranch') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Branches
                </a>
            </li>

            <li class="mobile-dropdown">
                <div class="mobile-link-container !p-0">
                    <a href="{{ route('sub_one.booking_calendar.showBookingCalendar') }}"
                        class="mobile-link mobile-link-text hover:bg-[#f0f0f0] rounded-l-md" data-main-link="true">
                        Calendar
                    </a>
                    <button type="button"
                        class="mobile-dropdown-toggle p-3 rounded-r-md focus:outline-none transition-colors">
                        <svg class="mobile-dropdown-arrow w-4 h-4 text-[#7F5539] transition-transform duration-300"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <ul class="mobile-dropdown-content space-y-1">
                    <li>
                        <a href="{{ route('sub_one.scan_qr_code_bookings.showQrCodeBookingScanner') }}"
                            class="mobile-link mobile-sub-link-button">
                            Scan Qr-Code Booking
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('sub_one.customer_checkins.index') }}"
                            class="mobile-link mobile-sub-link-button">
                            Customer Check-ins
                        </a>
                    </li>
                </ul>
            </li>

            <li class="mobile-dropdown">
                <div class="mobile-link-container !p-0">
                    <a href="{{ route('sub_one.inventory.index') }}"
                        class="mobile-link mobile-link-text hover:bg-[#f0f0f0] rounded-l-md" data-main-link="true">
                        Inventory
                    </a>
                    <button type="button"
                        class="mobile-dropdown-toggle p-3 rounded-r-md focus:outline-none transition-colors">
                        <svg class="mobile-dropdown-arrow w-4 h-4 text-[#7F5539] transition-transform duration-300"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <ul class="mobile-dropdown-content space-y-1">
                    <li>
                        <a href="{{ route('sub_one.products.showProduct') }}" class="mobile-link mobile-sub-link-button">
                            Products
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('sub_one.ingredients.showIngredient') }}"
                            class="mobile-link mobile-sub-link-button">
                            Ingredients
                        </a>
                    </li>
                </ul>
            </li>

            <li class="mobile-dropdown">
                <div class="mobile-link-container !p-0">
                    <a href="{{ route('sub_one.pos.index') }}"
                        class="mobile-link mobile-link-text hover:bg-[#f0f0f0] rounded-l-md" data-main-link="true">
                        POS
                    </a>
                    <button type="button"
                        class="mobile-dropdown-toggle p-3 rounded-r-md focus:outline-none transition-colors">
                        <svg class="mobile-dropdown-arrow w-4 h-4 text-[#7F5539] transition-transform duration-300"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <ul class="mobile-dropdown-content space-y-1">
                    <li>
                        <a href="{{ route('sub_one.pos.history') }}" class="mobile-link mobile-sub-link-button">
                            Order List
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="{{ route('sub_one.staff.showStaffList') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Staff
                </a>
            </li>

            <li class="mobile-dropdown">
                <div class="mobile-link-container !p-0">
                    <a href="{{ route('sub_one.loyalty_tiers.index') }}"
                        class="mobile-link mobile-link-text hover:bg-[#f0f0f0] rounded-l-md" data-main-link="true">
                        Reward Tiers
                    </a>
                    <button type="button"
                        class="mobile-dropdown-toggle p-3 rounded-r-md focus:outline-none transition-colors">
                        <svg class="mobile-dropdown-arrow w-4 h-4 text-[#7F5539] transition-transform duration-300"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <ul class="mobile-dropdown-content space-y-1">
                    <li>
                        <a href="{{ route('sub_one.customer_rewards.index') }}"
                            class="mobile-link mobile-sub-link-button">
                            Customer Rewards
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="{{ route('sub_one.reports.staff_report') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Reports
                </a>
            </li>

            <li>
                <a href="{{ route('sub_one.feedback.index') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Feedback
                </a>
            </li>
        @endauth

        @auth('staff')
            <li>
                <a href="{{ route('sub_two.my_shift_schedules.showMyShift') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Shifts
                </a>
            </li>

            <li>
                <a href="{{ route('sub_two.branches.showBranch') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Branch
                </a>
            </li>

            <li class="mobile-dropdown">
                {{-- 1. SPLIT BUTTON STRUCTURE --}}
                <div class="mobile-link-container !p-0">
                    <a href="{{ route('sub_two.booking_calendar.showBookingCalendar') }}"
                        class="mobile-link mobile-link-text hover:bg-[#f0f0f0] rounded-l-md" data-main-link="true">
                        Calendar
                    </a>
                    <button type="button"
                        class="mobile-dropdown-toggle p-3 rounded-r-md focus:outline-none transition-colors">
                        <svg class="mobile-dropdown-arrow w-4 h-4 text-[#7F5539] transition-transform duration-300"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                {{-- END SPLIT BUTTON --}}
                <ul class="mobile-dropdown-content space-y-1">
                    <li>
                        <a href="{{ route('sub_two.scan_qr_code_bookings.showQrCodeBookingScanner') }}"
                            class="mobile-link mobile-sub-link-button">
                            Scan Qr-Code Booking
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('sub_two.customer_checkins.index') }}"
                            class="mobile-link mobile-sub-link-button">
                            Customer Check-ins
                        </a>
                    </li>
                </ul>
            </li>

            <li class="mobile-dropdown">
                <div class="mobile-link-container !p-0">
                    <a href="{{ route('sub_two.inventory.index') }}"
                        class="mobile-link mobile-link-text hover:bg-[#f0f0f0] rounded-l-md" data-main-link="true">
                        Inventory
                    </a>
                    <button type="button"
                        class="mobile-dropdown-toggle p-3 rounded-r-md focus:outline-none transition-colors">
                        <svg class="mobile-dropdown-arrow w-4 h-4 text-[#7F5539] transition-transform duration-300"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <ul class="mobile-dropdown-content space-y-1">
                    <li>
                        <a href="{{ route('sub_two.products.showProduct') }}" class="mobile-link mobile-sub-link-button">
                            Products
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('sub_two.ingredients.showIngredient') }}"
                            class="mobile-link mobile-sub-link-button">
                            Ingredients
                        </a>
                    </li>
                </ul>
            </li>

            <li class="mobile-dropdown">
                <div class="mobile-link-container !p-0">
                    <a href="{{ route('sub_two.pos.index') }}"
                        class="mobile-link mobile-link-text hover:bg-[#f0f0f0] rounded-l-md" data-main-link="true">
                        POS
                    </a>
                    <button type="button"
                        class="mobile-dropdown-toggle p-3 rounded-r-md focus:outline-none transition-colors">
                        <svg class="mobile-dropdown-arrow w-4 h-4 text-[#7F5539] transition-transform duration-300"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <ul class="mobile-dropdown-content space-y-1">
                    <li>
                        <a href="{{ route('sub_two.pos.history') }}" class="mobile-link mobile-sub-link-button">
                            Order List
                        </a>
                    </li>
                </ul>
            </li>

            <li class="mobile-dropdown">
                <div class="mobile-link-container !p-0">
                    <a href="{{ route('sub_two.loyalty_tiers.index') }}"
                        class="mobile-link mobile-link-text hover:bg-[#f0f0f0] rounded-l-md" data-main-link="true">
                        Reward Tiers
                    </a>
                    <button type="button"
                        class="mobile-dropdown-toggle p-3 rounded-r-md focus:outline-none transition-colors">
                        <svg class="mobile-dropdown-arrow w-4 h-4 text-[#7F5539] transition-transform duration-300"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <ul class="mobile-dropdown-content space-y-1">
                    <li>
                        <a href="{{ route('sub_two.customer_rewards.index') }}"
                            class="mobile-link mobile-sub-link-button">
                            Customer Rewards
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="{{ route('sub_two.reports.my_report') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Reports
                </a>
            </li>

            <li>
                <a href="{{ route('sub_two.feedback.index') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Feedback
                </a>
            </li>
        @endauth

        @auth('customer')
            <li>
                <a href="{{ route('sub_three.home.showHome') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Home
                </a>
            </li>

            <li>
                <a href="{{ route('sub_three.my_bookings.showMyBookings') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Bookings
                </a>
            </li>

            <li>
                <a href="{{ route('sub_three.my_rewards.showMyRewards') }}"
                    class="mobile-link mobile-link-text mobile-link-container hover:bg-[#f0f0f0]">
                    Rewards
                </a>
            </li>
            </script>
        @endauth
    </ul>
</div>

<script>
    // ------------------------------------------------------------------
    // NOTIFICATION FIXES - WITH PAGINATION
    // ------------------------------------------------------------------

    // State Management
    const state = {
        notifications: [],
        activeTab: 'all',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        baseUrl: '',
        currentPage: 1,
        pageSize: 10,
        hasMore: false,
        isLoading: false
    };

    function getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.startsWith('/sub_one')) return '/sub_one';
        if (currentPath.startsWith('/sub_two')) return '/sub_two';
        if (currentPath.startsWith('/sub_three')) return '/sub_three';
        return '';
    }

    async function apiFetch(url, options = {}) {
        const defaultHeaders = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': state.csrfToken,
            'Accept': 'application/json'
        };

        if (!state.csrfToken) {
            console.error('CSRF token not found. Assuming guest user.');
            return {
                success: false,
                error: 'Missing CSRF Token'
            };
        }

        try {
            const fullUrl = state.baseUrl + url;
            const response = await fetch(fullUrl, {
                ...options,
                headers: {
                    ...defaultHeaders,
                    ...options.headers,
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                if (response.status === 204) return {
                    success: true
                };
                if (response.status === 404) return {
                    success: false,
                    error: 'Not found'
                };
                if (response.status === 500) return {
                    success: false,
                    error: 'Server error'
                };
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return {
                    success: false,
                    error: 'Invalid response format'
                };
            }

        } catch (error) {
            console.error('API Fetch Error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    // Notification Functions with Pagination
    async function fetchNotifications(page = 1) {
        if (state.isLoading) return;

        state.isLoading = true;
        state.currentPage = page;

        const list = document.getElementById('notification-list');
        const loading = document.getElementById('notification-loading');

        // Show loading indicator if first page
        if (page === 1) {
            if (loading) loading.style.display = 'block';
            if (list) {
                list.innerHTML = '';
                const loadingItem = document.createElement('li');
                loadingItem.id = 'notification-loading';
                loadingItem.className = 'p-4 text-center text-gray-500';
                loadingItem.textContent = 'Loading notifications...';
                list.appendChild(loadingItem);
            }
        } else {
            // Add loading indicator at the end for "Load More"
            const loadingItem = document.createElement('li');
            loadingItem.className = 'p-2 text-center text-gray-500 text-sm';
            loadingItem.textContent = 'Loading...';
            loadingItem.id = 'load-more-loading';
            list.appendChild(loadingItem);
        }

        // Update pagination buttons to show loading state
        updatePaginationButtons();

        try {
            const data = await apiFetch(
                `/notifications?page=${page}&limit=${state.pageSize}&tab=${state.activeTab}`);

            // Remove loading indicators
            if (loading) loading.style.display = 'none';
            const loadingItem = document.getElementById('load-more-loading');
            if (loadingItem) loadingItem.remove();

            if (data && data.success && Array.isArray(data.notifications)) {
                if (page === 1) {
                    state.notifications = data.notifications;
                } else {
                    state.notifications = [...state.notifications, ...data.notifications];
                }

                // Always assume there might be more if we got a full page
                state.hasMore = data.notifications.length >= state.pageSize;

                renderNotifications();
                updateNotificationBadge();
            } else {
                if (list && page === 1) {
                    list.innerHTML =
                        `<li class="p-4 text-center text-gray-500">${data?.error || 'Could not load notifications.'}</li>`;
                }
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
            if (list && page === 1) {
                list.innerHTML = `<li class="p-4 text-center text-gray-500">Error loading notifications.</li>`;
            }
        } finally {
            state.isLoading = false;
            updatePaginationButtons();
        }
    }

    function renderNotifications() {
        const list = document.getElementById('notification-list');
        if (!list) return;

        // Filter notifications based on active tab
        const filtered = state.notifications.filter(n => {
            if (!n) return false;
            if (state.activeTab === 'unread') return !n.read_at;
            if (state.activeTab === 'read') return !!n.read_at;
            return true;
        });

        const loading = document.getElementById('notification-loading');
        if (loading) loading.style.display = 'none';

        if (filtered.length === 0) {
            list.innerHTML = `<li class="p-4 text-center text-gray-500">No ${state.activeTab} notifications.</li>`;
            updatePaginationButtons();
            return;
        }

        // Build notifications HTML with booking reminder specific styling
        list.innerHTML = filtered.map(n => {
            if (!n) return '';

            const isRead = !!n.read_at;
            const data = n.data || {};
            const createdAt = n.created_at ? new Date(n.created_at) : new Date();
            const timeAgo = getTimeAgo(createdAt);
            const message = data.message || 'No message';
            const title = data.title || 'Notification';
            const notificationUrl = data.url || '#';
            const type = data.type || 'general';
            const action = data.action || null;

            // Check if it's a booking reminder
            const isBookingReminder = type.includes('booking_') ||
                action === 'booking_start_reminder' ||
                action === 'booking_end_reminder';

            // Get appropriate icon based on notification type
            let iconSvg = getIconSvg(data.action);
            let iconColor = getIconColor(data.action);

            // Override for booking reminders
            if (isBookingReminder) {
                if (type === 'booking_start_reminder' || action === 'booking_start_reminder') {
                    iconSvg =
                        '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />';
                    iconColor = 'text-blue-500';
                } else if (type === 'booking_end_reminder' || action === 'booking_end_reminder') {
                    iconSvg =
                        '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />';
                    iconColor = 'text-orange-500';
                }
            }

            // Booking reminder specific styling
            const bookingBadge = isBookingReminder ?
                `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${type === 'booking_start_reminder' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'} ml-2">
                ${type === 'booking_start_reminder' ? 'Start Reminder' : 'End Reminder'}
            </span>` : '';

            return `
        <li class="notification-item border-b border-gray-100 last:border-b-0 ${isBookingReminder ? 'bg-blue-50' : ''}" data-id="${n.id}">
            <div class="flex justify-between items-start p-3 rounded-lg hover:bg-gray-100 ${isRead ? 'opacity-70' : 'font-semibold'}">
                <a href="${notificationUrl}" class="flex-1 notification-message-link" data-url="${notificationUrl}">
                    <div class="notification-message cursor-pointer flex items-start">
                        <div class="flex-shrink-0 mt-1 mr-3">
                            <svg class="w-5 h-5 ${iconColor}" fill="currentColor" viewBox="0 0 20 20">
                                ${iconSvg}
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center mb-1">
                                <p class="text-sm font-medium text-gray-900">${title}</p>
                                ${bookingBadge}
                            </div>
                            <p class="text-sm text-gray-800 mb-1">${message}</p>
                            
                            ${data.booking_ref_no ? `
                            <div class="text-xs text-gray-600 mt-2 space-y-1">
                                ${data.booking_ref_no ? `<div><span class="font-medium">Ref:</span> ${data.booking_ref_no}</div>` : ''}
                                ${data.service_name ? `<div><span class="font-medium">Service:</span> ${data.service_name}</div>` : ''}
                                ${data.branch_name ? `<div><span class="font-medium">Branch:</span> ${data.branch_name}</div>` : ''}
                                ${data.time ? `<div><span class="font-medium">Time:</span> ${data.time}</div>` : ''}
                            </div>
                            ` : ''}
                            
                            <p class="text-xs text-gray-500 mt-2">${timeAgo}</p>
                        </div>
                    </div>
                </a>

                <button title="${isRead ? 'Mark as Unread' : 'Mark as Read'}"
                        class="mark-one-btn ml-2 p-1 rounded-full hover:bg-gray-200 focus:outline-none flex-shrink-0 transition-colors"
                        data-id="${n.id}"
                        data-action="${isRead ? 'unread' : 'read'}">
                    <svg class="w-4 h-4 ${isRead ? 'text-gray-500 hover:text-blue-500' : 'text-green-500 hover:text-green-700'}"
                         fill="currentColor" viewBox="0 0 20 20">
                        ${isRead ? 
                            '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />' :
                            '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.94 7.707 9.647a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />'
                        }
                    </svg>
                </button>
            </div>
        </li>`;
        }).join('');

        setupNotificationItemHandlers();
        updatePaginationButtons();
    }

    function getIconSvg(action) {
        switch (action) {
            case 'created':
                return '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />';
            case 'updated':
                return '<path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />';
            case 'status_changed':
                return '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />';
            default:
                return '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />';
        }
    }

    // Add debug button (optional, for testing)
    const debugBtn = document.createElement('button');
    debugBtn.textContent = 'Debug Notifications';
    debugBtn.className = 'hidden'; // Hide by default
    debugBtn.onclick = debugNotifications;
    document.body.appendChild(debugBtn);

    async function debugNotifications() {
        console.log('Current state:', {
            baseUrl: state.baseUrl,
            notificationsCount: state.notifications.length,
            currentPage: state.currentPage,
            hasMore: state.hasMore,
            isLoading: state.isLoading
        });

        // Test the API endpoint directly
        const testUrl = state.baseUrl + `/notifications?page=1&limit=${state.pageSize}`;
        console.log('Testing URL:', testUrl);

        try {
            const response = await fetch(testUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': state.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);

        } catch (error) {
            console.error('Debug fetch error:', error);
        }
    }

    // Helper function for icon colors based on action type
    function getIconColor(action) {
        switch (action) {
            case 'created':
                return 'text-green-500';
            case 'updated':
                return 'text-blue-500';
            case 'status_changed':
                return 'text-yellow-500';
            case 'deactivated':
                return 'text-red-500';
            case 'reactivated':
                return 'text-green-500';
            default:
                return 'text-gray-500';
        }
    }

    function updatePaginationButtons() {
        const list = document.getElementById('notification-list');
        if (!list) return;

        // Remove existing pagination buttons
        const existingMoreBtn = list.querySelector('#load-more-notifications');
        const existingLessBtn = list.querySelector('#show-less-notifications');
        if (existingMoreBtn) existingMoreBtn.remove();
        if (existingLessBtn) existingLessBtn.remove();

        // Always show "Load More" if we have notifications or know there are more
        // This makes it independent of read/unread status
        if (state.notifications.length > 0 || state.hasMore) {
            const moreButton = document.createElement('li');
            moreButton.id = 'load-more-notifications';
            moreButton.className = 'p-3 text-center border-t border-gray-200';
            moreButton.innerHTML = `
            <button class="text-sm text-[#7F5539] hover:text-[#4A2C1D] font-medium hover:underline focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                    ${state.isLoading ? 'disabled' : ''}>
                ${state.isLoading ? 'Loading...' : 'Load More'}
            </button>
        `;
            list.appendChild(moreButton);

            moreButton.querySelector('button').addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!state.isLoading) {
                    await fetchNotifications(state.currentPage + 1);
                }
            });
        }

        // Show "Show Less" if we're beyond the first page
        if (state.currentPage > 1) {
            const lessButton = document.createElement('li');
            lessButton.id = 'show-less-notifications';
            lessButton.className = 'p-3 text-center border-t border-gray-200';
            lessButton.innerHTML = `
            <button class="text-sm text-[#7F5539] hover:text-[#4A2C1D] font-medium hover:underline focus:outline-none">
                Show Less (Back to recent)
            </button>
        `;
            list.appendChild(lessButton);

            lessButton.querySelector('button').addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                await fetchNotifications(1);
            });
        }
    }

    function getTimeAgo(date) {
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;

        return date.toLocaleDateString();
    }

    function updateNotificationBadge() {
        const badge = document.getElementById('notification-badge');
        if (!badge) return;

        const unreadCount = state.notifications.filter(n => !n.read_at).length;

        if (unreadCount > 0) {
            let displayCount = unreadCount > 9 ? '9+' : unreadCount;
            badge.textContent = displayCount;
            badge.dataset.count = displayCount;

            // Adjust width for 9+
            if (unreadCount > 9) {
                badge.classList.add('px-2');
                badge.classList.remove('px-1');
                badge.style.minWidth = '28px';
            } else {
                badge.classList.remove('px-2');
                badge.classList.add('px-1');
                badge.style.minWidth = '20px';
            }

            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function updateTabStyles() {
        ['all', 'unread', 'read'].forEach(tab => {
            const button = document.getElementById(`tab-${tab}`);
            if (button) {
                if (tab === state.activeTab) {
                    button.classList.add('border-[#4A2C1D]', 'text-[#4A2C1D]');
                    button.classList.remove('border-transparent', 'text-gray-600');
                } else {
                    button.classList.remove('border-[#4A2C1D]', 'text-[#4A2C1D]');
                    button.classList.add('border-transparent', 'text-gray-600');
                }
            }
        });
    }

    function setupNotificationItemHandlers() {
        const list = document.getElementById('notification-list');
        if (!list) return;

        list.removeEventListener('click', handleNotificationClick);
        list.addEventListener('click', handleNotificationClick);
    }

    function handleNotificationClick(e) {
        const notifModal = document.getElementById('notif-modal');
        const markOneBtn = e.target.closest('.mark-one-btn');

        if (markOneBtn) {
            e.preventDefault();
            e.stopPropagation();

            const id = markOneBtn.dataset.id;
            const action = markOneBtn.dataset.action;

            handleMarkOne(id, action);
            return;
        }

        const notificationMessage = e.target.closest('.notification-message');
        const notificationLink = e.target.closest('.notification-message-link');

        if (notificationMessage || notificationLink) {
            e.preventDefault();
            e.stopPropagation();

            const item = (notificationMessage || notificationLink).closest('.notification-item');
            const id = item.dataset.id;
            const notification = state.notifications.find(n => n.id === id);

            // Mark as read if unread
            if (notification && !notification.read_at) {
                handleMarkOne(id, 'read');
            }

            // Get the URL and navigate to it WITHOUT closing the dropdown
            const linkElement = item.querySelector('.notification-message-link');
            if (linkElement) {
                const url = linkElement.getAttribute('href') || linkElement.getAttribute('data-url');
                if (url && url !== '#') {
                    // Navigate to the URL WITHOUT closing the notification dropdown
                    // The dropdown will naturally close when the page changes
                    console.log('Navigating to:', url);
                    window.location.href = url;
                }
            }
        }
    }

    async function handleMarkAllRead(e) {
        e.stopPropagation();
        const data = await apiFetch('/notifications/mark-all-read', {
            method: 'POST'
        });
        if (data && data.success) {
            state.notifications.forEach(n => n.read_at = new Date().toISOString());
            renderNotifications();
            updateNotificationBadge();
        }
    }

    async function handleMarkAllUnread(e) {
        e.stopPropagation();
        const data = await apiFetch('/notifications/mark-all-unread', {
            method: 'POST'
        });
        if (data && data.success) {
            state.notifications.forEach(n => n.read_at = null);
            renderNotifications();
            updateNotificationBadge();
        }
    }

    async function handleMarkOne(id, action = 'read') {
        const notification = state.notifications.find(n => n.id === id);
        if (!notification) {
            return;
        }

        // Store original state for rollback
        const originalReadAt = notification.read_at;

        // Optimistically update UI
        notification.read_at = (action === 'read') ? new Date().toISOString() : null;

        // Re-render but DON'T reset pagination
        renderNotifications();
        updateNotificationBadge();

        // Keep pagination buttons visible
        updatePaginationButtons();

        // Send API request
        const url = `/notifications/${id}/mark-${action}`;
        const data = await apiFetch(url, {
            method: 'POST'
        });

        if (!data || !data.success) {
            // Rollback on error
            notification.read_at = originalReadAt;
            renderNotifications();
            updateNotificationBadge();
            updatePaginationButtons();
            alert('Failed to update notification status');
        }
        // If successful, UI is already updated
    }

    function setupNotificationHandlers() {
        // Tab handlers
        document.querySelectorAll('#notif-modal button[data-tab]').forEach(tabBtn => {
            tabBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                state.activeTab = tabBtn.dataset.tab;
                updateTabStyles();

                // Reset to page 1 but keep pagination buttons
                state.currentPage = 1;
                state.hasMore = true; // Assume there might be more

                // Fetch first page
                fetchNotifications(1);
            });
        });

        // Mark all buttons
        const markAllReadBtn = document.getElementById('mark-all-read');
        const markAllUnreadBtn = document.getElementById('mark-all-unread');

        if (markAllReadBtn) {
            markAllReadBtn.removeEventListener('click', handleMarkAllRead);
            markAllReadBtn.addEventListener('click', handleMarkAllRead);
        }

        if (markAllUnreadBtn) {
            markAllUnreadBtn.removeEventListener('click', handleMarkAllUnread);
            markAllUnreadBtn.addEventListener('click', handleMarkAllUnread);
        }
    }

    // ------------------------------------------------------------------
    // FIXED MOBILE SIDEBAR WIDTH CALCULATION (NO SHRINKING)
    // ------------------------------------------------------------------

    function calculateFixedSidebarWidth() {
        const mobileMenu = document.getElementById('mobile-menu');
        if (!mobileMenu) return;

        // Get all mobile link containers (the actual buttons)
        const mobileLinkContainers = mobileMenu.querySelectorAll('.mobile-link-container');
        let maxWidth = 0;

        // Create a temporary span to measure text width
        const tempSpan = document.createElement('span');
        tempSpan.style.visibility = 'hidden';
        tempSpan.style.position = 'absolute';
        tempSpan.style.whiteSpace = 'nowrap';
        tempSpan.style.fontWeight = '600';
        tempSpan.style.fontSize = '16px';
        document.body.appendChild(tempSpan);

        // Find the longest text width from mobile link containers
        mobileLinkContainers.forEach(container => {
            const linkText = container.querySelector('.mobile-link-text');
            if (linkText) {
                const text = linkText.textContent.trim();
                tempSpan.textContent = text;
                const textWidth = tempSpan.offsetWidth;

                // Account for padding, dropdown toggle button, and margins
                const containerPadding = 32; // 1rem on each side = 32px
                const dropdownToggleWidth = 48; // Approximate width of dropdown toggle button
                const safetyBuffer = 16;

                const totalWidth = textWidth + containerPadding + dropdownToggleWidth + safetyBuffer;

                if (totalWidth > maxWidth) {
                    maxWidth = totalWidth;
                }
            }
        });

        // Also check sub-link buttons
        const subLinkButtons = mobileMenu.querySelectorAll('.mobile-sub-link-button');
        subLinkButtons.forEach(button => {
            const text = button.textContent.trim();
            tempSpan.textContent = text;
            const textWidth = tempSpan.offsetWidth;

            // Sub-links have more left padding (2.5rem = 40px)
            const subLinkPadding = 56; // 0.75rem left + 0.75rem right + extra indentation
            const safetyBuffer = 16;

            const totalWidth = textWidth + subLinkPadding + safetyBuffer;

            if (totalWidth > maxWidth) {
                maxWidth = totalWidth;
            }
        });

        // Remove temporary span
        document.body.removeChild(tempSpan);

        // Set minimum and maximum bounds
        const minWidth = 280;
        const maxWidthLimit = 400;

        let finalWidth = Math.max(minWidth, Math.min(maxWidthLimit, maxWidth));

        // Add scrollbar width to account for potential scrollbar
        const scrollbarWidth = 16;
        const widthWithScrollbar = finalWidth + scrollbarWidth;

        console.log('Calculated fixed sidebar width:', finalWidth + 'px (with scrollbar: ' + widthWithScrollbar +
            'px)');

        // Apply the calculated width accounting for scrollbar
        mobileMenu.style.width = `${widthWithScrollbar}px`;
        mobileMenu.style.minWidth = `${widthWithScrollbar}px`;
        mobileMenu.style.maxWidth = `${widthWithScrollbar}px`;

        // Store the width for reference
        mobileMenu.dataset.fixedWidth = widthWithScrollbar;

        // Force the content area to maintain its width by adding right padding
        const contentElements = mobileMenu.querySelectorAll('.mobile-link-container, .mobile-sub-link-button');
        contentElements.forEach(element => {
            element.style.paddingRight = `${scrollbarWidth}px`;
        });
    }

    // ------------------------------------------------------------------
    // CLICK HANDLING FIXES
    // ------------------------------------------------------------------

    function setupSidebarClickHandling() {
        const mobileMenu = document.getElementById('mobile-menu');
        if (!mobileMenu) return;

        // Prevent clicks inside sidebar from closing it
        mobileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Also prevent propagation from all interactive elements inside
        const interactiveElements = mobileMenu.querySelectorAll('a, button, .mobile-dropdown-toggle');
        interactiveElements.forEach(element => {
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    }

    function setupGlobalClickHandler() {
        document.addEventListener('click', (e) => {
            const mobileMenu = document.getElementById('mobile-menu');
            const menuToggle = document.getElementById('menu-toggle');
            const notifBtn = document.getElementById('notif-btn');
            const notifModal = document.getElementById('notif-modal');
            const userBtn = document.getElementById('user-btn');
            const userModal = document.getElementById('user-modal');

            // Close mobile menu when clicking outside
            if (mobileMenu && !mobileMenu.classList.contains('-translate-x-full') &&
                !mobileMenu.contains(e.target) &&
                !menuToggle.contains(e.target)) {
                closeMobileMenu();
            }

            // Close mobile dropdowns when clicking outside of them (but inside menu)
            if (mobileMenu && !mobileMenu.classList.contains('-translate-x-full')) {
                const clickedInsideDropdown = e.target.closest('.mobile-dropdown.active');
                if (!clickedInsideDropdown) {
                    closeAllMobileDropdowns();
                }
            }

            // Close notification modal when clicking outside
            if (notifBtn && notifModal && !notifBtn.contains(e.target) && !notifModal.contains(e.target)) {
                notifModal.classList.add('hidden');
                // Reset to first page when closing modal
                state.currentPage = 1;
            }

            // Close user dropdown when clicking outside
            if (userBtn && userModal && !userBtn.contains(e.target) && !userModal.contains(e.target)) {
                userModal.classList.add('hidden');
            }
        });
    }

    function closeMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const iconHamburger = document.getElementById('icon-hamburger');
        const iconClose = document.getElementById('icon-close');
        const body = document.body;

        if (mobileMenu) {
            mobileMenu.classList.add('-translate-x-full');
            body.style.overflow = 'auto';
            // Close all dropdowns when menu closes
            closeAllMobileDropdowns();
        }
        if (iconHamburger) iconHamburger.classList.remove('hidden');
        if (iconClose) iconClose.classList.add('hidden');
    }

    // ------------------------------------------------------------------
    // ACTIVE LINK FUNCTIONALITY
    // ------------------------------------------------------------------

    function setActiveLinks() {
        const currentPath = getNormalizedPath(window.location.href);

        // First, remove all active classes to start fresh
        document.querySelectorAll('#desktop-nav-links a.desktop-link, #desktop-nav-links a.desktop-sub-link').forEach(
            link => {
                link.classList.remove('active-link-desktop');
            });

        // Remove all mobile active classes
        document.querySelectorAll('#mobile-menu a.mobile-link').forEach(link => {
            link.classList.remove('active-link-mobile');
        });

        // Close all mobile dropdowns initially
        closeAllMobileDropdowns();

        // --- Desktop Links & Dropdowns ---
        document.querySelectorAll('#desktop-nav-links a.desktop-link, #desktop-nav-links a.desktop-sub-link').forEach(
            link => {
                const linkPath = getNormalizedPath(link.href);

                // Only activate if it's an exact match
                if (currentPath === linkPath) {
                    link.classList.add('active-link-desktop');

                    // If it's a sub-link, also activate its parent dropdown main link
                    if (link.classList.contains('desktop-sub-link')) {
                        const parentGroup = link.closest('[data-dropdown-group]');
                        if (parentGroup) {
                            const mainLink = parentGroup.querySelector('a.desktop-dropdown-link');
                            if (mainLink) {
                                mainLink.classList.add('active-link-desktop');
                            }
                        }
                    }
                }
            });

        // --- Mobile Links (Main and Sub) ---
        let activeSubLinkFound = false;

        // First pass: Check for active sub-links
        document.querySelectorAll('#mobile-menu a.mobile-link.mobile-sub-link-button').forEach(link => {
            const linkPath = getNormalizedPath(link.href);

            if (currentPath === linkPath) {
                link.classList.add('active-link-mobile');
                activeSubLinkFound = true;

                // If it's an active sub-link, activate its parent dropdown
                const parentDropdown = link.closest('.mobile-dropdown');
                if (parentDropdown) {
                    parentDropdown.classList.add('active');
                }
            }
        });

        // Second pass: Check for active main links (only if no sub-link is active)
        if (!activeSubLinkFound) {
            document.querySelectorAll('#mobile-menu a.mobile-link:not(.mobile-sub-link-button)').forEach(link => {
                const linkPath = getNormalizedPath(link.href);

                if (currentPath === linkPath) {
                    link.classList.add('active-link-mobile');
                    // DO NOT open the dropdown - only mark the main link as active
                }
            });
        }
    }

    function getNormalizedPath(url) {
        if (!url) return '';
        try {
            const urlObj = new URL(url, window.location.origin);
            return urlObj.pathname.replace(/\/$/, '');
        } catch (e) {
            return url.split(/[?#]/)[0].replace(/\/$/, '');
        }
    }

    // ------------------------------------------------------------------
    // MOBILE DROPDOWN LOGIC (FIXED - Main links only navigate, dropdown button only toggles)
    // ------------------------------------------------------------------

    function setupMobileDropdowns() {
        const dropdownToggles = document.querySelectorAll('.mobile-dropdown-toggle');

        dropdownToggles.forEach(toggle => {
            toggle.removeEventListener('click', handleMobileDropdownClick);
            toggle.addEventListener('click', handleMobileDropdownClick);
        });

        // Handle clicks on the main link part - ONLY FOR NAVIGATION
        const mainLinks = document.querySelectorAll('.mobile-link-text[data-main-link="true"]');
        mainLinks.forEach(link => {
            link.removeEventListener('click', handleMainLinkClick);
            link.addEventListener('click', handleMainLinkClick);
        });
    }

    function handleMobileDropdownClick(e) {
        e.preventDefault();
        e.stopPropagation();

        const dropdown = this.closest('.mobile-dropdown');
        const wasActive = dropdown.classList.contains('active');

        // Close all other dropdowns first
        closeAllMobileDropdowns();

        // Toggle this dropdown (only if clicking the toggle button)
        if (!wasActive) {
            dropdown.classList.add('active');
        } else {
            dropdown.classList.remove('active');
        }
    }

    function handleMainLinkClick(e) {
        // MAIN LINK SHOULD ONLY NAVIGATE, NEVER TOGGLE DROPDOWN
        // Allow default navigation behavior to happen
        // Don't prevent default - let the link navigate normally

        // If dropdown is currently open, close it but still navigate
        const dropdown = this.closest('.mobile-dropdown');
        if (dropdown && dropdown.classList.contains('active')) {
            dropdown.classList.remove('active');
        }

        // Allow the link to navigate normally - don't prevent default
    }

    function closeAllMobileDropdowns() {
        document.querySelectorAll('.mobile-dropdown.active').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    }

    // ------------------------------------------------------------------
    // MENU TOGGLE FUNCTIONALITY (MODIFIED - Only calculate width on open)
    // ------------------------------------------------------------------

    function setupMenuToggle() {
        const menuToggle = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const body = document.body;
        const iconHamburger = document.getElementById('icon-hamburger');
        const iconClose = document.getElementById('icon-close');

        // Only set up if menu toggle exists (i.e., user is logged in)
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation();

                const isOpening = mobileMenu.classList.contains('-translate-x-full');
                mobileMenu.classList.toggle('-translate-x-full');
                iconHamburger.classList.toggle('hidden');
                iconClose.classList.toggle('hidden');

                if (!mobileMenu.classList.contains('-translate-x-full')) {
                    body.style.overflow = 'hidden';
                    // Only calculate width when opening the menu
                    if (!mobileMenu.dataset.fixedWidth) {
                        calculateFixedSidebarWidth();
                    }
                } else {
                    body.style.overflow = 'auto';
                }
            });
        }
    }

    // ------------------------------------------------------------------
    // Initialization
    // ------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function() {
        // Set base URL for notifications
        state.baseUrl = getBaseUrl();

        // Set active links
        setActiveLinks();

        // Set up mobile dropdowns
        setupMobileDropdowns();

        // Set up click handling
        setupSidebarClickHandling();
        setupGlobalClickHandler();
        setupMenuToggle(); // This will only work if menu-toggle exists

        // Set up notification handlers
        setupNotificationHandlers();

        // Notification button functionality
        const notifBtn = document.getElementById('notif-btn');
        const notifModal = document.getElementById('notif-modal');

        if (notifBtn && notifModal) {
            notifBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = notifModal.classList.toggle('hidden');
                if (!isHidden) {
                    // Position the modal first
                    positionNotificationModal();

                    // Reset state and fetch fresh notifications
                    state.currentPage = 1;
                    state.hasMore = true;
                    fetchNotifications(1);
                } else {
                    // Reset to page 1 when closing modal
                    state.currentPage = 1;
                }
            });
        }

        // User dropdown functionality
        const userBtn = document.getElementById('user-btn');
        const userModal = document.getElementById('user-modal');

        if (userBtn && userModal) {
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = userModal.classList.toggle('hidden');
                if (!isHidden) {
                    // Position the modal first
                    positionUserModal();
                }
            });
        }

        // Handle window resize for both modals
        window.addEventListener('resize', () => {
            positionNotificationModal();
            positionUserModal();
        });

        // Also position modals when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initial positioning if modals are visible on load
            positionNotificationModal();
            positionUserModal();
        });

        // Function to position the notification modal
        function positionNotificationModal() {
            const notifModal = document.getElementById('notif-modal');
            if (!notifModal) return;

            // Reset all positioning
            notifModal.style.position = 'fixed';
            notifModal.style.top = '64px';
            notifModal.style.right = '24px';
            notifModal.style.left = 'auto';
            notifModal.style.bottom = 'auto';
            notifModal.style.transform = 'none';
            notifModal.style.margin = '0';
            notifModal.style.padding = '0';
        }

        // Function to position the user modal
        function positionUserModal() {
            const userModal = document.getElementById('user-modal');
            if (!userModal) return;

            // Reset all positioning
            userModal.style.position = 'fixed';
            userModal.style.top = '64px';
            userModal.style.right = '24px';
            userModal.style.left = 'auto';
            userModal.style.bottom = 'auto';
            userModal.style.transform = 'none';
            userModal.style.width = 'auto';
            userModal.style.minWidth = '200px';
        }

        // Initial notification count if user is logged in
        if (state.baseUrl) {
            fetchNotificationCount();
            setInterval(fetchNotificationCount, 30000);
        }
    });

    // Notification count polling (simplified)
    async function fetchNotificationCount() {
        const notifModal = document.getElementById('notif-modal');
        if (notifModal && !notifModal.classList.contains('hidden')) {
            return;
        }

        const data = await apiFetch('/notifications/counts');
        if (data && data.success) {
            updateBadgeCount(data.unread_count);
        }
    }

    function updateBadgeCount(unreadCount) {
        const badge = document.getElementById('notification-badge');
        if (!badge) return;

        if (unreadCount > 0) {
            let displayCount = unreadCount > 9 ? '9+' : unreadCount;
            badge.textContent = displayCount;
            badge.dataset.count = displayCount;

            // Adjust width for 9+
            if (unreadCount > 9) {
                badge.classList.add('px-2');
                badge.classList.remove('px-1');
                badge.style.minWidth = '28px';
            } else {
                badge.classList.remove('px-2');
                badge.classList.add('px-1');
                badge.style.minWidth = '20px';
            }

            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
</script>
