import { expect, test } from "@playwright/test";
import { loginAsAdmin } from "./support/auth";
import { baseURL } from "./support/config";

/**
 * Apply reusable editorial magazine style pack + latest-posts homepage.
 */
test.describe("Apply editorial magazine theme", () => {
  test("publish editorial style pack from General settings", async ({ page }) => {
    test.setTimeout(120_000);
    await loginAsAdmin(page);

    await page.goto(`${baseURL}/admin/system/general`);
    await expect(page.locator("#pubApplyEditorialPack")).toBeVisible({ timeout: 30_000 });

    const readingSelect = page.locator('select[name="reading_show_on_front"]');
    if (await readingSelect.count()) {
      await readingSelect.selectOption("posts");
    }

    await page.locator("#pubApplyEditorialPack").check();
    await page.getByRole("button", { name: "Save" }).first().click();
    await page.waitForURL(/\/admin\/system\/general/, { timeout: 45_000 });

    await page.goto(`${baseURL}/admin/system/general`);
    await expect(page.locator('select[name="pub_theme_chrome"]')).toHaveValue("editorial");
    await expect(page.locator('input[name="pub_theme_blog_kicker"]')).toHaveValue(/HERE'S WHAT'S NEW/i);

    await page.goto(`${baseURL}/`);
    const htmlClass = await page.locator("html").getAttribute("class");
    expect(htmlClass || "").toContain("pub-theme-editorial");
    expect(htmlClass || "").toContain("pub-chrome-editorial");
    expect(htmlClass || "").toContain("pub-blog-magazine");
    await expect(page.locator(".public-blog-kicker")).toContainText(/HERE'S WHAT'S NEW/i);

    console.log(`Editorial magazine theme applied at ${baseURL}/`);
  });
});
