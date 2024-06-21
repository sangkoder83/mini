<?php

declare(strict_types=1);

namespace Mini;

class Captcha
{
    protected static $cookieName = 'captcha';
    protected static $cookieLifetime = 300; // 5 minutes
    protected static $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    protected static $length = 6;
    protected static $encryptionKey = 'YourEncryptionKeyHere';

    public static function generate()
    {
        $captcha = self::generateRandomString();
        $encryptedCaptcha = self::encrypt($captcha);
        setcookie(self::$cookieName, $encryptedCaptcha, time() + self::$cookieLifetime, '/');
        return $captcha;
    }

    public static function validate($userInput)
    {
        if (isset($_COOKIE[self::$cookieName])) {
            $encryptedCaptcha = $_COOKIE[self::$cookieName];
            $captcha = self::decrypt($encryptedCaptcha);
            if (strcasecmp($captcha, $userInput) === 0) {
                // CAPTCHA is correct
                self::regenerate();
                return true;
            }
        }
        return false;
    }

    protected static function regenerate()
    {
        self::generate();
    }

    protected static function generateRandomString()
    {
        $charactersLength = strlen(self::$characters);
        $randomString = '';
        for ($i = 0; $i < self::$length; $i++) {
            $randomString .= self::$characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    protected static function encrypt($data)
    {
        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', self::$encryptionKey, 0, substr(self::$encryptionKey, 0, 16)));
    }

    protected static function decrypt($data)
    {
        return openssl_decrypt(base64_decode($data), 'AES-256-CBC', self::$encryptionKey, 0, substr(self::$encryptionKey, 0, 16));
    }
}
