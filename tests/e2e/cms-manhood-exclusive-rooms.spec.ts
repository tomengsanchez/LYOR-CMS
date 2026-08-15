import { expect, test, type Page } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import { baseURL } from './support/config';

/**
 * Short Recovering Biblical Manhood post with free image by URL (no re-upload).
 * Run headed: npm run test:e2e:cms-manhood-exclusive-rooms
 */

const IMAGE_URL =
  'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?auto=format&fit=crop&w=1200&q=80';
const IMAGE_CAPTION =
  'Recovering Biblical Manhood — Manhood Exclusive Rooms. Free image (Unsplash): ' + IMAGE_URL;

const POST = {
  title: 'Manhood Exclusive Rooms',
  slug: 'manhood-exclusive-rooms',
  excerpt:
    'Recovering Biblical Manhood: Adam hid in fear; Jesus obeyed in fear. Brotherhood, accountability, and displaying Christ.',
  tags: 'tomeng, biblical manhood, elliptical men, preaching, faith',
  citation:
    'You cannot do manhood alone—Adam hid in fear, but Jesus obeyed; value brotherhood and display the manhood of Christ.',
  llmSummary:
    'From the Recovering Biblical Manhood series (Manhood Exclusive Rooms), Tomeng Sanchez contrasts Adam’s fear and hiding (Genesis 3) with Jesus’ obedience under sorrow (Matthew 26; Romans 5:19). Men are called to value brotherhood, choose an accountability partner, and display the manhood of Christ. Script prepared by Tomeng Sanchez; edited by Ps. Jamey and Coach Jan Manual.',
};

const BODY_PARAS = [
  'Series: Recovering Biblical Manhood — Topic: Manhood Exclusive Rooms. Script by Tomeng Sanchez; edited by Ps. Jamey & Coach Jan Manual.',
  'Every man has a story of falling and rising. In Genesis 3, Adam stood beside Eve during temptation and did nothing; after the Fall he hid in fear and shame. The enemy’s goal is to paralyze you—fear, shame, and a broken relationship with God.',
  'In Matthew 26:38–39 Jesus was overwhelmed with sorrow to the point of death—yet unlike Adam, He obeyed: “Not as I will, but as you will.” Romans 5:19: through one man’s disobedience many became sinners; through one man’s obedience many will be made righteous.',
  'What to do: value brotherhood—you cannot man up in isolation. Have an accountability partner. Display the manhood of Christ: look to Him, then imitate and show Him.',
  'Declaration: I am a man. I will be part of this community. I will display Jesus in my life.',
];

test.describe('Manhood Exclusive Rooms post', () => {
  test('register free image by URL and publish short post via Playwright', async ({ page }) => {
    test.setTimeout(180_000);
    await loginAsAdmin(page);

    const mediaId = await registerExternalMedia(page);
    const postId = await ensurePublishedPost(page, mediaId);
    await savePostLayout(page, postId, mediaId);

    await page.goto(`${baseURL}/blog/${POST.slug}`);
    await expect(page.getByRole('heading', { name: POST.title })).toBeVisible({ timeout: 30_000 });
    await expect(page.locator(`img[src="${IMAGE_URL}"]`).first()).toBeVisible();
    await expect
      .poll(async () =>
        page.locator(`img[src="${IMAGE_URL}"]`).first().evaluate((img: HTMLImageElement) => img.naturalWidth),
      )
      .toBeGreaterThan(0);
    await expect(page.locator('.cms-mod-image-caption').first()).toContainText(IMAGE_URL);
    await expect(page.getByText(/You cannot do manhood alone|value brotherhood/i).first()).toBeVisible();
    await expect(page.getByText(/Romans 5:19/i).first()).toBeVisible();

    console.log(`Published ${POST.title} as post #${postId} with external media #${mediaId}`);
  });
});

async function registerExternalMedia(page: Page): Promise<number> {
  await page.goto(`${baseURL}/admin/media`);
  await expect(page.locator('#mediaRegisterUrlForm')).toBeVisible({ timeout: 30_000 });
  await page.fill('#mediaSourceUrl', IMAGE_URL);
  await page.fill('#mediaExternalAlt', 'Open Bible — Recovering Biblical Manhood');
  await page.fill('#mediaExternalCaption', IMAGE_CAPTION);

  await Promise.all([
    page.waitForURL(/\/admin\/media/, { timeout: 30_000 }),
    page.locator('#mediaRegisterUrlForm button[type="submit"]').click(),
  ]);
  await expect(page.getByText(/External image registered/i)).toBeVisible({ timeout: 15_000 });

  const ids = await page.locator('.card img[src], .card img').evaluateAll((imgs) =>
    imgs
      .map((img) => {
        const card = img.closest('.card');
        const form = card?.querySelector('form[action*="media/delete/"]');
        const action = form?.getAttribute('action') || '';
        const m = action.match(/media\/delete\/(\d+)/);
        return m ? Number(m[1]) : 0;
      })
      .filter((n) => n > 0),
  );
  expect(ids.length).toBeGreaterThan(0);
  return Math.max(...ids);
}

