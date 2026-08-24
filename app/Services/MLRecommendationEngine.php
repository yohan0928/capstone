<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\ServiceName;
use App\Models\ServiceCategory;
use App\Models\BookingPayment;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\CustomerAccount;
use Illuminate\Support\Facades\DB;

class MLRecommendationEngine
{
    private $ownerId;
    private $branchIds;
    private $dateFrom;
    private $dateTo;
    
    // Enhanced weights with booking-specific focus
    private $weights = [
        'revenue_impact' => 0.25,
        'customer_satisfaction' => 0.15,
        'operational_efficiency' => 0.20,
        'booking_optimization' => 0.25,  // Focus on booking patterns
        'service_performance' => 0.15,
    ];
    
    private $benchmarks = [];
    
    public function __construct($ownerId, $branchIds, $dateFrom, $dateTo)
    {
        $this->ownerId = $ownerId;
        $this->branchIds = $branchIds;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        
        $this->loadBenchmarks();
    }
    
    /**
     * Enhanced recommendation generation with booking data
     */
    public function generateRecommendations($metrics, $bookingData, $productData, $feedbackData, $inventoryData = null)
    {
        $recommendations = [];
        
        // 1. Extract features from all business data
        $features = $this->extractEnhancedFeatures($metrics, $bookingData);
        
        // 2. Detect patterns in booking and service performance
        $patterns = $this->detectBookingPatterns($features);
        
        // 3. Generate specific recommendations
        $recommendations = array_merge(
            $this->generateBookingOptimizationRecommendations($features, $patterns),
            $this->generateServicePerformanceRecommendations($features, $patterns),
            $this->generateRevenueOptimizationRecommendations($features, $patterns),
            $this->generateCustomerRetentionRecommendations($features, $patterns),
            $this->generateOperationalEfficiencyRecommendations($features, $patterns)
        );
        
        // 4. Score recommendations
        $scoredRecommendations = $this->scoreEnhancedRecommendations($recommendations, $features);
        
        // 5. Apply business context
        $finalRecommendations = $this->applyBusinessContext($scoredRecommendations, $features);
        
        // Sort by final score and return
        usort($finalRecommendations, function($a, $b) {
            return $b['final_score'] <=> $a['final_score'];
        });
        
        return array_slice($finalRecommendations, 0, 12);
    }
    
    /**
     * Extract comprehensive features from booking data
     */
    private function extractEnhancedFeatures($metrics, $bookingData)
    {
        $features = [];
        
        // Booking-specific features
        $features['booking_occupancy_rate'] = $metrics['occupancy_rate'] ?? 0;
        $features['avg_booking_value'] = $metrics['avg_booking_value'] ?? 0;
        $features['booking_cancellation_rate'] = $this->calculateBookingCancellationRate($bookingData);
        $features['online_booking_ratio'] = $this->calculateOnlineBookingRatio($bookingData);
        $features['booking_lead_time'] = $this->calculateAverageLeadTime();
        $features['peak_hour_utilization'] = $this->calculatePeakHourUtilization();
        $features['service_diversity'] = $this->calculateServiceDiversity();
        
        // Revenue features
        $features['revenue_growth'] = $this->calculateRevenueGrowth();
        $features['revenue_consistency'] = $this->calculateRevenueConsistency();
        
        // Customer features
        $features['customer_retention_rate'] = $metrics['retention_rate'] ?? 0;
        $features['new_customer_rate'] = $this->calculateNewCustomerRate();
        $features['customer_satisfaction'] = $metrics['average_rating'] ?? 0;
        
        // Operational features
        $features['staff_efficiency'] = $this->estimateStaffEfficiency();
        $features['resource_utilization'] = $this->calculateResourceUtilization();
        
        // Service performance features
        $features['service_popularity_variance'] = $this->calculateServicePopularityVariance();
        $features['service_revenue_concentration'] = $this->calculateServiceRevenueConcentration();
        
        return $this->normalizeFeatures($features);
    }
    
