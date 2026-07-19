<?php
use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase {
    
    public function setUp(): void {
        parent::setUp();
        // Set a mock environment key for testing
        putenv('SECURITY_KEY=test_secure_key_12345');
    }

    public function test_encrypt_and_decrypt_id() {
        $original_id = 42;
        
        // Test Encryption
        $encrypted = encrypt_id($original_id);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($original_id, $encrypted);
        
        // Test Decryption
        $decrypted = decrypt_id($encrypted);
        $this->assertEquals($original_id, $decrypted);
    }
    
    public function test_decrypt_invalid_hash_returns_empty() {
        $invalid_hash = 'invalid_random_string_that_is_not_base64';
        $decrypted = decrypt_id($invalid_hash);
        $this->assertEmpty($decrypted);
    }

    public function test_decrypt_numeric_bypass_is_prevented() {
        // Ensuring CRIT-01 is permanently fixed
        $malicious_id = '42';
        $decrypted = decrypt_id($malicious_id);
        
        // It should return empty string, NOT the plain integer back
        $this->assertEmpty($decrypted);
    }
}
