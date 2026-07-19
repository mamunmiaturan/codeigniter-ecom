# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Audit pass across routing, tests, static analysis and security. See `AUDIT_REPORT.md`
for full findings, evidence and the outstanding-issue list.

### Security

- **HIGH — Destructive actions are no longer reachable by GET.** All 31 mutating
  endpoints (28 `*/delete/*`, `activity-logs/clear`, `email-logs/clear`,
  `catalog_rule/reindex`) are now POST + CSRF. Previously a crawler, link
  prefetcher or `<img src>` could delete records or wipe logs — this was not
  theoretical: a read-only GET sweep during the audit deleted the email logs twice.
  - `confirm_modal()` (the single funnel behind every `btn_delete()` button) now
    POSTs with a CSRF token instead of issuing a bare `$.ajax` GET.
  - Added `window.CsrfToken`, a global token store in `layout/index.php`. Needed
    because `csrf_regenerate` is ON: the token rendered at page load is stale the
    moment anything posts (the DataTables handlers post on load). It refreshes from
    any JSON response carrying `csrfHash`. The CSRF cookie is HttpOnly, so JS
    cannot read it directly.
  - `catalog_rule/reindex` and the two log-`clear` endpoints are now POST forms.
    The `clear` endpoints previously had **no UI at all** and were URL-only, so
    Clear buttons were added to the activity/email log views, gated on the same
    permission each controller enforces.
- **Route guard: a GET can no longer reach a non-GET endpoint.** Converting the
  routes alone was **not** sufficient — CI3's router silently skips a route whose
  verb does not match and falls through to controller/method auto-routing, so
  `GET /faq/delete/{id}` still deleted, and `GET /activitylog/clear` still wiped the
  log via its auto-routed alias (the canonical URI is `activity-logs/clear`).
  `MY_Controller::_enforce_route_guard()` now returns 405 when a GET resolves to a
  controller/method that is declared without a GET route, whichever URI reached it.
  Scoped deliberately to GET only: methods legitimately serve verbs no single route
  declares — the login form POSTs to the auto-routed `/authentication` while
  `Authentication@index` is declared GET-only via the `login` route.
- **CRITICAL — Closed an unauthenticated payment bypass.** `Payment::mock_pay()` marked any
  order `paid` with no authentication and no verification, releasing digital downloads via
  `grant_for_order()`. The gateway's `is_active` flag did not gate it (only the checkout
  selector reads that flag, and a `payment_settings` DB row overrides the config default).
  `mock`, `mock_pay` and `mock_cancel` now fail closed unless the mock gateway is active
  **and** `ENVIRONMENT !== 'production'`.
- Deactivated the `mock` payment gateway by default in `config/payment_methods.php`
  (defence in depth — the endpoint guard above is the control that actually closes the hole).
- Restored `can_impersonate_users()` and `can_impersonate_target()`, the authorisation
  guards for "Login as user". Both were called but never defined, so the feature fatalled
  on every request; the impersonation policy they encode was consequently unenforceable.

### Fixed

- **Health checks were entirely broken.** `/health`, `/health/ready` and `/health/details`
  all returned HTTP 500 (`Call to undefined method Health::jsonResponse()`) — `Health`
  extends `CI_Controller`, but `jsonResponse()` lives on `MY_Controller`. `_emit()` now
  writes JSON directly, keeping probes free of session/CSRF overhead.
- **SMS sending and credential emails fatalled** on `messaging_branch_id_for_user()`, an
  undefined function inherited from an upstream branch-aware app. Removed the three call
  sites; email/SMS now resolve the global gateway (this build has no branch concept and no
  `users.branch_id` column).
- Restored `ajax_access_denied()`, called by `Language::add_phrase`/`import_phrase` but
  never defined — permission denial fatalled instead of returning a JSON 403.
- Restored `status_badge()`, called by the email-log view but never defined (another
  helper lost in the port). The page only appeared to work because an empty log meant
  the row loop never ran; with any log entry present it fatalled.
- The backup delete button pointed at `backup/delete_file/{file}`, but `Backup` has no
  `delete_file()` method and no such route exists — the button was dead. Now `backup/delete`.
- `/coupon` and `/coupon/create` returned HTTP 500: the controller rendered `coupon/index`
  but the views live under `catalog/coupon/`, unlike every sibling catalog controller.
- `/sms/send-sms` returned HTTP 500: `Sms_model::get_active_users()` filtered `users.status`,
  a column that lives on `login_credential`. Now joins, matching `App_model`/`User_model`.
