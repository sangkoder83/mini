<?php

declare(strict_types=1);

namespace Mini;

class CSRF
{
    // Method to generate a CSRF token
    public static function generateToken()
    {


        // Generate a random token
        $token = bin2hex(random_bytes(32));
        if ($_ENV['CSRF'] == 0) {
            setcookie('csrf_token', '', time() - 3600, '/'); // Expire the cookie
        }
        // Store the token in a cookie
        setcookie('csrf_token', $token, [
            'expires' => 0, // Session cookie
            'path' => '/', // Available across the entire domain
            'secure' => true, // Only sent over HTTPS
            'httponly' => true, // Not accessible via JavaScript
            'samesite' => 'Strict' // Protect against CSRF attacks
        ]);

        return $token;
    }

    public static function getToken()
    {
        // // Check if a CSRF token already exists in the session
        // if (!isset($_COOKIE['csrf_token'])) {
        //     // If not, generate a new CSRF token
        //     $_COOKIE['csrf_token'] = self::generateToken();
        // }

        // Return the CSRF token
        return $_COOKIE['csrf_token'];
    }

    // Method to verify the CSRF token
    public static function verifyToken($token)
    {

        // Check if the token exists in the cookie
        if (isset($_COOKIE['csrf_token']) && hash_equals($_COOKIE['csrf_token'], $token)) {
            // Token is valid, delete it from the cookie
            // setcookie('csrf_token', '', time() - 3600, '/'); // Expire the cookie
            return true;
        }

        // Token is invalid or missing
        return false;
    }
}
