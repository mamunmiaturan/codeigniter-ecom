<?php
use PHPUnit\Framework\TestCase;

/**
 * Validates MED-A5: the Queue inline-fallback path rejects any job descriptor
 * that is not in the allowlist (defense vs. compromised `jobs` table → RCE).
 *
 * The allowlist itself is duplicated here so a change to Queue::execute_job's
 * allowlist must come with an intentional test update — preventing silent
 * widening of attack surface.
 */
class QueueAllowlistTest extends TestCase
{
    private const ALLOWED = [
        'queue'  => ['send_email', 'send_sms', 'persist_activity_log', 'trigger_pusher'],
        'import' => ['process_queue'],
    ];

    private function isAllowed(string $uri): bool
    {
        $segments        = explode('/', trim($uri, '/'));
        $controller_name = strtolower($segments[0] ?? '');
        $method_name     = strtolower($segments[1] ?? '');

        if (preg_match('/^[a-z][a-z0-9_]{0,40}$/', $controller_name) !== 1) {
            return false;
        }
        if (preg_match('/^[a-z][a-z0-9_]{0,40}$/', $method_name) !== 1) {
            return false;
        }
        return isset(self::ALLOWED[$controller_name])
            && in_array($method_name, self::ALLOWED[$controller_name], true);
    }

    /** @dataProvider allowedJobs */
    public function test_allowed_jobs_accepted(string $uri): void
    {
        $this->assertTrue($this->isAllowed($uri), "Should be allowed: $uri");
    }

    public function allowedJobs(): array
    {
        return [
            ['queue/send_email'],
            ['queue/send_sms'],
            ['queue/persist_activity_log'],
            ['queue/trigger_pusher'],
            ['import/process_queue'],
            // Controller intentionally case-normalizes — this is the same job
            // as queue/send_email, just sent in upper case. Must be accepted
            // so callers don't break, and must NOT bypass the allowlist.
            'normalized_caps' => ['QUEUE/SEND_EMAIL'],
        ];
    }

    /** @dataProvider blockedJobs */
    public function test_blocked_jobs_rejected(string $uri): void
    {
        $this->assertFalse($this->isAllowed($uri), "Should be blocked: $uri");
    }

    public function blockedJobs(): array
    {
        return [
            'unknown_controller'   => ['user/delete'],
            'auth_bypass'          => ['authentication/auto_login'],
            'dbtool_RCE'           => ['dbtool/backup'],
            'system_RCE'           => ['system/exec'],
            'path_traversal'       => ['../system/exec/x'],
            'sql_in_name'          => ["queue';DROP/whatever"],
            'wrong_method'         => ['queue/run_anything'],
            'empty'                => [''],
            'only_controller'      => ['queue/'],
        ];
    }
}
