import { test, expect, type Page } from '@playwright/test';
import { adminPass, adminUser, baseURL } from './support/config';

/**
 * Seed sample pages with distinct visual layouts + content widths,
 * then rebuild the Primary Menu so they appear in the public header.
 *
 * Run: npm run test:e2e:cms-pages-layouts-menu
 */
type SamplePageDef = {
  title: string;
  slug: string;
  menuLabel: string;
  contentLayout: '' | 'narrow' | 'normal' | 'wide';
  layoutKind: 'about' | 'services' | 'features' | 'team' | 'contact';
};

const SAMPLE_PAGES: SamplePageDef[] = [
  {
    title: 'About',
    slug: 'about',
    menuLabel: 'About',
    contentLayout: 'narrow',
    layoutKind: 'about',
  },
  {
    title: 'Services',
    slug: 'services',
    menuLabel: 'Services',
    contentLayout: 'normal',
    layoutKind: 'services',
  },
  {
    title: 'Features',
    slug: 'features',
    menuLabel: 'Features',
    contentLayout: 'wide',
    layoutKind: 'features',
  },
  {
    title: 'Team',
    slug: 'team',
    menuLabel: 'Team',
    contentLayout: '',
    layoutKind: 'team',
  },
  {
    title: 'Contact',
    slug: 'contact',
    menuLabel: 'Contact',
    contentLayout: 'narrow',
    layoutKind: 'contact',
  },
];

