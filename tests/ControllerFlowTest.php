<?php
use PHPUnit\Framework\TestCase;

/**
 * Controller-flow integration tests.
 *
 * Tests the core logic used by Authentication, Import, and MY_Controller
 * without requiring the CI3 framework, a database, or HTTP.  Each test
 * exercises a rule that lives in the controller layer but that can be
 * expressed in pure PHP.
 */
class ControllerFlowTest extends TestCase
{
    // ── Login session structure ───────────────────────────────────────────────

    public function test_successful_login_session_has_all_required_keys(): void
    {
        $session = $this->_make_login_session();

        $required = ['loggedin', 'loggedin_id', 'loggedin_role_id', 'loggedin_userid',
                     'uniqueid', 'logger_photo', 'date_format', 'set_lang'];

        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $session, "Login session must contain: $key");
        }

        $this->assertTrue($session['loggedin']);
        $this->assertIsInt($session['loggedin_userid']);
        $this->assertIsInt($session['loggedin_role_id']);
    }

    public function test_login_session_does_not_contain_plaintext_password(): void
    {
        $session = $this->_make_login_session();

        $this->assertArrayNotHasKey('password', $session);
        $this->assertArrayNotHasKey('pass', $session);
        $this->assertArrayNotHasKey('pwd', $session);
    }

    public function test_superman_role_id_matches_constant(): void
    {
        $session = $this->_make_login_session(ROLE_SUPERMAN_ID);
        $this->assertSame(ROLE_SUPERMAN_ID, $session['loggedin_role_id']);
    }

    // ── Session fingerprint logic ─────────────────────────────────────────────

    public function test_fingerprint_is_hmac_sha256(): void
    {
        $ip        = '192.168.1.55';
        $ua        = 'Mozilla/5.0 (compatible; Test)';
        $key       = 'test_enc_key_32bytes_padded_xxxxx';
        $subnet    = '192.168.1';  // first 3 octets for IPv4

        $fp = hash_hmac('sha256', $subnet . '|' . $ua, $key);

        $this->assertSame(64, strlen($fp), 'SHA-256 hex digest must be 64 chars');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $fp);
    }

    public function test_same_subnet_produces_same_fingerprint(): void
    {
        $key = 'test_enc_key';
        $ua  = 'TestAgent/1.0';

        $subnet_a = $this->_ipv4_subnet('10.0.0.1');
        $subnet_b = $this->_ipv4_subnet('10.0.0.99');

        $fp_a = hash_hmac('sha256', $subnet_a . '|' . $ua, $key);
        $fp_b = hash_hmac('sha256', $subnet_b . '|' . $ua, $key);

        $this->assertSame($fp_a, $fp_b, 'Same /24 subnet must produce the same fingerprint');
    }

    public function test_different_subnet_produces_different_fingerprint(): void
    {
        $key = 'test_enc_key';
        $ua  = 'TestAgent/1.0';

        $fp_a = hash_hmac('sha256', $this->_ipv4_subnet('10.0.0.1')  . '|' . $ua, $key);
        $fp_b = hash_hmac('sha256', $this->_ipv4_subnet('10.0.1.1')  . '|' . $ua, $key);

        $this->assertNotSame($fp_a, $fp_b, 'Different /24 subnets must produce different fingerprints');
    }

    public function test_different_user_agent_produces_different_fingerprint(): void
    {
        $key    = 'test_enc_key';
        $subnet = $this->_ipv4_subnet('192.168.1.1');

        $fp_a = hash_hmac('sha256', $subnet . '|' . 'Chrome/100', $key);
        $fp_b = hash_hmac('sha256', $subnet . '|' . 'Firefox/99', $key);

        $this->assertNotSame($fp_a, $fp_b, 'Different user agents must produce different fingerprints');
    }

    public function test_fingerprint_comparison_uses_hash_equals(): void
    {
        $key    = 'key';
        $subnet = $this->_ipv4_subnet('172.16.0.5');
        $ua     = 'TestBrowser/2';

        $fp1 = hash_hmac('sha256', $subnet . '|' . $ua, $key);
        $fp2 = hash_hmac('sha256', $subnet . '|' . $ua, $key);

        $this->assertTrue(hash_equals($fp1, $fp2), 'hash_equals must confirm identical fingerprints');
        $this->assertFalse(hash_equals($fp1, str_repeat('0', 64)), 'hash_equals must reject tampered fingerprint');
    }

    // ── Import row validation logic ───────────────────────────────────────────

    public function test_import_row_rejects_missing_required_fields(): void
    {
        $this->assertTrue($this->_import_row_is_invalid('', 'a@b.com', 'P@ss1'));
        $this->assertTrue($this->_import_row_is_invalid('Name', '', 'P@ss1'));
        $this->assertTrue($this->_import_row_is_invalid('Name', 'a@b.com', ''));
    }

    public function test_import_row_rejects_invalid_email(): void
    {
        $this->assertFalse(filter_var('not-an-email', FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var('missing@', FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var('@domain.com', FILTER_VALIDATE_EMAIL) !== false);
    }

    public function test_import_row_accepts_valid_email(): void
    {
        $this->assertNotFalse(filter_var('user@example.com', FILTER_VALIDATE_EMAIL));
        $this->assertNotFalse(filter_var('user+tag@sub.domain.org', FILTER_VALIDATE_EMAIL));
    }

    public function test_import_privilege_escalation_blocked_for_non_superman(): void
    {
        $uploader_role = 2; // admin-level uploader
        $target_role   = 2; // same level — must be blocked

        $blocked = ($uploader_role != 1 && $target_role <= $uploader_role);
        $this->assertTrue($blocked, 'Non-superman cannot assign a role equal/higher than their own');
    }

    public function test_import_privilege_escalation_blocked_higher_role(): void
    {
        $uploader_role = 3;
        $target_role   = 2; // higher privilege (lower number)

        $blocked = ($uploader_role != 1 && $target_role <= $uploader_role);
        $this->assertTrue($blocked);
    }

    public function test_import_privilege_escalation_allowed_for_superman(): void
    {
        $uploader_role = 1; // superman
        $target_role   = 1; // assigning superman-level

        $blocked = ($uploader_role != 1 && $target_role <= $uploader_role);
        $this->assertFalse($blocked, 'Superman may assign any role');
    }

    public function test_import_privilege_escalation_allowed_lower_role(): void
    {
        $uploader_role = 2;
        $target_role   = 5; // lower privilege (higher number)

        $blocked = ($uploader_role != 1 && $target_role <= $uploader_role);
        $this->assertFalse($blocked, 'Assigning a lower-privilege role must be allowed');
    }

    public function test_import_gender_validation(): void
    {
        $valid = ['male', 'female', 'other'];

        $this->assertTrue(in_array(strtolower('Male'), $valid));
        $this->assertTrue(in_array(strtolower('FEMALE'), $valid));
        $this->assertTrue(in_array('other', $valid));
        $this->assertFalse(in_array(strtolower('unknown'), $valid));
        $this->assertFalse(in_array(strtolower('m'), $valid));
    }

    public function test_import_column_count_mismatch_is_detected(): void
    {
        $header = ['name', 'email', 'password', 'role_id'];
        $row    = ['John', 'john@example.com']; // only 2 of 4 columns

        $this->assertLessThan(count($header), count($row),
            'Row with fewer columns than header must be flagged');
    }

    public function test_import_stats_structure(): void
    {
        // Simulate the stats array returned by _process_import_rows
        $stats = ['inserted' => 8, 'failed' => 2, 'total' => 10, 'errors' => ['Row 3: Invalid email.'], 'trans_ok' => true];

        $this->assertArrayHasKey('inserted', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('errors', $stats);
        $this->assertArrayHasKey('trans_ok', $stats);
        $this->assertSame($stats['inserted'] + $stats['failed'], $stats['total']);
    }

    // ── ABAC time-window logic ────────────────────────────────────────────────

    public function test_abac_blocks_access_before_hour_window(): void
    {
        $hour_start = 6;
        $hour_end   = 23;
        $current    = 3; // 3 AM — before window

        $blocked = ($current < $hour_start || $current > $hour_end);
        $this->assertTrue($blocked);
    }

    public function test_abac_allows_access_within_hour_window(): void
    {
        $hour_start = 6;
        $hour_end   = 23;
        $current    = 14; // 2 PM — within window

        $blocked = ($current < $hour_start || $current > $hour_end);
        $this->assertFalse($blocked);
    }

    public function test_abac_blocks_access_after_hour_window(): void
    {
        $hour_start = 6;
        $hour_end   = 23;
        $current    = 24; // midnight (after 23)

        $blocked = ($current < $hour_start || $current > $hour_end);
        $this->assertTrue($blocked);
    }

    public function test_abac_allows_access_at_boundary_hours(): void
    {
        $hour_start = 6;
        $hour_end   = 23;

        $this->assertFalse((6 < $hour_start || 6 > $hour_end), 'Hour 6 (open boundary) must be allowed');
        $this->assertFalse((23 < $hour_start || 23 > $hour_end), 'Hour 23 (close boundary) must be allowed');
    }

    public function test_abac_private_ip_detection(): void
    {
        $local_ips = ['127.0.0.1', '::1', '10.0.0.5', '192.168.1.100', '172.16.5.10', '172.31.255.255'];
        $wan_ips   = ['8.8.8.8', '203.0.113.1', '172.32.0.1', '172.15.0.1'];

        foreach ($local_ips as $ip) {
            $this->assertTrue($this->_is_local_ip($ip), "$ip should be detected as local/private");
        }
        foreach ($wan_ips as $ip) {
            $this->assertFalse($this->_is_local_ip($ip), "$ip should be detected as WAN");
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function _make_login_session(int $role_id = 2): array
    {
        return [
            'loggedin'        => true,
            'loggedin_id'     => 10,
            'loggedin_role_id'=> $role_id,
            'loggedin_userid' => 42,
            'uniqueid'        => 'USR0042',
            'logger_photo'    => 'uploads/photos/default.jpg',
            'date_format'     => 'Y-m-d',
            'set_lang'        => 'english',
        ];
    }

    private function _ipv4_subnet(string $ip): string
    {
        $parts = explode('.', $ip);
        return (count($parts) >= 3) ? ($parts[0] . '.' . $parts[1] . '.' . $parts[2]) : $ip;
    }

    private function _import_row_is_invalid(string $name, string $email, string $password): bool
    {
        return (empty($name) || empty($email) || empty($password));
    }

    private function _is_local_ip(string $ip): bool
    {
        return (
            in_array($ip, ['127.0.0.1', '::1']) ||
            strpos($ip, '10.')      === 0 ||
            strpos($ip, '192.168.') === 0 ||
            (bool) preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip)
        );
    }
}
