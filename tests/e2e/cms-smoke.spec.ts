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

  test('welcome page is public homepage', async ({ page }) => {
    await page.goto(`${baseURL}/`);
    await expect(page.locator('h1')).toContainText('Welcome');
  });

  test('welcome page slug still works', async ({ page }) => {
    await page.goto(`${baseURL}/p/welcome`);
    await expect(page.locator('h1')).toContainText('Welcome');
  });

  test('public dark mode toggle', async ({ page }) => {
    await page.goto(`${baseURL}/`);
    const toggle = page.locator('#publicThemeToggle');
    const visible = await toggle.isVisible().catch(() => false);
    if (!visible) {
      test.skip(true, 'Theme toggle disabled in General settings');
      return;
    }
    await toggle.click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await toggle.click();
    await expect(page.locator('html')).not.toHaveAttribute('data-theme', 'dark');
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
