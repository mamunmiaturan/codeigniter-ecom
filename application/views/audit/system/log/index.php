<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
/* ━━ opcodesio/log-viewer style ━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:13px;transition:background .15s,color .15s}

/* ── DARK (default) ── */
body{background:#111827;color:#d1d5db}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:#1f2937}
::-webkit-scrollbar-thumb{background:#374151;border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:#4b5563}

/* ── LIGHT ── */
body.light{background:#f3f4f6;color:#111827}
body.light ::-webkit-scrollbar-track{background:#e5e7eb}
body.light ::-webkit-scrollbar-thumb{background:#9ca3af}

/* ━━ Shell ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.lv{display:flex;height:100vh;overflow:hidden}

/* ━━ SIDEBAR ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.lv-sb{width:22rem;flex-shrink:0;display:flex;flex-direction:column;overflow:hidden;
  background:#1f2937;border-right:1px solid #374151}
body.light .lv-sb{background:#fff;border-right-color:#e5e7eb}

.lv-sb-header{padding:16px 16px 12px;border-bottom:1px solid #374151;flex-shrink:0}
body.light .lv-sb-header{border-bottom-color:#e5e7eb}

/* back link */
.lv-back{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:#6b7280;margin-bottom:10px;transition:color .12s}
.lv-back:hover{color:#9ca3af}
body.light .lv-back{color:#9ca3af}
body.light .lv-back:hover{color:#6b7280}

/* brand row */
.lv-brand{display:flex;align-items:center;justify-content:space-between}
.lv-brand-left{display:flex;align-items:center;gap:8px}
.lv-brand-title{font-size:17px;font-weight:700;color:#0ea5e9}
body.light .lv-brand-title{color:#0284c7}
.lv-brand-icon{color:#0ea5e9;font-size:16px}
body.light .lv-brand-icon{color:#0284c7}

/* controls row */
.lv-sb-controls{display:flex;align-items:center;gap:6px;padding:8px 12px;border-bottom:1px solid #374151;flex-shrink:0}
body.light .lv-sb-controls{border-bottom-color:#e5e7eb}
.lv-sort-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:4px;
  padding:5px 8px;border-radius:6px;border:1px solid #374151;background:transparent;
  color:#9ca3af;font-size:11px;cursor:pointer;transition:all .12s;white-space:nowrap}
.lv-sort-btn:hover{border-color:#4b5563;color:#d1d5db;background:#374151}
.lv-sort-btn.active{border-color:#0ea5e9;color:#0ea5e9;background:rgba(14,165,233,.1)}
body.light .lv-sort-btn{border-color:#e5e7eb;color:#6b7280}
body.light .lv-sort-btn:hover{background:#f3f4f6;color:#374151}
body.light .lv-sort-btn.active{border-color:#0284c7;color:#0284c7;background:#e0f2fe}
.lv-theme-toggle{width:32px;height:32px;display:flex;align-items:center;justify-content:center;
  border-radius:6px;border:1px solid #374151;background:transparent;color:#9ca3af;cursor:pointer;transition:all .12s;flex-shrink:0}
.lv-theme-toggle:hover{border-color:#4b5563;background:#374151;color:#d1d5db}
body.light .lv-theme-toggle{border-color:#e5e7eb;color:#6b7280}
body.light .lv-theme-toggle:hover{background:#f3f4f6;color:#374151}

/* file list */
.lv-sb-scroll{flex:1;overflow-y:auto;padding:6px 0}

/* group */
.lv-group{margin:0 8px 2px}
.lv-group-hd{display:flex;align-items:center;gap:6px;padding:6px 8px;border-radius:6px;
  cursor:pointer;user-select:none;transition:background .1s;color:#9ca3af}
.lv-group-hd:hover{background:#374151;color:#d1d5db}
body.light .lv-group-hd{color:#6b7280}
body.light .lv-group-hd:hover{background:#f3f4f6;color:#374151}
.lv-group-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.lv-group-name{flex:1;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.7px}
.lv-group-badge{font-size:10px;padding:1px 5px;border-radius:8px;background:#374151;color:#6b7280}
body.light .lv-group-badge{background:#e5e7eb;color:#9ca3af}
.lv-chev{font-size:9px;transition:transform .15s}
.lv-group-hd.open .lv-chev{transform:rotate(90deg)}
.lv-group-files{display:none;padding:1px 4px 2px 4px}
.lv-group-hd.open+.lv-group-files{display:block}

/* file row */
.lv-file{display:flex;align-items:center;gap:6px;padding:7px 8px;border-radius:6px;
  cursor:pointer;border:1px solid transparent;transition:all .1s;color:#9ca3af;margin-bottom:1px}
.lv-file:hover{background:#374151;color:#d1d5db;border-color:#4b5563}
.lv-file.active{background:rgba(14,165,233,.15);border-color:#0ea5e9;color:#38bdf8}
body.light .lv-file{color:#6b7280}
body.light .lv-file:hover{background:#f9fafb;border-color:#d1d5db;color:#374151}
body.light .lv-file.active{background:#e0f2fe;border-color:#0284c7;color:#0284c7}
.lv-file-icon{font-size:12px;flex-shrink:0;color:#4b5563}
body.light .lv-file-icon{color:#9ca3af}
.lv-file.active .lv-file-icon{color:#0ea5e9}
body.light .lv-file.active .lv-file-icon{color:#0284c7}
.lv-file-name{flex:1;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  font-family:'JetBrains Mono','Courier New',monospace;font-weight:500}
.lv-file-meta{display:flex;flex-direction:column;align-items:flex-end;gap:1px;flex-shrink:0}
.lv-file-size{font-size:10px;color:#6b7280}
body.light .lv-file-size{color:#9ca3af}
.lv-file-actions{display:flex;gap:2px;opacity:0;transition:opacity .1s;flex-shrink:0}
.lv-file:hover .lv-file-actions{opacity:1}
.lv-file-btn{width:22px;height:22px;display:flex;align-items:center;justify-content:center;
  border-radius:4px;border:none;background:transparent;cursor:pointer;font-size:10px;
  color:#6b7280;transition:all .1s}
.lv-file-btn:hover{background:#4b5563;color:#d1d5db}
.lv-file-btn.del:hover{background:#7f1d1d;color:#fca5a5}
body.light .lv-file-btn:hover{background:#e5e7eb;color:#374151}
body.light .lv-file-btn.del:hover{background:#fee2e2;color:#dc2626}

/* ━━ MAIN ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.lv-main{flex:1;display:flex;flex-direction:column;overflow:hidden;background:#111827}
body.light .lv-main{background:#f3f4f6}

/* topbar */
.lv-topbar{display:flex;align-items:center;gap:8px;padding:10px 16px;
  background:#1f2937;border-bottom:1px solid #374151;flex-shrink:0}
body.light .lv-topbar{background:#fff;border-bottom-color:#e5e7eb}

/* search */
.lv-search{flex:1;display:flex;align-items:center;gap:0;border:1px solid #374151;
  border-radius:8px;background:#111827;overflow:hidden;transition:border-color .15s}
.lv-search:focus-within{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.15)}
body.light .lv-search{border-color:#d1d5db;background:#fff}
body.light .lv-search:focus-within{border-color:#0284c7;box-shadow:0 0 0 3px rgba(2,132,199,.12)}
.lv-search-icon{padding:0 10px;color:#4b5563;font-size:12px;flex-shrink:0}
body.light .lv-search-icon{color:#9ca3af}
#lv_search{flex:1;background:transparent;border:none;outline:none;padding:7px 0;
  font-size:12.5px;color:#d1d5db;font-family:'JetBrains Mono','Courier New',monospace}
#lv_search::placeholder{color:#4b5563;font-family:'Inter',sans-serif}
body.light #lv_search{color:#111827}
body.light #lv_search::placeholder{color:#9ca3af}
.lv-search-clear{padding:0 8px;color:#4b5563;cursor:pointer;font-size:11px;display:none;transition:color .1s}
.lv-search-clear:hover{color:#9ca3af}
.lv-search-clear.show{display:flex;align-items:center}
.lv-search-submit{padding:7px 14px;background:#0ea5e9;color:#fff;border:none;cursor:pointer;
  font-size:12px;font-weight:500;white-space:nowrap;transition:background .12s;flex-shrink:0}
.lv-search-submit:hover{background:#0284c7}

/* icon buttons */
.lv-icon-btn{width:34px;height:34px;display:flex;align-items:center;justify-content:center;
  border-radius:7px;border:1px solid #374151;background:transparent;color:#9ca3af;
  cursor:pointer;transition:all .12s;font-size:13px;flex-shrink:0}
.lv-icon-btn:hover{background:#374151;border-color:#4b5563;color:#d1d5db}
body.light .lv-icon-btn{border-color:#d1d5db;color:#6b7280}
body.light .lv-icon-btn:hover{background:#f3f4f6;border-color:#9ca3af;color:#374151}

/* ━━ LEVEL FILTER BAR ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.lv-levelbar{display:flex;align-items:center;gap:5px;padding:8px 16px;
  background:#1f2937;border-bottom:1px solid #374151;flex-shrink:0;flex-wrap:wrap}
body.light .lv-levelbar{background:#fff;border-bottom-color:#e5e7eb}
.lv-lvl{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;
  font-size:11.5px;font-weight:600;cursor:pointer;border:1.5px solid transparent;
  background:transparent;transition:all .12s;letter-spacing:.2px}
.lv-lvl-cnt{font-size:10px;font-weight:700;padding:0 5px;border-radius:8px;
  background:rgba(255,255,255,.1);min-width:18px;text-align:center}
/* ALL */
.lv-lvl.ALL     {border-color:#374151;color:#6b7280}
.lv-lvl.ALL.on  {background:#374151;border-color:#4b5563;color:#d1d5db}
body.light .lv-lvl.ALL    {border-color:#d1d5db;color:#6b7280}
body.light .lv-lvl.ALL.on {background:#f3f4f6;border-color:#9ca3af;color:#111827}
/* ERROR */
.lv-lvl.ERROR   {border-color:#7f1d1d;color:#f87171}
.lv-lvl.ERROR.on{background:#450a0a;border-color:#f87171}
body.light .lv-lvl.ERROR   {border-color:#fca5a5;color:#dc2626}
body.light .lv-lvl.ERROR.on{background:#fef2f2;border-color:#dc2626}
/* WARNING */
.lv-lvl.WARNING   {border-color:#78350f;color:#fbbf24}
.lv-lvl.WARNING.on{background:#451a03;border-color:#fbbf24}
body.light .lv-lvl.WARNING   {border-color:#fcd34d;color:#d97706}
body.light .lv-lvl.WARNING.on{background:#fffbeb;border-color:#d97706}
/* INFO */
.lv-lvl.INFO   {border-color:#0c4a6e;color:#38bdf8}
.lv-lvl.INFO.on{background:#082f49;border-color:#38bdf8}
body.light .lv-lvl.INFO   {border-color:#7dd3fc;color:#0284c7}
body.light .lv-lvl.INFO.on{background:#e0f2fe;border-color:#0284c7}
/* DEBUG */
.lv-lvl.DEBUG   {border-color:#374151;color:#9ca3af}
.lv-lvl.DEBUG.on{background:#1f2937;border-color:#6b7280}
body.light .lv-lvl.DEBUG   {border-color:#d1d5db;color:#6b7280}
body.light .lv-lvl.DEBUG.on{background:#f9fafb;border-color:#6b7280}
/* SUCCESS */
.lv-lvl.SUCCESS   {border-color:#14532d;color:#4ade80}
.lv-lvl.SUCCESS.on{background:#052e16;border-color:#4ade80}
body.light .lv-lvl.SUCCESS   {border-color:#86efac;color:#16a34a}
body.light .lv-lvl.SUCCESS.on{background:#f0fdf4;border-color:#16a34a}

.lv-file-label{margin-left:auto;font-size:10.5px;color:#4b5563;
  font-family:'JetBrains Mono','Courier New',monospace;white-space:nowrap;max-width:280px;overflow:hidden;text-overflow:ellipsis}
body.light .lv-file-label{color:#9ca3af}

/* ━━ CONTENT ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.lv-content{flex:1;overflow-y:auto;background:#111827}
body.light .lv-content{background:#f3f4f6}

/* empty */
.lv-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;
  height:100%;gap:12px;color:#4b5563}
.lv-empty i{font-size:42px;color:#1f2937}
body.light .lv-empty i{color:#e5e7eb}
.lv-empty p{font-size:13px;color:#6b7280}

/* loader */
.lv-loader{display:none;align-items:center;justify-content:center;gap:10px;padding:60px;color:#6b7280}
.lv-loader.show{display:flex}
@keyframes lv-spin{to{transform:rotate(360deg)}}
.lv-spinner{width:20px;height:20px;border:2px solid #374151;border-top-color:#0ea5e9;
  border-radius:50%;animation:lv-spin .6s linear infinite}

/* ━━ LOG TABLE ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.lv-tbl{width:100%;border-collapse:collapse}
.lv-thead{position:sticky;top:0;z-index:2;background:#1f2937;border-bottom:1px solid #374151}
body.light .lv-thead{background:#fff;border-bottom-color:#e5e7eb}
.lv-thead th{padding:6px 12px;font-size:10px;font-weight:600;color:#6b7280;
  text-transform:uppercase;letter-spacing:.8px;text-align:left;white-space:nowrap}
.lv-thead th.num{width:48px;text-align:right;padding-right:8px}
.lv-thead th.lvl{width:90px}
.lv-thead th.time{width:170px}

/* rows */
.lv-row{border-bottom:1px solid #1f2937;cursor:pointer;transition:background .07s}
body.light .lv-row{border-bottom-color:#f3f4f6}
/* level-tinted hover rows — opcodesio style */
.lv-row:hover,.lv-row.open{background:#1f2937}
body.light .lv-row:hover,body.light .lv-row.open{background:#f9fafb}
.lv-row.err:hover,.lv-row.err.open  {background:#1c0a0a}
.lv-row.warn:hover,.lv-row.warn.open{background:#1c1206}
.lv-row.info:hover,.lv-row.info.open{background:#071726}
.lv-row.dbg:hover,.lv-row.dbg.open  {background:#141920}
.lv-row.suc:hover,.lv-row.suc.open  {background:#071412}
body.light .lv-row.err:hover,body.light .lv-row.err.open  {background:#fff5f5}
body.light .lv-row.warn:hover,body.light .lv-row.warn.open{background:#fffdf0}
body.light .lv-row.info:hover,body.light .lv-row.info.open{background:#f0f9ff}
body.light .lv-row.dbg:hover,body.light .lv-row.dbg.open  {background:#fafafa}
body.light .lv-row.suc:hover,body.light .lv-row.suc.open  {background:#f0fdf4}

.lv-td{padding:8px 12px;vertical-align:top}
.lv-td.num{color:#374151;font-size:10.5px;text-align:right;padding-right:8px;
  font-family:'JetBrains Mono','Courier New',monospace;white-space:nowrap;vertical-align:middle}
body.light .lv-td.num{color:#d1d5db}

/* expand chevron */
.lv-row-chev{width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;
  border-radius:4px;transition:transform .15s,color .12s;color:#4b5563;font-size:9px;vertical-align:middle}
.lv-row.open .lv-row-chev{transform:rotate(90deg)}
.lv-row:hover .lv-row-chev{color:#9ca3af}

/* level badge */
.lv-badge{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:700;
  padding:2px 8px;border-radius:20px;letter-spacing:.3px;white-space:nowrap}
.lv-badge i{font-size:10px}
.lv-badge.ERROR  {background:#450a0a;color:#f87171;border:1px solid #7f1d1d}
.lv-badge.WARNING{background:#451a03;color:#fbbf24;border:1px solid #78350f}
.lv-badge.INFO   {background:#082f49;color:#38bdf8;border:1px solid #0c4a6e}
.lv-badge.DEBUG  {background:#1f2937;color:#9ca3af;border:1px solid #374151}
.lv-badge.SUCCESS{background:#052e16;color:#4ade80;border:1px solid #14532d}
body.light .lv-badge.ERROR  {background:#fef2f2;color:#dc2626;border-color:#fca5a5}
body.light .lv-badge.WARNING{background:#fffbeb;color:#d97706;border-color:#fcd34d}
body.light .lv-badge.INFO   {background:#e0f2fe;color:#0284c7;border-color:#7dd3fc}
body.light .lv-badge.DEBUG  {background:#f9fafb;color:#6b7280;border-color:#d1d5db}
body.light .lv-badge.SUCCESS{background:#f0fdf4;color:#16a34a;border-color:#86efac}

/* time */
.lv-td.time{color:#6b7280;font-size:11.5px;
  font-family:'JetBrains Mono','Courier New',monospace;white-space:nowrap;vertical-align:top;padding-top:10px}
body.light .lv-td.time{color:#9ca3af}

/* message */
.lv-msg{color:#d1d5db;font-size:12.5px;line-height:1.5;word-break:break-word;
  font-family:'JetBrains Mono','Courier New',monospace}
body.light .lv-msg{color:#1f2937}
.lv-meta{color:#6b7280;font-size:11px;margin-top:3px;font-family:'JetBrains Mono','Courier New',monospace}
body.light .lv-meta{color:#9ca3af}

/* expanded detail */
.lv-detail{display:none;margin:6px 0 8px 0;border-radius:6px;overflow:hidden;
  border:1px solid #374151;background:#0d1117}
body.light .lv-detail{border-color:#e5e7eb;background:#f8fafc}
.lv-row.open .lv-detail{display:block}
.lv-detail-pre{padding:12px 14px;font-size:11.5px;color:#c9d1d9;
  white-space:pre-wrap;word-break:break-all;line-height:1.65;
  font-family:'JetBrains Mono','Courier New',monospace;max-height:320px;overflow-y:auto}
body.light .lv-detail-pre{color:#374151}

/* copy btn */
.lv-copy-btn{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:5px;
  border:1px solid #374151;background:transparent;color:#4b5563;font-size:10.5px;
  cursor:pointer;transition:all .12s;white-space:nowrap;vertical-align:middle}
.lv-copy-btn:hover{border-color:#0ea5e9;color:#38bdf8;background:rgba(14,165,233,.08)}
body.light .lv-copy-btn{border-color:#e5e7eb;color:#9ca3af}
body.light .lv-copy-btn:hover{border-color:#0284c7;color:#0284c7;background:#e0f2fe}

/* ━━ FOOTER / PAGINATION ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.lv-footer{display:flex;align-items:center;justify-content:space-between;padding:8px 16px;
  background:#1f2937;border-top:1px solid #374151;flex-shrink:0}
body.light .lv-footer{background:#fff;border-top-color:#e5e7eb}
.lv-finfo{font-size:11px;color:#6b7280}
body.light .lv-finfo{color:#9ca3af}
.lv-pag{display:flex;gap:3px;align-items:center}
.lv-pag a,.lv-pag span.pg{display:inline-flex;align-items:center;justify-content:center;
  min-width:28px;height:28px;padding:0 6px;border-radius:6px;border:1px solid #374151;
  color:#9ca3af;font-size:12px;background:transparent;text-decoration:none;transition:all .12s;cursor:pointer}
.lv-pag a:hover{background:#374151;border-color:#4b5563;color:#d1d5db}
.lv-pag a.on{background:#0ea5e9;border-color:#0ea5e9;color:#fff;font-weight:600}
body.light .lv-pag a,body.light .lv-pag span.pg{border-color:#e5e7eb;color:#6b7280;background:#fff}
body.light .lv-pag a:hover{background:#f3f4f6;border-color:#d1d5db;color:#374151}
body.light .lv-pag a.on{background:#0284c7;border-color:#0284c7;color:#fff}
.lv-pag .dots{border:none;background:transparent;color:#4b5563;cursor:default;pointer-events:none}
body.light .lv-pag .dots{color:#9ca3af}

/* per-page select */
.lv-per-page{padding:4px 8px;border-radius:6px;border:1px solid #374151;background:#1f2937;
  color:#9ca3af;font-size:11.5px;outline:none;cursor:pointer}
body.light .lv-per-page{border-color:#e5e7eb;background:#fff;color:#6b7280}
</style>

<?php
$ci        = get_instance();
$csrf_name = $ci->security->get_csrf_token_name();
$csrf_hash = $ci->security->get_csrf_hash();

$sidebar = [];
foreach ($groups as $gkey => $g) {
    $files = [];
    foreach ($g['files'] as $f) {
        $files[] = ['key' => $f['key'], 'name' => $f['name'], 'size' => $f['size']];
    }
    $sidebar[] = ['key' => $gkey, 'label' => $g['label'], 'color' => $g['color'], 'files' => $files];
}

$FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES;
$cfg = json_encode([
    'loadUrl'   => base_url('system-logs/load-file'),
    'delUrl'    => base_url('system-logs/delete-file'),
    'canDelete' => (bool) get_permission('system_log', 'is_delete'),
    'csrf'      => ['name' => $csrf_name, 'hash' => $csrf_hash],
    'groups'    => $sidebar,
], $FLAGS);
?>

<div class="lv" id="lv_root">

  <!-- ━━ SIDEBAR ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
  <aside class="lv-sb">
    <div class="lv-sb-header">
      <a class="lv-back" href="<?= base_url(); ?>">
        <i class="fas fa-arrow-left" style="font-size:10px"></i> Back to system
      </a>
      <div class="lv-brand">
        <div class="lv-brand-left">
          <i class="fas fa-terminal lv-brand-icon"></i>
          <span class="lv-brand-title">Log Viewer</span>
        </div>
      </div>
    </div>

    <!-- sort + theme -->
    <div class="lv-sb-controls">
      <button class="lv-sort-btn active" id="lv_s_new" onclick="setSort('newest')">
        <i class="fas fa-arrow-down-short-wide" style="font-size:10px"></i> Newest first
      </button>
      <button class="lv-sort-btn" id="lv_s_old" onclick="setSort('oldest')">
        <i class="fas fa-arrow-up-short-wide" style="font-size:10px"></i> Oldest first
      </button>
      <button class="lv-theme-toggle" onclick="toggleTheme()" title="Toggle theme">
        <i class="fas fa-sun" id="lv_theme_i"></i>
      </button>
    </div>

    <div class="lv-sb-scroll" id="lv_sidebar"></div>
  </aside>

  <!-- ━━ MAIN ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
  <main class="lv-main">

    <!-- search bar -->
    <div class="lv-topbar">
      <div class="lv-search">
        <span class="lv-search-icon" id="lv_search_icon"><i class="fas fa-search"></i></span>
        <input id="lv_search" type="text" placeholder="Search logs…" autocomplete="off">
        <span class="lv-search-clear" id="lv_clear_btn" onclick="clearSearch()">
          <i class="fas fa-times"></i>
        </span>
        <button class="lv-search-submit" onclick="doSearch()">Search</button>
      </div>
      <button class="lv-icon-btn" onclick="lvReload()" title="Reload"><i class="fas fa-rotate-right"></i></button>
    </div>

    <!-- level filter bar -->
    <div class="lv-levelbar" id="lv_levelbar" style="display:none">
      <button class="lv-lvl ALL on" id="lb_all"     onclick="setLevel('')">ALL <span class="lv-lvl-cnt" id="lc_all">0</span></button>
      <button class="lv-lvl ERROR"  id="lb_error"   onclick="setLevel('ERROR')"><i class="fas fa-circle-xmark"></i> ERROR <span class="lv-lvl-cnt" id="lc_error">0</span></button>
      <button class="lv-lvl WARNING"id="lb_warning" onclick="setLevel('WARNING')"><i class="fas fa-triangle-exclamation"></i> WARN <span class="lv-lvl-cnt" id="lc_warn">0</span></button>
      <button class="lv-lvl INFO"   id="lb_info"    onclick="setLevel('INFO')"><i class="fas fa-circle-info"></i> INFO <span class="lv-lvl-cnt" id="lc_info">0</span></button>
      <button class="lv-lvl DEBUG"  id="lb_debug"   onclick="setLevel('DEBUG')"><i class="fas fa-bug"></i> DEBUG <span class="lv-lvl-cnt" id="lc_debug">0</span></button>
      <button class="lv-lvl SUCCESS"id="lb_success" onclick="setLevel('SUCCESS')" style="display:none"><i class="fas fa-circle-check"></i> SUCCESS <span class="lv-lvl-cnt" id="lc_success">0</span></button>
      <span class="lv-file-label" id="lv_flabel"></span>
    </div>

    <!-- log entries -->
    <div class="lv-content" id="lv_content">
      <div class="lv-empty" id="lv_empty">
        <i class="fas fa-rectangle-list"></i>
        <p>Select a log file from the sidebar to get started</p>
      </div>
      <div class="lv-loader" id="lv_loader"><div class="lv-spinner"></div> Loading…</div>
      <table class="lv-tbl" id="lv_tbl" style="display:none">
        <thead class="lv-thead">
          <tr>
            <th class="num">#</th>
            <th style="width:28px"></th>
            <th class="lvl">Level</th>
            <th class="time">Datetime</th>
            <th>Message</th>
            <th style="width:80px;text-align:right;padding-right:16px">Copy</th>
          </tr>
        </thead>
        <tbody id="lv_tbody"></tbody>
      </table>
    </div>

    <!-- footer -->
    <div class="lv-footer" id="lv_footer" style="display:none">
      <span class="lv-finfo" id="lv_finfo"></span>
      <div style="display:flex;align-items:center;gap:8px">
        <select class="lv-per-page" id="lv_per_page" onchange="changePerPage()">
          <option value="50">50 / page</option>
          <option value="100" selected>100 / page</option>
          <option value="200">200 / page</option>
          <option value="500">500 / page</option>
        </select>
        <div class="lv-pag" id="lv_pag"></div>
      </div>
    </div>

  </main>
</div>

<script>
var CFG = <?= $cfg; ?>;
var LV  = { file:null, level:'', search:'', page:1, sort:'newest', perPage:100 };

/* ━━ Theme ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
(function(){ _applyTheme(localStorage.getItem('lv_theme')||'dark'); })();
function toggleTheme(){
    var m = document.body.classList.contains('light')?'dark':'light';
    _applyTheme(m); localStorage.setItem('lv_theme',m);
}
function _applyTheme(m){
    document.body.classList.toggle('light', m==='light');
    var i = _e('lv_theme_i');
    if(i) i.className = m==='light'?'fas fa-moon':'fas fa-sun';
}

/* ━━ Sort ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function setSort(s){
    LV.sort=s; LV.page=1;
    _e('lv_s_new').classList.toggle('active',s==='newest');
    _e('lv_s_old').classList.toggle('active',s==='oldest');
    _buildSidebar();
    if(LV.file) _fetch();
}

/* ━━ Sidebar ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function _buildSidebar(){
    var html='';
    CFG.groups.forEach(function(g){
        var files=g.files.slice().sort(function(a,b){
            return LV.sort==='newest'?b.name.localeCompare(a.name):a.name.localeCompare(b.name);
        });
        html+='<div class="lv-group"><div class="lv-group-hd open" data-role="grp-hd">'
            +'<i class="fas fa-folder lv-chev" style="font-size:11px;color:'+_ea(g.color)+'"></i>'
            +'<span class="lv-group-name">'+_esc(g.label)+'</span>'
            +'<span class="lv-group-badge">'+files.length+'</span>'
            +'<i class="fas fa-chevron-right lv-chev" style="margin-left:auto"></i>'
            +'</div><div class="lv-group-files">';
        files.forEach(function(f){
            var act=LV.file===f.key?' active':'';
            html+='<div class="lv-file'+act+'" data-key="'+_ea(f.key)+'" data-role="file">'
                +'<i class="fas fa-file-lines lv-file-icon"></i>'
                +'<span class="lv-file-name">'+_esc(f.name)+'</span>'
                +'<div class="lv-file-meta"><span class="lv-file-size">'+_esc(f.size)+'</span></div>'
                +'<div class="lv-file-actions">'
                +'<button class="lv-file-btn" data-key="'+_ea(f.key)+'" data-role="ext" title="Open in new tab"><i class="fas fa-arrow-up-right-from-square"></i></button>'
                +(CFG.canDelete?'<button class="lv-file-btn del" data-key="'+_ea(f.key)+'" data-role="del" title="Delete"><i class="fas fa-trash-can"></i></button>':'')
                +'</div></div>';
        });
        html+='</div></div>';
    });
    _e('lv_sidebar').innerHTML=html;
}

/* sidebar events */
_e('lv_sidebar').addEventListener('click',function(e){
    var grpHd=e.target.closest('[data-role="grp-hd"]');
    if(grpHd){grpHd.classList.toggle('open');return;}
    var btn=e.target.closest('[data-role]');
    if(!btn) return;
    e.stopPropagation();
    var role=btn.dataset.role, key=btn.dataset.key;
    if(role==='ext'){window.open(CFG.loadUrl+'?file='+encodeURIComponent(key),'_blank');return;}
    if(role==='del'){delFile(key,btn);return;}
    if(role==='file'){loadFile(key);return;}
});

/* ━━ Load file ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function loadFile(key,page){
    LV.file=key; LV.page=page||1; LV.search=''; LV.level='';
    _e('lv_search').value='';
    _e('lv_clear_btn').classList.remove('show');
    _resetLvl(); _buildSidebar(); _fetch();
}
function lvReload(){ if(LV.file) _fetch(); }

/* ━━ Level filter ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
var _LVL_IDS={''  :'lb_all','ERROR':'lb_error','WARNING':'lb_warning',
              'INFO':'lb_info','DEBUG':'lb_debug','SUCCESS':'lb_success'};
function setLevel(lv){
    LV.level=lv; LV.page=1; _resetLvl();
    var el=_e(_LVL_IDS[lv]||'lb_all'); if(el) el.classList.add('on');
    _fetch();
}
function _resetLvl(){
    Object.values(_LVL_IDS).forEach(function(id){ var el=_e(id); if(el) el.classList.remove('on'); });
}

/* ━━ Search ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function doSearch(){
    if(!LV.file) return;
    LV.search=_e('lv_search').value.trim();
    LV.page=1; _fetch();
}
function clearSearch(){
    LV.search=''; _e('lv_search').value='';
    _e('lv_clear_btn').classList.remove('show');
    if(LV.file){LV.page=1;_fetch();}
}
_e('lv_search').addEventListener('input',function(){
    _e('lv_clear_btn').classList.toggle('show', this.value.length>0);
});
_e('lv_search').addEventListener('keydown',function(e){ if(e.key==='Enter') doSearch(); });

/* ━━ Per-page ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function changePerPage(){
    LV.perPage=parseInt(_e('lv_per_page').value)||100;
    LV.page=1; if(LV.file) _fetch();
}

/* ━━ Fetch ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function _fetch(){
    if(!LV.file) return;
    var url=CFG.loadUrl
        +'?file='+encodeURIComponent(LV.file)
        +'&level='+encodeURIComponent(LV.level)
        +'&search='+encodeURIComponent(LV.search)
        +'&page='+LV.page
        +'&sort='+LV.sort
        +'&per_page='+LV.perPage;

    _e('lv_empty').style.display='none';
    _e('lv_loader').classList.add('show');
    _e('lv_tbl').style.display='none';
    _e('lv_levelbar').style.display='none';
    _e('lv_footer').style.display='none';

    fetch(url,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(function(d){ _render(d); })
        .catch(function(err){ _renderErr(err.message); });
}

/* ━━ Render ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
var _ROW_CLS={ERROR:'err',WARNING:'warn',INFO:'info',DEBUG:'dbg',SUCCESS:'suc'};
var _BADGE_ICON={ERROR:'fas fa-circle-xmark',WARNING:'fas fa-triangle-exclamation',
                 INFO:'fas fa-circle-info',DEBUG:'fas fa-bug',SUCCESS:'fas fa-circle-check'};

function _render(d){
    _e('lv_loader').classList.remove('show');
    if(d.error){_renderErr(d.error);return;}

    var lc=d.level_counts||{};
    var tot=(lc.ERROR||0)+(lc.WARNING||0)+(lc.INFO||0)+(lc.DEBUG||0)+(lc.SUCCESS||0);
    _t('lc_all',tot); _t('lc_error',lc.ERROR||0); _t('lc_warn',lc.WARNING||0);
    _t('lc_info',lc.INFO||0); _t('lc_debug',lc.DEBUG||0); _t('lc_success',lc.SUCCESS||0);
    var sb=_e('lb_success'); if(sb) sb.style.display=(lc.SUCCESS>0)?'':'none';
    _t('lv_flabel', LV.file+' · '+d.total.toLocaleString()+' entries');
    _e('lv_levelbar').style.display='flex';

    var offset=(d.page-1)*d.per_page;
    var html='';
    if(d.entries && d.entries.length){
        d.entries.forEach(function(e,i){
            var lv=(e.level||'DEBUG').toUpperCase();
            var rc=_ROW_CLS[lv]||'dbg';
            var bi=_BADGE_ICON[lv]||'fas fa-circle';
            var msg=e.message||'', meta=e.meta||'', time=e.time||'';
            var isLong=msg.length>200||meta.length>0;
            var preview=msg.length>200?msg.substring(0,200)+'…':msg;
            var full=meta?msg+'\n\n'+meta:msg;
            var idx=offset+i+1;

            html+='<tr class="lv-row '+rc+'" onclick="toggleRow(this)">';
            html+='<td class="lv-td num"><span class="lv-row-chev"><i class="fas fa-chevron-right"></i></span></td>';
            html+='<td class="lv-td num" style="color:#374151">'+idx+'</td>';
            html+='<td class="lv-td" style="vertical-align:middle"><span class="lv-badge '+lv+'"><i class="'+bi+'"></i> '+lv+'</span></td>';
            html+='<td class="lv-td time">'+_esc(time)+'</td>';
            html+='<td class="lv-td">'
                +'<div class="lv-msg">'+_esc(preview)+'</div>'
                +(meta?'<div class="lv-meta">'+_esc(meta)+'</div>':'')
                +(isLong?'<div class="lv-detail"><div class="lv-detail-pre">'+_esc(full)+'</div></div>':'')
                +'</td>';
            html+='<td class="lv-td" style="text-align:right;vertical-align:middle;padding-right:12px">'
                +'<button class="lv-copy-btn" onclick="copyEntry(event,'+idx+','+JSON.stringify(full)+')"><i class="fas fa-link"></i> #'+idx+'</button>'
                +'</td>';
            html+='</tr>';
        });
    } else {
        html='<tr><td colspan="6" style="padding:60px;text-align:center;color:#4b5563">'
            +'<i class="fas fa-circle-check" style="color:#4ade80;font-size:28px;display:block;margin-bottom:10px"></i>'
            +'No entries match the current filter.</td></tr>';
    }
    _e('lv_tbody').innerHTML=html;
    _e('lv_tbl').style.display='';
    _renderPag(d);
}

function _renderPag(d){
    var footer=_e('lv_footer');
    if(d.total===0){footer.style.display='none';return;}
    footer.style.display='flex';
    var from=(d.page-1)*d.per_page+1, to=Math.min(d.page*d.per_page,d.total);
    _t('lv_finfo','Showing '+from.toLocaleString()+' – '+to.toLocaleString()+' of '+d.total.toLocaleString()+' entries');
    if(d.total_pages<=1){_e('lv_pag').innerHTML='';return;}
    var cur=d.page,tp=d.total_pages,h='';
    if(cur>1) h+='<a onclick="goPage('+(cur-1)+')">‹</a>';
    var s=Math.max(1,cur-2),e2=Math.min(tp,cur+2);
    if(s>1){h+='<a onclick="goPage(1)">1</a>';if(s>2)h+='<a class="dots">…</a>';}
    for(var p=s;p<=e2;p++) h+='<a class="'+(p===cur?'on':'')+'" onclick="goPage('+p+')">'+p+'</a>';
    if(e2<tp){if(e2<tp-1)h+='<a class="dots">…</a>';h+='<a onclick="goPage('+tp+')">'+tp+'</a>';}
    if(cur<tp) h+='<a onclick="goPage('+(cur+1)+')">›</a>';
    _e('lv_pag').innerHTML=h;
}

function goPage(p){LV.page=p;_fetch();_e('lv_content').scrollTop=0;}
function toggleRow(tr){tr.classList.toggle('open');}

/* copy entry */
function copyEntry(e,idx,text){
    e.stopPropagation();
    var btn=e.currentTarget;
    navigator.clipboard.writeText('#'+idx+'\n'+text).then(function(){
        btn.innerHTML='<i class="fas fa-check"></i> Copied!';
        setTimeout(function(){btn.innerHTML='<i class="fas fa-link"></i> #'+idx;},1200);
    });
}

/* ━━ Delete ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function delFile(key,btn){
    if(!confirm('Delete "'+key+'"?\n\nThis cannot be undone.')) return;
    var body=CFG.csrf.name+'='+encodeURIComponent(CFG.csrf.hash)+'&file='+encodeURIComponent(key);
    fetch(CFG.delUrl,{method:'POST',credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:body})
    .then(function(r){return r.json();})
    .then(function(res){
        if(res.success){
            CFG.groups.forEach(function(g){ g.files=g.files.filter(function(f){return f.key!==key;}); });
            _buildSidebar();
            if(LV.file===key){
                LV.file=null;
                _e('lv_tbody').innerHTML='';
                _e('lv_tbl').style.display='none';
                _e('lv_levelbar').style.display='none';
                _e('lv_footer').style.display='none';
                _e('lv_empty').style.display='flex';
            }
        } else {alert(res.error||'Delete failed');}
    }).catch(function(){alert('Network error');});
}

/* ━━ Error render ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function _renderErr(msg){
    _e('lv_loader').classList.remove('show');
    _e('lv_levelbar').style.display='flex';
    _e('lv_tbody').innerHTML='<tr><td colspan="6" style="padding:60px;text-align:center;color:#f87171">'
        +'<i class="fas fa-triangle-exclamation" style="font-size:28px;display:block;margin-bottom:10px"></i>'
        +_esc(msg||'Error loading file')+'</td></tr>';
    _e('lv_tbl').style.display='';
    _t('lv_flabel',LV.file||'');
}

/* ━━ Helpers ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function _e(id){return document.getElementById(id);}
function _t(id,v){var el=_e(id);if(el)el.textContent=v;}
function _ea(s){return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function _esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* ━━ Init ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
_buildSidebar();
(function(){ if(CFG.groups.length&&CFG.groups[0].files.length) loadFile(CFG.groups[0].files[0].key); })();
</script>
