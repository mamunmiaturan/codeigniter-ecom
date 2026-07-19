<?php
use PHPUnit\Framework\TestCase;

/**
 * Tests for sanitizeString() in general_helper.php.
 *
 * sanitizeString() is a security-critical function — it strips tags and
 * encodes special HTML characters before values reach views.  These tests
 * document the expected behaviour and guard against regressions.
 */
class SanitizationTest extends TestCase
{
    // ── Basic sanitization ────────────────────────────────────────────────────

    public function test_plain_string_is_returned_unchanged(): void
    {
        $this->assertSame('hello world', sanitizeString('hello world'));
    }

    public function test_returns_empty_string_for_empty_input(): void
    {
        $this->assertSame('', sanitizeString(''));
    }

    public function test_returns_null_input_unchanged(): void
    {
        // sanitizeString() early-returns $var as-is when empty() is true
        $this->assertNull(sanitizeString(null));
    }

    public function test_strips_html_tags(): void
    {
        $result = sanitizeString('<b>bold</b>');
        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringNotContainsString('</b>', $result);
    }

    public function test_strips_script_tags(): void
    {
        $result = sanitizeString('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
    }

    public function test_encodes_angle_brackets(): void
    {
        $result = sanitizeString('<div>test</div>');
        $this->assertStringNotContainsString('<div>', $result);
    }

    public function test_trims_leading_and_trailing_whitespace(): void
    {
        $this->assertSame(sanitizeString('  hello  '), sanitizeString('hello'));
    }

    // ── XSS payload hardening ─────────────────────────────────────────────────

    public function test_xss_event_handler_is_neutralised(): void
    {
        $payload = '<img src=x onerror=alert(1)>';
        $result  = sanitizeString($payload);
        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringNotContainsString('onerror', $result);
    }

    public function test_javascript_uri_in_tag_is_stripped(): void
    {
        $payload = '<a href="javascript:void(0)">click</a>';
        $result  = sanitizeString($payload);
        $this->assertStringNotContainsString('<a', $result);
    }

    // ── Array input ───────────────────────────────────────────────────────────

    public function test_array_values_are_sanitized_recursively(): void
    {
        $input  = ['name' => '<b>Alice</b>', 'bio' => 'hello'];
        $result = sanitizeString($input);
        $this->assertIsArray($result);
        $this->assertStringNotContainsString('<b>', $result['name']);
        $this->assertSame('hello', $result['bio']);
    }

    public function test_nested_array_is_sanitized(): void
    {
        $input  = ['outer' => ['inner' => '<script>evil</script>']];
        $result = sanitizeString($input);
        $this->assertStringNotContainsString('<script>', $result['outer']['inner']);
    }
}
