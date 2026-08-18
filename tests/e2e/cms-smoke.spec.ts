import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import { baseURL } from './support/config';

test.describe('Simple CMS smoke', () => {
  test('login and dashboard loads', async ({ page }) => {
    await loginAsAdmin(page);
    await expect(page.locator('h2')).toContainText('Dashboard');
  });

  test('public blog loads', async ({ page }) => {
    await page.goto(`${baseURL}/blog`);
    await expect(page.locator('h1')).toContainText('Blog');
  });

  test('public homepage loads with skip target', async ({ page }) => {
    const res = await page.goto(`${baseURL}/`);
    expect(res?.ok()).toBeTruthy();
    await expect(page.locator('#public-content')).toBeVisible();
    await expect(page.locator('h1').first()).toBeVisible();
  });

  test('welcome page slug still works when published', async ({ page }) => {
    const res = await page.goto(`${baseURL}/p/welcome`);
    const notFound = page.locator('h1').filter({ hasText: /Page not found/i });
    if (!res?.ok() || (await notFound.count()) > 0) {
      test.skip(true, 'welcome page not published on this instance');
      return;
    }
    await expect(page.locator('#public-content')).toBeVisible();
  });

  test('public color mode follows admin default (no visitor toggle)', async ({ page }) => {
    await page.goto(`${baseURL}/`);
    await expect(page.locator('#publicThemeToggle')).toHaveCount(0);
    const html = page.locator('html');
    await expect(html).toHaveAttribute('data-default-color-mode', /.+/);
    const mode = await html.getAttribute('data-default-color-mode');
    if (mode === 'dark') {
      await expect(html).toHaveAttribute('data-theme', 'dark');
    } else if (mode === 'light') {
      await expect(html).not.toHaveAttribute('data-theme', 'dark');
    }
  });

  test('public site applies theme classes on html', async ({ page }) => {
    await page.goto(`${baseURL}/`);
    const html = page.locator('html');
    await expect(html).toHaveClass(/pub-theme-/);
    await expect(html).toHaveClass(/pub-font-/);
    await expect(html).toHaveClass(/pub-radius-/);
  });

  test('auth login inherits public theme', async ({ page }) => {
    await page.goto(`${baseURL}/admin/login`);
    const html = page.locator('html');
    await expect(html).toHaveClass(/pub-theme-/);
    await expect(html).toHaveAttribute('data-default-color-mode', /.+/);
    await expect(page.locator('.auth-card')).toBeVisible();
  });
});
