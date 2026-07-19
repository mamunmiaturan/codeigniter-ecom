<?php
use PHPUnit\Framework\TestCase;

/**
 * Reproduces the Audit_chain hashing logic in isolation so we can verify the
 * cryptographic properties (chain continuity, tamper detection) without
 * standing up a database. The real library is exercised once an integration
 * test harness lands.
 */
class AuditChainTest extends TestCase
{
    private string $key = 'ci_test_key_for_audit_0000000000000000000000000000000';

    private function append(array &$chain, array $payload): array
    {
        ksort($payload);
        $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payload_hash = hash('sha256', $canonical);
        $prev = end($chain)['chain_hash'] ?? '';
        $chain_hash = hash_hmac('sha256', $prev . $payload_hash, $this->key);
        $row = compact('prev', 'payload_hash', 'chain_hash', 'canonical');
        $chain[] = ['prev_hash' => $prev, 'payload_hash' => $payload_hash,
                    'chain_hash' => $chain_hash, 'payload' => $canonical];
        return $row;
    }

    private function verify(array $chain): array
    {
        $prev = '';
        foreach ($chain as $i => $row) {
            $payload_hash = hash('sha256', $row['payload']);
            $expected = hash_hmac('sha256', $prev . $payload_hash, $this->key);
            if (!hash_equals($expected, $row['chain_hash'])
                || $row['prev_hash'] !== $prev
                || $row['payload_hash'] !== $payload_hash) {
                return ['ok' => false, 'broken_at' => $i];
            }
            $prev = $row['chain_hash'];
        }
        return ['ok' => true, 'broken_at' => null];
    }

    public function test_clean_chain_verifies()
    {
        $chain = [];
        $this->append($chain, ['action' => 'login', 'user' => 1]);
        $this->append($chain, ['action' => 'update', 'user' => 1]);
        $this->append($chain, ['action' => 'delete', 'user' => 2]);
        $result = $this->verify($chain);
        $this->assertTrue($result['ok']);
        $this->assertNull($result['broken_at']);
    }

    public function test_tampered_payload_breaks_chain()
    {
        $chain = [];
        $this->append($chain, ['action' => 'login', 'user' => 1]);
        $this->append($chain, ['action' => 'update', 'user' => 1]);
        // Adversary edits row 0's payload silently.
        $chain[0]['payload'] = '{"action":"superadmin","user":1}';
        $result = $this->verify($chain);
        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['broken_at']);
    }

    public function test_deleted_row_breaks_chain()
    {
        $chain = [];
        $this->append($chain, ['action' => 'login', 'user' => 1]);
        $this->append($chain, ['action' => 'update', 'user' => 1]);
        $this->append($chain, ['action' => 'delete', 'user' => 2]);
        // Drop the middle row — the third now has stale prev_hash.
        unset($chain[1]);
        $chain = array_values($chain);
        $result = $this->verify($chain);
        $this->assertFalse($result['ok']);
        $this->assertSame(1, $result['broken_at']);
    }

    public function test_reordered_rows_break_chain()
    {
        $chain = [];
        $this->append($chain, ['action' => 'login', 'user' => 1]);
        $this->append($chain, ['action' => 'update', 'user' => 1]);
        [$chain[0], $chain[1]] = [$chain[1], $chain[0]];
        $this->assertFalse($this->verify($chain)['ok']);
    }

    public function test_canonical_payload_is_deterministic()
    {
        $a = ['b' => 2, 'a' => 1];
        $b = ['a' => 1, 'b' => 2];
        ksort($a); ksort($b);
        $this->assertSame(
            json_encode($a, JSON_UNESCAPED_SLASHES),
            json_encode($b, JSON_UNESCAPED_SLASHES)
        );
    }
}
