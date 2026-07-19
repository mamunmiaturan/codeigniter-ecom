<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * RFC 6238 TOTP (Time-based One-Time Password) Library
 *
 * Generates and verifies 6-digit TOTP codes compatible with
 * Google Authenticator, Authy, and any RFC 6238-compliant app.
 */
class Totp
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const CODE_LENGTH  = 6;
    private const STEP         = 30; // 30-second window

    /**
     * Generate a cryptographically random 20-byte base32-encoded secret.
     */
    public function generate_secret(): string
    {
        $bytes = random_bytes(20);
        return $this->_base32_encode($bytes);
    }

    /**
     * Get the current TOTP code for a given secret.
     */
    public function get_code(string $secret): string
    {
        return $this->_hotp($secret, (int) floor(time() / self::STEP));
    }

    /**
     * Verify a submitted code against the secret, allowing for clock drift
     * of ±$window steps (each step = 30 seconds).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $ts = (int) floor(time() / self::STEP);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->_hotp($secret, $ts + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verify a backup code (single-use plain-text code, case-insensitive).
     * Returns the updated backup-codes JSON string with the used code removed,
     * or NULL if the code is invalid.
     */
    public function verify_backup(string $backup_codes_json, string $submitted): ?string
    {
        $codes = json_decode($backup_codes_json, true);
        if (!is_array($codes)) {
            return null;
        }
        $submitted = strtoupper(trim(str_replace('-', '', $submitted)));
        foreach ($codes as $idx => $stored) {
            if (hash_equals(strtoupper(str_replace('-', '', $stored)), $submitted)) {
                unset($codes[$idx]);
                return json_encode(array_values($codes));
            }
        }
        return null;
    }

    /**
     * Generate 8 random human-friendly backup codes (e.g. ABCD-1234).
     */
    public function generate_backup_codes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $part1 = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $part2 = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $codes[] = $part1 . '-' . $part2;
        }
        return $codes;
    }

    /**
     * Return a Google Charts QR code URL for the OTP provisioning URI.
     * Compatible with any authenticator app that scans QR codes.
     */
    public function get_qr_url(string $secret, string $label, string $issuer = 'Auth'): string
    {
        $uri = sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&digits=%d&period=%d',
            rawurlencode($issuer . ':' . $label),
            $secret,
            rawurlencode($issuer),
            self::CODE_LENGTH,
            self::STEP
        );
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($uri);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function _hotp(string $secret, int $counter): string
    {
        $key   = $this->_base32_decode($secret);
        $msg   = pack('J', $counter); // 64-bit big-endian counter
        $hash  = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
             (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::CODE_LENGTH);
        return str_pad((string) $code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function _base32_encode(string $bytes): string
    {
        $chars  = self::BASE32_CHARS;
        $bin    = '';
        foreach (str_split($bytes) as $byte) {
            $bin .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split(str_pad($bin, (int) ceil(strlen($bin) / 5) * 5, '0'), 5) as $chunk) {
            $out .= $chars[bindec($chunk)];
        }
        return $out;
    }

    private function _base32_decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/', '', $encoded));
        $chars   = self::BASE32_CHARS;
        $bin     = '';
        foreach (str_split($encoded) as $char) {
            $pos  = strpos($chars, $char);
            if ($pos === false) continue;
            $bin .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split(substr($bin, 0, (int) (strlen($bin) / 8) * 8), 8) as $byte) {
            $out .= chr(bindec($byte));
        }
        return $out;
    }
}
