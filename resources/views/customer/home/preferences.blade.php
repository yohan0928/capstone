<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Your Preferences - LinkudHub</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f5f2;
        }

        .survey-step {
            animation: slideUpFade 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .selection-card-content {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }

        label:hover .selection-card-content {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border-color: #e6ddd4;
        }

        input:checked + .selection-card-content {
            border-color: #7f5539;
            background-color: #fff8f3;
            box-shadow: 0 4px 6px -1px rgba(127, 85, 57, 0.1);
        }
        
        input:checked + .selection-card-content .check-icon {
            opacity: 1;
            transform: scale(1);
        }

        .check-icon {
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .step-indicator {
            transition: all 0.3s ease;
        }
        
        .step-indicator.active {
            background-color: #7f5539;
            color: white;
        }
        
        .step-indicator.completed {
            background-color: #10b981;
            color: white;
        }

        .location-btn {
            transition: all 0.3s ease;
        }
        .location-btn:hover {
            transform: translateY(-2px);
        }
        .location-btn:active {
            transform: scale(0.98);
        }
        
        .features-grid {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 4px;
        }
        
        .features-grid::-webkit-scrollbar {
            width: 4px;
        }
        
        .features-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .features-grid::-webkit-scrollbar-thumb {
            background: #d4c4b2;
            border-radius: 10px;
        }
        
        .features-grid::-webkit-scrollbar-thumb:hover {
            background: #7f5539;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <nav class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-[#e6ddd4]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="text-sm font-semibold text-gray-500">Tell us your preferences</div>
            </div>
            <div class="text-sm font-medium text-[#7f5539]">
                Step <span id="currentStepDisplay">1</span> of 6
            </div>
        </div>
        <div class="w-full bg-gray-100 h-1">
            <div id="surveyProgressBar" class="bg-[#7f5539] h-1 transition-all duration-500 ease-out" style="width: 16.67%"></div>
        </div>
    </nav>

    <!-- Step Indicators -->
    <div class="max-w-3xl mx-auto px-4 pt-6">
        <div class="flex justify-between items-center">
            <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold bg-[#7f5539] text-white" id="step1Indicator">1</div>
            <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>
            <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold bg-gray-200 text-gray-500" id="step2Indicator">2</div>
            <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>
            <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold bg-gray-200 text-gray-500" id="step3Indicator">3</div>
            <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>
            <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold bg-gray-200 text-gray-500" id="step4Indicator">4</div>
            <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>
            <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold bg-gray-200 text-gray-500" id="step5Indicator">5</div>
            <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>
            <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold bg-gray-200 text-gray-500" id="step6Indicator">6</div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-gray-500">
            <span>📍 Location</span>
            <span>💰 Rate</span>
            <span>✨ Amenities</span>
            <span>🪑 Space</span>
            <span>🕐 Time</span>
            <span>⭐ Rating</span>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-grow flex flex-col justify-center py-8 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto w-full px-4">
            
            <form id="preferencesForm" method="POST" action="{{ route('sub_three.home.preferences.save') }}">
                @csrf
                
                <!-- STEP 1: LOCATION -->
                <div id="step1" class="survey-step">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl md:text-3xl font-bold text-[#4a3429] mb-3">📍 Where Are You?</h1>
                        <p class="text-gray-600 text-base">Share your exact location for the most accurate recommendations.</p>
                        <p class="text-xs text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i> 
                            This is the most important factor for finding nearby workspaces
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="text-center">
                            <div class="w-20 h-20 bg-[#f5f0eb] rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-location-dot text-3xl text-[#7f5539]"></i>
                            </div>
                            
                            <div id="locationStatus" class="mb-4">
                                @if(isset($customerLocation) && isset($customerLocation['source']) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown')
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="flex items-center gap-2 text-green-600">
                                            <i class="fas fa-check-circle"></i>
                                            <span class="font-medium">Location detected</span>
                                        </div>
                                        <p class="text-sm text-gray-700">
                                            @if(isset($customerLocation['place_name']) && !empty($customerLocation['place_name']))
                                                {{ $customerLocation['place_name'] }}
                                            @elseif(isset($customerLocation['full_address']) && !empty($customerLocation['full_address']))
                                                {{ $customerLocation['full_address'] }}
                                            @else
                                                {{ number_format(isset($customerLocation['latitude']) ? $customerLocation['latitude'] : 0, 4) }}, {{ number_format(isset($customerLocation['longitude']) ? $customerLocation['longitude'] : 0, 4) }}
                                            @endif
                                        </p>
                                        @if(isset($customerLocation['city']) && !empty($customerLocation['city']))
                                            <p class="text-xs text-gray-400">
                                                {{ $customerLocation['city'] }}
                                                @if(isset($customerLocation['state']) && !empty($customerLocation['state']))
                                                    , {{ $customerLocation['state'] }}
                                                @endif
                                                @if(isset($customerLocation['country']) && !empty($customerLocation['country']) && $customerLocation['country'] != 'Philippines')
                                                    , {{ $customerLocation['country'] }}
                                                @endif
                                                @if(isset($customerLocation['postcode']) && !empty($customerLocation['postcode']))
                                                    - {{ $customerLocation['postcode'] }}
                                                @endif
                                            </p>
                                        @endif
                                        @if(isset($customerLocation['house_number']) && !empty($customerLocation['house_number']) && isset($customerLocation['road']) && !empty($customerLocation['road']))
                                            <p class="text-xs text-gray-400">
                                                🏠 {{ $customerLocation['house_number'] }} {{ $customerLocation['road'] }}
                                            </p>
                                        @endif
                                        <p class="text-xs text-gray-400">
                                            <i class="fas fa-clock mr-1"></i>
                                            Expires: {{ isset($customerLocation['expires_at']) ? \Carbon\Carbon::parse($customerLocation['expires_at'])->format('h:i A') : '30 minutes' }}
                                        </p>
                                    </div>
                                @else
                                    <p class="text-gray-500 text-sm">No location detected</p>
                                    <p class="text-xs text-gray-400 mt-1">Click the button below to share your exact location</p>
                                @endif
                            </div>
                            
                            <button type="button" onclick="getCustomerLocation()" 
                                class="location-btn px-6 py-3 bg-[#7f5539] hover:bg-[#6b4f3c] text-white rounded-xl font-semibold shadow-lg shadow-[#7f5539]/20 transition-all">
                                <i class="fas fa-location-dot mr-2"></i>
                                <span id="locationBtnText">
                                    @if(isset($customerLocation) && isset($customerLocation['source']) && $customerLocation['source'] != 'default' && $customerLocation['source'] != 'unknown')
                                        <i class="fas fa-check mr-1"></i> Update Exact Location
                                    @else
                                        Share My Exact Location
                                    @endif
                                </span>
                            </button>
                            
                            <p class="text-xs text-gray-400 mt-3">
                                <i class="fas fa-shield-alt mr-1"></i> 
                                Your location is only used for recommendations and expires after 30 minutes
                            </p>
                            
                            <div id="addressPreview" class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200 @if(!isset($customerLocation) || !isset($customerLocation['source']) || $customerLocation['source'] == 'default' || $customerLocation['source'] == 'unknown') hidden @endif">
                                <p class="text-xs text-gray-500">📍 Exact location detected:</p>
                                <p id="previewAddress" class="text-sm font-medium text-gray-800">
                                    @if(isset($customerLocation['place_name']) && !empty($customerLocation['place_name']))
                                        {{ $customerLocation['place_name'] }}
                                    @elseif(isset($customerLocation['full_address']) && !empty($customerLocation['full_address']))
                                        {{ $customerLocation['full_address'] }}
                                    @endif
                                </p>
                                <p id="previewDetails" class="text-xs text-gray-400 mt-1">
                                    @if(isset($customerLocation['house_number']) && !empty($customerLocation['house_number']) && isset($customerLocation['road']) && !empty($customerLocation['road']))
                                        🏠 {{ $customerLocation['house_number'] }} {{ $customerLocation['road'] }}
                                    @endif
                                    @if(isset($customerLocation['postcode']) && !empty($customerLocation['postcode']))
                                        • 📮 {{ $customerLocation['postcode'] }}
                                    @endif
                                </p>
                            </div>
                            
                            <input type="hidden" name="latitude" id="latitude" value="{{ isset($customerLocation['latitude']) ? $customerLocation['latitude'] : '' }}">
                            <input type="hidden" name="longitude" id="longitude" value="{{ isset($customerLocation['longitude']) ? $customerLocation['longitude'] : '' }}">
                            <input type="hidden" name="place_name" id="placeName" value="{{ isset($customerLocation['place_name']) ? $customerLocation['place_name'] : '' }}">
                            <input type="hidden" name="full_address" id="fullAddress" value="{{ isset($customerLocation['full_address']) ? $customerLocation['full_address'] : '' }}">
                            <input type="hidden" name="city" id="city" value="{{ isset($customerLocation['city']) ? $customerLocation['city'] : '' }}">
                            <input type="hidden" name="state" id="state" value="{{ isset($customerLocation['state']) ? $customerLocation['state'] : '' }}">
                            <input type="hidden" name="country" id="country" value="{{ isset($customerLocation['country']) ? $customerLocation['country'] : '' }}">
                            <input type="hidden" name="postcode" id="postcode" value="{{ isset($customerLocation['postcode']) ? $customerLocation['postcode'] : '' }}">
                            <input type="hidden" name="house_number" id="houseNumber" value="{{ isset($customerLocation['house_number']) ? $customerLocation['house_number'] : '' }}">
                            <input type="hidden" name="road" id="road" value="{{ isset($customerLocation['road']) ? $customerLocation['road'] : '' }}">
                            <input type="hidden" name="suburb" id="suburb" value="{{ isset($customerLocation['suburb']) ? $customerLocation['suburb'] : '' }}">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: RATE -->
                <div id="step2" class="survey-step hidden">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl md:text-3xl font-bold text-[#4a3429] mb-3">💰 What's Your Budget?</h1>
                        <p class="text-gray-600 text-base">Set your preferred per-hour rate range.</p>
                        <p class="text-xs text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i> 
                            This helps us find branches within your budget
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <label class="block text-sm font-bold text-gray-700 mb-4 uppercase tracking-wide">
                            <i class="fas fa-tag text-[#7f5539] mr-2"></i>Per-Hour Rate (PHP)
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-gray-400">₱</span>
                                <input type="number" name="min_rate" placeholder="Min Rate" 
                                    value="{{ $decodedPreferences['min_rate_preferred'] ?? '' }}" 
                                    class="w-full pl-8 pr-4 py-3 rounded-xl bg-gray-50 border-transparent focus:bg-white focus:border-[#7f5539] focus:ring-0 transition-all text-gray-800 placeholder-gray-400 font-medium">
                            </div>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-gray-400">₱</span>
                                <input type="number" name="max_rate" placeholder="Max Rate" 
                                    value="{{ $decodedPreferences['max_rate_preferred'] ?? '' }}" 
                                    class="w-full pl-8 pr-4 py-3 rounded-xl bg-gray-50 border-transparent focus:bg-white focus:border-[#7f5539] focus:ring-0 transition-all text-gray-800 placeholder-gray-400 font-medium">
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button type="button" onclick="setRateRange(0, 50)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-600 transition-colors">Under ₱50</button>
                            <button type="button" onclick="setRateRange(50, 100)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-600 transition-colors">₱50 - ₱100</button>
                            <button type="button" onclick="setRateRange(100, 200)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-600 transition-colors">₱100 - ₱200</button>
                            <button type="button" onclick="setRateRange(200, 300)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-600 transition-colors">₱200 - ₱300</button>
                            <button type="button" onclick="setRateRange(300, null)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-600 transition-colors">₱300+</button>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: AMENITIES -->
<div id="step3" class="survey-step hidden">
    <div class="text-center mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-[#4a3429] mb-3">✨ What amenities matter most?</h1>
        <p class="text-gray-600 text-base">Select the features you look for in a co-working space.</p>
        <p class="text-xs text-gray-400 mt-2">
            <i class="fas fa-info-circle mr-1"></i> 
            {{ count($uniqueFeatures ?? []) }} amenities available across all branches
        </p>
    </div>

    <div id="featuresContainer" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        @forelse ($uniqueFeatures as $feature)
            @php 
                $preferredFeatures = $decodedPreferences['preferred_features'] ?? [];
                $isSelected = !empty($preferredFeatures) && in_array($feature, $preferredFeatures);
            @endphp
            <label class="relative group cursor-pointer">
                <input type="checkbox" name="features[]" value="{{ $feature }}"
                    {{ $isSelected ? 'checked' : '' }} class="peer sr-only">
                <div class="selection-card-content p-3 rounded-xl border-2 border-gray-200 bg-white h-full transition-all hover:border-[#d4c4b2] flex items-center gap-3">
                    <div class="w-8 h-8 bg-[#f5f0eb] rounded-full flex items-center justify-center text-[#7f5539] flex-shrink-0">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                    <span class="font-medium text-gray-700 text-sm flex-1">{{ $feature }}</span>
                    <div class="check-icon w-5 h-5 bg-[#7f5539] rounded-full flex items-center justify-center text-white shadow-sm flex-shrink-0">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                </div>
            </label>
        @empty
            <div class="col-span-full text-center py-8 text-gray-500">
                <i class="fas fa-box-open text-3xl mb-2 block"></i>
                <p>No amenities found. Please add features to branches first.</p>
            </div>
        @endforelse
    </div>
</div>

                <!-- STEP 4: SPACE TYPE -->
                <div id="step4" class="survey-step hidden">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl md:text-3xl font-bold text-[#4a3429] mb-3">🪑 What's your preferred space type?</h1>
                        <p class="text-gray-600 text-base">Choose the kind of space you usually work in.</p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ($spaceTypes as $spaceType)
                            @php 
                                $preferredSpaceTypes = $decodedPreferences['preferred_space_types'] ?? [];
                                $isSelected = !empty($preferredSpaceTypes) && in_array($spaceType['value'], $preferredSpaceTypes);
                                $icons = [
                                    'seat' => 'fa-chair',
                                    'room' => 'fa-door-open',
                                    'meeting_room' => 'fa-users',
                                    'office' => 'fa-building'
                                ];
                                $icon = $icons[$spaceType['value']] ?? 'fa-cube';
                            @endphp
                            <label class="relative group cursor-pointer">
                                <input type="checkbox" name="space_types[]" value="{{ $spaceType['value'] }}"
                                    {{ $isSelected ? 'checked' : '' }} class="peer sr-only">
                                <div class="selection-card-content p-4 rounded-xl border-2 border-gray-200 bg-white h-full transition-all hover:border-[#d4c4b2] flex flex-col items-center text-center gap-2">
                                    <div class="w-12 h-12 bg-[#f5f0eb] rounded-full flex items-center justify-center text-[#7f5539]">
                                        <i class="fas {{ $icon }} text-xl"></i>
                                    </div>
                                    <span class="font-semibold text-gray-700 text-sm">{{ $spaceType['label'] }}</span>
                                    <div class="check-icon w-5 h-5 bg-[#7f5539] rounded-full flex items-center justify-center text-white">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- STEP 5: TIME SLOTS & DURATION -->
                <div id="step5" class="survey-step hidden">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl md:text-3xl font-bold text-[#4a3429] mb-3">🕐 When & How Long Do You Work?</h1>
                        <p class="text-gray-600 text-base">Select your preferred time slots and session duration.</p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-4 uppercase tracking-wide">
                            <i class="fas fa-clock text-[#7f5539] mr-2"></i>Preferred Time Slots
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($timeSlots as $timeSlot)
                                @php 
                                    $preferredTimeSlots = $decodedPreferences['preferred_time_slots'] ?? [];
                                    $isSelected = !empty($preferredTimeSlots) && in_array($timeSlot['value'], $preferredTimeSlots);
                                    $icons = [
                                        'early_morning' => 'fa-sun',
                                        'morning' => 'fa-cloud-sun',
                                        'afternoon' => 'fa-cloud-sun',
                                        'evening' => 'fa-moon',
                                        'late_night' => 'fa-star-of-life'
                                    ];
                                    $icon = $icons[$timeSlot['value']] ?? 'fa-clock';
                                    $bgColors = [
                                        'early_morning' => 'bg-amber-50',
                                        'morning' => 'bg-sky-50',
                                        'afternoon' => 'bg-blue-50',
                                        'evening' => 'bg-indigo-50',
                                        'late_night' => 'bg-purple-50'
                                    ];
                                    $bgColor = $bgColors[$timeSlot['value']] ?? 'bg-gray-50';
                                @endphp
                                <label class="relative group cursor-pointer">
                                    <input type="checkbox" name="time_slots[]" value="{{ $timeSlot['value'] }}"
                                        {{ $isSelected ? 'checked' : '' }} class="peer sr-only">
                                    <div class="selection-card-content p-4 rounded-xl border-2 border-gray-200 {{ $bgColor }} h-full transition-all hover:border-[#d4c4b2] flex items-center gap-4">
                                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-[#7f5539] shadow-sm">
                                            <i class="fas {{ $icon }} text-lg"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-800 text-sm">{{ $timeSlot['label'] }}</p>
                                        </div>
                                        <div class="check-icon w-5 h-5 bg-[#7f5539] rounded-full flex items-center justify-center text-white">
                                            <i class="fas fa-check text-xs"></i>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <label class="block text-sm font-bold text-gray-700 mb-4 uppercase tracking-wide">
                            <i class="fas fa-hourglass-half text-[#7f5539] mr-2"></i>Preferred Session Duration
                        </label>
                        @if(!empty($durationOptions))
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach ($durationOptions as $duration)
                                    @php 
                                        $storedDuration = $decodedPreferences['preferred_session_duration'] ?? null;
                                        $isSelected = ($storedDuration !== null && (string)$storedDuration == (string)$duration['value']);
                                    @endphp
                                    <label class="relative group cursor-pointer">
                                        <input type="radio" name="session_duration" value="{{ $duration['value'] }}"
                                            {{ $isSelected ? 'checked' : '' }} class="peer sr-only">
                                        <div class="selection-card-content p-4 rounded-xl border-2 border-gray-200 bg-white h-full transition-all hover:border-[#d4c4b2] flex flex-col items-center text-center gap-2">
                                            <div class="w-10 h-10 bg-[#f5f0eb] rounded-full flex items-center justify-center text-[#7f5539]">
                                                <i class="fas fa-hourglass-half text-lg"></i>
                                            </div>
                                            <span class="font-semibold text-gray-700 text-sm">{{ $duration['label'] }}</span>
                                            <div class="check-icon w-5 h-5 bg-[#7f5539] rounded-full flex items-center justify-center text-white">
                                                <i class="fas fa-check text-xs"></i>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-3 text-center">
                                <i class="fas fa-database mr-1"></i> 
                                {{ count($durationOptions) }} duration options available from our services
                            </p>
                        @else
                            <div class="text-center py-4 text-gray-500">
                                <i class="fas fa-clock text-2xl mb-2 block"></i>
                                <p>No session durations available. Please add services with time durations.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- STEP 6: RATING -->
                <div id="step6" class="survey-step hidden">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl md:text-3xl font-bold text-[#4a3429] mb-3">⭐ What Rating Do You Prefer?</h1>
                        <p class="text-gray-600 text-base">Set your minimum rating preference.</p>
                        <p class="text-xs text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i> 
                            This is the least important factor in our recommendations
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <label class="block text-sm font-bold text-gray-700 mb-4 uppercase tracking-wide">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>Minimum Rating
                        </label>
                        <div class="flex flex-wrap gap-3">
                            @php
                                $currentMinRating = $preference->min_rating_preferred ?? 'any';
                                if ($currentMinRating === null) {
                                    $currentMinRating = 'any';
                                }
                            @endphp
                            @foreach($ratingOptions as $option)
                                @php 
                                    $isSelected = $currentMinRating == $option['value'];
                                @endphp
                                <label class="cursor-pointer">
                                    <input type="radio" name="min_rating" value="{{ $option['value'] }}" 
                                        {{ $isSelected ? 'checked' : '' }} 
                                        class="peer sr-only">
                                    <div class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 peer-checked:bg-[#7f5539] peer-checked:text-white peer-checked:border-[#7f5539] transition-all hover:bg-gray-100 text-sm">
                                        @if($option['value'] != 'any')
                                            <i class="fas fa-star text-xs mr-1"></i> 
                                        @endif
                                        {{ $option['label'] }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>

            <form id="skipForm" method="POST" action="{{ route('sub_three.home.preferences.save') }}" style="display:none;">
                @csrf
                <input type="hidden" name="features" value="[]">
                <input type="hidden" name="space_types" value="[]">
                <input type="hidden" name="peak_hours" value="[]">
                <input type="hidden" name="time_slots" value="[]">
                <input type="hidden" name="session_duration" value="">
                <input type="hidden" name="min_rate" value="">
                <input type="hidden" name="max_rate" value="">
                <input type="hidden" name="min_rating" value="">
                <input type="hidden" name="latitude" value="">
                <input type="hidden" name="longitude" value="">
            </form>
        </div>
    </main>

    <!-- Footer / Controls -->
    <footer class="bg-white border-t border-gray-100 py-4 px-4 sm:px-8 sticky bottom-0">
        <div class="max-w-3xl mx-auto flex items-center justify-between">
            <div class="flex gap-3">
                <button type="button" onclick="prevStep()" id="prevBtn" 
                    class="px-6 py-2.5 rounded-lg text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-800 transition-colors opacity-0 pointer-events-none">
                    <i class="fas fa-arrow-left mr-2 text-sm"></i> Back
                </button>
            </div>
            <div class="flex gap-3 items-center">
                <button type="button" onclick="skipAllPreferences()" 
                    class="px-5 py-2.5 text-sm font-medium text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fas fa-times mr-1"></i> Skip All
                </button>
                
                <button type="button" onclick="skipStep()" 
                    class="px-5 py-2.5 text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">
                    Skip
                </button>
                
                <button type="button" onclick="nextStep()" id="nextBtn" 
                    class="px-8 py-2.5 bg-[#7f5539] hover:bg-[#6b4f3c] text-white rounded-xl font-semibold shadow-lg shadow-orange-900/10 transform transition-all active:scale-95 flex items-center">
                    Next <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </button>
                
                <button type="button" onclick="savePreferences()" id="saveBtn" 
                    class="hidden px-8 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold shadow-lg shadow-green-900/10 transform transition-all active:scale-95 flex items-center">
                    Finish <i class="fas fa-check ml-2 text-xs"></i>
                </button>
            </div>
        </div>
    </footer>

    <!-- Success Overlay -->
    <div id="successOverlay" class="fixed inset-0 bg-[#f8f5f2] z-[60] hidden flex-col items-center justify-center p-4">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6 animate-bounce">
            <i class="fas fa-check text-3xl text-green-600"></i>
        </div>
        <h2 class="text-3xl font-bold text-[#4a3429] mb-2 text-center">All Set!</h2>
        <p class="text-gray-600 text-lg mb-8 text-center max-w-md">We've personalized your branch recommendations based on your choices.</p>
        <button onclick="window.location.href='{{ route('sub_three.home.showHome') }}?preferences_saved=true'" 
            class="px-8 py-3 bg-[#7f5539] text-white rounded-xl font-semibold shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
            Go to Home
        </button>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black/50 z-[70] hidden flex-col items-center justify-center">
        <div class="bg-white rounded-2xl p-8 max-w-sm w-full text-center">
            <div class="w-16 h-16 border-4 border-[#7f5539] border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-gray-700 font-medium">Saving your preferences...</p>
            <p class="text-gray-400 text-sm mt-1">This will just take a moment</p>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 6;

        function updateProgressBar() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('surveyProgressBar').style.width = `${progress}%`;
            document.getElementById('currentStepDisplay').innerText = currentStep;
            
            for (let i = 1; i <= totalSteps; i++) {
                const indicator = document.getElementById(`step${i}Indicator`);
                if (i < currentStep) {
                    indicator.classList.remove('active', 'bg-gray-200', 'text-gray-500');
                    indicator.classList.add('completed', 'bg-green-600', 'text-white');
                } else if (i === currentStep) {
                    indicator.classList.remove('completed', 'bg-green-600', 'bg-gray-200', 'text-gray-500');
                    indicator.classList.add('active', 'bg-[#7f5539]', 'text-white');
                } else {
                    indicator.classList.remove('active', 'completed', 'bg-[#7f5539]', 'bg-green-600', 'text-white');
                    indicator.classList.add('bg-gray-200', 'text-gray-500');
                }
            }
        }

        function showStep(step) {
            document.querySelectorAll('.survey-step').forEach(el => el.classList.add('hidden'));
            const target = document.getElementById(`step${step}`);
            if(target) {
                target.classList.remove('hidden');
                target.style.animation = 'none';
                target.offsetHeight; 
                target.style.animation = 'slideUpFade 0.4s cubic-bezier(0.16, 1, 0.3, 1)';
            }
            currentStep = step;
            updateNavigation();
            updateProgressBar();
            updateNextButtonState();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            if (step === 3) {
                updateAmenitiesCount();
            }
        }

        function nextStep() {
            if (currentStep === 1) {
                const lat = document.getElementById('latitude').value;
                const lng = document.getElementById('longitude').value;
                if (!lat || !lng) {
                    if (!confirm('You haven\'t shared your location. Continue without location?')) {
                        return;
                    }
                }
            }
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
        
        function prevStep() { 
            if (currentStep > 1) showStep(currentStep - 1); 
        }
        
        function skipStep() { 
            if (currentStep < totalSteps) showStep(currentStep + 1); 
        }

        function updateNavigation() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const saveBtn = document.getElementById('saveBtn');

            if (currentStep === 1) {
                prevBtn.classList.add('opacity-0', 'pointer-events-none');
            } else {
                prevBtn.classList.remove('opacity-0', 'pointer-events-none');
            }

            if (currentStep === totalSteps) {
                nextBtn.classList.add('hidden');
                saveBtn.classList.remove('hidden');
            } else {
                nextBtn.classList.remove('hidden');
                saveBtn.classList.add('hidden');
            }
        }

        function updateNextButtonState() {
            const nextBtn = document.getElementById('nextBtn');
            nextBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
        }

        function updateAmenitiesCount() {
            const checked = document.querySelectorAll('#featuresContainer input[type="checkbox"]:checked');
            document.getElementById('selectedAmenitiesCount').textContent = checked.length;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('amenitySearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const items = document.querySelectorAll('.amenity-item');
                    
                    items.forEach(item => {
                        const feature = item.getAttribute('data-feature');
                        if (searchTerm === '' || feature.includes(searchTerm)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
            
            document.querySelectorAll('#featuresContainer input[type="checkbox"]').forEach(input => {
                input.addEventListener('change', updateAmenitiesCount);
            });
            
            updateAmenitiesCount();
        });

        document.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', () => updateNextButtonState());
        });

        function setRateRange(min, max) {
            document.querySelector('input[name="min_rate"]').value = min;
            if(max !== null) {
                document.querySelector('input[name="max_rate"]').value = max;
            } else {
                document.querySelector('input[name="max_rate"]').value = '';
            }
        }

        function getCustomerLocation() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }

            const btn = document.getElementById('locationBtnText');
            const status = document.getElementById('locationStatus');
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Getting location...';
            status.innerHTML = '<p class="text-sm text-gray-500">Requesting location permission...</p>';

            navigator.geolocation.getCurrentPosition(
                async function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    
                    status.innerHTML = `
                        <div class="flex items-center justify-center gap-2 text-green-600">
                            <i class="fas fa-check-circle"></i>
                            <span>Location detected!</span>
                            <span class="text-xs text-gray-400">(${lat.toFixed(4)}, ${lng.toFixed(4)})</span>
                        </div>
                    `;
                    btn.innerHTML = '<i class="fas fa-check mr-2"></i> Location Saved';
                    
                    await getAddressFromCoordinates(lat, lng);
                },
                function(error) {
                    let errorMessage = 'Unable to get your location. ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Please enable location permissions in your browser.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'The request to get your location timed out.';
                            break;
                        default:
                            errorMessage += 'Please try again.';
                    }
                    status.innerHTML = `
                        <div class="flex items-center justify-center gap-2 text-red-500">
                            <i class="fas fa-exclamation-circle"></i>
                            <span class="text-sm">${errorMessage}</span>
                        </div>
                    `;
                    btn.innerHTML = '<i class="fas fa-location-dot mr-2"></i> Share My Exact Location';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        }

        async function getAddressFromCoordinates(latitude, longitude) {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&addressdetails=1&zoom=18`;
                
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'User-Agent': 'LinkudHub/1.0'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch address');
                }
                
                const data = await response.json();
                const address = data.address || {};
                
                const locationData = {
                    latitude: latitude,
                    longitude: longitude,
                    houseNumber: address.house_number || address.building || '',
                    road: address.road || address.street || address.pedestrian || '',
                    suburb: address.suburb || address.district || address.neighbourhood || address.quarter || '',
                    city: address.city || address.town || address.village || address.municipality || '',
                    state: address.state || address.region || address.province || '',
                    country: address.country || '',
                    postcode: address.postcode || '',
                    fullAddress: data.display_name || '',
                    placeName: ''
                };
                
                const parts = [];
                if (locationData.houseNumber && locationData.road) {
                    parts.push(`${locationData.houseNumber} ${locationData.road}`);
                } else if (locationData.road) {
                    parts.push(locationData.road);
                }
                if (locationData.city) {
                    parts.push(locationData.city);
                } else if (locationData.suburb) {
                    parts.push(locationData.suburb);
                }
                if (locationData.state) {
                    parts.push(locationData.state);
                }
                
                locationData.placeName = parts.length > 0 ? parts.join(', ') : locationData.fullAddress || 'Your Location';
                
                updateLocationUI(locationData);
                await sendLocationToServer(locationData);
                
            } catch (error) {
                console.error('Error getting address:', error);
                await getAddressFromBigDataCloud(latitude, longitude);
            }
        }

        async function getAddressFromBigDataCloud(latitude, longitude) {
            try {
                const url = `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`;
                
                const response = await fetch(url);
                const data = await response.json();
                
                const locationData = {
                    latitude: latitude,
                    longitude: longitude,
                    placeName: (data.city || data.locality || '') + ', ' + (data.principalSubdivision || ''),
                    fullAddress: (data.city || data.locality || '') + ', ' + (data.principalSubdivision || '') + ', ' + (data.countryName || ''),
                    city: data.city || data.locality || '',
                    state: data.principalSubdivision || '',
                    country: data.countryName || '',
                    postcode: data.postcode || '',
                    houseNumber: '',
                    road: '',
                    suburb: data.locality || ''
                };
                
                updateLocationUI(locationData);
                await sendLocationToServer(locationData);
                
            } catch (error) {
                console.error('BigDataCloud fallback failed:', error);
            }
        }

        function updateLocationUI(locationData) {
            const locationDisplay = document.getElementById('locationDisplay');
            if (locationDisplay) {
                locationDisplay.textContent = locationData.placeName || locationData.fullAddress || 'Your Location';
            }
            
            const locationDetails = document.getElementById('locationDetails');
            if (locationDetails) {
                let html = `<span class="text-gray-700">${locationData.fullAddress || locationData.placeName || 'Your Location'}</span>`;
                html += `<span class="text-gray-400 ml-2">(GPS)</span>`;
                
                if (locationData.city || locationData.state) {
                    html += `<br><span class="text-xs text-gray-400">📍 ${locationData.city || ''} ${locationData.state ? ', ' + locationData.state : ''} ${locationData.postcode ? '- ' + locationData.postcode : ''}</span>`;
                }
                
                if (locationData.houseNumber && locationData.road) {
                    html += `<br><span class="text-xs text-gray-400">🏠 ${locationData.houseNumber} ${locationData.road}</span>`;
                }
                
                locationDetails.innerHTML = html;
            }
            
            const statusSpan = document.getElementById('locationStatus');
            if (statusSpan) {
                statusSpan.innerHTML = `<span class="text-green-500">✅ Location found! ${locationData.placeName || locationData.fullAddress || ''}</span>`;
            }
            
            const btnText = document.getElementById('locationBtnText');
            if (btnText) {
                btnText.innerHTML = '<i class="fas fa-check mr-2"></i> Location Saved';
            }
            
            const fields = ['latitude', 'longitude', 'place_name', 'full_address', 'city', 'state', 'country', 'postcode', 'house_number', 'road', 'suburb'];
            fields.forEach(field => {
                const el = document.getElementById(field);
                if (el) {
                    const key = field === 'place_name' ? 'placeName' : 
                               field === 'full_address' ? 'fullAddress' : field;
                    el.value = locationData[key] || '';
                }
            });
            
            sessionStorage.setItem('userLocation', JSON.stringify(locationData));
        }

        async function sendLocationToServer(locationData) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('latitude', locationData.latitude);
                formData.append('longitude', locationData.longitude);
                formData.append('place_name', locationData.placeName || locationData.fullAddress || '');
                formData.append('full_address', locationData.fullAddress || locationData.placeName || '');
                formData.append('city', locationData.city || '');
                formData.append('state', locationData.state || '');
                formData.append('country', locationData.country || '');
                formData.append('postcode', locationData.postcode || '');
                formData.append('house_number', locationData.houseNumber || '');
                formData.append('road', locationData.road || '');
                formData.append('suburb', locationData.suburb || '');

                const response = await fetch('{{ route("sub_three.home.location.update") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to update location');
                }

                const data = await response.json();
                console.log('Location saved:', data);
                
                showNotification('Location updated successfully!', 'success');
                
            } catch (error) {
                console.error('Error sending location to server:', error);
            }
        }

        function showNotification(message, type = 'success') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 z-[100] ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full max-w-sm`;
            notification.innerHTML = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        function toggleLocationDropdown() {
            const dropdown = document.getElementById('locationDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        async function clearCustomerLocation() {
            if (!confirm('Clear your current location? You can always share it again.')) {
                return;
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                const formData = new FormData();
                formData.append('_token', csrfToken);

                const response = await fetch('{{ route("sub_three.home.location.clear") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('Location cleared!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to clear location');
                }
            } catch (error) {
                console.error('Error clearing location:', error);
                alert(error.message || 'Failed to clear location. Please try again.');
            }
        }

        function clearSearch() {
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            if (searchInput) searchInput.value = '';
            if (searchForm) searchForm.submit();
        }

        function skipAllPreferences() {
            if (!confirm('Are you sure you want to skip all preferences? You can always update them later from the homepage.')) {
                return;
            }
            window.location.href = '{{ route("sub_three.home.showHome") }}';
        }

        async function savePreferences() {
            const form = document.getElementById('preferencesForm');
            const btn = document.getElementById('saveBtn');
            const originalText = btn.innerHTML;
            const overlay = document.getElementById('loadingOverlay');
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            btn.disabled = true;
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            
            try {
                const formData = new FormData(form);
                
                const response = await fetch(form.action, { 
                    method: 'POST', 
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                
                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Server error:', errorData);
                    
                    if (response.status === 422 && errorData.errors) {
                        let errorMessage = 'Please fix the following:\n';
                        for (const [field, messages] of Object.entries(errorData.errors)) {
                            errorMessage += `- ${field}: ${messages.join(', ')}\n`;
                        }
                        alert(errorMessage);
                    } else {
                        alert(errorData.message || 'Something went wrong. Please try again.');
                    }
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return;
                }
                
                const data = await response.json();
                
                if(data.success) {
                    document.getElementById('successOverlay').classList.remove('hidden');
                    document.getElementById('successOverlay').classList.add('flex');
                    
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '{{ route("sub_three.home.showHome") }}?preferences_saved=true';
                    }, 2000);
                } else {
                    alert(data.message || 'Something went wrong. Please try again.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch(e) {
                console.error('Error:', e);
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                alert('Error saving preferences. Please try again.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateProgressBar();
            updateNavigation();
            updateNextButtonState();
            updateAmenitiesCount();
        });

        window.getCustomerLocation = getCustomerLocation;
        window.toggleLocationDropdown = toggleLocationDropdown;
        window.clearCustomerLocation = clearCustomerLocation;
        window.prevStep = prevStep;
        window.nextStep = nextStep;
        window.skipStep = skipStep;
        window.skipAllPreferences = skipAllPreferences;
        window.savePreferences = savePreferences;
        window.setRateRange = setRateRange;
        window.clearSearch = clearSearch;
    </script>
</body>

</html>