    /**
     * Detect patterns in booking and service data
     */
    private function detectBookingPatterns($features)
    {
        $patterns = [];
        
        // Load historical successful booking patterns
        $historicalPatterns = $this->loadHistoricalBookingPatterns();
        
        foreach ($historicalPatterns as $pattern) {
            $similarity = $this->cosineSimilarity($features, $pattern['features']);
            
            if ($similarity > 0.7) {
                $patterns[] = [
                    'pattern_type' => $pattern['type'],
                    'similarity_score' => $similarity,
                    'success_rate' => $pattern['success_rate'],
                    'recommended_actions' => $pattern['recommended_actions']
                ];
            }
        }
        
        // Detect seasonal patterns in bookings
        $seasonalPatterns = $this->detectSeasonalBookingPatterns();
        $patterns = array_merge($patterns, $seasonalPatterns);
        
        // Detect service-specific patterns
        $servicePatterns = $this->detectServiceUsagePatterns();
        $patterns = array_merge($patterns, $servicePatterns);
        
        return $patterns;
    }
    
    /**
     * Generate booking optimization recommendations
     */
    private function generateBookingOptimizationRecommendations($features, $patterns)
    {
        $recommendations = [];
        
        // 1. Low occupancy rate
        if ($features['booking_occupancy_rate'] < 0.6) {
            $occupancy = $features['booking_occupancy_rate'] * 100;
            $recommendations[] = $this->createEnhancedRecommendation(
                'booking_optimization',
                'Improve Booking Occupancy Rate',
                "Your booking occupancy rate is {$occupancy}%. Consider optimizing scheduling, offering off-peak discounts, or improving service visibility.",
                'Medium',
                '15-25%',
                'Implement dynamic pricing and targeted promotions for low-occupancy periods',
                0.85,
                'high_priority',
                'Increase revenue by filling unused time slots'
            );
        }
        
        // 2. High cancellation rate
        if ($features['booking_cancellation_rate'] > 0.15) {
            $cancellationRate = $features['booking_cancellation_rate'] * 100;
            $recommendations[] = $this->createEnhancedRecommendation(
                'booking_management',
                'Reduce Booking Cancellations',
                "Cancellation rate is {$cancellationRate}%. This affects revenue and resource planning.",
                'High',
                '10-20%',
                'Implement reminder systems, flexible rescheduling policies, and cancellation fees',
                0.9,
                'high_priority',
                'Improve revenue predictability and resource utilization'
            );
        }
        
        // 3. Low online booking ratio
        if ($features['online_booking_ratio'] < 0.3) {
            $onlineRatio = $features['online_booking_ratio'] * 100;
            $recommendations[] = $this->createEnhancedRecommendation(
                'digital_transformation',
                'Increase Online Bookings',
                "Only {$onlineRatio}% of bookings are made online. Online bookings reduce administrative work.",
                'Medium',
                '8-15%',
                'Promote online booking convenience on your website and social media',
                0.75,
                'medium_priority',
                'Reduce manual work and improve customer convenience'
            );
        }
        
        // 4. Long booking lead time
        if ($features['booking_lead_time'] > 2.0) { // More than 2 days average lead time
            $recommendations[] = $this->createEnhancedRecommendation(
                'booking_timing',
                'Optimize Booking Timing',
                'Average booking lead time is long. Last-minute bookings might be missed opportunities.',
                'Medium',
                '5-12%',
                'Create last-minute booking promotions and optimize scheduling',
                0.7,
                'medium_priority',
                'Capture spontaneous booking opportunities'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Generate service performance recommendations
     */
    private function generateServicePerformanceRecommendations($features, $patterns)
    {
        $recommendations = [];
        
        // 1. High service popularity variance
        if ($features['service_popularity_variance'] > 0.8) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'service_portfolio',
                'Balance Service Portfolio',
                'Some services are significantly more popular than others. This indicates untapped potential.',
                'Medium',
                '10-18%',
                'Promote underperforming services or bundle them with popular ones',
                0.8,
                'medium_priority',
                'Maximize revenue from all service offerings'
            );
        }
        
        // 2. High service revenue concentration
        if ($features['service_revenue_concentration'] > 0.7) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'revenue_diversification',
                'Diversify Service Revenue',
                'Revenue is heavily concentrated in few services. This creates business risk.',
                'High',
                '12-20%',
                'Develop and promote complementary services',
                0.85,
                'high_priority',
                'Reduce dependency on specific services'
            );
        }
        
