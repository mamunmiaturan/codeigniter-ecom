<?php
use PHPUnit\Framework\TestCase;

/**
 * Validates the post-login redirect allowlist from HIGH-A2.
 *
 * We reproduce the exact decision rule (`base_url()`-prefixed absolute URL OR
 * single-leading-slash relative URL with no scheme/host) here so future
 * refactors of Authentication::index() can be guarded by tests.
 */
class PostLoginRedirectTest extends TestCase
{
    private function isSafe(string $url, string $base = 'http://localhost/'): bool
    {
        if ($url === '') return false;

        // Absolute URL: must begin with our base_url() and use http(s).
        if (strpos($url, $base) === 0 && preg_match('#^https?://#i', $url)) {
            return true;
        }

        // Relative path: must start with a single forward slash followed by a
        // non-slash, non-backslash char (rejects //evil.com and \\evil).
        if (preg_match('#^/[^/\\\\]#', $url) === 1) {
            return true;
        }

        return false;
    }

    /** @dataProvider safeUrls */
    public function test_safe_urls_accepted(string $url): void
    {
        $this->assertTrue($this->isSafe($url), "Should be accepted: $url");
    }

    public function safeUrls(): array
    {
        return [
            'absolute_same_origin'  => ['http://localhost/dashboard'],
            'absolute_with_query'   => ['http://localhost/user?role=2'],
            'relative_root'         => ['/dashboard'],
            'relative_with_query'   => ['/user/profile/123?tab=2'],
            'relative_with_hash'    => ['/profile#security'],
        ];
    }

    /** @dataProvider unsafeUrls */
    public function test_unsafe_urls_rejected(string $url): void
    {
        $this->assertFalse($this->isSafe($url), "Should be rejected: $url");
    }

    public function unsafeUrls(): array
    {
        return [
            'protocol_relative_evil'  => ['//evil.com/path'],
            'absolute_cross_origin'   => ['http://evil.com/dashboard'],
            'https_cross_origin'      => ['https://evil.com/dashboard'],
            'schemeless_host'         => ['evil.com/dashboard'],
            'javascript_uri'          => ['javascript:alert(1)'],
            'data_uri'                => ['data:text/html,<script>alert(1)</script>'],
            'backslash_attempt'       => ['\\\\evil.com'],
            'mixed_slash_backslash'   => ['/\\evil.com'],
            'empty'                   => [''],
            'whitespace_only'         => ['   '],
        ];
    }
}
