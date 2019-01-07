<?php

namespace Stack\Plugins\OAuth;

class JWT {

  public static function sign($payload, $secret) {
    // Create token header as a JSON string
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    // Create token payload as a JSON string
    $payload = json_encode($payload);
    
    // Encode Header to Base64Url String
    $base64UrlHeader = self::base64UrlEncode($header);
    
    // Encode Payload to Base64Url String
    $base64UrlPayload = self::base64UrlEncode($payload);
    
    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    
    // Encode Signature to Base64Url String
    $base64UrlSignature = self::base64UrlEncode($signature);
    
    // Create JWT
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
  }

  public static function decode($jwt) {
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $jwt);
    $payload = self::base64UrlDecode($payloadEncoded);
    return json_decode($payload);
  }

  public static function verify(string $jwt, string $secret) {
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $jwt);

    $dataEncoded = "$headerEncoded.$payloadEncoded";

    $signature = self::base64UrlDecode($signatureEncoded);
    
    $rawSignature = hash_hmac('sha256', $dataEncoded, $secret, true);

    return hash_equals($rawSignature, $signature) ? self::decode($jwt) : false;
  }

  public static function expired ($token, $secret = null) :bool {
    if (\is_null($secret) && \is_object($token)) {
      $current_time = time();
      $expires_time = $token->exp;
      return $current_time >= $expires_time;
    } else if (\is_string($token)) {
      return expired(self::verify($token), $secret);
    }

    return false;
  }

  public static function base64UrlDecode($data) {
    return base64_decode( str_replace(['-', '_', ''], ['+', '/', '='], $data) );
  }

  public static function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
  }
}