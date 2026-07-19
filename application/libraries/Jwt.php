<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Minimal self-contained JWT implementation (HS256 only).
 *
 * Intentionally small — does not pull in firebase/php-jwt to keep the
 * dependency surface trivial. We deliberately do NOT accept the "none" alg.
 * Constant-time compare via hash_equals defends against timing oracles.
 *
 * Usage:
 *   $jwt = new Jwt();
 *   $token = $jwt->encode(['sub' => 42, 'scope' => 'read:user'], 900);
 *   $claims = $jwt->decode($token);  // throws on invalid / expired / wrong alg
 */
class Jwt
{
    /** @var string Key used for HS256. Always loaded from SECURITY_KEY env. */
    private $key;

    /** @var string Issuer claim — set to base_url() at construction. */
    private $issuer;

    /** @var int Acceptable clock skew in seconds when validating exp/nbf. */
    private $leeway = 30;

    public function __construct()
    {
        $key = getenv('JWT_SECRET') ?: getenv('SECURITY_KEY') ?: '';
        if (strlen($key) < 32) {
            // Fail loudly rather than fall back to a weak key.
            throw new RuntimeException(
                'JWT requires JWT_SECRET (or SECURITY_KEY) of >= 32 bytes. '
                . 'Generate one with: openssl rand -hex 32'
            );
        }
        $this->key = $key;
        $this->issuer = function_exists('base_url') ? base_url() : 'app';
    }

    public function encode(array $payload, int $ttl_seconds, string $type = 'access'): string
    {
        $now = time();
        $payload = array_merge([
            'iss'   => $this->issuer,
            'iat'   => $now,
            'nbf'   => $now,
            'exp'   => $now + max(1, $ttl_seconds),
            'jti'   => bin2hex(random_bytes(16)),
            'type'  => $type,
        ], $payload);

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            self::b64url(json_encode($header,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            self::b64url(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), $this->key, true);
        $segments[] = self::b64url($signature);
        return implode('.', $segments);
    }

    /**
     * @throws RuntimeException on any validation failure.
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('JWT: malformed token');
        }
        [$head_b64, $payload_b64, $sig_b64] = $parts;

        $header = json_decode(self::b64url_decode($head_b64), true);
        if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
            // Reject "none" and any non-HS256 alg explicitly.
            throw new RuntimeException('JWT: unsupported algorithm');
        }

        $expected_sig = hash_hmac('sha256', $head_b64 . '.' . $payload_b64, $this->key, true);
        $actual_sig   = self::b64url_decode($sig_b64);
        if (!hash_equals($expected_sig, $actual_sig)) {
            throw new RuntimeException('JWT: signature mismatch');
        }

        $payload = json_decode(self::b64url_decode($payload_b64), true);
        if (!is_array($payload)) {
            throw new RuntimeException('JWT: bad payload');
        }

        $now = time();
        if (isset($payload['nbf']) && $payload['nbf'] - $this->leeway > $now) {
            throw new RuntimeException('JWT: not yet valid');
        }
        if (!isset($payload['exp']) || $payload['exp'] + $this->leeway < $now) {
            throw new RuntimeException('JWT: expired');
        }
        if (!isset($payload['iss']) || $payload['iss'] !== $this->issuer) {
            throw new RuntimeException('JWT: issuer mismatch');
        }
        return $payload;
    }

    /** @return string|null Extracts the bearer token from an Authorization header */
    public static function extract_bearer(?string $header): ?string
    {
        if (!is_string($header) || $header === '') {
            return null;
        }
        if (preg_match('/^Bearer\s+([A-Za-z0-9._\-]+)$/', $header, $m)) {
            return $m[1];
        }
        return null;
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64url_decode(string $data): string
    {
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
