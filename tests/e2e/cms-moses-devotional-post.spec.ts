import { test, expect, type Page } from '@playwright/test';
import { adminPass, adminUser, baseURL } from './support/config';

/**
 * Seed a devotional post: Moses did not enter the Promised Land.
 * Uses an external image URL (no media upload); owner + source URL go in the caption.
 *
 * Run: npm run test:e2e:cms-moses-devotional:fast
 */

const POST_SLUG = 'moses-and-the-promised-land';
const POST_TITLE = 'When the Journey Ends at the Threshold: Moses and the Promised Land';

const IMAGE_URL =
  'https://upload.wikimedia.org/wikipedia/commons/8/8e/Gustave_Dore_-_Moses_View_of_Promised_Land.jpg';
const IMAGE_SOURCE_PAGE =
  'https://commons.wikimedia.org/wiki/File:Gustave_Dore_-_Moses_View_of_Promised_Land.jpg';
const IMAGE_OWNER = 'Gustave Doré (public domain, Wikimedia Commons)';
const IMAGE_CAPTION = `Owner: ${IMAGE_OWNER}. Source: ${IMAGE_SOURCE_PAGE}`;

test.describe('Moses devotional post', () => {
  test.setTimeout(120_000);

  async function login(page: Page): Promise<void> {
    await page.goto(`${baseURL}/admin/login`);
    await page.fill('input[name="username"]', adminUser);
    await page.fill('input[name="password"]', adminPass);
    await Promise.all([
      page.waitForURL((url) => !url.pathname.includes('/admin/login'), { timeout: 45_000 }),
      page.click('button[type="submit"]'),
    ]);
    if (page.url().includes('/admin/login/2fa')) {
      throw new Error('Email 2FA is enabled — disable it for E2E.');
    }
    await expect(page.getByLabel('Admin navigation').getByRole('link', { name: 'Posts' })).toBeVisible({
      timeout: 30_000,
    });
  }

  async function ensurePublishedPost(page: Page): Promise<number> {
    await page.goto(`${baseURL}/admin/posts?q=${encodeURIComponent(POST_SLUG)}`);
    const row = page.locator('tbody tr').filter({ hasText: POST_SLUG });
    if ((await row.count()) > 0) {
      await row.first().getByRole('link', { name: 'Edit' }).click();
      await page.waitForURL(/\/admin\/posts\/edit\/\d+/);
    } else {
      await page.goto(`${baseURL}/admin/posts/create`);
      await expect(page.locator('#postForm')).toBeVisible();
    }

    await page.fill('#postForm input[name="title"]', POST_TITLE);
    await page.fill('#postForm input[name="slug"]', POST_SLUG);
    await page.selectOption('#postForm select[name="status"]', 'published');
    await page.selectOption('#postForm select[name="featured_image_id"]', '');
    await page.fill(
      '#postForm textarea[name="excerpt"]',
      'Moses led a nation for forty years, yet God did not let him cross into Canaan. A devotional on obedience, legacy, and faith that outlasts the finish line.',
    );
    await page.fill('#postForm input[name="tags"]', 'devotional, moses, faith, obedience');

    const cat = page.locator('#postForm select[name="category_id"]');
    const general = cat.locator('option', { hasText: 'General' });
    if ((await general.count()) > 0) {
      const val = await general.first().getAttribute('value');
      if (val) {
        await cat.selectOption(val);
      }
    }

    await Promise.all([
      page.waitForURL(/\/admin\/posts\/(view|edit)\/\d+/, { timeout: 60_000 }),
      page.locator('#postForm button[type="submit"]').click(),
    ]);
    const idMatch = page.url().match(/\/admin\/posts\/(?:view|edit)\/(\d+)/);
    expect(idMatch).toBeTruthy();
    return Number(idMatch![1]);
  }

  async function saveDevotionalLayout(page: Page, postId: number): Promise<void> {
    await page.goto(`${baseURL}/admin/builder/post/${postId}`);
    await expect(page.locator('#cmsBuilderCanvas')).toBeVisible();
    await page.waitForFunction('!!window.CmsBuilderApi');

    const layoutPayload = {
      imageUrl: IMAGE_URL,
      imageAlt: 'Moses viewing the Promised Land — engraving by Gustave Doré',
      imageCaption: IMAGE_CAPTION,
      title: POST_TITLE,
      paragraphs: [
        '<p>Moses stands at one of the most sobering thresholds in Scripture. After decades of leading Israel through the wilderness — through plagues, seas, manna, rebellion, and long nights of intercession — he climbs Mount Nebo and sees the land God promised. He does not enter it.</p>',
        '<p>Numbers 20 records the moment that closed the door: Moses struck the rock instead of speaking to it as God commanded. In that flash of frustration, leadership that had carried a nation faltered at the edge of trust. God remained faithful; Moses bore the consequence.</p>',
        '<p>Deuteronomy 34:1–5 tells the end with gentle gravity. Moses views the land from afar. God shows him what he labored for. Then Moses dies there, and the Lord buries him. No monument. No parade into victory. Only a faithful life whose fruit others would harvest.</p>',
        '<p><strong>Obedience is not optional at the finish line.</strong> Great seasons of faithfulness do not erase the cost of disobedience in a single moment. God is merciful, but he is also holy — and leaders are held to the weight of their calling.</p>',
        '<p><strong>Legacy is not always personal possession.</strong> Moses did not walk into Canaan, yet Joshua did because Moses stayed faithful in the work God gave him. Sometimes our greatest gift is preparing the next generation to finish what we cannot.</p>',
        '<p><strong>God honors faithfulness even when the outcome differs.</strong> Moses appears with Elijah at the Transfiguration. He is remembered as the servant of the Lord. The story does not end at Nebo; it continues in God’s larger redemption.</p>',
        '<p>Father, thank you for Moses — his courage, his grief, his nearness to you, and his humanity. When I am tired at the threshold of promise, teach me to speak to the rock and not strike it. Help me trust your timing more than my frustration. Let my obedience today become someone else’s open door tomorrow. Amen.</p>',
        '<p><em>Scripture: Numbers 20:7–12; Deuteronomy 34:1–7; Hebrews 11:24–29</em></p>',
      ],
    };

    await page.evaluate((payload) => {
      const api = (window as unknown as { CmsBuilderApi: { setLayout: (l: unknown) => void } })
        .CmsBuilderApi;
      const uid = () => 'el_' + Math.random().toString(16).slice(2, 14);
      const heading = (text: string, level: number) => ({
        id: uid(),
        type: 'heading',
        data: { text, level: level || 2 },
        design: { text_align: 'left' },
        advanced: {},
      });
      const text = (body: string) => ({
        id: uid(),
        type: 'text',
        data: { text: body },
        design: { text_align: 'left' },
        advanced: {},
      });
      const image = (url: string, alt: string, caption: string) => ({
        id: uid(),
        type: 'image',
        data: { media_id: null, url, alt, caption, link: '' },
        design: { text_align: 'center' },
        advanced: {},
      });
      const divider = () => ({
        id: uid(),
        type: 'divider',
        data: { style: 'solid' },
        design: {},
        advanced: {},
      });
      const spacer = () => ({
        id: uid(),
        type: 'spacer',
        data: { size: 'sm' },
        design: {},
        advanced: {},
      });
      const col = (width: number, modules: unknown[]) => ({
        id: uid(),
        width,
        settings: {},
        modules,
      });
      const row = (widths: number[], modulesPerCol: unknown[][]) => ({
        id: uid(),
        settings: {},
        columns: widths.map((w, i) => col(w, modulesPerCol[i] || [])),
      });
      const section = (widths: number[], modulesPerCol: unknown[][], bg = '') => ({
        id: uid(),
        type: 'regular',
        settings: { bg_color: bg, padding: '1.5rem 0', css_class: '' },
        rows: [row(widths, modulesPerCol)],
      });

      const modules: unknown[] = [
        heading(payload.title, 1),
        spacer(),
        image(payload.imageUrl, payload.imageAlt, payload.imageCaption),
        spacer(),
        text(payload.paragraphs[0]),
        text(payload.paragraphs[1]),
        text(payload.paragraphs[2]),
        divider(),
        heading('What this teaches us', 2),
        text(payload.paragraphs[3]),
        text(payload.paragraphs[4]),
        text(payload.paragraphs[5]),
        divider(),
        heading('Prayer', 2),
        text(payload.paragraphs[6]),
        spacer(),
        text(payload.paragraphs[7]),
      ];

      api.setLayout({
        version: 1,
        sections: [section([12], [modules], '#fafbfc')],
      });
    }, layoutPayload);

    const saveResponse = page.waitForResponse(
      (res) =>
        res.url().includes(`/admin/builder/post/${postId}`) &&
        res.url().includes('/save') &&
        res.request().method() === 'POST',
      { timeout: 30_000 },
    );
    await page.locator('#cmsBuilderSave').click();
    expect((await saveResponse).ok()).toBeTruthy();
    await expect(page.locator('#cmsBuilderStatus')).toContainText('Saved', { timeout: 15_000 });
  }

  test('creates Moses devotional with external image URL and caption attribution', async ({ page }) => {
    await login(page);
    const postId = await ensurePublishedPost(page);
    await saveDevotionalLayout(page, postId);

    await page.goto(`${baseURL}/blog/${POST_SLUG}`);
    await expect(page.getByRole('heading', { name: POST_TITLE, level: 1 })).toBeVisible({
      timeout: 20_000,
    });

    const figure = page.locator('.cms-mod-image').first();
    await expect(figure.locator('img')).toHaveAttribute('src', IMAGE_URL);
    await expect(figure.locator('.cms-mod-image-caption')).toContainText(IMAGE_OWNER);
    await expect(figure.locator('.cms-mod-image-caption')).toContainText(IMAGE_SOURCE_PAGE);

    await expect(page.getByText('Moses stands at one of the most sobering thresholds')).toBeVisible();
    await expect(page.getByText('Scripture: Numbers 20:7–12')).toBeVisible();

    await page.goto(`${baseURL}/blog`);
    await expect(
      page.locator(`a[href="/blog/${POST_SLUG}"]`).filter({ hasText: POST_TITLE }).first(),
    ).toBeVisible({ timeout: 20_000 });

    console.log(`Moses devotional ready at ${baseURL}/blog/${POST_SLUG}`);
  });
});
