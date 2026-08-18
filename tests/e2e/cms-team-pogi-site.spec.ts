import { test, expect, type Page, type APIRequestContext } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { adminPass, adminUser, baseURL } from './support/config';

/**
 * Build a Team POGI marketing site: 5 pages + 5 posts with free stock photos
 * (Lorem Picsum), then wire pages into the Primary Menu and set homepage.
 *
 * Facebook: https://www.facebook.com/teampogi31/
 *
 * Run: npm run test:e2e:cms-team-pogi-site
 */

const FACEBOOK_URL = 'https://www.facebook.com/teampogi31/';

type MediaAsset = { key: string; picsumId: number; w: number; h: number; alt: string; file: string };

const FREE_IMAGES: MediaAsset[] = [
  { key: 'hero', picsumId: 1015, w: 1400, h: 800, alt: 'Team POGI horizon', file: 'pogi-hero.jpg' },
  { key: 'mission', picsumId: 1011, w: 1200, h: 800, alt: 'Mission workshop', file: 'pogi-mission.jpg' },
  { key: 'projects', picsumId: 1036, w: 1200, h: 800, alt: 'Projects in motion', file: 'pogi-projects.jpg' },
  { key: 'crew', picsumId: 338, w: 1200, h: 800, alt: 'Crew collaboration', file: 'pogi-crew.jpg' },
  { key: 'join', picsumId: 1060, w: 1200, h: 800, alt: 'Join the team', file: 'pogi-join.jpg' },
  { key: 'post1', picsumId: 201, w: 1200, h: 630, alt: 'Introducing Team POGI', file: 'pogi-post-1.jpg' },
  { key: 'post2', picsumId: 292, w: 1200, h: 630, alt: 'Build in public', file: 'pogi-post-2.jpg' },
  { key: 'post3', picsumId: 367, w: 1200, h: 630, alt: 'Network night', file: 'pogi-post-3.jpg' },
  { key: 'post4', picsumId: 433, w: 1200, h: 630, alt: 'Craft over complexity', file: 'pogi-post-4.jpg' },
  { key: 'post5', picsumId: 544, w: 1200, h: 630, alt: 'How we ship', file: 'pogi-post-5.jpg' },
];

type PageDef = {
  title: string;
  slug: string;
  menuLabel: string;
  contentLayout: '' | 'narrow' | 'normal' | 'wide';
  kind: 'home' | 'mission' | 'projects' | 'crew' | 'join';
  imageKey: string;
};

const PAGES: PageDef[] = [
  {
    title: 'Team POGI',
    slug: 'team-pogi',
    menuLabel: 'Team POGI',
    contentLayout: '',
    kind: 'home',
    imageKey: 'hero',
  },
  {
    title: 'Our Mission',
    slug: 'mission',
    menuLabel: 'Mission',
    contentLayout: '',
    kind: 'mission',
    imageKey: 'mission',
  },
  {
    title: 'Projects',
    slug: 'projects',
    menuLabel: 'Projects',
    contentLayout: '',
    kind: 'projects',
    imageKey: 'projects',
  },
  {
    title: 'The Crew',
    slug: 'crew',
    menuLabel: 'Crew',
    contentLayout: '',
    kind: 'crew',
    imageKey: 'crew',
  },
  {
    title: 'Join Us',
    slug: 'join-us',
    menuLabel: 'Join',
    contentLayout: '',
    kind: 'join',
    imageKey: 'join',
  },
];

type PostDef = {
  title: string;
  slug: string;
  excerpt: string;
  tags: string;
  imageKey: string;
  kind: 'intro' | 'public' | 'network' | 'craft' | 'ship';
};

const POSTS: PostDef[] = [
  {
    title: 'Introducing Team POGI',
    slug: 'introducing-team-pogi',
    excerpt: 'People, Ownership, Grit, Integrity — the crew behind the craft.',
    tags: 'team-pogi, culture',
    imageKey: 'post1',
    kind: 'intro',
  },
  {
    title: 'Why we build in public',
    slug: 'build-in-public',
    excerpt: 'Shipping openly makes better products and stronger teammates.',
    tags: 'team-pogi, engineering',
    imageKey: 'post2',
    kind: 'public',
  },
  {
    title: 'Network night recap',
    slug: 'network-night-recap',
    excerpt: 'Cables, coffee, and camaraderie — a night of hands-on installs.',
    tags: 'team-pogi, networks',
    imageKey: 'post3',
    kind: 'network',
  },
  {
    title: 'Craft over complexity',
    slug: 'craft-over-complexity',
    excerpt: 'Simple systems that people can trust beat clever ones they cannot.',
    tags: 'team-pogi, craft',
    imageKey: 'post4',
    kind: 'craft',
  },
  {
    title: 'How Team POGI ships',
    slug: 'how-team-pogi-ships',
    excerpt: 'Clear owners, short loops, and honest demos — every week.',
    tags: 'team-pogi, delivery',
    imageKey: 'post5',
    kind: 'ship',
  },
];

