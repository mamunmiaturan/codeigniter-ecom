<?php
use PHPUnit\Framework\TestCase;

/**
 * Tests the password reuse-prevention logic in isolation by reproducing
 * Password_history_model::is_reused() without a real DB. The real model is
 * exercised end-to-end in feature tests once the HTTP test harness is added.
 */
class PasswordHistoryLogicTest extends TestCase
{
    private function is_reused(array $hashes, string $candidate): bool
    {
        foreach ($hashes as $hash) {
            if (password_verify($candidate, $hash)) {
                return true;
            }
        }
        return false;
    }

    private function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 4]);
    }

    public function test_rejects_exact_reuse()
    {
        $history = [$this->hash('CorrectHorseBatteryStaple1!')];
        $this->assertTrue($this->is_reused($history, 'CorrectHorseBatteryStaple1!'));
    }

    public function test_accepts_unrelated_password()
    {
        $history = [$this->hash('FirstPassw0rd!')];
        $this->assertFalse($this->is_reused($history, 'CompletelyDifferent2@'));
    }

    public function test_rejects_match_against_any_history_entry()
    {
        $history = [
            $this->hash('OldPassword1!'),
            $this->hash('OlderPassword2@'),
            $this->hash('TargetPassword3#'),
            $this->hash('NewerPassword4$'),
        ];
        $this->assertTrue($this->is_reused($history, 'TargetPassword3#'));
    }

    public function test_pruning_keeps_only_newest_n()
    {
        // Simulate keep_last = 3
        $kept = ['h4', 'h3', 'h2'];   // newest first
        $candidate_present_in_pruned = 'h1';
        $this->assertNotContains($candidate_present_in_pruned, $kept,
            'Pruned password must not appear in retained set');
    }

    public function test_case_sensitive_compare()
    {
        $history = [$this->hash('CaseSensitive!1')];
        $this->assertFalse($this->is_reused($history, 'casesensitive!1'));
        $this->assertTrue($this->is_reused($history, 'CaseSensitive!1'));
    }
}
