<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Append-only HMAC-chained audit log.
 *
 * Storage: `audit_chain` table (one row per event).
 *   chain_hash[n] = HMAC-SHA256(SECURITY_KEY, prev_chain_hash || payload_hash)
 *
 * Properties:
 *  - Any deleted, edited, or reordered row breaks every subsequent hash.
 *  - An adversary with full DB write can still tamper, but cannot do so
 *    silently — `verify_chain()` will point at the first mismatch.
 *  - The key is the same SECURITY_KEY used elsewhere, so an attacker who
 *    extracted that secret could forge entries. Keep it out of DB dumps.
 *
 * Concurrency: relies on `id ASC` order. A short transaction inserts the row
 * after computing the chain hash from the current tail; under contention the
 * race window is on the order of a few ms. For a true cryptographic ledger,
 * push the writes through a single queue worker.
 */
class Audit_chain
{
    /** @var CI_Controller */
    protected $ci;

    /** @var string */
    private $key;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->database();
        $k = getenv('AUDIT_CHAIN_KEY') ?: getenv('SECURITY_KEY') ?: '';
        if (strlen($k) < 32) {
            // Don't throw — let activity logging keep working even if the chain
            // is misconfigured. We log loudly so operators see it.
            log_message('error', 'Audit_chain: SECURITY_KEY < 32 bytes; chain disabled until configured.');
        }
        $this->key = $k;
    }

    public function enabled(): bool
    {
        return strlen($this->key) >= 32 && $this->ci->db->table_exists('audit_chain');
    }

    /**
     * Append one event. $payload should be a plain assoc array; we canonicalize
     * (sort keys + JSON_UNESCAPED_SLASHES) to make hashes reproducible.
     */
    public function append(array $payload): void
    {
        if (!$this->enabled()) {
            return;
        }
        $canonical    = $this->_canonical($payload);
        $payload_hash = hash('sha256', $canonical);

        // Read tail under SELECT … FOR UPDATE-equivalent (CI3 portable: trans).
        $this->ci->db->trans_start();
        $tail = $this->ci->db
            ->select('chain_hash')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('audit_chain')
            ->row();
        $prev_hash = $tail->chain_hash ?? '';

        $chain_hash = hash_hmac('sha256', $prev_hash . $payload_hash, $this->key);

        $this->ci->db->insert('audit_chain', [
            'prev_hash'    => $prev_hash,
            'payload_hash' => $payload_hash,
            'chain_hash'   => $chain_hash,
            'payload'      => $canonical,
        ]);
        $this->ci->db->trans_complete();
    }

    /**
     * Walk the chain in ID order. Returns ['ok' => bool, 'broken_at' => int|null,
     * 'checked' => int]. Caller can limit how many rows to verify (default: all).
     */
    public function verify_chain(int $limit = 0): array
    {
        if (!$this->enabled()) {
            return ['ok' => false, 'broken_at' => null, 'checked' => 0, 'reason' => 'disabled'];
        }
        $query = $this->ci->db->order_by('id', 'ASC');
        if ($limit > 0) {
            $query->limit($limit);
        }
        $rows = $query->get('audit_chain')->result();
        $prev = '';
        $count = 0;
        foreach ($rows as $row) {
            $count++;
            $payload_hash = hash('sha256', (string) $row->payload);
            $expected = hash_hmac('sha256', $prev . $payload_hash, $this->key);
            if (!hash_equals($expected, (string) $row->chain_hash)
                || (string) $row->prev_hash !== $prev
                || (string) $row->payload_hash !== $payload_hash) {
                return ['ok' => false, 'broken_at' => (int) $row->id, 'checked' => $count];
            }
            $prev = (string) $row->chain_hash;
        }
        return ['ok' => true, 'broken_at' => null, 'checked' => $count];
    }

    private function _canonical(array $payload): string
    {
        ksort($payload);
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
