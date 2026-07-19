# End-to-end test suite

These specs drive the real running app via Chromium (Playwright). They mirror
the live smoke probes the PHP test suite already performs, but cross-verify
from a browser context — covering CSP delivery, CSRF meta injection,
viewport, form a11y, and JSON contract of the API auth surface.

## Quickstart

```sh
# One-time install
npm i -D @playwright/test
npx playwright install --with-deps chromium

# Run against the bundled PHP dev server (Playwright will start it for you)
npx playwright test

# Or against a remote env
E2E_BASE_URL=https://staging.example.com npx playwright test
```

## What's covered

| Spec | Asserts |
|------|---------|
| `smoke.spec.ts` → public surface | login page renders, all 9 security headers present, CSRF meta tag injected |
| `smoke.spec.ts` → health | `/health` 200 ok JSON; `/health/ready` per-check structure; `/health/details` 401 |
| `smoke.spec.ts` → API auth | login/refresh/me contract, validation codes, `alg:none` JWT downgrade rejected |
| `smoke.spec.ts` → a11y | label-for binding, password toggle is `<button>` w/ aria-label, viewport meta |

## CI

The provided `.github/workflows/tests.yml` runs PHPUnit on a matrix of PHP
versions but does **not** invoke Playwright. To wire it in, add:

```yaml
- name: Install Playwright
  run: |
    npm i -D @playwright/test
    npx playwright install --with-deps chromium

- name: Start dev server
  run: php -S 127.0.0.1:8765 -t . smoke_router.php &

- name: Wait for health
  run: until curl -fsS http://127.0.0.1:8765/health; do sleep 1; done

- name: Run e2e
  run: npx playwright test
```
