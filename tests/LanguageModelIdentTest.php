<?php
use PHPUnit\Framework\TestCase;

/**
 * Validates MED-A3: Language_model rejects unsafe identifiers for ALTER/DROP
 * COLUMN statements so the model is safe even if a future caller skips the
 * controller-side regex on language codes.
 */
class LanguageModelIdentTest extends TestCase
{
    private function makeModel()
    {
        if (!class_exists('CI_Model')) {
            eval('#[\AllowDynamicProperties] class CI_Model { public function __construct() {} public function __get($k) { return $this->$k ?? null; } }');
        }
        if (!class_exists('Base_Model')) {
            eval('#[\AllowDynamicProperties] class Base_Model extends CI_Model { protected $table = "x"; public function __construct() { parent::__construct(); } }');
        }
        if (!class_exists('MY_Model')) {
            eval('#[\AllowDynamicProperties] class MY_Model extends Base_Model { public function __construct() { parent::__construct(); } }');
        }
        require_once APPPATH . 'models/Language_model.php';
        if (!class_exists('TestableLanguageModel')) {
            eval('#[\AllowDynamicProperties] class TestableLanguageModel extends Language_model {}');
        }

        $model = new TestableLanguageModel();
        $model->db = new class {
            public $queries = [];
            public function query($sql) { $this->queries[] = $sql; return true; }
        };
        return $model;
    }

    /** @dataProvider badIdents */
    public function test_modify_column_position_rejects_bad_idents($column, $after)
    {
        $model = $this->makeModel();
        $this->assertFalse($model->modify_column_position($column, $after));
        $this->assertCount(0, $model->db->queries);
    }

    /** @dataProvider badIdents */
    public function test_drop_column_rejects_bad_idents($column, $after)
    {
        $model = $this->makeModel();
        $this->assertFalse($model->drop_column('languages', $column));
        $this->assertCount(0, $model->db->queries);
    }

    public function badIdents(): array
    {
        return [
            'backtick_injection'  => ['en`; DROP TABLE users; --', 'word_key'],
            'space'               => ['en us', 'word_key'],
            'semicolon'           => ['en;', 'word_key'],
            'comment'             => ['en/*x*/', 'word_key'],
            'starts_with_digit'   => ['1en', 'word_key'],
            'empty'               => ['', 'word_key'],
            'too_long'            => [str_repeat('a', 80), 'word_key'],
        ];
    }

    public function test_drop_column_blocks_system_columns()
    {
        $model = $this->makeModel();
        foreach (['id', 'word_key', 'created_at', 'updated_at'] as $col) {
            $this->assertFalse($model->drop_column('languages', $col),
                "Should refuse to drop system column: $col");
        }
        $this->assertCount(0, $model->db->queries);
    }

    public function test_modify_column_position_accepts_safe_ident()
    {
        $model = $this->makeModel();
        $result = $model->modify_column_position('spanish', 'word_key');
        $this->assertNotFalse($result);
        $this->assertCount(1, $model->db->queries);
        $this->assertStringContainsString('`spanish`', $model->db->queries[0]);
        $this->assertStringContainsString('`word_key`', $model->db->queries[0]);
    }
}
