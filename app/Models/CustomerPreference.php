<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPreference extends Model
{
    use SoftDeletes;

    protected $table = 'customer_preferences';

    /**
     * RECOMMENDATION ENGINE WEIGHT CONSTANTS
     * These must match the weights in HomeController
     */
    const WEIGHT_LOCATION = 0.30;
    const WEIGHT_FEATURES = 0.20;
    const WEIGHT_RATE = 0.15;
    const WEIGHT_SPACE_TYPE = 0.12;
    const WEIGHT_TIME_SLOT = 0.10;
    const WEIGHT_DURATION = 0.08;
    const WEIGHT_RATING = 0.05;

    protected $fillable = [
        'customer_account_id',
        'preferred_branch_ids',
        'preferred_location_city',
        'preferred_category_ids',
        'preferred_service_ids',
        'preferred_space_types',
        'preferred_features',
        'preferred_peak_hours',
        'preferred_time_slots',
        'preferred_time_duration',
        'preferred_session_duration',
        'preferred_start_time',        // NEW: User's preferred start time
        'preferred_end_time',          // NEW: User's preferred end time
        'preferred_seat_count',
        'min_duration_minutes',
        'max_duration_minutes',
        'min_price_preferred',
        'max_price_preferred',
        'min_rate_preferred',
        'max_rate_preferred',           
        'min_rating_preferred',
        'has_booking_history',
        'preference_strength',
        'preferences_completed_at',
    ];

    protected $casts = [
        'preferred_branch_ids' => 'array',
        'preferred_category_ids' => 'array',
        'preferred_service_ids' => 'array',
        'preferred_space_types' => 'array',
        'preferred_features' => 'array',
        'preferred_peak_hours' => 'array',
        'preferred_time_slots' => 'array',          
        'preferred_time_duration' => 'array',
        'preferred_session_duration' => 'decimal:2', 
        'preferred_start_time' => 'string',          // NEW
        'preferred_end_time' => 'string',            // NEW
        'preferred_seat_count' => 'integer',         
        'min_price_preferred' => 'decimal:2',
        'max_price_preferred' => 'decimal:2',
        'min_rate_preferred' => 'decimal:2',
        'max_rate_preferred' => 'decimal:2',
        'min_rating_preferred' => 'decimal:1',
        'has_booking_history' => 'boolean',
        'preferences_completed_at' => 'datetime',
        'preference_strength' => 'integer',
    ];

    /**
     * Get the customer that owns the preference
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    /**
     * Get preferred branches
     */
    public function preferredBranches()
    {
        if (empty($this->preferred_branch_ids)) {
            return collect();
        }
        return Branch::whereIn('id', $this->preferred_branch_ids)->get();
    }

    /**
     * Get preferred categories
     */
    public function preferredCategories()
    {
        if (empty($this->preferred_category_ids)) {
            return collect();
        }
        return ServiceCategory::whereIn('id', $this->preferred_category_ids)->get();
    }

    /**
     * Get preferred services
     */
    public function preferredServices()
    {
        if (empty($this->preferred_service_ids)) {
            return collect();
        }
        return ServiceName::whereIn('id', $this->preferred_service_ids)->get();
    }

    /**
     * ================================================================
     * RECOMMENDATION ENGINE INTEGRATION METHODS
     * These methods support the recommendation algorithm in HomeController
     * ================================================================
     */

    /**
     * Get all preferences as a formatted array for the recommendation engine
     * This matches the structure expected by HomeController::getBestBranchRecommendation()
     */
    public function getRecommendationData(): array
    {
        return [
            'preferred_features' => $this->preferred_features ?? [],
            'preferred_space_types' => $this->preferred_space_types ?? [],
            'preferred_time_slots' => $this->preferred_time_slots ?? [],
            'preferred_start_time' => $this->preferred_start_time ?? null,
            'preferred_end_time' => $this->preferred_end_time ?? null,
            'preferred_session_duration' => $this->preferred_session_duration ?? null,
            'min_rate_preferred' => $this->min_rate_preferred ?? null,
            'max_rate_preferred' => $this->max_rate_preferred ?? null,
            'min_rating_preferred' => $this->min_rating_preferred ?? null,
            'preferred_branch_ids' => $this->preferred_branch_ids ?? [],
            'preferred_category_ids' => $this->preferred_category_ids ?? [],
            'preferred_service_ids' => $this->preferred_service_ids ?? [],
            'preferred_peak_hours' => $this->preferred_peak_hours ?? [],
            'preferred_time_duration' => $this->preferred_time_duration ?? [],
        ];
    }

    /**
     * Get preference strength for each dimension (0-100)
     * Used for displaying the breakdown in the UI
     */
    public function getDimensionStrengths(): array
    {
        return [
            'location' => $this->getLocationPreferenceStrength(),
            'features' => $this->getFeaturesPreferenceStrength(),
            'rate' => $this->getRatePreferenceStrength(),
            'space_type' => $this->getSpaceTypePreferenceStrength(),
            'time_slot' => $this->getTimeSlotPreferenceStrength(),
            'duration' => $this->getDurationPreferenceStrength(),
            'rating' => $this->getRatingPreferenceStrength(),
        ];
    }

    /**
     * Get location preference strength
     * Returns 100 if location is set, 0 otherwise
     */
    public function getLocationPreferenceStrength(): int
    {
        // Location is determined by GPS, not stored in preferences
        // If user has completed preferences, location is assumed
        return $this->preferences_completed_at ? 100 : 0;
    }

    /**
     * Get features preference strength
     * Based on number of features selected
     */
    public function getFeaturesPreferenceStrength(): int
    {
        $features = $this->preferred_features ?? [];
        if (empty($features)) {
            return 0;
        }
        // 3+ features = 100%, 2 features = 66%, 1 feature = 33%
        return min(100, (count($features) / 3) * 100);
    }

    /**
     * Get rate preference strength
     * Based on whether rate range is set
     */
    public function getRatePreferenceStrength(): int
    {
        if (!is_null($this->min_rate_preferred) || !is_null($this->max_rate_preferred)) {
            return 100;
        }
        return 0;
    }

    /**
     * Get space type preference strength
     * Based on number of space types selected
     */
    public function getSpaceTypePreferenceStrength(): int
    {
        $spaceTypes = $this->preferred_space_types ?? [];
        if (empty($spaceTypes)) {
            return 0;
        }
        // 2+ space types = 100%, 1 space type = 50%
        return min(100, count($spaceTypes) * 50);
    }

    /**
     * Get time slot preference strength
     * Based on number of time slots selected
     */
    public function getTimeSlotPreferenceStrength(): int
    {
        $timeSlots = $this->preferred_time_slots ?? [];
        if (empty($timeSlots)) {
            return 0;
        }
        // 3+ time slots = 100%, 2 = 66%, 1 = 33%
        return min(100, (count($timeSlots) / 3) * 100);
    }

    /**
     * Get duration preference strength
     * Based on whether session duration is set
     */
    public function getDurationPreferenceStrength(): int
    {
        if (!is_null($this->preferred_session_duration) && $this->preferred_session_duration > 0) {
            return 100;
        }
        return 0;
    }

    /**
     * Get rating preference strength
     * Based on whether min rating is set
     */
    public function getRatingPreferenceStrength(): int
    {
        if (!is_null($this->min_rating_preferred) && $this->min_rating_preferred > 0) {
            return 100;
        }
        return 0;
    }

    /**
     * Get the percentage of preferences completed
     */
    public function getCompletionPercentage(): int
    {
        $dimensions = $this->getDimensionStrengths();
        $total = array_sum($dimensions);
        $count = count(array_filter($dimensions, function($value) {
            return $value > 0;
        }));
        return $count > 0 ? round($total / count($dimensions)) : 0;
    }

    /**
     * Get missing preference dimensions
     */
    public function getMissingDimensions(): array
    {
        $missing = [];
        $dimensions = [
            'features' => 'Preferred Features',
            'space_type' => 'Preferred Space Types',
            'time_slot' => 'Preferred Time Slots',
            'duration' => 'Preferred Session Duration',
            'rate' => 'Preferred Rate Range',
            'rating' => 'Minimum Rating',
        ];

        foreach ($dimensions as $key => $label) {
            $method = 'get' . ucfirst($key) . 'PreferenceStrength';
            if (method_exists($this, $method) && $this->$method() === 0) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Check if preferences are ready for hybrid recommendation
     * Requires at least 50% completion
     */
    public function isReadyForHybrid(): bool
    {
        return $this->getCompletionPercentage() >= 50;
    }

    /**
     * Get recommendation readiness status
     */
    public function getReadinessStatus(): array
    {
        $percentage = $this->getCompletionPercentage();
        $missing = $this->getMissingDimensions();

        return [
            'percentage' => $percentage,
            'is_ready' => $percentage >= 50,
            'missing_dimensions' => $missing,
            'status' => $percentage >= 75 ? 'Excellent' 
                : ($percentage >= 50 ? 'Good' 
                    : ($percentage >= 25 ? 'Basic' : 'Needs improvement')),
            'recommendation' => empty($missing) ? 'All preferences set!' 
                : 'Missing: ' . implode(', ', array_slice($missing, 0, 3)) . (count($missing) > 3 ? ' and ' . (count($missing) - 3) . ' more' : '')
        ];
    }

    /**
     * ================================================================
     * END RECOMMENDATION ENGINE INTEGRATION METHODS
     * ================================================================
     */

    /**
     * Check if preferences are completed and have actual data
     */
    public function isCompleted(): bool
    {
        if (is_null($this->preferences_completed_at)) {
            return false;
        }
        
        $hasData = !empty($this->preferred_features) ||
                   !empty($this->preferred_space_types) ||
                   !empty($this->preferred_peak_hours) ||
                   !empty($this->preferred_time_slots) ||
                   !empty($this->preferred_branch_ids) ||
                   !empty($this->preferred_category_ids) ||
                   !empty($this->preferred_service_ids) ||
                   !empty($this->preferred_time_duration) ||
                   !is_null($this->preferred_session_duration) ||
                   !is_null($this->preferred_start_time) ||
                   !is_null($this->preferred_end_time) ||
                   !is_null($this->min_rate_preferred) ||
                   !is_null($this->max_rate_preferred) ||
                   !is_null($this->min_rating_preferred);
        
        return $hasData;
    }

    /**
     * Check if preferences are empty (skip all)
     */
    public function isEmpty(): bool
    {
        return empty($this->preferred_features) && 
               empty($this->preferred_space_types) && 
               empty($this->preferred_peak_hours) && 
               empty($this->preferred_time_slots) &&
               empty($this->preferred_branch_ids) && 
               empty($this->preferred_category_ids) && 
               empty($this->preferred_service_ids) &&
               empty($this->preferred_time_duration) &&
               is_null($this->preferred_session_duration) &&
               is_null($this->preferred_start_time) &&
               is_null($this->preferred_end_time) &&
               is_null($this->min_rate_preferred) &&
               is_null($this->max_rate_preferred) &&
               is_null($this->min_rating_preferred);
    }

    /**
     * Calculate preference strength based on selections (0-1 scale)
     * UPDATED with priority weights matching the recommendation engine
     */
    public function calculateStrength(): float
    {
        $strength = 0;
        $totalWeights = 0;

        // Location (weight: 30%) - HIGHEST
        if ($this->preferences_completed_at && !empty($this->preferred_location_city)) {
            $strength += self::WEIGHT_LOCATION;
        }
        $totalWeights += self::WEIGHT_LOCATION;

        // Features (weight: 20%)
        if (!empty($this->preferred_features)) {
            $strength += self::WEIGHT_FEATURES * min(count($this->preferred_features) / 3, 1);
        }
        $totalWeights += self::WEIGHT_FEATURES;

        // Rate (weight: 15%)
        if (!is_null($this->min_rate_preferred) || !is_null($this->max_rate_preferred)) {
            $strength += self::WEIGHT_RATE;
        }
        $totalWeights += self::WEIGHT_RATE;

        // Space types (weight: 12%)
        if (!empty($this->preferred_space_types)) {
            $strength += self::WEIGHT_SPACE_TYPE * min(count($this->preferred_space_types) / 2, 1);
        }
        $totalWeights += self::WEIGHT_SPACE_TYPE;

        // Time slots (weight: 10%)
        if (!empty($this->preferred_time_slots)) {
            $strength += self::WEIGHT_TIME_SLOT * min(count($this->preferred_time_slots) / 3, 1);
        }
        $totalWeights += self::WEIGHT_TIME_SLOT;

        // Session duration (weight: 8%)
        if (!is_null($this->preferred_session_duration) && $this->preferred_session_duration > 0) {
            $strength += self::WEIGHT_DURATION;
        }
        $totalWeights += self::WEIGHT_DURATION;

        // Rating (weight: 5%) - LOWEST
        if (!is_null($this->min_rating_preferred) && $this->min_rating_preferred > 0) {
            $strength += self::WEIGHT_RATING;
        }
        $totalWeights += self::WEIGHT_RATING;

        // Start time (bonus, not in main weights)
        if (!is_null($this->preferred_start_time)) {
            $strength += 0.05;
        }
        $totalWeights += 0.05;

        // End time (bonus, not in main weights)
        if (!is_null($this->preferred_end_time)) {
            $strength += 0.05;
        }
        $totalWeights += 0.05;

        // Branches (bonus)
        if (!empty($this->preferred_branch_ids)) {
            $strength += 0.05 * min(count($this->preferred_branch_ids) / 5, 1);
        }
        $totalWeights += 0.05;

        // Categories (bonus)
        if (!empty($this->preferred_category_ids)) {
            $strength += 0.05 * min(count($this->preferred_category_ids) / 3, 1);
        }
        $totalWeights += 0.05;

        // Services (bonus)
        if (!empty($this->preferred_service_ids)) {
            $strength += 0.05 * min(count($this->preferred_service_ids) / 10, 1);
        }
        $totalWeights += 0.05;

        return $totalWeights > 0 ? round($strength / $totalWeights, 2) : 0;
    }

    /**
     * Calculate strength percentage for display (0-100%)
     */
    public function getStrengthPercentageAttribute(): int
    {
        return (int) round($this->preference_strength * 100);
    }

    /**
     * Get strength level (Basic, Intermediate, Advanced, Complete)
     */
    public function getStrengthLevelAttribute(): string
    {
        $strength = $this->preference_strength;

        if ($strength >= 0.75)
            return 'Complete';
        if ($strength >= 0.5)
            return 'Advanced';
        if ($strength >= 0.25)
            return 'Intermediate';
        return 'Basic';
    }

    /**
     * Update preference strength
     */
    public function updateStrength(): void
    {
        $this->preference_strength = $this->calculateStrength();
        $this->save();
    }

    /**
     * Check if user has strong preferences
     */
    public function hasStrongPreferences(): bool
    {
        return $this->preference_strength >= 0.5;
    }

    /**
     * Get formatted display strength (e.g., 7.5/10)
     */
    public function getDisplayStrengthAttribute(): string
    {
        $strength = $this->preference_strength ?? 0;
        return number_format($strength * 10, 1) . '/10';
    }

    /**
     * Get last updated time in human readable format
     */
    public function getLastUpdatedAttribute(): string
    {
        if (!$this->preferences_completed_at) {
            return 'Never';
        }

        return $this->preferences_completed_at->diffForHumans();
    }

    /**
     * Scope: Users with completed preferences
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('preferences_completed_at');
    }

    /**
     * Scope: Users with strong preferences
     */
    public function scopeStrong($query)
    {
        return $query->where('preference_strength', '>=', 0.5);
    }

    /**
     * Mark preferences as completed
     */
    public function markAsCompleted(): void
    {
        $this->preferences_completed_at = now();
        $this->preference_strength = $this->calculateStrength();
        $this->save();
    }

    /**
     * Reset preferences
     */
    public function reset(): void
    {
        $this->preferred_branch_ids = [];
        $this->preferred_category_ids = [];
        $this->preferred_service_ids = [];
        $this->preferred_space_types = [];
        $this->preferred_features = [];
        $this->preferred_peak_hours = [];
        $this->preferred_time_slots = [];
        $this->preferred_time_duration = [];
        $this->preferred_session_duration = null;
        $this->preferred_start_time = null;
        $this->preferred_end_time = null;
        $this->preferred_seat_count = null;
        $this->min_duration_minutes = null;
        $this->max_duration_minutes = null;
        $this->min_price_preferred = null;
        $this->max_price_preferred = null;
        $this->min_rate_preferred = null;
        $this->max_rate_preferred = null;
        $this->min_rating_preferred = null;
        $this->preferences_completed_at = null;
        $this->preference_strength = 0;
        $this->save();
    }

    /**
     * Check if user has any preferences set
     */
    public function hasAnyPreferences(): bool
    {
        return !empty($this->preferred_branch_ids) ||
            !empty($this->preferred_category_ids) ||
            !empty($this->preferred_service_ids) ||
            !empty($this->preferred_space_types) ||
            !empty($this->preferred_features) ||
            !empty($this->preferred_peak_hours) ||
            !empty($this->preferred_time_slots) ||
            !empty($this->preferred_time_duration) ||
            !is_null($this->preferred_session_duration) ||
            !is_null($this->preferred_start_time) ||
            !is_null($this->preferred_end_time) ||
            !is_null($this->min_rate_preferred) ||
            !is_null($this->max_rate_preferred) ||
            !is_null($this->min_rating_preferred);
    }

    /**
     * Get all preference categories with their priority weights for display
     */
    public function getPreferenceCategories(): array
    {
        return [
            'location' => [
                'label' => '📍 Location',
                'weight' => 30,
                'priority' => 'Highest',
                'value' => $this->preferred_location_city ?? 'Not set',
                'status' => $this->preferred_location_city ? '✅ Set' : '❌ Not set',
                'max' => 1,
            ],
            'features' => [
                'label' => '✨ Features',
                'weight' => 20,
                'priority' => 'High',
                'value' => $this->preferred_features ? count($this->preferred_features) . ' selected' : 'None selected',
                'status' => !empty($this->preferred_features) ? '✅ Set' : '❌ Not set',
                'max' => 10,
            ],
            'rate' => [
                'label' => '💰 Rate',
                'weight' => 15,
                'priority' => 'High',
                'value' => $this->min_rate_preferred && $this->max_rate_preferred 
                    ? '₱' . $this->min_rate_preferred . ' - ₱' . $this->max_rate_preferred
                    : ($this->min_rate_preferred ? '₱' . $this->min_rate_preferred . '+' : 'Not set'),
                'status' => (!is_null($this->min_rate_preferred) || !is_null($this->max_rate_preferred)) ? '✅ Set' : '❌ Not set',
                'max' => 1000,
            ],
            'space_type' => [
                'label' => '🪑 Space Type',
                'weight' => 12,
                'priority' => 'Medium',
                'value' => $this->preferred_space_types ? count($this->preferred_space_types) . ' selected' : 'None selected',
                'status' => !empty($this->preferred_space_types) ? '✅ Set' : '❌ Not set',
                'max' => 4,
            ],
            'time_slot' => [
                'label' => '🕐 Time Slot',
                'weight' => 10,
                'priority' => 'Medium-Low',
                'value' => $this->preferred_time_slots ? count($this->preferred_time_slots) . ' selected' : 'None selected',
                'status' => !empty($this->preferred_time_slots) ? '✅ Set' : '❌ Not set',
                'max' => 5,
            ],
            'session_duration' => [
                'label' => '⏱️ Duration',
                'weight' => 8,
                'priority' => 'Low',
                'value' => $this->preferred_session_duration ? $this->preferred_session_duration . ' hrs' : 'Not set',
                'status' => !is_null($this->preferred_session_duration) ? '✅ Set' : '❌ Not set',
                'max' => 8,
            ],
            'rating' => [
                'label' => '⭐ Rating',
                'weight' => 5,
                'priority' => 'Lowest',
                'value' => $this->min_rating_preferred ? $this->min_rating_preferred . '★+' : 'Not set',
                'status' => !is_null($this->min_rating_preferred) ? '✅ Set' : '❌ Not set',
                'max' => 5,
            ],
            'start_time' => [
                'label' => '⏰ Start Time',
                'weight' => 5,
                'priority' => 'Bonus',
                'value' => $this->preferred_start_time ?: 'Not set',
                'status' => !is_null($this->preferred_start_time) ? '✅ Set' : '❌ Not set',
                'max' => 24,
            ],
            'end_time' => [
                'label' => '⏰ End Time',
                'weight' => 5,
                'priority' => 'Bonus',
                'value' => $this->preferred_end_time ?: 'Not set',
                'status' => !is_null($this->preferred_end_time) ? '✅ Set' : '❌ Not set',
                'max' => 24,
            ],
        ];
    }

    /**
     * Get preference completion summary
     */
    public function getCompletionSummary(): array
    {
        $categories = $this->getPreferenceCategories();
        $completed = 0;
        $total = count($categories);

        foreach ($categories as $category) {
            if (strpos($category['status'], '✅') !== false) {
                $completed++;
            }
        }

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => round(($completed / $total) * 100),
            'level' => $this->getStrengthLevelAttribute(),
            'recommendation_ready' => $this->isReadyForHybrid(),
        ];
    }

    /**
     * Get formatted time preferences for display
     */
    public function getFormattedTimePreferences(): array
    {
        $timeSlots = $this->preferred_time_slots ?? [];
        $slotLabels = [
            'early_morning' => '5:00 AM - 8:00 AM',
            'morning' => '8:00 AM - 12:00 PM',
            'afternoon' => '12:00 PM - 5:00 PM',
            'evening' => '5:00 PM - 9:00 PM',
            'late_night' => '9:00 PM - 11:59 PM',
        ];

        $formatted = [];
        foreach ($timeSlots as $slot) {
            if (isset($slotLabels[$slot])) {
                $formatted[] = $slotLabels[$slot];
            }
        }

        return [
            'time_slots' => $formatted,
            'start_time' => $this->preferred_start_time,
            'end_time' => $this->preferred_end_time,
            'session_duration' => $this->preferred_session_duration,
        ];
    }

    /**
     * Get preference match for a specific branch
     * This is used by the recommendation engine
     */
    public function getBranchMatchScore($branchId): array
    {
        // This would be called by the recommendation engine
        // Returns the match score for a specific branch
        // Implementation is in HomeController
        return [
            'branch_id' => $branchId,
            'match_percentage' => 0,
            'scores' => $this->getDimensionStrengths(),
        ];
    }

    /**
     * Get a summary of what's missing for better recommendations
     */
    public function getImprovementTips(): array
    {
        $tips = [];
        $dimensions = $this->getDimensionStrengths();

        if ($dimensions['features'] < 50) {
            $tips[] = 'Add more features to improve match accuracy';
        }
        if ($dimensions['space_type'] < 50) {
            $tips[] = 'Select your preferred space types';
        }
        if ($dimensions['time_slot'] < 50) {
            $tips[] = 'Add time slots you usually work';
        }
        if ($dimensions['duration'] < 50) {
            $tips[] = 'Set your preferred session duration';
        }
        if ($dimensions['rate'] < 50) {
            $tips[] = 'Set your budget range for better price matches';
        }
        if ($dimensions['rating'] < 50) {
            $tips[] = 'Set your minimum rating preference';
        }

        return $tips;
    }
}