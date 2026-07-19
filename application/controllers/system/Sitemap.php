<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Marketing
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Sitemap.php
 *
 * Public XML sitemap generated from active products, categories and CMS pages.
 * Extends CI_Controller directly (no session/auth/route-guard overhead) and
 * emits application/xml. Route: sitemap.xml
 */
class Sitemap extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
    }

    public function index()
    {
        $urls = [
            ['loc' => base_url('/'),      'priority' => '1.0'],
            ['loc' => base_url('shop'), 'priority' => '0.9'],
        ];

        foreach ($this->db->select('slug, updated_at, created_at')->where('status', 'Active')->where('deleted_at', null)->order_by('id', 'DESC')->get('products')->result() as $p) {
            $urls[] = ['loc' => base_url('product/' . rawurlencode($p->slug)), 'lastmod' => date('Y-m-d', strtotime($p->updated_at ?: $p->created_at)), 'priority' => '0.8'];
        }
        foreach ($this->db->select('slug')->where('status', 'Active')->where('deleted_at', null)->get('categories')->result() as $c) {
            $urls[] = ['loc' => base_url('shop?category=' . rawurlencode($c->slug)), 'priority' => '0.6'];
        }
        if ($this->db->table_exists('cms_pages')) {
            foreach ($this->db->select('slug, updated_at, created_at')->where('status', 'Active')->where('deleted_at', null)->get('cms_pages')->result() as $pg) {
                $urls[] = ['loc' => base_url('page/' . rawurlencode($pg->slug)), 'lastmod' => date('Y-m-d', strtotime($pg->updated_at ?: $pg->created_at)), 'priority' => '0.5'];
            }
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
            if (!empty($u['lastmod'])) {
                $xml .= '<lastmod>' . $u['lastmod'] . '</lastmod>';
            }
            $xml .= '<changefreq>weekly</changefreq><priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>';

        $this->output->set_content_type('application/xml', 'utf-8')->set_output($xml);
    }
}
