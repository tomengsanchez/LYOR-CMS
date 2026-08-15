import { expect, test } from "@playwright/test";
import { loginAsAdmin } from "./support/auth";
import { baseURL } from "./support/config";

/**
 * Fill AI citation / BLUF / FAQ on post id 7 with content about that sermon.
 * Run: BASE_URL=http://cms.local npx playwright test tests/e2e/cms-post-7-ai-fields.spec.ts --workers=1
 */
test.describe("Post 7 AI citation fields", () => {
  test("update citation snippet, summary, and FAQ on /admin/posts/edit/7", async ({
    page,
  }) => {
    test.setTimeout(90_000);
    await loginAsAdmin(page);

    await page.goto(`${baseURL}/admin/posts/edit/7`);
    await expect(page.locator("#llmSummary")).toBeVisible({ timeout: 30_000 });
    await expect(page.locator("#citationSnippet")).toBeVisible();

    const title =
      (await page.locator('input[name="title"]').inputValue()) || "this post";

    await page.fill(
      "#llmSummary",
      "A preaching message from Romans 5:12, 19 on the weight of one decision: small daily choices become habits, habits shape character, and character shapes destiny—so choose wisely for yourself and the next generation.",
    );

    await page.fill(
      "#citationSnippet",
      "One small decision can shape habits, character, destiny, and even a generation—so choose wisely.",
    );

    // Clear existing FAQ rows, then add two content-related pairs.
    while ((await page.locator(".faq-row").count()) > 0) {
      await page.locator(".faq-row .faq-remove").first().click();
    }
    await page.locator("#faqAddRow").click();
    await page.locator(".faq-row .faq-q").first().fill(
      "Why do small decisions matter according to Romans 5?",
    );
    await page.locator(".faq-row .faq-a").first().fill(
      "Romans 5:12 and 19 show that one person’s choice (Adam’s sin, Christ’s obedience) can affect many. Likewise, everyday choices—smoking, drinking, lying, faithfulness—carry lasting consequences for habits, character, and destiny.",
    );

    await page.locator("#faqAddRow").click();
    await expect(page.locator(".faq-row")).toHaveCount(2);
    await page.locator(".faq-row .faq-q").nth(1).fill(
      "How do small choices become destiny?",
    );
    await page.locator(".faq-row .faq-a").nth(1).fill(
      "Repeated small decisions become habits; habits form character; character shapes destiny. Skipping exercise once is minor, but 365 days can lead to health problems—just as repeated lying or unfaithfulness can ruin trust and family.",
    );

    await Promise.all([
      page.waitForURL(/\/admin\/posts\/view\/7/, { timeout: 45_000 }),
      page.getByRole("button", { name: "Save" }).click(),
    ]);

    await page.goto(`${baseURL}/admin/posts/edit/7`);
    await expect(page.locator("#citationSnippet")).toHaveValue(
      /One small decision can shape habits/,
    );
    await expect(page.locator("#llmSummary")).toHaveValue(/Romans 5:12/);
    await expect(page.locator(".faq-row .faq-q").first()).toHaveValue(
      /Why do small decisions matter/,
    );
    await expect(page.locator(".faq-row")).toHaveCount(2);

    console.log(`Updated content-related AI fields on post 7 (${title})`);
  });
});
