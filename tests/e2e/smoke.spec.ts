import { expect, test } from '@playwright/test';

/**
 * Browser-driven smoke suite. Mirrors the PHP smoke probes in
 * tests/SmokeProbesTest.php so the same contract is verified at two layers.
 */
test.describe('public surface', () => {
  test('login page renders and exposes security headers', async ({ page, request }) => {
    const res = await request.get('/authentication');
    expect(res.status()).toBe(200);
    const headers = res.headers();
    expect(headers['content-security-policy']).toMatch(/default-src 'self'/);
    expect(headers['strict-transport-security']).toMatch(/max-age=/);
    expect(headers['x-frame-options']).toBe('SAMEORIGIN');
    expect(headers['x-content-type-options']).toBe('nosniff');
    expect(headers['cross-origin-opener-policy']).toBe('same-origin');

    await page.goto('/authentication');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    // CSRF meta tag auto-injected by MY_Controller::_output().
    await expect(page.locator('meta[name="csrf-token"]')).toHaveCount(1);
  });

  test('liveness endpoint returns ok JSON', async ({ request }) => {
    const res = await request.get('/health');
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.status).toBe('ok');
    expect(body).toHaveProperty('time');
  });

  test('readiness endpoint reports per-check status', async ({ request }) => {
    const res = await request.get('/health/ready');
    expect([200, 503]).toContain(res.status());
    const body = await res.json();
    expect(body).toHaveProperty('checks.database.ok');
    expect(body).toHaveProperty('checks.cache_dir.ok');
  });

  test('health/details requires token', async ({ request }) => {
    const res = await request.get('/health/details');
    expect(res.status()).toBe(401);
  });
});

test.describe('API auth surface', () => {
  test('login requires email and password', async ({ request }) => {
    const res = await request.post('/api/v1/auth/login', {
      data: { email: '', password: '' },
    });
    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.status).toBe('error');
    expect(body.code).toBe(422);
  });

  test('login rejects bad email format', async ({ request }) => {
    const res = await request.post('/api/v1/auth/login', {
      data: { email: 'not-an-email', password: 'x' },
    });
    expect(res.status()).toBe(422);
    expect((await res.json()).message).toMatch(/invalid email/i);
  });

  test('login returns 401 for unknown user', async ({ request }) => {
    const res = await request.post('/api/v1/auth/login', {
      data: { email: 'nobody@example.com', password: 'WrongPass1!' },
    });
    expect(res.status()).toBe(401);
  });

  test('me requires bearer token', async ({ request }) => {
    const res = await request.get('/api/v1/auth/me');
    expect(res.status()).toBe(401);
    expect((await res.json()).message).toMatch(/missing bearer/i);
  });

  test('refresh rejects empty token', async ({ request }) => {
    const res = await request.post('/api/v1/auth/refresh', {
      data: { refresh_token: '' },
    });
    expect(res.status()).toBe(422);
  });

  test('refresh rejects invalid token', async ({ request }) => {
    const res = await request.post('/api/v1/auth/refresh', {
      data: { refresh_token: 'garbage_invalid_xxxxxxxxxxxxx' },
    });
    expect(res.status()).toBe(401);
  });

  test('JWT rejects alg=none downgrade', async ({ request }) => {
    // Hand-rolled none-alg JWT with a fake admin payload.
    const header = Buffer.from('{"alg":"none","typ":"JWT"}').toString('base64url');
    const payload = Buffer.from(JSON.stringify({
      sub: 1, role: 1, scope: '*', exp: Math.floor(Date.now() / 1000) + 600,
    })).toString('base64url');
    const token = `${header}.${payload}.`;
    const res = await request.get('/api/v1/auth/me', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(401);
  });
});

test.describe('accessibility — login page', () => {
  test('all inputs have associated labels', async ({ page }) => {
    await page.goto('/authentication');
    const emailLabel = page.locator('label[for="email"]');
    const passwordLabel = page.locator('label[for="password"]');
    await expect(emailLabel).toHaveCount(1);
    await expect(passwordLabel).toHaveCount(1);
  });

  test('password toggle is a real button with aria-label', async ({ page }) => {
    await page.goto('/authentication');
    const toggle = page.locator('#togglePassword');
    await expect(toggle).toBeVisible();
    expect(await toggle.evaluate(el => el.tagName)).toBe('BUTTON');
    expect(await toggle.getAttribute('aria-label')).toBeTruthy();
  });

  test('viewport meta tag present', async ({ page }) => {
    await page.goto('/authentication');
    const vp = page.locator('meta[name="viewport"]');
    await expect(vp).toHaveCount(1);
    expect(await vp.getAttribute('content')).toMatch(/width=device-width/);
  });
});
