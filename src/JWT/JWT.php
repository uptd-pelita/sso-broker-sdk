<?php

namespace Baliprov\SSOBroker\JWT;

class JWT
{
    /** @var string */
    private $secret;

    /** @var array */
    private $header = [
        "alg" => "HS256",
        "typ" => "JWT"
    ];

    /** @var mixed */
    private $payload;

    /** @var string|null */
    private $jwtString = null;

    /**
     * @param string|null $secret
     */
    public function __construct($secret = null)
    {
        $this->secret = $secret ?? config('sso-broker.jwt_secret', 'SSO-JWT-SECRET-KEY');
    }

    /**
     * Set custom secret key
     *
     * @param string $secret
     * @return self
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * Set custom header
     *
     * @param array $header
     * @return self
     */
    public function setHeader(array $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64UrlEncode($data)
    {
        $urlSafeData = strtr(base64_encode($data), '+/', '-_');
        return rtrim($urlSafeData, '=');
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64UrlDecode($data)
    {
        $urlUnsafeData = strtr($data, '-_', '+/');
        $paddedData = str_pad($urlUnsafeData, strlen($data) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($paddedData);
    }

    /**
     * Generate JWT encapsulated String
     *
     * @param string $algo
     * @param array $header
     * @param array $payload
     * @param string $secret
     * @return string
     */
    private function generateJWT($algo, array $header, array $payload, $secret)
    {
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $dataEncoded = "$headerEncoded.$payloadEncoded";
        $rawSignature = hash_hmac($algo, $dataEncoded, $secret, true);
        $signatureEncoded = $this->base64UrlEncode($rawSignature);
        $this->jwtString = "$dataEncoded.$signatureEncoded";
        return $this->jwtString;
    }

    /**
     * Verify / decode encapsulated JWT String
     *
     * @param string $algo
     * @param string $jwt
     * @param string $secret
     * @return mixed
     */
    private function verifyJWT($algo, $jwt, $secret)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        $dataEncoded = "$headerEncoded.$payloadEncoded";
        $signature = $this->base64UrlDecode($signatureEncoded);
        $rawSignature = hash_hmac($algo, $dataEncoded, $secret, true);

        if (hash_equals($rawSignature, $signature)) {
            $this->payload = json_decode($this->base64UrlDecode($payloadEncoded));
            return $this->payload;
        }

        return false;
    }

    /**
     * Set payload data
     *
     * @param mixed $payload
     * @return self
     */
    public function setPayloadJWT($payload)
    {
        $this->payload = (array) $payload;
        return $this;
    }

    /**
     * Get payload data
     *
     * @return mixed
     */
    public function getPayloadJWT()
    {
        return $this->payload;
    }

    /**
     * Set JWT string for decoding
     *
     * @param string $jwt
     * @return self
     */
    public function setJWTString($jwt)
    {
        $this->jwtString = $jwt;
        return $this;
    }

    /**
     * Get encoded JWT string
     *
     * @return string|null
     */
    public function getJWTString()
    {
        return $this->jwtString;
    }

    /**
     * Encode payload to JWT string
     *
     * @return string
     */
    public function encodeJWT()
    {
        return $this->generateJWT('sha256', $this->header, $this->payload, $this->secret);
    }

    /**
     * Decode JWT string to payload
     *
     * @return mixed
     */
    public function decodeJWT()
    {
        return $this->verifyJWT('sha256', $this->jwtString, $this->secret);
    }

    /**
     * Static helper to quickly encode data
     *
     * @param array $payload
     * @param string|null $secret
     * @return string
     */
    public static function encode(array $payload, $secret = null)
    {
        $jwt = new self($secret);
        $jwt->setPayloadJWT($payload);
        return $jwt->encodeJWT();
    }

    /**
     * Static helper to quickly decode token
     *
     * @param string $token
     * @param string|null $secret
     * @return mixed
     */
    public static function decode($token, $secret = null)
    {
        $jwt = new self($secret);
        $jwt->setJWTString($token);
        return $jwt->decodeJWT();
    }
}