async function ensurePublishedPost(page: Page, featuredMediaId: number): Promise<number> {
  await page.goto(`${baseURL}/admin/posts?q=${encodeURIComponent(POST.slug)}`);
  const row = page.locator('tbody tr').filter({ hasText: POST.slug });
  if ((await row.count()) > 0) {
    await row.first().getByRole('link', { name: 'Edit' }).click();
    await page.waitForURL(/\/admin\/posts\/edit\/\d+/);
  } else {
    await page.goto(`${baseURL}/admin/posts/create`);
    await expect(page.locator('#postForm')).toBeVisible();
  }

  await page.fill('#postForm input[name="title"]', POST.title);
  await page.fill('#postForm input[name="slug"]', POST.slug);
  await page.selectOption('#postForm select[name="status"]', 'published');
  await page.fill('#postForm textarea[name="excerpt"]', POST.excerpt);
  await page.fill('#postForm input[name="tags"]', POST.tags);
  await page.selectOption('#postForm select[name="featured_image_id"]', String(featuredMediaId));
  await page.fill('#postForm textarea[name="llm_summary"]', POST.llmSummary);
  if (await page.locator('#citationSnippet').count()) {
    await page.fill('#citationSnippet', POST.citation);
    while ((await page.locator('.faq-row').count()) > 0) {
      await page.locator('.faq-row .faq-remove').first().click();
    }
    await page.locator('#faqAddRow').click();
    await page.locator('.faq-row .faq-q').first().fill('What is the goal of the enemy in this message?');
    await page.locator('.faq-row .faq-a').first().fill(
      'To paralyze men with fear and shame so they hide from God—as Adam did after the Fall—instead of obeying like Jesus.',
    );
    await page.locator('#faqAddRow').click();
    await page.locator('.faq-row .faq-q').nth(1).fill('How should men respond according to this talk?');
    await page.locator('.faq-row .faq-a').nth(1).fill(
      'Value brotherhood, choose an accountability partner, and display the manhood of Christ through obedience—not isolation.',
    );
  }

  await Promise.all([
    page.waitForURL(/\/admin\/posts\/(view|edit)\/\d+/, { timeout: 60_000 }),
    page.getByRole('button', { name: 'Save' }).click(),
  ]);
  const idMatch = page.url().match(/\/admin\/posts\/(?:view|edit)\/(\d+)/);
  expect(idMatch).toBeTruthy();
  return Number(idMatch![1]);
}

async function savePostLayout(page: Page, postId: number, mediaId: number): Promise<void> {
  await page.goto(`${baseURL}/admin/builder/post/${postId}`);
  await expect(page.locator('#cmsBuilderCanvas')).toBeVisible();
  await page.waitForFunction('!!window.CmsBuilderApi');

  await page.evaluate(
    `(({ title, mediaId, imageUrl, caption, paras }) => {
      const api = window.CmsBuilderApi;
      const uid = () => 'el_' + Math.random().toString(16).slice(2, 14);
      const heading = (text, level) => ({
        id: uid(), type: 'heading', data: { text: text, level: level || 2 },
        design: { text_align: 'left' }, advanced: {}
      });
      const text = (body) => ({
        id: uid(), type: 'text', data: { text: body },
        design: { text_align: 'left' }, advanced: {}
      });
      // URL + caption (no re-upload); media_id also set for featured library link.
      const image = () => ({
        id: uid(), type: 'image',
        data: { media_id: null, url: imageUrl, alt: title, caption: caption, link: '' },
        design: { text_align: 'center' }, advanced: {}
      });
      const spacer = () => ({ id: uid(), type: 'spacer', data: { size: 'md' }, design: {}, advanced: {} });
      const col = (width, modules) => ({ id: uid(), width: width, settings: {}, modules: modules });
      const row = (widths, modulesPerCol) => ({
        id: uid(), settings: {},
        columns: widths.map(function (w, i) { return col(w, modulesPerCol[i] || []); })
      });
      const section = (widths, modulesPerCol, bg) => ({
        id: uid(), type: 'regular',
        settings: { bg_color: bg || '', padding: '2rem 0', css_class: '' },
        rows: [row(widths, modulesPerCol)]
      });
      const modules = [
        heading(title, 1),
        spacer(),
        image(),
        spacer(),
      ].concat(paras.map(function (p) { return text(p); })).concat([
        spacer(),
        text('— Tomeng Sanchez'),
      ]);
      api.setLayout({
        version: 1,
        sections: [section([12], [modules], '#ffffff')],
      });
      void mediaId;
    })(${JSON.stringify({
      title: POST.title,
      mediaId,
      imageUrl: IMAGE_URL,
      caption: IMAGE_CAPTION,
      paras: BODY_PARAS,
    })})`,
  );

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
