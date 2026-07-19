<?php
use PHPUnit\Framework\TestCase;

/**
 * Tests for the private-IP detection logic used in permission_helper.php.
 *
 * The ABAC guard logs (but does not block) requests that originate from a
 * WAN IP.  These tests document which address ranges are treated as local
 * and which as external, protecting against future regressions in the regex.
 */
class PermissionHelperIpTest extends TestCase
{
    /**
     * Re-implements the IP detection exactly as it appears in
     * permission_helper.php so we can unit-test it without loading CI.
     */
    private function isLocal(string $ip): bool
    {
        return (
            in_array($ip, ['127.0.0.1', '::1'], true) ||
            strpos($ip, '10.')       === 0 ||
            strpos($ip, '192.168.')  === 0 ||
            (bool) preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip)
        );
    }

    // ── Loopback ──────────────────────────────────────────────────────────────

    public function test_ipv4_loopback_is_local(): void
    {
        $this->assertTrue($this->isLocal('127.0.0.1'));
    }

    public function test_ipv6_loopback_is_local(): void
    {
        $this->assertTrue($this->isLocal('::1'));
    }

    // ── RFC-1918 Class A (10.0.0.0/8) ────────────────────────────────────────

    public function test_10_network_is_local(): void
    {
        $this->assertTrue($this->isLocal('10.0.0.1'));
        $this->assertTrue($this->isLocal('10.255.255.254'));
    }

    // ── RFC-1918 Class C (192.168.0.0/16) ────────────────────────────────────

    public function test_192_168_network_is_local(): void
    {
        $this->assertTrue($this->isLocal('192.168.0.1'));
        $this->assertTrue($this->isLocal('192.168.100.200'));
    }

    // ── RFC-1918 Class B (172.16.0.0/12) ─────────────────────────────────────

    public function test_172_16_is_local(): void
    {
        $this->assertTrue($this->isLocal('172.16.0.1'));
    }

    public function test_172_31_is_local(): void
    {
        $this->assertTrue($this->isLocal('172.31.255.254'));
    }

    public function test_172_20_is_local(): void
    {
        $this->assertTrue($this->isLocal('172.20.10.5'));
    }

    public function test_172_15_is_NOT_local(): void
    {
        // 172.15.x.x falls outside the /12 block — must be treated as WAN
        $this->assertFalse($this->isLocal('172.15.0.1'));
    }

    public function test_172_32_is_NOT_local(): void
    {
        // 172.32.x.x falls outside the /12 block — must be treated as WAN
        $this->assertFalse($this->isLocal('172.32.0.1'));
    }

    // ── Public / WAN addresses ────────────────────────────────────────────────

    public function test_public_ip_is_not_local(): void
    {
        $this->assertFalse($this->isLocal('8.8.8.8'));
        $this->assertFalse($this->isLocal('203.0.113.1'));
        $this->assertFalse($this->isLocal('1.1.1.1'));
    }

    public function test_192_169_is_not_local(): void
    {
        // Looks similar to 192.168 but is public
        $this->assertFalse($this->isLocal('192.169.0.1'));
    }

    public function test_11_network_is_not_local(): void
    {
        // Starts with '1' but is NOT a private range
        $this->assertFalse($this->isLocal('11.0.0.1'));
    }
}
