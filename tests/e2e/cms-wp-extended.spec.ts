import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import { baseURL } from './support/config';

test.describe('Simple CMS extended features', () => {
  test('site search form accepts query', async ({ page }) => {
    await page.goto(`${baseURL}/search?q=welcome`);
    await expect(page.locator('#siteSearchInput')).toHaveValue('welcome');
    await expect(page.locator('h1')).toContainText(/Search/);
  });

  test('blog search form accepts query when theme enables it', async ({ page }) => {
    await page.goto(`${baseURL}/blog?q=welcome`);
    await expect(page.locator('h1')).toContainText(/Search|Blog/);
    const blogSearch = page.locator('#blogSearchInput');
    if ((await blogSearch.count()) === 0) {
      test.skip(true, 'Pulse and similar packs hide the on-blog search box');
      return;
    }
    await expect(blogSearch).toHaveValue('welcome');
  });

  test('admin comments page loads', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${baseURL}/admin/comments`);
    await expect(page.locator('h2')).toContainText('Comments');
  });

  test('admin widgets page loads', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${baseURL}/admin/widgets`);
    await expect(page.locator('h2')).toContainText('Widgets');
  });

  test('post JSON export when enabled', async ({ page }) => {
    const response = await page.request.get(`${baseURL}/blog/welcome.json`);
    if (response.status() === 404) {
      test.skip(true, 'JSON export disabled or welcome post missing');
      return;
    }
    expect(response.ok()).toBeTruthy();
    const json = await response.json();
    expect(json.type).toBe('post');
    expect(json).toHaveProperty('content_text');
  });

  test('dashboard shows pending comments stat', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${baseURL}/admin`);
    await expect(page.locator('.stat-label').filter({ hasText: 'Comments pending' })).toBeVisible();
  });

  test('RSS feed returns XML when enabled', async ({ page }) => {
    const response = await page.request.get(`${baseURL}/feed.xml`);
    if (response.status() === 404) {
      test.skip(true, 'RSS feed disabled in General settings');
      return;
    }
    expect(response.ok()).toBeTruthy();
    const body = await response.text();
    expect(body).toContain('<rss');
    expect(body).toContain('<channel>');
  });

  test('llms.txt is available when enabled', async ({ page }) => {
    const response = await page.request.get(`${baseURL}/llms.txt`);
    if (response.status() === 404) {
      test.skip(true, 'llms.txt disabled in General settings');
      return;
    }
    expect(response.ok()).toBeTruthy();
    const body = await response.text();
    expect(body).toMatch(/^# /);
    expect(body).toContain('http');
  });
});
