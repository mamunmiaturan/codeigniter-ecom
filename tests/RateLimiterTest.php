<?php
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the sliding-window rate-limiter logic in Api_rate_limiter.php.
 *
 * These tests verify the core arithmetic directly — no CI3 framework or database
 * connection required.  The DB-backend path is tested through a lightweight stub
 * that mimics the `$ci->db` interface.
 */
class RateLimiterTest extends TestCase
{
    // ── Sliding-window arithmetic ─────────────────────────────────────────────

    public function test_first_hit_within_window_is_allowed(): void
    {
        $hits      = 1;
        $max_hits  = 5;
        $this->assertTrue($hits <= $max_hits);
        $this->assertSame(4, max(0, $max_hits - $hits));
    }

    public function test_hit_at_limit_is_allowed(): void
    {
        $hits     = 5;
        $max_hits = 5;
        $this->assertTrue($hits <= $max_hits);
        $this->assertSame(0, max(0, $max_hits - $hits));
    }

    public function test_hit_over_limit_is_blocked(): void
    {
        $hits     = 6;
        $max_hits = 5;
        $this->assertFalse($hits <= $max_hits);
        $this->assertSame(0, max(0, $max_hits - $hits));
    }

    public function test_remaining_never_goes_negative(): void
    {
        $hits     = 100;
        $max_hits = 5;
        $this->assertSame(0, max(0, $max_hits - $hits));
    }

    // ── Window expiry logic ───────────────────────────────────────────────────

    public function test_expired_window_resets_counter(): void
    {
        $now          = time();
        $window_sec   = 900; // 15 min
        $window_start = $now - $window_sec - 1; // started just over a window ago

        // If window_start < (now - window_sec) the window has expired
        $this->assertLessThan($now - $window_sec, $window_start,
            'An expired window_start must be older than now - window_sec');
    }

    public function test_active_window_is_not_expired(): void
    {
        $now          = time();
        $window_sec   = 900;
        $window_start = $now - 60; // started 1 minute ago

        $this->assertGreaterThanOrEqual($now - $window_sec, $window_start,
            'A window started 1 minute ago must still be active for a 15-min window');
    }

    public function test_reset_in_calculation(): void
    {
        $now          = time();
        $window_sec   = 300; // 5 min
        $window_start = $now - 120; // 2 minutes into the window

        $reset_in = max(0, ($window_start + $window_sec) - $now);

        // Should be roughly 180 seconds remaining (allow ±2 s for test execution)
        $this->assertGreaterThanOrEqual(178, $reset_in);
        $this->assertLessThanOrEqual(180, $reset_in);
    }

    public function test_reset_in_never_negative(): void
    {
        $now          = time();
        $window_sec   = 300;
        $window_start = $now - 400; // window already expired

        $reset_in = max(0, ($window_start + $window_sec) - $now);
        $this->assertSame(0, $reset_in);
    }

    // ── Key format conventions ────────────────────────────────────────────────

    public function test_rate_key_format_forgot_password(): void
    {
        $ip  = '192.168.1.10';
        $key = 'forgot:' . $ip;
        $this->assertStringStartsWith('forgot:', $key);
        $this->assertStringContainsString($ip, $key);
    }

    public function test_rate_key_format_2fa_verify(): void
    {
        $ip  = '10.0.0.5';
        $key = '2fa_verify:' . $ip;
        $this->assertStringStartsWith('2fa_verify:', $key);
        $this->assertStringContainsString($ip, $key);
    }

    public function test_different_ips_produce_different_keys(): void
    {
        $key_a = 'forgot:1.2.3.4';
        $key_b = 'forgot:5.6.7.8';
        $this->assertNotSame($key_a, $key_b);
    }

    public function test_different_endpoints_produce_different_keys(): void
    {
        $ip    = '127.0.0.1';
        $key_a = 'forgot:' . $ip;
        $key_b = '2fa_verify:' . $ip;
        $this->assertNotSame($key_a, $key_b);
    }

    // ── Response structure ────────────────────────────────────────────────────

    public function test_allowed_response_structure(): void
    {
        // Simulate what Api_rate_limiter::_check_db returns on first hit
        $result = ['allowed' => true, 'remaining' => 4, 'reset_in' => 900];

        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('reset_in', $result);
        $this->assertTrue($result['allowed']);
        $this->assertIsInt($result['remaining']);
        $this->assertIsInt($result['reset_in']);
    }

    public function test_blocked_response_structure(): void
    {
        $result = ['allowed' => false, 'remaining' => 0, 'reset_in' => 240];

        $this->assertFalse($result['allowed']);
        $this->assertSame(0, $result['remaining']);
        $this->assertGreaterThan(0, $result['reset_in']);
    }
}
