<?php
$role_id = loggedin_role_id();
$ci =& get_instance();
$ci->load->library('redis_lib');

if ($ci->redis_lib->is_enabled()) {
    $redis_key = 'sidebar_html_role_' . $role_id;
    $sidebar_html = $ci->redis_lib->get($redis_key);
    if (empty($sidebar_html)) {
        $sidebar_html = generate_sidebar_content($role_id);
        $ci->redis_lib->set($redis_key, $sidebar_html, 3600); // cache for 1 hour
    }
    // Render dynamic sidebar content from Redis cache safely
    eval('?>' . $sidebar_html);
} else {
    $filename = get_role_sidebar_filename($role_id);
    $role_sidebar_file = 'layout/sidebar/' . str_replace('.php', '', $filename);
    $full_path = APPPATH . 'views/' . $role_sidebar_file . '.php';

    // Regenerate if missing OR if the file is a stale placeholder
    $is_stale = file_exists($full_path) && strpos(file_get_contents($full_path), 'Stale') !== false;

    if (!file_exists($full_path) || $is_stale) {
        generate_sidebar_files();
    }

    if (file_exists($full_path) && strpos(file_get_contents($full_path), 'Stale') === false) {
        $ci->load->view($role_sidebar_file);
    } else {
        // Fallback: If folder permissions block writing files, render the sidebar content dynamically
        $sidebar_html = generate_sidebar_content($role_id);
        // Execute inline PHP code inside the sidebar string safely
        eval('?>' . $sidebar_html);
    }
}