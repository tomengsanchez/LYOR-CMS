import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./support/auth";
import { baseURL } from "./support/config";

/**
 * Newsletter public form + admin list.
 * BASE_URL from `.env.playwright` or env (example http://cms.local).
 * Run: npm run test:e2e:cms-newsletter
 */
test.describe("CMS newsletter", () => {
  test.describe.configure({ mode: "serial" });
  test.setTimeout(90_000);

  const stamp = `${Date.now()}`;
  const email = `e2e.nl.${stamp}@example.com`;

  test("widget type, public signup, admin confirm and delete", async ({
    page,
  }) => {
    await loginAsAdmin(page);
    await page.goto(`${baseURL}/admin/widgets?area=footer`);
    const types = page.locator(".widget-type-select").first();
    await expect(types).toBeVisible();
    expect((await types.innerHTML()) || "").toContain("newsletter");

    await page.goto(`${baseURL}/subscribe`);
    await expect(page.locator("h1")).toContainText("Subscribe");
    await page.locator("#newsletterEmail").fill(email);
    await page.locator("#newsletterConsent").check();
    await page.getByRole("button", { name: "Subscribe" }).click();
    await expect(page.locator(".newsletter-site-flash")).toBeVisible();

    await page.goto(
      `${baseURL}/admin/subscribers?q=${encodeURIComponent(email)}`,
    );
    await expect(page.locator("h2")).toContainText("Subscribers");
    await expect(page.locator("table")).toContainText(email);

    const confirm = page.getByRole("button", { name: "Confirm" });
    if ((await confirm.count()) > 0) {
      await confirm.first().click();
      await expect(page.locator("table")).toContainText("Confirmed");
    }

    page.on("dialog", (d) => d.accept());
    await page.getByRole("button", { name: "Delete" }).first().click();
    await page.waitForLoadState("networkidle");
    await page.goto(
      `${baseURL}/admin/subscribers?q=${encodeURIComponent(email)}`,
    );
    await expect(page.locator("table")).not.toContainText(email);
  });
});
