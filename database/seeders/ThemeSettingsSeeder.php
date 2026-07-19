<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ThemeSettingsSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('theme_settings');
        // Align theme settings seeder with new schema (dark_skin column removed)
        $this->db->insert('theme_settings', [
            'id' => 1,
            'primary_color' => '#5956ea',
            'secondary_color' => '#6c757d',
            'sidebar_color' => '#ffffff',
            'sidebar_text_color' => '#6b7280',
            'navbar_color' => '#ffffff',
            'navbar_text_color' => '#6b7280',
            'dark_mode' => 0,
            'dark_skin' => 'false'
        ]);
        echo "ThemeSettingsSeeder Finished." . PHP_EOL;
    }
}
