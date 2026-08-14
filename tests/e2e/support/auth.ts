import { expect, type Page } from "@playwright/test";
import { adminPass, adminUser } from "./config";

const adminLoginPath = "/admin/login";
const adminHomePath = "/admin";

/** POST /admin/logout with CSRF when signed in; otherwise ensure we land on login. */
export async function logout(page: Page): Promise<void> {
  await page.goto(adminHomePath);
  if (page.url().includes(adminLoginPath)) {
    return;
  }
  const csrf = await page
    .locator('meta[name="csrf-token"]')
    .getAttribute("content")
    .catch(() => null);
  if (csrf) {
    await page.request.post("/admin/logout", {
      form: { csrf_token: csrf },
    });
  }
  await page.goto(adminLoginPath);
  await page.waitForURL(/\/admin\/login/, { timeout: 30_000 });
}

/** Sign in as admin; waits for authenticated layout shell. */
export async function loginAsAdmin(page: Page): Promise<void> {
  await logout(page);
  await page.goto(adminLoginPath);
  await page.fill('input[name="username"]', adminUser);
  await page.fill('input[name="password"]', adminPass);
  await page.click('button[type="submit"]');

  await page.waitForURL(
    (url) => !url.pathname.startsWith("/admin/login"),
    { timeout: 45_000 },
  );

  if (page.url().includes("/admin/login/2fa")) {
    throw new Error(
      "Email 2FA is enabled. Disable enable_email_2fa in Security Settings for E2E, or extend auth.ts.",
    );
  }

  await expect(
    page.locator('aside.admin-sidebar, [aria-label="Admin navigation"], main, h2').first(),
  ).toBeVisible({ timeout: 30_000 });
}

export async function loginAs(
  page: Page,
  username: string,
  password: string,
): Promise<void> {
  await logout(page);
  await page.goto(adminLoginPath);
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL(
    (url) => !url.pathname.startsWith("/admin/login"),
    { timeout: 45_000 },
  );
  if (page.url().includes("/admin/login/2fa")) {
    throw new Error(
      "Email 2FA is enabled. Disable enable_email_2fa in Security Settings for E2E.",
    );
  }
  await expect(
    page.locator('aside.admin-sidebar, [aria-label="Admin navigation"], main, h2').first(),
  ).toBeVisible({ timeout: 30_000 });
}
