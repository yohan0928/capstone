<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeocodingService
{
    protected $provider;
    protected $apiKey;

    public function __construct()
    {
        $this->provider = config('geocoding.provider', 'open-meteo');
        $this->apiKey = config('geocoding.api_key');
    }

    /**
     * Geocode an address to get latitude and longitude
     */
    public function geocodeAddress($address)
    {
        if (empty($address)) {
            return null;
        }

        // Check cache first
        $cacheKey = 'geocode_' . md5($address);
        if (config('geocoding.cache.enabled', true)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        // Try the configured provider
        $result = null;
        switch ($this->provider) {
            case 'open-meteo':
                $result = $this->geocodeOpenMeteo($address);
                break;
            case 'nominatim':
                $result = $this->geocodeNominatim($address);
                break;
            case 'bigdatacloud':
                $result = $this->geocodeBigDataCloud($address);
                break;
            case 'google':
                $result = $this->geocodeGoogle($address);
                break;
            default:
                $result = $this->geocodeOpenMeteo($address);
        }

        // Try fallback if configured
        if (!$result && config('geocoding.fallback.enabled', true)) {
            $fallbackProvider = config('geocoding.fallback.provider', 'nominatim');
            if ($fallbackProvider !== $this->provider) {
                $result = $this->geocodeWithProvider($address, $fallbackProvider);
            }
        }

        // Cache the result if successful
        if ($result && config('geocoding.cache.enabled', true)) {
            Cache::put($cacheKey, $result, config('geocoding.cache.duration', 604800));
        }

        return $result;
    }

    /**
     * Geocode using a specific provider
     */
    private function geocodeWithProvider($address, $provider)
    {
        switch ($provider) {
            case 'open-meteo':
                return $this->geocodeOpenMeteo($address);
            case 'nominatim':
                return $this->geocodeNominatim($address);
            case 'bigdatacloud':
                return $this->geocodeBigDataCloud($address);
            case 'google':
                return $this->geocodeGoogle($address);
            default:
                return $this->geocodeOpenMeteo($address);
        }
    }

    /**
     * Open-Meteo Geocoding API (Completely Free, No API Key Required)
     * Documentation: https://open-meteo.com/en/docs/geocoding-api
     */
    protected function geocodeOpenMeteo($address)
    {
        try {
            $response = Http::timeout(10)->get('https://geocoding-api.open-meteo.com/v1/search', [
                'name' => $address,
                'count' => 1,
                'language' => 'en',
                'format' => 'json'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['results'])) {
                    $result = $data['results'][0];
                    return [
                        'latitude' => (float) $result['latitude'],
                        'longitude' => (float) $result['longitude'],
                        'formatted_address' => $this->formatOpenMeteoAddress($result),
                        'provider' => 'open-meteo'
                    ];
                }
            }

            Log::warning('Open-Meteo geocoding failed', [
                'address' => $address,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Open-Meteo geocoding error', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Format Open-Meteo address result
     */
    protected function formatOpenMeteoAddress($result)
    {
        $parts = [];
        
        if (!empty($result['name'])) {
            $parts[] = $result['name'];
        }
        if (!empty($result['admin1'])) {
            $parts[] = $result['admin1'];
        }
        if (!empty($result['country'])) {
            $parts[] = $result['country'];
        }
        
        return implode(', ', $parts);
    }

    /**
     * OpenStreetMap Nominatim API (Free, No API Key Required)
     * Documentation: https://nominatim.org/release-docs/develop/api/Search/
     * Note: Rate limiting is applied, please respect their policies
     */
    protected function geocodeNominatim($address)
    {
        try {
            $response = Http::timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data) && isset($data[0])) {
                    $result = $data[0];
                    return [
                        'latitude' => (float) $result['lat'],
                        'longitude' => (float) $result['lon'],
                        'formatted_address' => $result['display_name'],
                        'provider' => 'nominatim'
                    ];
                }
            }

            Log::warning('Nominatim geocoding failed', [
                'address' => $address,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Nominatim geocoding error', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * BigDataCloud API (Free for 50,000 monthly queries with sign-up)
     * Documentation: https://www.bigdatacloud.com/geocoding-apis/free-forward-geocode
     */
    protected function geocodeBigDataCloud($address)
    {
        if (empty($this->apiKey)) {
            Log::warning('BigDataCloud API key is not configured');
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://api.bigdatacloud.net/data/geocode', [
                'address' => $address,
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data) && isset($data['latitude']) && isset($data['longitude'])) {
                    return [
                        'latitude' => (float) $data['latitude'],
                        'longitude' => (float) $data['longitude'],
                        'formatted_address' => $data['locality'] ?? $address,
                        'provider' => 'bigdatacloud'
                    ];
                }
            }

            Log::warning('BigDataCloud geocoding failed', [
                'address' => $address,
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('BigDataCloud geocoding error', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Google Geocoding API (Requires API Key)
     * Documentation: https://developers.google.com/maps/documentation/geocoding/overview
     */
    protected function geocodeGoogle($address)
    {
        if (empty($this->apiKey)) {
            Log::warning('Google Geocoding API key is not configured');
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $result = $data['results'][0];
                    return [
                        'latitude' => (float) $result['geometry']['location']['lat'],
                        'longitude' => (float) $result['geometry']['location']['lng'],
                        'formatted_address' => $result['formatted_address'],
                        'provider' => 'google'
                    ];
                }
            }

            Log::warning('Google geocoding failed', [
                'address' => $address,
                'status' => $response->json()['status'] ?? 'unknown'
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Google geocoding error', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Batch geocode multiple addresses
     */
    public function batchGeocode($addresses)
    {
        $results = [];
        
        foreach ($addresses as $address) {
            $results[$address] = $this->geocodeAddress($address);
            
            // Sleep to respect rate limits for Nominatim
            if ($this->provider === 'nominatim') {
                usleep(1000000); // Sleep 1 second between requests
            }
        }
        
        return $results;
    }

    /**
     * Validate if coordinates are valid
     */
    public function validateCoordinates($latitude, $longitude)
    {
        return $latitude >= -90 && $latitude <= 90 && 
               $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Get the currently configured provider name
     */
    public function getProviderName()
    {
        return $this->provider;
    }

    /**
     * Clear the geocoding cache
     */
    public function clearCache($address = null)
    {
        if ($address) {
            $cacheKey = 'geocode_' . md5($address);
            Cache::forget($cacheKey);
        } else {
            // Clear all geocoding cache (use with caution)
            // This would need a more sophisticated approach
            Log::warning('Clearing all geocoding cache is not implemented');
        }
    }
}