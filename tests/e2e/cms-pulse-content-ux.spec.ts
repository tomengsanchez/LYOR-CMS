import { test, expect, type Page } from "@playwright/test";
import { loginAsAdmin } from "./support/auth";
import { baseURL } from "./support/config";

/**
 * End-to-end coverage for work since Pulse widgets:
 * extra widget areas, skip-link / 404 search, duplicate, schedule/sticky,
 * site search & archives, reading UX, bulk actions, password gate.
 *
 * BASE_URL from `.env.playwright` or env (example http://cms.local).
 * Run: npm run test:e2e:cms-pulse-ux
 */
const stamp = `${Date.now()}`;
const postTitle = `E2EPulse Post ${stamp}`;
const scheduledTitle = `E2EPulse Scheduled ${stamp}`;
const pageTitle = `E2EPulse Page ${stamp}`;
const secretBody = `e2e_pw_secret_${stamp}`;
const openExcerpt = `Open excerpt ${stamp}`;

test.describe("CMS Pulse through password UX", () => {
  test.describe.configure({ mode: "serial" });
  test.setTimeout(90_000);

  test("public skip-link, 404 search, and widget areas", async ({ page }) => {
    await page.goto(`${baseURL}/`);
    await expect(page.locator("a.skip-to-content")).toHaveAttribute(
      "href",
      "#public-content",
    );
    await expect(page.locator("#public-content")).toBeVisible();

    const nf = await page.goto(`${baseURL}/e2e-missing-${stamp}`);
    expect(nf?.status()).toBe(404);
    await expect(page.locator("h1")).toContainText("Page not found");
    await expect(page.locator("#nfSearch")).toBeVisible();
    await expect(page.locator('form[action="/search"]')).toBeVisible();

    await loginAsAdmin(page);
    await page.goto(`${baseURL}/admin/widgets`);
    await expect(page.locator("h2")).toContainText("Widgets");
    for (const area of [
      "Header",
      "After header",
      "Homepage",
      "Sidebar",
      "After content",
      "Footer",
    ]) {
      await expect(page.locator(".nav-tabs")).toContainText(area);
    }
    await page.goto(`${baseURL}/admin/widgets?area=header`);
    const types = page.locator(".widget-type-select").first();
    await expect(types).toBeVisible();
    const typeHtml = (await types.innerHTML()) || "";
    expect(typeHtml).toContain("featured_posts");
    expect(typeHtml).toContain("archives");
    expect(typeHtml).toContain("cta");
    expect(typeHtml).toContain("social");

    await page.goto(`${baseURL}/admin/customize`);
    await expect(
      page.getByRole("link", { name: /Download Pulse zip/i }),
    ).toBeVisible();
    await expect(
      page.getByRole("button", { name: /Pulse \(header/i }),
    ).toBeVisible();
  });

  test("duplicate, bulk UI, sticky reading UX, search, archives", async ({
    page,
  }) => {
    await loginAsAdmin(page);

    await page.goto(`${baseURL}/admin/posts`);
    await expect(page.locator("#postBulkForm")).toBeVisible();
    await expect(page.locator("[data-bulk-apply]")).toBeDisabled();
    const firstRow = page.locator(".js-bulk-row").first();
    await firstRow.check();
    await expect(page.locator("[data-bulk-count]")).toContainText("1 selected");
    await expect(page.locator("[data-bulk-apply]")).toBeEnabled();
    await page.locator("#postBulkAction").selectOption("pin");

    await page.goto(`${baseURL}/admin/pages`);
    await expect(page.locator("#pageBulkForm")).toBeVisible();
    await expect(page.getByRole("button", { name: "Duplicate" }).first()).toBeVisible();
    await page.getByRole("button", { name: "Duplicate" }).first().click();
    await expect(page).toHaveURL(/\/admin\/pages\/edit\//);
    await expect(page.locator('input[name="title"]')).toHaveValue(/^Copy of /);
    const dupId = page.url().match(/pages\/edit\/(\d+)/)?.[1];
    if (dupId) {
      const csrf = await page.locator('input[name="csrf_token"]').first().inputValue();
      const del = await page.request.post(`${baseURL}/admin/pages/delete/${dupId}`, {
        form: { csrf_token: csrf },
      });
      expect(del.status(), "delete duplicate should redirect or succeed").toBeLessThan(400);
    }

    await page.goto(`${baseURL}/admin/posts/create`);
    await page.locator('#postForm input[name="title"]').fill(postTitle);
    await page.locator('#postForm select[name="status"]').selectOption("published");
    await page.locator("#postSticky").check();
    await page.locator('#postForm textarea[name="excerpt"]').fill(openExcerpt);
    await page.locator('#postForm textarea[name="body"]').fill(
      `<h2>Alpha ${stamp}</h2><p>${openExcerpt} extra words for a reading time estimate that is at least one minute of filler text so the public post shows minutes.</p><h2>Beta heading</h2><p>More words more words more words more words more words more words more words.</p>`,
    );
    await page.locator('#postForm button[type="submit"]').click();
    await expect(page).toHaveURL(/\/admin\/posts\/view\//);
    const publicHref = await page
      .getByRole("link", { name: "View public" })
      .getAttribute("href");
    expect(publicHref).toBeTruthy();

    await page.goto(`${baseURL}${publicHref}`);
    await expect(page.locator("h1").first()).toContainText(postTitle);
    await expect(page.locator(".public-badge").filter({ hasText: "Pinned" })).toBeVisible();
    await expect(page.locator(".public-read-time")).toBeVisible();
    await expect(page.locator(".public-toc")).toContainText("On this page");
    await expect(page.locator(".public-share")).toBeVisible();
    await expect(page.getByRole("button", { name: "Copy link" })).toBeVisible();

    await page.goto(`${baseURL}/search?q=${encodeURIComponent(openExcerpt)}`);
    await expect(page.locator("h1")).toContainText("Search");
    await expect(page.locator("#searchPostsHeading")).toBeVisible();
    await expect(page.locator('section[aria-labelledby="searchPostsHeading"]')).toContainText(
      postTitle,
    );

    const year = new Date().getFullYear();
    const archive = await page.goto(`${baseURL}/blog/archive/${year}`);
    expect(archive?.ok()).toBeTruthy();
    await expect(page.locator("h1")).toContainText(String(year));

    await page.goto(`${baseURL}/blog/author/admin`);
    await expect(page.locator("h1")).toContainText(/admin/i);
  });

  test("scheduled post stays off the public site until preview", async ({
    page,
    browser,
  }) => {
    await loginAsAdmin(page);
    await page.goto(`${baseURL}/admin/posts/create`);
    await page.locator('#postForm input[name="title"]').fill(scheduledTitle);
    await page.locator('#postForm select[name="status"]').selectOption("published");
    await page.locator("#postPublishedAt").fill("2099-12-31T23:00");
    await page.locator('#postForm textarea[name="body"]').fill("<p>Future only</p>");
    await page.locator('#postForm button[type="submit"]').click();
    await expect(page).toHaveURL(/\/admin\/posts\/view\//);
    await expect(page.locator("body")).toContainText("scheduled");

    const preview = page.getByRole("link", { name: "Preview" });
    await expect(preview).toBeVisible();
    const previewHref = await preview.getAttribute("href");
    expect(previewHref).toContain("preview=1");

    const publicPath = (previewHref || "").replace(/\?preview=1$/, "");
    const visitor = await browser.newContext();
    const vp = await visitor.newPage();
    const res = await vp.goto(`${baseURL}${publicPath}`);
    expect(res?.status()).toBe(404);
    await visitor.close();

    await page.goto(`${baseURL}${previewHref}`);
    await expect(page.locator(".public-preview-banner")).toBeVisible();
    await expect(page.locator("body")).toContainText(scheduledTitle);
  });

  test("password-protected page gates visitors and JSON; editors bypass", async ({
    page,
    browser,
  }) => {
    await loginAsAdmin(page);
    await page.goto(`${baseURL}/admin/pages/create`);
    await page.locator('#pageForm input[name="title"]').fill(pageTitle);
    await page.locator('#pageForm select[name="status"]').selectOption("published");
    await page.locator("#pageContentPassword").fill("secret99");
    await page.locator('#pageForm textarea[name="body"]').fill(
      `<p>Visible after unlock ${secretBody}</p>`,
    );
    await page.locator('#pageForm button[type="submit"]').click();
    await expect(page).toHaveURL(/\/admin\/pages\/view\//);
    await expect(page.locator("body")).toContainText("Password protected");

    const publicHref = await page
      .getByRole("link", { name: "View public" })
      .getAttribute("href");
    expect(publicHref).toBeTruthy();
    const publicPath = publicHref || "/";
    const jsonPath = publicPath === "/" ? "/index.json" : `${publicPath}.json`;

    await page.goto(`${baseURL}${publicPath}`);
    await expect(page.locator(".cms-body")).toContainText(secretBody);
    await expect(page.locator(".public-password-gate")).toHaveCount(0);

    const visitor = await browser.newContext();
    const vp = await visitor.newPage();
    await vp.goto(`${baseURL}${publicPath}`);
    await expect(vp.locator(".public-password-gate")).toBeVisible();
    await expect(vp.locator(".cms-body")).toHaveCount(0);
    await expect(vp.locator("body")).not.toContainText(secretBody);

    const jsonRes = await visitor.request.get(`${baseURL}${jsonPath}`);
    if (jsonRes.ok()) {
      const json = await jsonRes.json();
      expect(json.protected).toBe(true);
      expect(json.content_text).toBeUndefined();
      expect(JSON.stringify(json)).not.toContain(secretBody);
    }

    await vp.goto(`${baseURL}/search?q=${encodeURIComponent(secretBody)}`);
    await expect(vp.locator("body")).not.toContainText(pageTitle);

    await vp.goto(`${baseURL}${publicPath}`);
    await expect(vp.locator(".public-password-gate")).toBeVisible();
    await vp.fill("#contentPasswordInput", "wrong-pass");
    await vp.getByRole("button", { name: "Unlock" }).click();
    await expect(vp.locator(".public-password-gate-error")).toBeVisible();

    await vp.fill("#contentPasswordInput", "secret99");
    await vp.getByRole("button", { name: "Unlock" }).click();
    await expect(vp.locator(".cms-body")).toContainText(secretBody);
    await expect(vp.locator(".public-password-gate")).toHaveCount(0);
    await visitor.close();
  });

  test("cleanup E2EPulse content via admin search", async ({ page }) => {
    await loginAsAdmin(page);
    page.on("dialog", (d) => d.accept());

    for (const kind of ["posts", "pages"] as const) {
      await page.goto(
        `${baseURL}/admin/${kind}?q=${encodeURIComponent("E2EPulse")}&per_page=100`,
      );
      const deletes = page.locator('form[data-confirm] button[type="submit"]');
      const n = await deletes.count();
      for (let i = 0; i < n; i++) {
        await page.goto(
          `${baseURL}/admin/${kind}?q=${encodeURIComponent("E2EPulse")}&per_page=100`,
        );
        const btn = page.locator('form[data-confirm] button[type="submit"]').first();
        if ((await btn.count()) === 0) {
          break;
        }
        await btn.click();
        await page.waitForLoadState("networkidle");
      }
    }
  });
});
