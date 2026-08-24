<?php

if (!function_exists('getGoogleMapsRouteUrl')) {
    function getGoogleMapsRouteUrl($destination)
    {
        $destination = urlencode($destination);
        return "https://www.google.com/maps/dir/?api=1&destination={$destination}&travelmode=driving";
    }
}