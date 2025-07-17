<?php
// Simplified functions for demo

function getUserDetails($user_id) {
    // Mock data for demonstration
    return [
        'id' => $user_id,
        'name' => 'Dr. John Smith',
        'username' => 'admin',
        'email' => 'admin@hospital.com',
        'role' => 'admin',
        'profile_image' => 'assets/img/default-avatar.png',
        'status' => 'active'
    ];
}

function getSetting($key, $default = '') {
    // Mock settings for demonstration
    $settings = [
        'site_title' => 'Cliniva Hospital CRM',
        'logo' => 'assets/img/default-avatar.png',
        'theme_color' => '#2563eb',
        'theme_mode' => 'light',
        'favicon' => 'assets/img/default-avatar.png'
    ];
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

function formatCurrency($amount, $currency_code = 'INR') {
    $symbols = [
        'INR' => '₹',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    
    $symbol = isset($symbols[$currency_code]) ? $symbols[$currency_code] : $currency_code;
    return $symbol . number_format($amount);
}
?>