test.describe('Team POGI website — pages, posts, images, menu', () => {
  test.setTimeout(480_000);

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

  async function downloadFreeImages(request: APIRequestContext): Promise<Map<string, string>> {
    const dir = path.join(process.cwd(), 'test-results', 'team-pogi-images');
    await mkdir(dir, { recursive: true });
    const paths = new Map<string, string>();

    for (const asset of FREE_IMAGES) {
      const url = `https://picsum.photos/id/${asset.picsumId}/${asset.w}/${asset.h}.jpg`;
      const res = await request.get(url, { maxRedirects: 5, timeout: 60_000 });
      expect(res.ok(), `Download failed for ${url}`).toBeTruthy();
      const buf = Buffer.from(await res.body());
      expect(buf.byteLength).toBeGreaterThan(1000);
      const filePath = path.join(dir, asset.file);
      await writeFile(filePath, buf);
      paths.set(asset.key, filePath);
      console.log(`Downloaded free image: ${asset.file} from ${url}`);
    }
    return paths;
  }

  async function uploadMedia(page: Page, filePath: string, alt: string): Promise<number> {
    await page.goto(`${baseURL}/admin/media`);
    const uploadForm = page.locator('form[action*="media/upload"]');
    await expect(uploadForm).toBeVisible();
    await uploadForm.locator('input[name="file"]').setInputFiles(filePath);
    await uploadForm.locator('input[name="alt_text"]').fill(alt);
    await Promise.all([
      page.waitForURL(/\/admin\/media/, { timeout: 60_000 }),
      uploadForm.locator('button[type="submit"]').click(),
    ]);
    await expect(page.getByText('File uploaded')).toBeVisible({ timeout: 30_000 });
    const ids = await page.locator('img.card-img-top').evaluateAll((imgs) =>
      imgs
        .map((img) => {
          const src = img.getAttribute('src') || '';
          const m = src.match(/\/serve\/media\/(\d+)/);
          return m ? Number(m[1]) : 0;
        })
        .filter((n) => n > 0),
    );
    expect(ids.length).toBeGreaterThan(0);
    return Math.max(...ids);
  }

  async function ensurePublishedPage(page: Page, def: PageDef): Promise<number> {
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

  async function ensurePublishedPost(
    page: Page,
    def: PostDef,
    featuredMediaId: number,
  ): Promise<number> {
    await page.goto(`${baseURL}/admin/posts?q=${encodeURIComponent(def.slug)}`);
    const row = page.locator('tbody tr').filter({ hasText: def.slug });
    if ((await row.count()) > 0) {
      await row.first().getByRole('link', { name: 'Edit' }).click();
      await page.waitForURL(/\/admin\/posts\/edit\/\d+/);
    } else {
      await page.goto(`${baseURL}/admin/posts/create`);
      await expect(page.locator('#postForm')).toBeVisible();
    }

    await page.fill('#postForm input[name="title"]', def.title);
    await page.fill('#postForm input[name="slug"]', def.slug);
    await page.selectOption('#postForm select[name="status"]', 'published');
    await page.fill('#postForm textarea[name="excerpt"]', def.excerpt);
    await page.fill('#postForm input[name="tags"]', def.tags);
    await page.selectOption('#postForm select[name="featured_image_id"]', String(featuredMediaId));

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

  async function savePageLayout(
    page: Page,
    pageId: number,
    kind: PageDef['kind'],
    mediaByKey: Map<string, number>,
  ): Promise<void> {
    await page.goto(`${baseURL}/admin/builder/page/${pageId}`);
    await expect(page.locator('#cmsBuilderCanvas')).toBeVisible();
    await page.waitForFunction('!!window.CmsBuilderApi');

    const media = Object.fromEntries(mediaByKey.entries());
    await page.evaluate(
      `(({ kind, media, facebookUrl }) => {
      const api = window.CmsBuilderApi;
      const uid = () => 'el_' + Math.random().toString(16).slice(2, 14);
      const heading = (text, level, align) => ({
        id: uid(), type: 'heading', data: { text: text, level: level || 2 },
        design: { text_align: align || 'center' }, advanced: {}
      });
      const text = (body, align) => ({
        id: uid(), type: 'text', data: { text: body },
        design: { text_align: align || 'left' }, advanced: {}
      });
      const image = (mediaId, alt) => ({
        id: uid(), type: 'image',
        data: { media_id: mediaId, url: '', alt: alt || '', link: '' },
        design: { text_align: 'center' }, advanced: {}
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
      const byId = (id) => api.columnLayouts.find(function (l) { return l.id === id; });

      let sections = [];
      if (kind === 'home') {
        sections = [
          section(byId('1').widths, [[
            heading('Team POGI', 1),
            text('People · Ownership · Grit · Integrity', 'center'),
            spacer(),
            image(media.hero, 'Team POGI hero'),
            spacer(),
            text('We build useful products, reliable networks, and a culture where craft and camaraderie travel together.', 'center'),
            button('Read our mission', '/p/mission'),
            button('Follow on Facebook', facebookUrl)
          ]], '#f7fafc'),
          section(byId('3').widths, [
            [blurb('People', 'Show up for each other — on stage, in sprints, and in the field.', '🤝')],
            [blurb('Ownership', 'Clear owners, honest demos, finished work.', '🎯')],
            [blurb('Grit & Integrity', 'Ship with care. Tell the truth about tradeoffs.', '⭐')]
          ])
        ];
      } else if (kind === 'mission') {
        sections = [
          section(byId('8-4').widths, [
            [
              heading('Our mission', 1, 'left'),
              text('Help teams ship clearer software and sturdier connections — with pride in the craft and respect for the people who use it.'),
              spacer(),
              text('Team POGI exists to turn chaos into plans people can run: products, projects, coaching, and office networks.')
            ],
            [image(media.mission, 'Mission')]
          ], '#eef6f1'),
          section(byId('2').widths, [
            [blurb('Clarity', 'Scope that fits on one page.', '🧭')],
            [blurb('Delivery', 'Short loops. Visible progress.', '📦')]
          ])
        ];
      } else if (kind === 'projects') {
        sections = [
          section(byId('1').widths, [[
            heading('Projects', 1),
            text('From CMS features to office networks — same discipline, different canvases.', 'center'),
            image(media.projects, 'Projects')
          ]], '#f0f4ff'),
          section(byId('4').widths, [
            [blurb('Web CMS', 'Pages, posts, menus, and themes that editors can trust.', '💻')],
            [blurb('Networks', 'Cabling, connectivity, and installs that just work.', '📡')],
            [blurb('Music & stage', 'Presence, setlists, and energy that move a room.', '🎸')],
            [blurb('Coaching', 'Conversations that unblock people.', '🌱')]
          ])
        ];
      } else if (kind === 'crew') {
        sections = [
          section(byId('1').widths, [[
            heading('The crew', 1),
            image(media.crew, 'Crew'),
            text('Multi-hyphenate builders who share one thread: craft, clarity, and care.', 'center')
          ]]),
          section(byId('4').widths, [
            [blurb('Builders', 'Front-end polish and solid backends.', '🛠️')],
            [blurb('Operators', 'Projects that finish well.', '📋')],
            [blurb('Connectors', 'Networks and community glue.', '🔗')],
            [blurb('Coaches', 'Habits and courage for the next mile.', '❤️')]
          ], '#faf7f2')
        ];
      } else {
        sections = [
          section(byId('3-6-3').widths, [
            [blurb('Facebook', 'Updates, events, and conversation.', '📘')],
            [
              heading('Join Team POGI', 1),
              image(media.join, 'Join Us'),
              spacer(),
              text('Whether you code, coach, cable, or create — if POGI resonates, say hello on Facebook.', 'center'),
              button('Follow Team POGI', facebookUrl),
              button('Browse the blog', '/blog'),
              divider(),
              cta('Ready to contribute?', 'Open a page, ship a layout, or join a network night.', 'See projects', '/p/projects')
            ],
            [blurb('Community', 'facebook.com/teampogi31', '🌐')]
          ], '#f7fafc')
        ];
      }

      api.setLayout({ version: 1, sections: sections });
    })(${JSON.stringify({ kind, media, facebookUrl: FACEBOOK_URL })})`,
    );

    const saveResponse = page.waitForResponse(
      (res) =>
        res.url().includes(`/admin/builder/page/${pageId}`) &&
        res.url().includes('/save') &&
        res.request().method() === 'POST',
      { timeout: 30_000 },
    );
    await page.locator('#cmsBuilderSave').click();
    expect((await saveResponse).ok()).toBeTruthy();
    await expect(page.locator('#cmsBuilderStatus')).toContainText('Saved', { timeout: 15_000 });
  }

  async function savePostLayout(
    page: Page,
    postId: number,
    kind: PostDef['kind'],
    mediaId: number,
    title: string,
  ): Promise<void> {
    await page.goto(`${baseURL}/admin/builder/post/${postId}`);
    await expect(page.locator('#cmsBuilderCanvas')).toBeVisible();
    await page.waitForFunction('!!window.CmsBuilderApi');

    await page.evaluate(
      `(({ kind, mediaId, title }) => {
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
      const image = (id, alt) => ({
        id: uid(), type: 'image',
        data: { media_id: id, url: '', alt: alt || '', link: '' },
        design: { text_align: 'center' }, advanced: {}
      });
      const spacer = () => ({ id: uid(), type: 'spacer', data: { size: 'sm' }, design: {}, advanced: {} });
      const col = (width, modules) => ({ id: uid(), width: width, settings: {}, modules: modules });
      const row = (widths, modulesPerCol) => ({
        id: uid(), settings: {},
        columns: widths.map(function (w, i) { return col(w, modulesPerCol[i] || []); })
      });
      const section = (widths, modulesPerCol, bg) => ({
        id: uid(), type: 'regular',
        settings: { bg_color: bg || '', padding: '1.5rem 0', css_class: '' },
        rows: [row(widths, modulesPerCol)]
      });

      const bodies = {
        intro: 'Team POGI is a crew of builders, musicians, coaches, and network installers who share one standard: do the work with pride.',
        public: 'Building in public means sharing unfinished thoughts, demos, and lessons — so the next person ships faster than we did.',
        network: 'Network night is where theory meets cable ties. We label ports, test links, and leave the office better than we found it.',
        craft: 'Complexity is easy. Craft is choosing the simple path that still solves the real problem — then documenting it for the next teammate.',
        ship: 'We ship with owners, checklists, and demos. If it is not visible, it is not done. That is the Team POGI loop.'
      };

      api.setLayout({
        version: 1,
        sections: [
          section([12], [[
            heading(title, 1),
            spacer(),
            image(mediaId, title),
            spacer(),
            text(bodies[kind] || bodies.intro),
            spacer(),
            text('— Team POGI')
          ]], '#fafafa')
        ]
      });
    })(${JSON.stringify({ kind, mediaId, title })})`,
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

  async function rebuildMenu(
    page: Page,
    pages: Array<{ title: string; menuLabel: string }>,
  ): Promise<void> {
    await page.goto(`${baseURL}/admin/menus`);
    await expect(page.locator('#menuForm')).toBeVisible();
    const rows = page.locator('#menuItemsTable tbody tr.menu-item-row');
    while ((await rows.count()) > 1) {
      await rows.last().locator('.menu-remove-row').click();
    }

    async function fillRow(
      index: number,
      opts: { label: string; type: string; pageTitle?: string; customUrl?: string },
    ): Promise<void> {
      const row = rows.nth(index);
      await row.locator('input[name="item_label[]"]').fill(opts.label);
      await row.locator('select[name="item_type[]"]').selectOption(opts.type);
      await page.waitForTimeout(200);
      if (opts.type === 'page' && opts.pageTitle) {
        const select = row.locator('select[name="item_object_id[]"]');
        const option = select.locator('option', { hasText: opts.pageTitle });
        await expect(option.first()).toBeAttached({ timeout: 10_000 });
        const value = await option.first().getAttribute('value');
        expect(value).toBeTruthy();
        await select.selectOption(value!);
      }
      if (opts.type === 'custom' && opts.customUrl) {
        const urlInput = row.locator('input[name="item_custom_url[]"]:not([type="hidden"])');
        await expect(urlInput).toBeVisible({ timeout: 10_000 });
        await urlInput.fill(opts.customUrl);
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
    await page.locator('#menuAddRow').click();
    await fillRow(2 + pages.length, {
      label: 'Facebook',
      type: 'custom',
      customUrl: FACEBOOK_URL,
    });

    await Promise.all([
      page.waitForURL(/\/admin\/menus/, { timeout: 30_000 }),
      page.locator('#menuForm button[type="submit"]').click(),
    ]);
    await expect(page.getByText('Menu saved.')).toBeVisible({ timeout: 15_000 });
  }

  async function activateEnterpriseTheme(page: Page): Promise<void> {
    await page.goto(`${baseURL}/admin/system/general`);
    const installBtn = page.getByRole('button', { name: 'Enterprise (modern navy & slate)' });
    await expect(installBtn).toBeVisible({ timeout: 15_000 });
    await Promise.all([
      page.waitForURL(/\/admin\/system\/general/, { timeout: 60_000 }),
      installBtn.click(),
    ]);
    await expect(page.locator('#cmsStylePackStatus')).toContainText('Enterprise', { timeout: 15_000 });
  }

  async function configureSite(page: Page, homePageTitle: string): Promise<void> {
    await page.goto(`${baseURL}/admin/system/general`);
    await expect(page.locator('input[name="app_name"]')).toBeVisible({ timeout: 30_000 });
    await page.fill('input[name="app_name"]', 'Team POGI');
    await page.fill('input[name="company_name"]', 'People · Ownership · Grit · Integrity');
    await page.locator('select[name="reading_show_on_front"]').selectOption('page');
    const homeSelect = page.locator('select[name="reading_page_on_front"]');
    const homeOption = homeSelect.locator('option', { hasText: homePageTitle });
    await expect(homeOption.first()).toBeAttached({ timeout: 15_000 });
    const homeValue = await homeOption.first().getAttribute('value');
    expect(homeValue).toBeTruthy();
    await homeSelect.selectOption(homeValue!);
    if (await page.locator('input[name="seo_facebook_url"]').count()) {
      await page.fill('input[name="seo_facebook_url"]', FACEBOOK_URL);
    }
    await Promise.all([
      page.waitForURL(/\/admin\/system\/general/, { timeout: 60_000 }),
      page.locator('#generalSettingsForm button[type="submit"]').click(),
    ]);
    await expect(page.getByText('General settings saved.')).toBeVisible({ timeout: 15_000 });
  }

  test('seed Team POGI site with 5 pages, 5 posts, free images, and menu', async ({
    page,
    request,
  }) => {
    const localFiles = await downloadFreeImages(request);
    await login(page);

    const mediaIds = new Map<string, number>();
    for (const asset of FREE_IMAGES) {
      const filePath = localFiles.get(asset.key);
      expect(filePath).toBeTruthy();
      const id = await uploadMedia(page, filePath!, asset.alt);
      mediaIds.set(asset.key, id);
      console.log(`Uploaded media #${id} (${asset.key})`);
    }

    const createdPages: Array<{ id: number; title: string; menuLabel: string; slug: string }> = [];
    for (const def of PAGES) {
      const id = await ensurePublishedPage(page, def);
      await savePageLayout(page, id, def.kind, mediaIds);
      createdPages.push({ id, title: def.title, menuLabel: def.menuLabel, slug: def.slug });
      console.log(`Page ready: ${def.title} (#${id})`);
    }

    for (const def of POSTS) {
      const featuredId = mediaIds.get(def.imageKey)!;
      const id = await ensurePublishedPost(page, def, featuredId);
      await savePostLayout(page, id, def.kind, featuredId, def.title);
      console.log(`Post ready: ${def.title} (#${id})`);
    }

    await rebuildMenu(page, createdPages);
    const homePage = createdPages.find((p) => p.slug === 'team-pogi')!;
    await configureSite(page, homePage.title);
    await activateEnterpriseTheme(page);

    await page.goto(`${baseURL}/`);
    const nav = page.locator('nav.public-nav[aria-label="Public"]');
    await expect(nav.getByRole('link', { name: 'Home' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Blog' })).toBeVisible();
    for (const item of createdPages) {
      await expect(nav.getByRole('link', { name: item.menuLabel })).toBeVisible();
    }
    await expect(nav.getByRole('link', { name: 'Facebook' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Team POGI', level: 1 })).toBeVisible({
      timeout: 20_000,
    });
    await expect(page.locator('.cms-layout img, .cms-mod-image img').first()).toBeVisible({
      timeout: 20_000,
    });
    await expect(page.getByText('People · Ownership · Grit · Integrity').first()).toBeVisible();

    await page.goto(`${baseURL}/blog`);
    for (const post of POSTS) {
      await expect(
        page.locator(`a[href="/blog/${post.slug}"]`).filter({ hasText: post.title }).first(),
      ).toBeVisible({ timeout: 20_000 });
    }

    console.log(`Team POGI site ready at ${baseURL}/ (Facebook: ${FACEBOOK_URL})`);
  });
});
