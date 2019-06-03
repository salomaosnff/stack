<?php

namespace Stack\Plugins\OAuth;

/***
 * Class JWT
 * @package Stack\Plugins\OAuth
 */
class JWT
{

    /**
     * Generate the encrypted JWT token with payload and secret
     *
     * @param object|array $payload Payload data
     * @param string $secret Secret key
     * @return string
     */
    public static function sign($payload, string $secret)
    {
        $payload['jti'] = $payload['jti'] ?? self::uuid4();

        // Create token header as a JSON string
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        // Create token payload as a JSON string
        $payload = json_encode($payload);

        // Encode Header to Base64Url String
        $base64UrlHeader = self::base64UrlEncode($header);

        // Encode Payload to Base64Url String
        $base64UrlPayload = self::base64UrlEncode($payload);

        // Create Signature Hash
        $signature = hash_hmac('SHA256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = self::base64UrlEncode($signature);

        // Create JWT
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Decode a JWT token payload
     *
     * @param string $jwt
     * @return object|null
     */
    public static function decode(string $jwt): ?object
    {
        $payload = explode('.', $jwt);

        if (!isset($payload[1])) {
            return null;
        }

        $payloadEncoded = $payload[1];
        $payloadDecoded = self::base64UrlDecode($payloadEncoded);
        return json_decode($payloadDecoded);
    }

    /**
     * Verify a JWT token and return it's payload if valid
     *
     * @param string $jwt JWT token
     * @param string $secret Token secret key
     * @return bool|object|null
     */
    public static function verify(string $jwt, string $secret)
    {
        @list($headerEncoded,
            $payloadEncoded,
            $signatureEncoded) = explode('.', $jwt);
        $dataEncoded       = "$headerEncoded.$payloadEncoded";
        $signature         = self::base64UrlDecode($signatureEncoded);
        $rawSignature      = hash_hmac('SHA256', $dataEncoded, $secret, true);
        return hash_equals($rawSignature, $signature) ? self::decode($jwt) : false;
    }

    /**
     * Check if a token or payload is expired
     *
     * @param string|object|array $token
     * @param string|null $secret
     * @return bool
     */
    public static function expired($token, $secret = null): bool
    {
        if (\is_null($secret) && \is_object($token)) {
            $token      = (object) $token;
            $expires_at = $token->exp;
            return $expires_at >= 0 && $expires_at <= time();
        } else if (\is_string($token)) {
            return self::expired(self::verify($token, $secret));
        }
        return false;
    }

    /**
     * Decode base64 url encoded
     *
     * @param $data
     * @return bool|string
     */
    public static function base64UrlDecode($data)
    {
        return base64_decode(str_replace(['-', '_', ''], ['+', '/', '='], $data));
    }

    /**
     * Encode to base64 url encoded
     *
     * @param $data
     * @return mixed
     */
    public static function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Generates a v4 UUID
     *
     * @return string
     */
    public static function uuid4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
