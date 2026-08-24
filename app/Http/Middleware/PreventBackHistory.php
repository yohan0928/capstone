<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PreventBackHistory
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Check if user is authenticated as customer
        if (Auth::guard('customer')->check()) {
            // Add headers to prevent caching
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
            $response->headers->set('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
            
            // Additional security headers
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            
            // Clear any output buffers
            if (ob_get_length() > 0) {
                ob_clean();
            }
        }
        
        // Check if user is authenticated as staff
        if (Auth::guard('staff')->check()) {
            // Add headers to prevent caching
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
            $response->headers->set('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
            
            // Additional security headers
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            
            // Clear any output buffers
            if (ob_get_length() > 0) {
                ob_clean();
            }
        }
        
        // Check if user is authenticated as owner
        if (Auth::guard('owner')->check()) {
            // Add headers to prevent caching
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
            $response->headers->set('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
            
            // Additional security headers
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            
            // Clear any output buffers
            if (ob_get_length() > 0) {
                ob_clean();
            }
        }
        
        return $response;
    }
}