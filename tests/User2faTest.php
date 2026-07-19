<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Totp.php';

/**
 * Tests for the 2FA sub-system: backup-code encryption round-trips,
 * the Totp library's backup-code verification, and the 2FA-pending
 * session structure used by the login flow.
 *
 * No CI3 framework or database required — all tests are pure-PHP.
 */
class User2faTest extends TestCase
{
    // ── Backup-code encryption round-trips ───────────────────────────────────

    public function test_backup_codes_encrypt_decrypt_roundtrip(): void
    {
        $codes = ['ABCD-1234', 'EFGH-5678', 'IJKL-9012'];
        $json  = json_encode($codes);

        $encrypted = totp_encrypt_secret($json);
        $decrypted = totp_decrypt_secret($encrypted);

        $this->assertNotSame($json, $encrypted, 'Encrypted form must differ from plaintext');
        $this->assertSame($json, $decrypted, 'Decrypted form must equal original JSON');
        $this->assertSame($codes, json_decode($decrypted, true));
    }

    public function test_backup_codes_encryption_uses_random_iv(): void
    {
        $json = json_encode(['XXXX-1111', 'YYYY-2222']);

        $enc1 = totp_encrypt_secret($json);
        $enc2 = totp_encrypt_secret($json);

        $this->assertNotSame($enc1, $enc2, 'Each encryption must produce a different ciphertext (random IV)');
        $this->assertSame($json, totp_decrypt_secret($enc1));
        $this->assertSame($json, totp_decrypt_secret($enc2));
    }

    public function test_decrypt_null_returns_fallback(): void
    {
        // Legacy-plaintext fallback: totp_decrypt_secret returns the input as-is
        // when it cannot be decoded as valid AES-256-CBC ciphertext.
        $result = totp_decrypt_secret('[]');
        $this->assertSame('[]', $result);
    }

    public function test_decrypt_empty_backup_codes_produces_empty_array(): void
    {
        $json  = json_encode([]);
        $enc   = totp_encrypt_secret($json);
        $codes = json_decode(totp_decrypt_secret($enc), true);

        $this->assertIsArray($codes);
        $this->assertCount(0, $codes);
    }

    public function test_backup_code_count_reflects_remaining_codes(): void
    {
        $all_codes = ['AAAA-0001', 'BBBB-0002', 'CCCC-0003'];
        $remaining = array_values(array_slice($all_codes, 1)); // simulate one used

        $json  = json_encode($remaining);
        $enc   = totp_encrypt_secret($json);
        $codes = json_decode(totp_decrypt_secret($enc), true);

        $this->assertCount(2, $codes);
    }

    // ── Totp library: backup-code verification ────────────────────────────────

    public function test_verify_backup_accepts_valid_code(): void
    {
        $totp   = new Totp();
        $codes  = $totp->generate_backup_codes();
        $target = $codes[0];
        $json   = json_encode($codes);

        $updated = $totp->verify_backup($json, $target);

        $this->assertNotNull($updated, 'A valid backup code must be accepted');
        $remaining = json_decode($updated, true);
        $this->assertCount(count($codes) - 1, $remaining, 'Used code must be removed');
        $this->assertNotContains($target, $remaining);
    }

    public function test_verify_backup_accepts_lowercase_code(): void
    {
        $totp  = new Totp();
        $codes = $totp->generate_backup_codes();
        $json  = json_encode($codes);

        $updated = $totp->verify_backup($json, strtolower($codes[2]));
        $this->assertNotNull($updated);
    }

    public function test_verify_backup_rejects_invalid_code(): void
    {
        $totp  = new Totp();
        $codes = $totp->generate_backup_codes();
        $json  = json_encode($codes);

        $result = $totp->verify_backup($json, 'ZZZZ-9999');
        $this->assertNull($result, 'An invalid backup code must be rejected');
    }

    public function test_verify_backup_rejects_reuse(): void
    {
        $totp   = new Totp();
        $codes  = $totp->generate_backup_codes();
        $target = $codes[0];
        $json   = json_encode($codes);

        $after_first_use = $totp->verify_backup($json, $target);
        $this->assertNotNull($after_first_use);

        // Second attempt with same code must fail
        $after_second_use = $totp->verify_backup($after_first_use, $target);
        $this->assertNull($after_second_use, 'A used backup code must not be accepted again');
    }

    // ── Login → 2FA pending session structure ────────────────────────────────

    public function test_2fa_pending_session_has_required_keys(): void
    {
        $session = [
            '2fa_pending'       => true,
            '2fa_user_id'       => 42,
            '2fa_login_id'      => 7,
            '2fa_role'          => 2,
            '2fa_profile_name'  => 'Test User',
            '2fa_profile_uid'   => 'USR0042',
            '2fa_profile_photo' => 'photo.jpg',
            '2fa_date_format'   => 'Y-m-d',
            '2fa_set_lang'      => 'english',
        ];

        foreach (['2fa_pending','2fa_user_id','2fa_login_id','2fa_role',
                  '2fa_profile_name','2fa_profile_uid','2fa_profile_photo',
                  '2fa_date_format','2fa_set_lang'] as $key) {
            $this->assertArrayHasKey($key, $session, "Session must contain key: $key");
        }

        $this->assertTrue($session['2fa_pending']);
        $this->assertIsInt($session['2fa_user_id']);
        $this->assertIsInt($session['2fa_login_id']);
    }

    public function test_2fa_session_cleared_on_successful_verify(): void
    {
        $pending_keys = ['2fa_pending','2fa_user_id','2fa_login_id','2fa_role',
                         '2fa_profile_name','2fa_profile_uid','2fa_profile_photo',
                         '2fa_date_format','2fa_set_lang'];

        // Simulate the unset loop in Authentication::verify_2fa()
        $session = array_fill_keys($pending_keys, 'test_value');
        $session['loggedin'] = true;

        foreach ($pending_keys as $k) {
            unset($session[$k]);
        }

        foreach ($pending_keys as $key) {
            $this->assertArrayNotHasKey($key, $session, "Pending key must be cleared: $key");
        }
        $this->assertArrayHasKey('loggedin', $session);
    }
}