        // 3. Low service diversity
        if ($features['service_diversity'] < 0.4) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'service_innovation',
                'Expand Service Offerings',
                'Limited service diversity may be missing customer segments.',
                'Medium',
                '15-25%',
                'Research customer needs and introduce new services',
                0.7,
                'medium_priority',
                'Capture new market segments'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Generate revenue optimization recommendations
     */
    private function generateRevenueOptimizationRecommendations($features, $patterns)
    {
        $recommendations = [];
        
        // 1. Low average booking value
        if ($features['avg_booking_value'] < 0.4) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'revenue_optimization',
                'Increase Average Booking Value',
                'Average booking value is below optimal. Consider upselling and bundling.',
                'High',
                '20-30%',
                'Create service bundles and train staff on upselling techniques',
                0.9,
                'high_priority',
                'Maximize revenue per customer'
            );
        }
        
        // 2. Inconsistent revenue
        if ($features['revenue_consistency'] < 0.6) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'revenue_stability',
                'Stabilize Revenue Streams',
                'Revenue shows high variability. This affects cash flow planning.',
                'Medium',
                '8-15%',
                'Implement subscription models or loyalty programs',
                0.75,
                'medium_priority',
                'Improve cash flow predictability'
            );
        }
        
        // 3. Slow revenue growth
        if ($features['revenue_growth'] < 0.1) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'growth_strategy',
                'Boost Revenue Growth',
                'Revenue growth has stagnated. Consider new marketing strategies.',
                'High',
                '15-25%',
                'Launch targeted marketing campaigns and referral programs',
                0.85,
                'high_priority',
                'Accelerate business growth'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Generate customer retention recommendations
     */
    private function generateCustomerRetentionRecommendations($features, $patterns)
    {
        $recommendations = [];
        
        // 1. Low customer retention
        if ($features['customer_retention_rate'] < 0.4) {
            $retentionRate = $features['customer_retention_rate'] * 100;
            $recommendations[] = $this->createEnhancedRecommendation(
                'customer_loyalty',
                'Improve Customer Retention',
                "Customer retention rate is {$retentionRate}%. Focus on building loyalty.",
                'High',
                '25-35%',
                'Implement loyalty programs and personalized follow-ups',
                0.9,
                'high_priority',
                'Increase customer lifetime value'
            );
        }
        
        // 2. Low new customer rate
        if ($features['new_customer_rate'] < 0.3) {
            $newCustomerRate = $features['new_customer_rate'] * 100;
            $recommendations[] = $this->createEnhancedRecommendation(
                'customer_acquisition',
                'Increase New Customer Acquisition',
                "New customer acquisition rate is {$newCustomerRate}%. Expand your reach.",
                'Medium',
                '15-22%',
                'Launch referral programs and partnerships',
                0.8,
                'medium_priority',
                'Expand customer base'
            );
        }
        
        // 3. Low customer satisfaction
        if ($features['customer_satisfaction'] < 0.7) {
            $satisfaction = $features['customer_satisfaction'] * 100;
            $recommendations[] = $this->createEnhancedRecommendation(
                'service_quality',
                'Enhance Service Quality',
                "Customer satisfaction score is {$satisfaction}%. Focus on service improvements.",
                'High',
                '18-28%',
                'Implement quality assurance and staff training programs',
                0.85,
                'high_priority',
                'Improve customer experience and word-of-mouth referrals'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Generate operational efficiency recommendations
     */
    private function generateOperationalEfficiencyRecommendations($features, $patterns)
    {
        $recommendations = [];
        
        // 1. Low staff efficiency
        if ($features['staff_efficiency'] < 0.6) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'staff_optimization',
                'Optimize Staff Scheduling',
                'Staff efficiency could be improved. Consider better scheduling.',
                'Medium',
                '10-18%',
                'Implement dynamic scheduling based on booking patterns',
                0.75,
                'medium_priority',
                'Reduce labor costs and improve service quality'
            );
        }
        
        // 2. Low resource utilization
        if ($features['resource_utilization'] < 0.5) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'resource_management',
                'Improve Resource Utilization',
                'Resources are underutilized. Optimize allocation.',
                'Medium',
                '8-15%',
                'Analyze usage patterns and adjust resource allocation',
                0.7,
                'medium_priority',
                'Maximize return on resources'
            );
        }
        
        // 3. High peak hour utilization
        if ($features['peak_hour_utilization'] > 0.8) {
            $recommendations[] = $this->createEnhancedRecommendation(
                'peak_management',
                'Manage Peak Hour Capacity',
                'Peak hours are highly utilized. Consider capacity expansion.',
                'High',
                '12-20%',
                'Add capacity or implement peak pricing',
                0.8,
                'high_priority',
                'Capture peak demand revenue'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Create enhanced recommendation structure
     */
    private function createEnhancedRecommendation($type, $title, $description, $impact, $revenuePotential, $action, $baseScore, $priority, $businessImpact)
    {
        return [
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'impact' => $impact,
            'estimated_revenue_increase' => $revenuePotential,
            'action' => $action,
            'base_score' => $baseScore,
            'priority' => $priority,
            'business_impact' => $businessImpact,
            'implementation_time' => $this->estimateImplementationTime($type),
            'resource_requirement' => $this->estimateResourceRequirement($type)
        ];
    }
    
    /**
     * Calculate booking cancellation rate from booking data
     */
    private function calculateBookingCancellationRate($bookingData)
    {
        $totalBookings = 0;
        $cancelledBookings = 0;
        
        if (isset($bookingData['by_status'])) {
            foreach ($bookingData['by_status'] as $status => $count) {
                $totalBookings += $count;
                if (strtolower($status) === 'cancelled') {
                    $cancelledBookings = $count;
                }
            }
        }
        
        return $totalBookings > 0 ? ($cancelledBookings / $totalBookings) : 0;
    }
    
    /**
     * Calculate online booking ratio
     */
    private function calculateOnlineBookingRatio($bookingData)
    {
        $onlineBookings = $bookingData['by_type']['Online'] ?? 0;
        $walkinBookings = $bookingData['by_type']['Walk-in'] ?? 0;
        $total = $onlineBookings + $walkinBookings;
        
        return $total > 0 ? ($onlineBookings / $total) : 0;
    }
    
    /**
     * Calculate average booking lead time (days between booking and service date)
     */
    private function calculateAverageLeadTime()
    {
        $averageLeadTime = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('booking_date', [$this->dateFrom, $this->dateTo])
            ->whereNotNull('date_start')
            ->selectRaw('AVG(DATEDIFF(date_start, booking_date)) as avg_lead_time')
            ->value('avg_lead_time');
        
        return $averageLeadTime ? floatval($averageLeadTime) : 0;
    }
    
    /**
     * Calculate peak hour utilization
     */
    private function calculatePeakHourUtilization()
    {
        $peakHourData = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('date_start', [$this->dateFrom, $this->dateTo])
            ->whereNotNull('start_time')
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as booking_count')
            ->groupBy('hour')
            ->orderByDesc('booking_count')
            ->first();
        
        $peakHourBookings = $peakHourData->booking_count ?? 0;
        $totalBookings = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('date_start', [$this->dateFrom, $this->dateTo])
            ->count();
        
        return $totalBookings > 0 ? ($peakHourBookings / $totalBookings) : 0;
    }
    
    /**
     * Calculate service diversity (number of unique services used)
     */
    private function calculateServiceDiversity()
    {
        $totalServices = ServiceName::whereIn('branch_id', $this->branchIds)
            ->where('active', 1)
            ->count();
        
        $usedServices = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('date_start', [$this->dateFrom, $this->dateTo])
            ->distinct('service_name_id')
            ->count('service_name_id');
        
        return $totalServices > 0 ? ($usedServices / $totalServices) : 0;
    }
    
    /**
     * Calculate service popularity variance
     */
    private function calculateServicePopularityVariance()
    {
        $serviceUsage = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('date_start', [$this->dateFrom, $this->dateTo])
            ->selectRaw('service_name_id, COUNT(*) as usage_count')
            ->groupBy('service_name_id')
            ->get()
            ->pluck('usage_count')
            ->toArray();
        
        if (count($serviceUsage) < 2) {
            return 0;
        }
        
        $mean = array_sum($serviceUsage) / count($serviceUsage);
        $variance = 0;
        
        foreach ($serviceUsage as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $variance = $variance / count($serviceUsage);
        $stdDev = sqrt($variance);
        
        return $mean > 0 ? ($stdDev / $mean) : 0;
    }
    
    /**
     * Calculate service revenue concentration (Gini coefficient)
     */
    private function calculateServiceRevenueConcentration()
    {
        $serviceRevenue = DB::table('bookings as b')
            ->join('booking_payments as bp', 'b.id', '=', 'bp.booking_id')
            ->whereIn('b.branch_id', $this->branchIds)
            ->whereBetween('b.date_start', [$this->dateFrom, $this->dateTo])
            ->where('bp.payment_status', 1)
            ->selectRaw('b.service_name_id, SUM(bp.total_amount) as revenue')
            ->groupBy('b.service_name_id')
            ->orderByDesc('revenue')
            ->get()
            ->pluck('revenue')
            ->toArray();
        
        if (count($serviceRevenue) < 2) {
            return 0;
        }
        
        // Sort ascending for Gini calculation
        sort($serviceRevenue);
        $n = count($serviceRevenue);
        $cumulativeRevenue = 0;
        $totalRevenue = array_sum($serviceRevenue);
        
        for ($i = 0; $i < $n; $i++) {
            $cumulativeRevenue += $serviceRevenue[$i] * ($i + 1);
        }
        
        // Gini coefficient formula
        $gini = (2 * $cumulativeRevenue) / ($n * $totalRevenue) - ($n + 1) / $n;
        
        return max(0, min(1, $gini));
    }
    
    /**
     * Calculate revenue growth (month-over-month)
     */
    private function calculateRevenueGrowth()
    {
        $currentRevenue = BookingPayment::whereIn('branch_id', $this->branchIds)
            ->whereBetween('payment_date', [$this->dateFrom, $this->dateTo])
            ->where('payment_status', 1)
            ->sum('total_amount');
        
        $previousPeriodFrom = $this->dateFrom->copy()->subMonth();
        $previousPeriodTo = $this->dateTo->copy()->subMonth();
        
        $previousRevenue = BookingPayment::whereIn('branch_id', $this->branchIds)
            ->whereBetween('payment_date', [$previousPeriodFrom, $previousPeriodTo])
            ->where('payment_status', 1)
            ->sum('total_amount');
        
        if ($previousRevenue == 0) {
            return $currentRevenue > 0 ? 1.0 : 0.0;
        }
        
        return ($currentRevenue - $previousRevenue) / $previousRevenue;
    }
    
    /**
     * Calculate revenue consistency (coefficient of variation)
     */
    private function calculateRevenueConsistency()
    {
        $dailyRevenue = [];
        $currentDate = $this->dateFrom->copy();
        
        while ($currentDate <= $this->dateTo) {
            $dailyTotal = BookingPayment::whereIn('branch_id', $this->branchIds)
                ->whereDate('payment_date', $currentDate)
                ->where('payment_status', 1)
                ->sum('total_amount');
            
            $dailyRevenue[] = $dailyTotal;
            $currentDate->addDay();
        }
        
        if (count($dailyRevenue) < 2) {
            return 0;
        }
        
        $mean = array_sum($dailyRevenue) / count($dailyRevenue);
        
        if ($mean == 0) {
            return 0;
        }
        
        $variance = 0;
        foreach ($dailyRevenue as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $stdDev = sqrt($variance / count($dailyRevenue));
        
        return 1 - min(1, $stdDev / $mean); // Higher = more consistent
    }
    
    /**
     * Calculate new customer rate
     */
    private function calculateNewCustomerRate()
    {
        $totalCustomers = CustomerAccount::whereHas('bookings', function($query) {
            $query->whereIn('branch_id', $this->branchIds)
                ->whereBetween('date_start', [$this->dateFrom, $this->dateTo]);
        })->count();
        
        $newCustomers = CustomerAccount::whereHas('bookings', function($query) {
            $query->whereIn('branch_id', $this->branchIds)
                ->whereBetween('date_start', [$this->dateFrom, $this->dateTo]);
        })
        ->where('date_joined', '>=', $this->dateFrom)
        ->count();
        
        return $totalCustomers > 0 ? ($newCustomers / $totalCustomers) : 0;
    }
    
    /**
     * Estimate staff efficiency (bookings per staff)
     */
    private function estimateStaffEfficiency()
    {
        $totalBookings = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('date_start', [$this->dateFrom, $this->dateTo])
            ->count();
        
        // Assuming average of 3 staff per branch (adjust based on your data)
        $totalStaff = count($this->branchIds) * 3;
        
        $efficiency = $totalStaff > 0 ? ($totalBookings / $totalStaff) : 0;
        
        // Normalize to 0-1 range (assuming 10 bookings per staff is optimal)
        return min(1, $efficiency / 10);
    }
    
    /**
     * Calculate resource utilization
     */
    private function calculateResourceUtilization()
    {
        $totalSeats = DB::table('seats')
            ->whereIn('branch_id', $this->branchIds)
            ->where('active', 1)
            ->count();
        
        $usedSeats = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('date_start', [$this->dateFrom, $this->dateTo])
            ->distinct('seat_id')
            ->count('seat_id');
        
        return $totalSeats > 0 ? ($usedSeats / $totalSeats) : 0;
    }
    
    /**
     * Load historical booking patterns
     */
    private function loadHistoricalBookingPatterns()
    {
        return [
            [
                'type' => 'high_occupancy_low_value',
                'features' => [
                    'booking_occupancy_rate' => 0.8,
                    'avg_booking_value' => 0.3,
                    'booking_cancellation_rate' => 0.1,
                    'online_booking_ratio' => 0.5
                ],
                'success_rate' => 0.88,
                'recommended_actions' => ['upselling', 'bundling', 'premium_services']
            ],
            [
                'type' => 'low_occupancy_high_satisfaction',
                'features' => [
                    'booking_occupancy_rate' => 0.4,
                    'customer_satisfaction' => 0.9,
                    'customer_retention_rate' => 0.7,
                    'service_diversity' => 0.6
                ],
                'success_rate' => 0.82,
                'recommended_actions' => ['marketing', 'visibility', 'promotions']
            ],
            [
                'type' => 'seasonal_booking_pattern',
                'features' => [
                    'revenue_consistency' => 0.3,
                    'peak_hour_utilization' => 0.9,
                    'booking_lead_time' => 1.5,
                    'service_popularity_variance' => 0.7
                ],
                'success_rate' => 0.85,
                'recommended_actions' => ['seasonal_pricing', 'advance_booking', 'packages']
            ]
        ];
    }
    
    /**
     * Detect seasonal booking patterns
     */
    private function detectSeasonalBookingPatterns()
    {
        $patterns = [];
        $currentMonth = now()->month;
        
        // Detect weekend vs weekday patterns
        $weekendBookings = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('date_start', [$this->dateFrom, $this->dateTo])
            ->whereRaw('DAYOFWEEK(date_start) IN (1,7)')  // Sunday = 1, Saturday = 7
            ->count();
        
        $weekdayBookings = Booking::whereIn('branch_id', $this->branchIds)
            ->whereBetween('date_start', [$this->dateFrom, $this->dateTo])
            ->whereRaw('DAYOFWEEK(date_start) BETWEEN 2 AND 6')
            ->count();
        
        $totalBookings = $weekendBookings + $weekdayBookings;
        
        if ($totalBookings > 0) {
            $weekendRatio = $weekendBookings / $totalBookings;
            
            if ($weekendRatio > 0.6) {
                $patterns[] = [
                    'pattern_type' => 'weekend_heavy',
                    'similarity_score' => 0.9,
                    'success_rate' => 0.8,
                    'recommended_actions' => ['weekend_pricing', 'weekday_promotions']
                ];
            } elseif ($weekendRatio < 0.3) {
                $patterns[] = [
                    'pattern_type' => 'weekday_heavy',
                    'similarity_score' => 0.85,
                    'success_rate' => 0.75,
                    'recommended_actions' => ['weekend_promotions', 'corporate_offers']
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * Detect service usage patterns
     */
    private function detectServiceUsagePatterns()
    {
        $patterns = [];
        
        // Get top services by revenue
        $topServices = DB::table('bookings as b')
            ->join('booking_payments as bp', 'b.id', '=', 'bp.booking_id')
            ->join('service_names as sn', 'b.service_name_id', '=', 'sn.id')
            ->whereIn('b.branch_id', $this->branchIds)
            ->whereBetween('b.date_start', [$this->dateFrom, $this->dateTo])
            ->where('bp.payment_status', 1)
            ->selectRaw('sn.service_name, COUNT(*) as booking_count, SUM(bp.total_amount) as revenue')
            ->groupBy('sn.service_name')
            ->orderByDesc('revenue')
            ->limit(3)
            ->get();
        
        if ($topServices->count() > 0) {
            $totalRevenue = $topServices->sum('revenue');
            $topServiceRevenue = $topServices->first()->revenue;
            
            // If top service contributes > 50% of revenue
            if ($totalRevenue > 0 && ($topServiceRevenue / $totalRevenue) > 0.5) {
                $patterns[] = [
                    'pattern_type' => 'dominant_service',
                    'similarity_score' => 0.95,
                    'success_rate' => 0.85,
                    'recommended_actions' => ['service_diversification', 'cross_selling']
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * Score enhanced recommendations
     */
    private function scoreEnhancedRecommendations($recommendations, $features)
    {
        foreach ($recommendations as &$recommendation) {
            $score = $recommendation['base_score'];
            
            // Adjust based on feature correlations
            switch ($recommendation['type']) {
                case 'booking_optimization':
                    $score *= (0.6 + 0.4 * (1 - $features['booking_occupancy_rate']));
                    break;
                case 'booking_management':
                    $score *= (0.7 + 0.3 * $features['booking_cancellation_rate']);
                    break;
                case 'service_portfolio':
                    $score *= (0.8 + 0.2 * $features['service_popularity_variance']);
                    break;
                case 'revenue_optimization':
                    $score *= (0.5 + 0.5 * (1 - $features['avg_booking_value']));
                    break;
                case 'customer_loyalty':
                    $score *= (0.6 + 0.4 * (1 - $features['customer_retention_rate']));
                    break;
            }
            
            $recommendation['base_score'] = min($score, 1.0);
        }
        
        return $recommendations;
    }
    
    /**
     * Apply business context to recommendations
     */
    private function applyBusinessContext($recommendations, $features)
    {
        foreach ($recommendations as &$recommendation) {
            // Adjust for business size (number of branches)
            $branchCount = count($this->branchIds);
            $branchFactor = min(1, $branchCount / 10); // Normalize to 0-1
            
            // Adjust for seasonality
            $seasonalityFactor = $this->getSeasonalityFactor();
            
            // Adjust for implementation feasibility
            $feasibilityFactor = $this->getFeasibilityFactor($recommendation['type']);
            
            // Calculate final score
            $recommendation['final_score'] = $recommendation['base_score'] 
                * (0.5 + 0.3 * $branchFactor + 0.2 * $seasonalityFactor)
                * $feasibilityFactor;
            
            // Ensure score is between 0 and 1
            $recommendation['final_score'] = max(0, min(1, $recommendation['final_score']));
        }
        
        return $recommendations;
    }
    
    /**
     * Get seasonality factor
     */
    private function getSeasonalityFactor()
    {
        $currentMonth = now()->month;
        
        // Higher factor for peak months (holiday season, summer)
        if (in_array($currentMonth, [11, 12, 6, 7, 8])) {
            return 1.2;
        }
        
        // Lower factor for off-peak months
        if (in_array($currentMonth, [1, 2, 9])) {
            return 0.8;
        }
        
        return 1.0;
    }
    
    /**
     * Get feasibility factor based on recommendation type
     */
    private function getFeasibilityFactor($type)
    {
        $feasibility = [
            'booking_optimization' => 0.9,
            'booking_management' => 0.8,
            'digital_transformation' => 0.7,
            'service_portfolio' => 0.6,
            'revenue_optimization' => 0.85,
            'customer_loyalty' => 0.75,
            'customer_acquisition' => 0.7,
            'service_quality' => 0.8,
            'staff_optimization' => 0.9,
            'resource_management' => 0.7,
            'peak_management' => 0.6,
            'revenue_stability' => 0.8,
            'growth_strategy' => 0.7,
            'service_innovation' => 0.5,
            'revenue_diversification' => 0.6,
            'booking_timing' => 0.9,
        ];
        
        return $feasibility[$type] ?? 0.7;
    }
    
    /**
     * Estimate implementation time
     */
    private function estimateImplementationTime($type)
    {
        $times = [
            'booking_optimization' => '2-4 weeks',
            'booking_management' => '1-2 weeks',
            'digital_transformation' => '4-8 weeks',
            'service_portfolio' => '3-6 weeks',
            'revenue_optimization' => '2-3 weeks',
            'customer_loyalty' => '4-6 weeks',
            'customer_acquisition' => '3-5 weeks',
            'service_quality' => '2-4 weeks',
            'staff_optimization' => '1-3 weeks',
            'resource_management' => '2-4 weeks',
            'peak_management' => '3-5 weeks',
            'revenue_stability' => '4-8 weeks',
            'growth_strategy' => '6-12 weeks',
            'service_innovation' => '8-16 weeks',
            'revenue_diversification' => '6-10 weeks',
            'booking_timing' => '1-2 weeks',
        ];
        
        return $times[$type] ?? '2-4 weeks';
    }
    
    /**
     * Estimate resource requirement
     */
    private function estimateResourceRequirement($type)
    {
        $resources = [
            'booking_optimization' => 'Low - Software configuration',
            'booking_management' => 'Low - Policy changes',
            'digital_transformation' => 'Medium - IT setup',
            'service_portfolio' => 'Medium - Marketing effort',
            'revenue_optimization' => 'Low - Staff training',
            'customer_loyalty' => 'Medium - Program setup',
            'customer_acquisition' => 'Medium - Marketing budget',
            'service_quality' => 'Medium - Training & monitoring',
            'staff_optimization' => 'Low - Schedule adjustment',
            'resource_management' => 'Low - Analysis & adjustment',
            'peak_management' => 'Medium - Capacity planning',
            'revenue_stability' => 'Medium - System implementation',
            'growth_strategy' => 'High - Marketing & development',
            'service_innovation' => 'High - R&D & training',
            'revenue_diversification' => 'Medium - Service development',
            'booking_timing' => 'Low - Promotion setup',
        ];
        
        return $resources[$type] ?? 'Medium';
    }
    
    /**
     * Cosine similarity calculation
     */
    private function cosineSimilarity($vectorA, $vectorB)
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;
        
        foreach ($vectorA as $key => $value) {
            if (isset($vectorB[$key])) {
                $dotProduct += $value * $vectorB[$key];
            }
            $magnitudeA += pow($value, 2);
        }
        
        foreach ($vectorB as $value) {
            $magnitudeB += pow($value, 2);
        }
        
        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);
        
        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0;
        }
        
        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
    
    /**
     * Normalize features to 0-1 range
     */
    private function normalizeFeatures($features)
    {
        $normalized = [];
        
        foreach ($features as $key => $value) {
            $normalized[$key] = $this->minMaxNormalize($value, $key);
        }
        
        return $normalized;
    }
    
    private function minMaxNormalize($value, $featureKey)
    {
        $min = $this->benchmarks[$featureKey]['min'] ?? 0;
        $max = $this->benchmarks[$featureKey]['max'] ?? 1;
        
        if ($max == $min) {
            return 0.5;
        }
        
        return max(0, min(1, ($value - $min) / ($max - $min)));
    }
    
    /**
     * Load industry benchmarks
     */
    private function loadBenchmarks()
    {
        $this->benchmarks = [
            'booking_occupancy_rate' => ['min' => 0, 'max' => 100],
            'avg_booking_value' => ['min' => 0, 'max' => 1000],
            'booking_cancellation_rate' => ['min' => 0, 'max' => 50],
            'online_booking_ratio' => ['min' => 0, 'max' => 100],
            'booking_lead_time' => ['min' => 0, 'max' => 14],
            'peak_hour_utilization' => ['min' => 0, 'max' => 100],
            'service_diversity' => ['min' => 0, 'max' => 100],
            'revenue_growth' => ['min' => -1, 'max' => 2],
            'revenue_consistency' => ['min' => 0, 'max' => 1],
            'customer_retention_rate' => ['min' => 0, 'max' => 100],
            'new_customer_rate' => ['min' => 0, 'max' => 100],
            'customer_satisfaction' => ['min' => 0, 'max' => 5],
            'staff_efficiency' => ['min' => 0, 'max' => 20],
            'resource_utilization' => ['min' => 0, 'max' => 100],
            'service_popularity_variance' => ['min' => 0, 'max' => 2],
            'service_revenue_concentration' => ['min' => 0, 'max' => 1],
        ];
    }
}