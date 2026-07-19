<?php
use PHPUnit\Framework\TestCase;

/**
 * Tests for encrypt_id() / decrypt_id() in general_helper.php.
 *
 * These functions protect internal database IDs that appear in URLs.
 * The test suite covers the happy path, invalid inputs, and the
 * numeric-bypass regression (CRIT-01) that was fixed in an earlier sprint.
 */
class EncryptIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('SECURITY_KEY=test_secure_key_phpunit_suite');
    }

    // ── Round-trip ────────────────────────────────────────────────────────────

    public function test_encrypt_then_decrypt_returns_original_id(): void
    {
        foreach ([1, 42, 999, 10000] as $id) {
            $encrypted = encrypt_id($id);
            $this->assertEquals($id, decrypt_id($encrypted),
                "Round-trip failed for ID {$id}");
        }
    }

    public function test_encrypted_value_differs_from_original(): void
    {
        $id        = 42;
        $encrypted = encrypt_id($id);
        $this->assertNotEquals($id, $encrypted);
        $this->assertNotEquals((string) $id, $encrypted);
    }

    public function test_encrypted_value_is_not_empty(): void
    {
        $this->assertNotEmpty(encrypt_id(1));
    }

    // ── Key sensitivity ───────────────────────────────────────────────────────

    public function test_different_keys_produce_different_ciphertext(): void
    {
        putenv('SECURITY_KEY=key_one');
        $enc1 = encrypt_id(42);

        putenv('SECURITY_KEY=key_two');
        $enc2 = encrypt_id(42);

        $this->assertNotEquals($enc1, $enc2);

        // Restore for subsequent tests
        putenv('SECURITY_KEY=test_secure_key_phpunit_suite');
    }

    // ── Invalid / malicious input rejection ───────────────────────────────────

    public function test_decrypt_garbage_string_returns_empty(): void
    {
        $result = decrypt_id('not_a_valid_encrypted_value_xyz');
        $this->assertEmpty($result);
    }

    public function test_decrypt_plain_integer_string_is_rejected(): void
    {
        // Regression guard for CRIT-01: passing a raw integer string must NOT
        // return the integer back, which would bypass the encryption layer.
        $result = decrypt_id('42');
        $this->assertEmpty($result);
    }

    public function test_decrypt_empty_string_returns_empty(): void
    {
        $this->assertEmpty(decrypt_id(''));
    }

    public function test_decrypt_zero_string_returns_empty(): void
    {
        $this->assertEmpty(decrypt_id('0'));
    }

    // ── Cross-contamination guard ─────────────────────────────────────────────

    public function test_encrypted_id_from_one_record_does_not_decrypt_to_another(): void
    {
        $enc7  = encrypt_id(7);
        $enc8  = encrypt_id(8);
        $this->assertNotEquals(decrypt_id($enc7), decrypt_id($enc8));
    }
}