- **The custom 404 page never rendered** — unknown URLs redirected to `/login`. CI3's 404
  override rewrites `$URI->rsegments` but leaves `$RTR->class` stale, so `MY_Controller`'s
  fail-closed route guard rejected its own 404 handler. The guard now resolves the
  class/method from `rsegments`, i.e. what CI actually dispatches.
- Fixed 7 broken route targets — all 361 now resolve (was 360/367):
  - Removed `api/v1/users`, `api/v1/user/{id}`, `api/v1/user/create` (`api/User` never existed).
  - Removed `login/authenticate` (no such method; the form posts to `/authentication`).
  - Removed `system-logs/clear` (no such method; `Systemlog` uses granular `delete_file()`).
  - Removed the `dbtool` web route (CLI-only controller that rejects all HTTP requests).
  - Repointed `user/view/{id}` → `User@profile`, the method that exists and the URL the UI links to.
- Sidebar highlighting: `sidebar_helper.php` still referenced pre-reorg view paths, so the
  entire Settings menu and the Email/SMS log entries never showed as active.
- Regenerated Composer's autoload classmap, which still mapped 8 deleted classes to
  missing files (a latent fatal on autoload).

### Changed

- `composer.lock` synced with `composer.json` — `phpstan/phpstan` was declared but absent
  from the lock, so `composer install` never installed it and CI's PHPStan job could not run.

### Added

- `phpunit.xml` — CI runs `vendor/bin/phpunit` with no path argument; with no config this
  printed usage and exited 2, so the test job failed while running zero tests. The suite
  (207 tests) now runs and passes on CI's exact invocation.
- `phpstan.neon` + `phpstan-baseline.neon` — CI runs `vendor/bin/phpstan analyse` with no
  path/config. Configured for level 5 with CodeIgniter's magic-loader idiom ignored
  (`CI_Controller` is `#[AllowDynamicProperties]`), reducing 5,235 reported errors to 26
  genuine ones, which are baselined so CI fails only on new problems.
- `AUDIT_REPORT.md` — full audit findings, evidence, and outstanding issues.

### Removed

- 30 orphaned files (~4,900 lines), each independently verified as zero-reference:
  - 18 duplicate views left behind by an unfinished reorg into `settings/`/`audit/`,
    emptying `views/{activity,backup,system,language,module,role,email,sms}/`.
  - 4 orphaned views (`settings/index.php` — which could never have rendered, as it
    redeclares `render_swatches()` from the autoloaded `theme_helper`; `profile/index.php`;
    `settings/_theme_presets_form.php`; `errors/error_404_message.php`).
  - 7 unused libraries (`Routerosapi`, `Vault`, `Media`, `Image_uploader`, `Notify`,
    `Datatables`, `DatatablesBuilder`).
  - `Profile_model` (unused — `Profile` uses `user_model`).

### Tests

- All 207 tests pass (was: 2 failures). **Both failures were test bugs, not app bugs:**
  - `JwtTest::test_rejects_tampered_signature` mutated the signature via `strtr('A','B')`;
    signatures are random per run, so a signature with no `A` was left **valid** and the
    test failed intermittently. The JWT library was verified correct — it rejects genuinely
    tampered signatures. The test now tampers deterministically.
  - `DbIntegrationTest::testQueryBuilderDelete` asserted via `num_rows()`; PDO's `rowCount()`
    reports the last DML statement's count for SQLite SELECTs, so it returned the DELETE's
    affected rows. The delete worked. Now asserts on the result set. (Production uses
    `mysqli`, where `num_rows()` is accurate.)

### Known issues

Reproduced but not fixed — see `AUDIT_REPORT.md` §3:

- **Queue dashboard is broken**: `/queuedashboard` returns 500 —
  `Table 'failed_jobs' doesn't exist`. A migration for it exists
  (`2026_05_23_000031_create_failed_jobs_table.php`) but has never run: this database
  was bootstrapped from a SQL dump, so the migration version is still 0. Pre-existing;
  verified identical on unmodified code. Fix by running the migration.
- **`Backup::restore_file()` permits `INSERT INTO` any table**, allowing a
  `database_restore`-only user to insert a `role=1` superadmin credential.
- `uploads/downloads/` is web-served despite a docblock claiming `.htaccess` protection;
  `svg` in the upload allowlist enables stored XSS when fetched directly.
- Login lockout is per-IP only; rotating IPs resets the counter for a target account.
- `SECURITY_KEY` falls back to a hardcoded literal instead of failing closed.
- 2FA backup codes are reversibly encrypted rather than hashed.
- `application/cache/` is tracked in git but is pure runtime state.
