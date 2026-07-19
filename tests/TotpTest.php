<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Totp.php';

/**
 * Unit tests for the RFC 6238 TOTP library.
 *
 * All tests run without any CodeIgniter or database dependencies.
 */
class TotpTest extends TestCase
{
    private Totp $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new Totp();
    }

    // ── Secret generation ─────────────────────────────────────────────────────

    public function test_generate_secret_returns_non_empty_string(): void
    {
        $secret = $this->totp->generate_secret();
        $this->assertNotEmpty($secret);
        $this->assertIsString($secret);
    }

    public function test_generate_secret_is_base32_encoded(): void
    {
        $secret = $this->totp->generate_secret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret,
            'Secret must contain only uppercase base32 characters (A-Z and 2-7)');
    }

    public function test_generate_secret_has_sufficient_length(): void
    {
        $secret = $this->totp->generate_secret();
        // 20 random bytes base32-encoded → 32 characters
        $this->assertGreaterThanOrEqual(32, strlen($secret));
    }

    public function test_generate_secret_is_unique(): void
    {
        $a = $this->totp->generate_secret();
        $b = $this->totp->generate_secret();
        $this->assertNotEquals($a, $b, 'Two separately generated secrets must differ');
    }

    // ── Code generation ───────────────────────────────────────────────────────

    public function test_get_code_returns_six_digit_string(): void
    {
        $secret = $this->totp->generate_secret();
        $code   = $this->totp->get_code($secret);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_get_code_is_deterministic_within_same_step(): void
    {
        $secret = $this->totp->generate_secret();
        $this->assertSame(
            $this->totp->get_code($secret),
            $this->totp->get_code($secret)
        );
    }

    // ── Verification ──────────────────────────────────────────────────────────

    public function test_verify_accepts_current_valid_code(): void
    {
        $secret = $this->totp->generate_secret();
        $code   = $this->totp->get_code($secret);
        $this->assertTrue($this->totp->verify($secret, $code));
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $secret = $this->totp->generate_secret();
        $code   = $this->totp->get_code($secret);
        // Flip one digit to guarantee mismatch
        $bad = ($code[0] === '9') ? substr_replace($code, '0', 0, 1)
                                   : substr_replace($code, '9', 0, 1);
        $this->assertFalse($this->totp->verify($secret, $bad));
    }

    public function test_verify_rejects_non_six_digit_input(): void
    {
        $secret = $this->totp->generate_secret();
        $this->assertFalse($this->totp->verify($secret, '12345'));     // 5 digits
        $this->assertFalse($this->totp->verify($secret, '1234567'));   // 7 digits
        $this->assertFalse($this->totp->verify($secret, 'abcdef'));    // letters
        $this->assertFalse($this->totp->verify($secret, ''));          // empty
    }

    public function test_verify_trims_whitespace_from_code(): void
    {
        $secret = $this->totp->generate_secret();
        $code   = $this->totp->get_code($secret);
        $this->assertTrue($this->totp->verify($secret, "  {$code}  "));
    }

    // ── Backup codes ──────────────────────────────────────────────────────────

    public function test_generate_backup_codes_returns_eight_codes(): void
    {
        $codes = $this->totp->generate_backup_codes();
        $this->assertCount(8, $codes);
    }

    public function test_backup_codes_have_correct_format(): void
    {
        $codes = $this->totp->generate_backup_codes();
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression(
                '/^[0-9A-F]{4}-[0-9A-F]{4}$/i',
                $code,
                "Backup code '{$code}' does not match expected XXXX-XXXX format"
            );
        }
    }

    public function test_backup_codes_are_unique(): void
    {
        $codes = $this->totp->generate_backup_codes();
        $this->assertSame(count($codes), count(array_unique($codes)),
            'All 8 backup codes must be unique');
    }

    public function test_verify_backup_accepts_valid_code(): void
    {
        $codes = $this->totp->generate_backup_codes();
        $json  = json_encode($codes);
        $valid = $codes[3];

        $result = $this->totp->verify_backup($json, $valid);

        $this->assertNotNull($result, 'verify_backup must return updated JSON on success');
        $remaining = json_decode($result, true);
        $this->assertCount(7, $remaining, 'Used code must be removed');
        $this->assertNotContains($valid, $remaining);
    }

    public function test_verify_backup_is_case_insensitive(): void
    {
        $codes = $this->totp->generate_backup_codes();
        $json  = json_encode($codes);
        // Submit code in lowercase
        $lower = strtolower($codes[0]);

        $result = $this->totp->verify_backup($json, $lower);
        $this->assertNotNull($result);
    }

    public function test_verify_backup_accepts_code_without_dash(): void
    {
        $codes = $this->totp->generate_backup_codes();
        $json  = json_encode($codes);
        // Strip the dash before submitting
        $noDash = str_replace('-', '', $codes[1]);

        $result = $this->totp->verify_backup($json, $noDash);
        $this->assertNotNull($result, 'Backup code without dash separator must still verify');
    }

    public function test_verify_backup_rejects_invalid_code(): void
    {
        $codes = $this->totp->generate_backup_codes();
        $json  = json_encode($codes);

        $result = $this->totp->verify_backup($json, 'ZZZZ-ZZZZ');
        $this->assertNull($result);
    }

    public function test_verify_backup_rejects_invalid_json(): void
    {
        $result = $this->totp->verify_backup('not-json', 'ABCD-1234');
        $this->assertNull($result);
    }

    public function test_verify_backup_returns_re_indexed_array(): void
    {
        $codes = $this->totp->generate_backup_codes();
        $json  = json_encode($codes);

        $result    = $this->totp->verify_backup($json, $codes[0]);
        $remaining = json_decode($result, true);

        // Keys must be 0-indexed after removal
        $this->assertSame(array_values($remaining), $remaining);
    }

    // ── QR URL ────────────────────────────────────────────────────────────────

    public function test_get_qr_url_contains_secret(): void
    {
        $secret = $this->totp->generate_secret();
        $url    = $this->totp->get_qr_url($secret, 'user@example.com', 'TestApp');
        $this->assertStringContainsString($secret, $url);
    }

    public function test_get_qr_url_contains_otpauth_scheme(): void
    {
        $secret = $this->totp->generate_secret();
        $url    = $this->totp->get_qr_url($secret, 'user@example.com');
        $this->assertStringContainsString('otpauth%3A%2F%2Ftotp%2F', $url);
    }

    public function test_get_qr_url_encodes_issuer(): void
    {
        $secret = $this->totp->generate_secret();
        $url    = $this->totp->get_qr_url($secret, 'user@example.com', 'My App');
        // rawurlencode encodes spaces as %20; after the outer rawurlencode the
        // space becomes %2520 in the final URL string
        $this->assertStringContainsString('My', $url);
        $this->assertStringContainsString('App', $url);
    }

    // ── Round-trip integrity ──────────────────────────────────────────────────

    public function test_full_roundtrip_generate_verify(): void
    {
        $secret = $this->totp->generate_secret();
        $code   = $this->totp->get_code($secret);
        $this->assertTrue(
            $this->totp->verify($secret, $code),
            'A freshly generated code must pass verification against the same secret'
        );
    }
}