test.describe('Sample pages — layouts + primary menu', () => {
  test.setTimeout(300_000);

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
    await expect(page.getByLabel('Admin navigation').getByRole('link', { name: 'Pages' })).toBeVisible({
      timeout: 30_000,
    });
  }

  async function ensurePublishedPage(page: Page, def: SamplePageDef): Promise<number> {
    await page.goto(`${baseURL}/admin/pages?q=${encodeURIComponent(def.slug)}`);
    const row = page.locator('tbody tr').filter({ hasText: def.slug });
    if ((await row.count()) > 0) {
      await row.first().getByRole('link', { name: 'Edit' }).click();
      await page.waitForURL(/\/admin\/pages\/edit\/\d+/);
    } else {
      await page.goto(`${baseURL}/admin/pages/create`);
      await expect(page.locator('#pageForm')).toBeVisible();
    }

    await page.fill('#pageForm input[name="title"]', def.title);
    await page.fill('#pageForm input[name="slug"]', def.slug);
    await page.selectOption('#pageForm select[name="status"]', 'published');
    await page.selectOption('#pageForm select[name="content_layout"]', def.contentLayout);

    await Promise.all([
      page.waitForURL(/\/admin\/pages\/(view|edit)\/\d+/, { timeout: 60_000 }),
      page.locator('#pageForm button[type="submit"]').click(),
    ]);

    const idMatch = page.url().match(/\/admin\/pages\/(?:view|edit)\/(\d+)/);
    expect(idMatch).toBeTruthy();
    return Number(idMatch![1]);
  }

  async function saveBuilderLayout(page: Page, pageId: number, layoutKind: SamplePageDef['layoutKind']): Promise<void> {
    await page.goto(`${baseURL}/admin/builder/page/${pageId}`);
    await expect(page.locator('#cmsBuilderCanvas')).toBeVisible();
    await expect(page.locator('#cmsBuilderSave')).toBeVisible();
    await page.waitForFunction('!!window.CmsBuilderApi');

    await page.evaluate(`((kind) => {
      const api = window.CmsBuilderApi;
      const uid = () => 'el_' + Math.random().toString(16).slice(2, 14);
      const heading = (text, level) => ({
        id: uid(), type: 'heading', data: { text: text, level: level || 2 },
        design: { text_align: 'center' }, advanced: {}
      });
      const text = (body, align) => ({
        id: uid(), type: 'text', data: { text: body },
        design: { text_align: align || 'left' }, advanced: {}
      });
      const blurb = (title, body, icon) => ({
        id: uid(), type: 'blurb',
        data: { title: title, text: body, icon: icon, media_id: null, url: '' },
        design: { text_align: 'center' }, advanced: {}
      });
      const button = (label, url) => ({
        id: uid(), type: 'button',
        data: { label: label, url: url || '#', style: 'primary' },
        design: { text_align: 'center' }, advanced: {}
      });
      const cta = (title, body, label, url) => ({
        id: uid(), type: 'cta',
        data: { title: title, text: body, label: label, url: url || '#' },
        design: {}, advanced: {}
      });
      const spacer = () => ({ id: uid(), type: 'spacer', data: { size: 'md' }, design: {}, advanced: {} });
      const divider = () => ({ id: uid(), type: 'divider', data: { style: 'solid' }, design: {}, advanced: {} });
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
      const layouts = api.columnLayouts;
      const byId = (id) => layouts.find(function (l) { return l.id === id; });

      let sections = [];
      if (kind === 'about') {
        const one = byId('1');
        sections = [
          section(one.widths, [[
            heading('About us', 1),
            text('A focused single-column story page — narrow content width for comfortable reading.', 'center'),
            divider(),
            text('We build clear, maintainable CMS experiences with a visual layout builder, themes, and WordPress-style menus.'),
            spacer(),
            button('Explore services', '/p/services')
          ]], '#f7fafc')
        ];
      } else if (kind === 'services') {
        const three = byId('3');
        sections = [
          section(byId('1').widths, [[
            heading('Services', 1),
            text('Three equal columns — great for offering cards.', 'center')
          ]], '#eef6f1'),
          section(three.widths, [
            [blurb('Design', 'Layouts, themes, and public polish that feel intentional.', '🎨')],
            [blurb('Build', 'Pages, posts, media, and menus wired for real publishing.', '🛠️')],
            [blurb('Launch', 'SEO, JSON feeds, and backups ready for production.', '🚀')]
          ])
        ];
      } else if (kind === 'features') {
        const split = byId('8-4');
        sections = [
          section(byId('1').widths, [[
            heading('Features', 1),
            text('Wide content width with a 2/3 + 1/3 split for narrative + call to action.', 'center')
          ]], '#f0f4ff'),
          section(split.widths, [
            [
              heading('Visual builder', 2),
              text('Sections, rows, columns, and modules — save reusable templates and preview desktop, tablet, and mobile.'),
              spacer(),
              text('Content width overrides let each page choose narrow, normal, or wide independently of the site theme.')
            ],
            [cta('Try the builder', 'Open any page and choose Edit via Frontend editor.', 'Go to Pages', '/admin/pages')]
          ])
        ];
      } else if (kind === 'team') {
        const four = byId('4');
        sections = [
          section(byId('1').widths, [[
            heading('Our team', 1),
            text('Four equal columns for people cards.', 'center')
          ]]),
          section(four.widths, [
            [blurb('Alex', 'Editor & publisher', '✍️')],
            [blurb('Sam', 'Theme & design', '🎛️')],
            [blurb('Jordan', 'Builder & modules', '🧱')],
            [blurb('Riley', 'Menus & SEO', '🔗')]
          ], '#faf7f2')
        ];
      } else {
        const mid = byId('3-6-3');
        sections = [
          section(mid.widths, [
            [blurb('Visit', 'Drop by the studio weekdays 9–5.', '📍')],
            [
              heading('Contact', 1),
              text('A 1/4 + 1/2 + 1/4 layout — side blurbs frame a centered message and button.', 'center'),
              spacer(),
              button('Email us', 'mailto:hello@example.com'),
              divider(),
              text('Or use the site menu to jump between sample layout pages.', 'center')
            ],
            [blurb('Call', '+1 (555) 010-2026', '📞')]
          ], '#f7fafc')
        ];
      }

      api.setLayout({ version: 1, sections: sections });
    })(${JSON.stringify(layoutKind)})`);

    const saveResponse = page.waitForResponse(
      (res) =>
        res.url().includes(`/admin/builder/page/${pageId}`) &&
        res.url().includes('/save') &&
        res.request().method() === 'POST',
      { timeout: 30_000 },
    );
    await page.locator('#cmsBuilderSave').click();
    const saved = await saveResponse;
    expect(saved.ok()).toBeTruthy();
    await expect(page.locator('#cmsBuilderStatus')).toContainText('Saved', { timeout: 15_000 });
  }

  async function rebuildPrimaryMenu(
    page: Page,
    pages: Array<{ id: number; menuLabel: string; title: string }>,
  ): Promise<void> {
    await page.goto(`${baseURL}/admin/menus`);
    await expect(page.locator('#menuForm')).toBeVisible();

    const rows = page.locator('#menuItemsTable tbody tr.menu-item-row');
    while ((await rows.count()) > 1) {
      await rows.last().locator('.menu-remove-row').click();
    }

    async function fillRow(
      index: number,
      opts: { label: string; type: string; pageTitle?: string },
    ): Promise<void> {
      const row = rows.nth(index);
      await row.locator('input[name="item_label[]"]').fill(opts.label);
      await row.locator('select[name="item_type[]"]').selectOption(opts.type);
      if (opts.type === 'page' && opts.pageTitle) {
        const select = row.locator('select[name="item_object_id[]"]');
        await expect(select).toBeVisible();
        const option = select.locator('option', { hasText: opts.pageTitle });
        await expect(option.first()).toBeAttached({ timeout: 10_000 });
        const value = await option.first().getAttribute('value');
        expect(value).toBeTruthy();
        await select.selectOption(value!);
      }
    }

    await fillRow(0, { label: 'Home', type: 'home' });

    await page.locator('#menuAddRow').click();
    await fillRow(1, { label: 'Blog', type: 'blog' });

    for (let i = 0; i < pages.length; i++) {
      await page.locator('#menuAddRow').click();
      await fillRow(2 + i, {
        label: pages[i].menuLabel,
        type: 'page',
        pageTitle: pages[i].title,
      });
    }

    await Promise.all([
      page.waitForURL(/\/admin\/menus/, { timeout: 30_000 }),
      page.locator('#menuForm button[type="submit"]').click(),
    ]);
    await expect(page.getByText('Menu saved.')).toBeVisible({ timeout: 15_000 });
  }

  test('create layout sample pages and put them on the menu', async ({ page }) => {
    await login(page);

    const created: Array<{ id: number; menuLabel: string; title: string; slug: string }> = [];
    for (const def of SAMPLE_PAGES) {
      const id = await ensurePublishedPage(page, def);
      await saveBuilderLayout(page, id, def.layoutKind);
      created.push({ id, menuLabel: def.menuLabel, title: def.title, slug: def.slug });
      console.log(`Page ready: ${def.title} (#${id}) layout=${def.layoutKind} width=${def.contentLayout || 'site-default'}`);
    }

    await rebuildPrimaryMenu(page, created);

    await page.goto(`${baseURL}/`);
    const nav = page.locator('nav.public-nav[aria-label="Public"]');
    await expect(nav.getByRole('link', { name: 'Home' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Blog' })).toBeVisible();
    for (const item of created) {
      await expect(nav.getByRole('link', { name: item.menuLabel })).toBeVisible();
    }

    for (const item of created) {
      await nav.getByRole('link', { name: item.menuLabel }).click();
      await expect(page.locator('.cms-layout')).toBeVisible({ timeout: 20_000 });
      await expect(page.getByRole('heading', { level: 1 }).first()).toBeVisible();
      console.log(`Public OK: ${baseURL}/p/${item.slug}`);
    }
  });
});
