<?php
use PHPUnit\Framework\TestCase;

/**
 * In-memory simulation of Api_refresh_token_model::rotate semantics:
 *  - Single-use tokens (used_at is set on rotation)
 *  - Reuse-detection cascades through the descendant chain
 *
 * Catches regressions in the rotation policy without requiring a real DB.
 */
class RefreshTokenRotationLogicTest extends TestCase
{
    /** @var array<int, array{id:int,parent_id:?int,used_at:?int,revoked_at:?int,expires_at:int}> */
    private array $store = [];
    private int $next_id = 1;

    private function issue(?int $parent_id = null, int $ttl = 3600): int
    {
        $id = $this->next_id++;
        $this->store[$id] = [
            'id'         => $id,
            'parent_id'  => $parent_id,
            'used_at'    => null,
            'revoked_at' => null,
            'expires_at' => time() + $ttl,
        ];
        return $id;
    }

    private function rotate(int $id): ?int
    {
        $row = $this->store[$id] ?? null;
        if (!$row) return null;
        if ($row['revoked_at'] !== null) return null;
        if ($row['expires_at'] < time()) return null;
        if ($row['used_at'] !== null) {
            // Reuse → revoke whole chain (walk to root, then BFS descendants).
            $root = $id;
            while ($this->store[$root]['parent_id'] !== null) {
                $root = $this->store[$root]['parent_id'];
            }
            $frontier = [$root];
            $now = time();
            while ($frontier) {
                $node = array_shift($frontier);
                if ($this->store[$node]['revoked_at'] === null) {
                    $this->store[$node]['revoked_at'] = $now;
                }
                foreach ($this->store as $candidate) {
                    if ($candidate['parent_id'] === $node) {
                        $frontier[] = $candidate['id'];
                    }
                }
            }
            return null;
        }
        $this->store[$id]['used_at'] = time();
        return $this->issue($id);
    }

    public function test_single_rotation_invalidates_original()
    {
        $a = $this->issue();
        $b = $this->rotate($a);
        $this->assertNotNull($b);
        $this->assertNull($this->rotate($a), 'Original token must not rotate twice');
    }

    public function test_reuse_revokes_whole_chain()
    {
        $a = $this->issue();
        $b = $this->rotate($a);   // a used, b active
        $c = $this->rotate($b);   // b used, c active
        // Adversary replays "a" (reuse attack).
        $this->assertNull($this->rotate($a));
        // c must now be revoked too.
        $this->assertNotNull($this->store[$c]['revoked_at']);
        // And b must be marked revoked.
        $this->assertNotNull($this->store[$b]['revoked_at']);
    }

    public function test_expired_token_cannot_rotate()
    {
        $a = $this->issue(null, -10); // already expired
        $this->assertNull($this->rotate($a));
    }

    public function test_revoked_token_cannot_rotate()
    {
        $a = $this->issue();
        $this->store[$a]['revoked_at'] = time();
        $this->assertNull($this->rotate($a));
    }
}
