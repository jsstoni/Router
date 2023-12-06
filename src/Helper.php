<?php

namespace Route;

use Firebase\JWT\{JWT, Key};

class Helper
{
    private static $privateKey = 'vR86^0YK2F&Xcy#35%ofj%wdrgNDAfH3scM&5w7p9yunzct6i^';
    private static $method = 'aes-256-ecb';

    public static function baseEncode($string): string
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    public static function baseDecode($token)
    {
        try {
            return base64_decode(str_pad(strtr($token, '-_', '+/'), strlen($token) % 4, '=', STR_PAD_RIGHT));
        } catch (\Exception $error) {
            http_response_code(501);
            exit();
        }
    }

    public static function generateToken($id)
    {
        $method = self::$method;
        $encrypted = openssl_encrypt($id, $method, self::$privateKey, OPENSSL_RAW_DATA);
        $encoded = self::baseEncode($encrypted);
        return $encoded;
    }

    public static function decodeToken($token)
    {
        $method = self::$method;
        $decoded = self::baseDecode($token);
        $decrypted = openssl_decrypt($decoded, $method, self::$privateKey, OPENSSL_RAW_DATA);
        return $decrypted;
    }

    public static function JWT_code($payload)
    {
        $jwt = JWT::encode($payload, self::$privateKey, 'HS256');
        return self::baseEncode($jwt);
    }

    public static function JWT_decode($token)
    {
        $decodedToken = self::baseDecode($token);

        if (!is_string($decodedToken)) {
            http_response_code(401);
            return null;
        }

        try {
            $data = JWT::decode($decodedToken, new Key(self::$privateKey, 'HS256'));
            return $data;
        } catch (\Exception $th) {
            http_response_code(401);
            return null;
        }
    }
}
