import { test, expect, type Page, type APIRequestContext } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { adminPass, adminUser, baseURL } from './support/config';

/**
 * Fresh Tomeng Sanchez personal site (after truncate): pages, posts, menu, branding.
 * Facebook: https://www.facebook.com/tomeng.sanchez
 *
 * Run headed: npm run test:e2e:cms-tomeng-site
 */

const FACEBOOK_URL = 'https://www.facebook.com/tomeng.sanchez';

type MediaAsset = { key: string; picsumId: number; w: number; h: number; alt: string; file: string };

const FREE_IMAGES: MediaAsset[] = [
  { key: 'home', picsumId: 1015, w: 1400, h: 800, alt: 'Tomeng Sanchez home', file: 'tomeng-home.jpg' },
  { key: 'about', picsumId: 338, w: 1200, h: 800, alt: 'About Tomeng', file: 'tomeng-about.jpg' },
  { key: 'work', picsumId: 1036, w: 1200, h: 800, alt: 'Work and craft', file: 'tomeng-work.jpg' },
  { key: 'messages', picsumId: 1011, w: 1200, h: 800, alt: 'Messages and preaching', file: 'tomeng-messages.jpg' },
  { key: 'connect', picsumId: 1060, w: 1200, h: 800, alt: 'Connect with Tomeng', file: 'tomeng-connect.jpg' },
  { key: 'post1', picsumId: 201, w: 1200, h: 630, alt: 'Weight of one decision', file: 'tomeng-post-1.jpg' },
  { key: 'post2', picsumId: 292, w: 1200, h: 630, alt: 'Build with craft', file: 'tomeng-post-2.jpg' },
  { key: 'post3', picsumId: 367, w: 1200, h: 630, alt: 'Music and focus', file: 'tomeng-post-3.jpg' },
  { key: 'post4', picsumId: 433, w: 1200, h: 630, alt: 'Networks that last', file: 'tomeng-post-4.jpg' },
  { key: 'post5', picsumId: 544, w: 1200, h: 630, alt: 'Coaching notes', file: 'tomeng-post-5.jpg' },
];

type PageDef = {
  title: string;
  slug: string;
  menuLabel: string;
  kind: 'home' | 'about' | 'work' | 'messages' | 'connect';
  imageKey: string;
  metaDescription: string;
  llmSummary: string;
  citation: string;
};

const PAGES: PageDef[] = [
  {
    title: 'Tomeng Sanchez',
    slug: 'tomeng-sanchez',
    menuLabel: 'Home',
    kind: 'home',
    imageKey: 'home',
    metaDescription: 'Official site of Tomeng Sanchez — musician, builder, coach, and communicator.',
    llmSummary:
      'Tomeng Sanchez is a musician, web developer, project manager, life coach, and network installer who writes and speaks about faith, craft, and everyday decisions.',
    citation:
      'Tomeng Sanchez builds, coaches, and communicates — connecting music, technology, and life-giving messages.',
  },
  {
    title: 'About Tomeng',
    slug: 'about-tomeng',
    menuLabel: 'About',
    kind: 'about',
    imageKey: 'about',
    metaDescription: 'Who Tomeng Sanchez is: musician, developer, coach, and Team POGI collaborator.',
    llmSummary:
      'About Tomeng Sanchez: a multi-hyphenate creator who ships websites, installs office networks, coaches people, and shares messages that help others choose wisely.',
    citation: 'Tomeng Sanchez is a musician, web developer, project manager, life coach, and office network installer.',
  },
  {
    title: 'Work & Craft',
    slug: 'work-and-craft',
    menuLabel: 'Work',
    kind: 'work',
    imageKey: 'work',
    metaDescription: 'Web development, project management, and network installs by Tomeng Sanchez.',
    llmSummary:
      'Tomeng’s work covers web development, project delivery, office network installs, and practical craftsmanship with Team POGI.',
    citation: 'Tomeng Sanchez ships reliable web systems and office networks with clear ownership and craft.',
  },
  {
    title: 'Messages',
    slug: 'messages',
    menuLabel: 'Messages',
    kind: 'messages',
    imageKey: 'messages',
    metaDescription: 'Preaching and life messages from Tomeng Sanchez on decisions, character, and faith.',
    llmSummary:
      'Messages from Tomeng Sanchez explore faith, character, and the weight of everyday decisions — including teaching from Romans 5 on how small choices shape destiny.',
    citation: 'Tomeng Sanchez teaches that one small decision can shape habits, character, destiny, and a generation.',
  },
  {
    title: 'Connect',
    slug: 'connect',
    menuLabel: 'Connect',
    kind: 'connect',
    imageKey: 'connect',
    metaDescription: 'Follow Tomeng Sanchez on Facebook and get in touch.',
    llmSummary:
      'Connect with Tomeng Sanchez on Facebook at facebook.com/tomeng.sanchez for updates, messages, and conversation.',
    citation: 'Follow Tomeng Sanchez on Facebook: https://www.facebook.com/tomeng.sanchez',
  },
];

type PostDef = {
  title: string;
  slug: string;
  excerpt: string;
  tags: string;
  imageKey: string;
  kind: 'decision' | 'craft' | 'music' | 'network' | 'coach';
  citation: string;
  faq: Array<{ question: string; answer: string }>;
};

const POSTS: PostDef[] = [
  {
    title: 'The Weight of One Decision',
    slug: 'weight-of-one-decision',
    excerpt: 'From Romans 5:12, 19 — small daily choices become habits, character, and destiny.',
    tags: 'tomeng, preaching, faith, decisions',
    imageKey: 'post1',
    kind: 'decision',
    citation: 'One small decision can shape habits, character, destiny, and even a generation—so choose wisely.',
    faq: [
      {
        question: 'What is the main point of this message?',
        answer:
          'Small everyday decisions matter as much as big ones because they become habits, form character, and shape destiny for you and others.',
      },
      {
        question: 'Which scripture does Tomeng teach from?',
        answer: 'Romans 5:12 and 19 — how one person’s choice can affect many.',
      },
    ],
  },
  {
    title: 'Craft Over Complexity',
    slug: 'craft-over-complexity',
    excerpt: 'Why Tomeng prefers simple systems people can trust over clever ones they cannot.',
    tags: 'tomeng, web-development, craft',
    imageKey: 'post2',
    kind: 'craft',
    citation: 'Craft is choosing the simple path that still solves the real problem — then documenting it.',
    faq: [
      {
        question: 'What does craft mean for Tomeng’s web work?',
        answer: 'Clear ownership, short feedback loops, and systems teammates can understand and maintain.',
      },
    ],
  },
  {
    title: 'Music, Focus, and Showing Up',
    slug: 'music-focus-showing-up',
    excerpt: 'How music trains presence — and why showing up daily beats waiting for inspiration.',
    tags: 'tomeng, music, life',
    imageKey: 'post3',
    kind: 'music',
    citation: 'Music teaches Tomeng Sanchez to show up daily: presence and practice beat waiting for inspiration.',
    faq: [
      {
        question: 'How does music shape Tomeng’s approach to work?',
        answer: 'Practice, listening, and consistency — the same discipline that builds songs builds products and character.',
      },
    ],
  },
  {
    title: 'Networks That Last',
    slug: 'networks-that-last',
    excerpt: 'Office installs with labels, tests, and honesty — leave every site better than you found it.',
    tags: 'tomeng, networks, team-pogi',
    imageKey: 'post4',
    kind: 'network',
    citation: 'A lasting office network is labeled, tested, and owned — leave every site better than you found it.',
    faq: [
      {
        question: 'What is Tomeng’s network install standard?',
        answer: 'Label ports, test links, document the layout, and hand over something the next person can trust.',
      },
    ],
  },
  {
    title: 'Notes from Coaching Conversations',
    slug: 'coaching-conversation-notes',
    excerpt: 'Life coaching is less advice, more honest questions that help people choose their next step.',
    tags: 'tomeng, coaching, growth',
    imageKey: 'post5',
    kind: 'coach',
    citation: 'Coaching with Tomeng Sanchez is honest questions that help people choose their next faithful step.',
    faq: [
      {
        question: 'What is Tomeng’s coaching style?',
        answer: 'Fewer lectures, more clarifying questions — so people own the decision and the follow-through.',
      },
    ],
  },
];

test.describe('Tomeng Sanchez website', () => {
  test.setTimeout(600_000);

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
    const dir = path.join(process.cwd(), 'test-results', 'tomeng-site-images');
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
    }
    return paths;
  }

  async function uploadMedia(page: Page, filePath: string, alt: string): Promise<number> {
    await page.goto(`${baseURL}/admin/media`);
    await expect(page.locator('form[action*="media/upload"]')).toBeVisible();
    await page.locator('input[name="file"]').setInputFiles(filePath);
    await page.locator('input[name="alt_text"]').fill(alt);
    await Promise.all([
      page.waitForURL(/\/admin\/media/, { timeout: 60_000 }),
      page.locator('form[action*="media/upload"] button[type="submit"]').click(),
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
    await page.fill('#pageForm textarea[name="meta_description"]', def.metaDescription);
    await page.fill('#pageForm textarea[name="llm_summary"]', def.llmSummary);
    if (await page.locator('#citationSnippet').count()) {
      await page.fill('#citationSnippet', def.citation);
    }

    await Promise.all([
      page.waitForURL(/\/admin\/pages\/(view|edit)\/\d+/, { timeout: 60_000 }),
      page.getByRole('button', { name: 'Save' }).click(),
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
    await page.fill('#postForm textarea[name="llm_summary"]', def.excerpt);
    if (await page.locator('#citationSnippet').count()) {
      await page.fill('#citationSnippet', def.citation);
      while ((await page.locator('.faq-row').count()) > 0) {
        await page.locator('.faq-row .faq-remove').first().click();
      }
      for (let i = 0; i < def.faq.length; i++) {
        await page.locator('#faqAddRow').click();
        await page.locator('.faq-row .faq-q').nth(i).fill(def.faq[i].question);
        await page.locator('.faq-row .faq-a').nth(i).fill(def.faq[i].answer);
      }
    }

    const cat = page.locator('#postForm select[name="category_id"]');
    const general = cat.locator('option', { hasText: 'General' });
    if ((await general.count()) > 0) {
      const val = await general.first().getAttribute('value');
      if (val) await cat.selectOption(val);
    }

    await Promise.all([
      page.waitForURL(/\/admin\/posts\/(view|edit)\/\d+/, { timeout: 60_000 }),
      page.getByRole('button', { name: 'Save' }).click(),
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
      `(({ kind, media, facebook }) => {
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
            heading('Tomeng Sanchez', 1),
            text('Musician · Builder · Coach · Communicator', 'center'),
            spacer(),
            image(media.home, 'Tomeng Sanchez'),
            spacer(),
            text('Welcome. This is my space for messages, craft, music, and the work of helping people choose wisely — online and offline.', 'center'),
            spacer(),
            button('Read the blog', '/blog'),
            button('Follow on Facebook', facebook)
          ]], '#ffffff'),
          section(byId('3').widths, [
            [blurb('Music', 'Songs and presence that keep me grounded.', '♪')],
            [blurb('Build', 'Websites, projects, and networks that last.', '⚙')],
            [blurb('Coach', 'Honest questions for your next step.', '★')]
          ], '#fafafa'),
          section(byId('1').widths, [[
            cta('Stay in the conversation', 'Follow updates and messages on Facebook.', 'Facebook', facebook)
          ]])
        ];
      } else if (kind === 'about') {
        sections = [
          section(byId('1').widths, [[
            heading('About Tomeng', 1),
            spacer(),
            image(media.about, 'About Tomeng Sanchez'),
            spacer(),
            text('I am Tomeng Sanchez — a musician, web developer, project manager, life coach, Team POGI collaborator, and office network installer.'),
            text('I care about craft you can trust, conversations that change direction, and faith that shows up in ordinary decisions.'),
            spacer(),
            button('Connect on Facebook', facebook)
          ]])
        ];
      } else if (kind === 'work') {
        sections = [
          section(byId('1').widths, [[
            heading('Work & Craft', 1),
            spacer(),
            image(media.work, 'Work and craft'),
            spacer(),
            text('I ship websites, manage delivery, and install office networks with clear labels, tests, and ownership.'),
          ]], '#ffffff'),
          section(byId('2').widths, [
            [blurb('Web & CMS', 'Clean sites people can actually maintain.', '⌘')],
            [blurb('Networks', 'Ports labeled, links tested, handoff documented.', '⌁')]
          ], '#fafafa')
        ];
      } else if (kind === 'messages') {
        sections = [
          section(byId('1').widths, [[
            heading('Messages', 1),
            spacer(),
            image(media.messages, 'Messages'),
            spacer(),
            text('I teach and write about faith, character, and the weight of everyday choices — including messages from Romans 5 on how one decision can shape a generation.'),
            spacer(),
            button('See latest posts', '/blog')
          ]])
        ];
      } else {
        sections = [
          section(byId('1').widths, [[
            heading('Connect', 1),
            spacer(),
            image(media.connect, 'Connect'),
            spacer(),
            text('The best place to follow along day to day is Facebook.'),
            spacer(),
            button('facebook.com/tomeng.sanchez', facebook),
            spacer(),
            text('Say hello, share a story, or drop a note about a message that helped you.')
          ]])
        ];
      }

      api.setLayout({ version: 1, sections: sections });
    })(${JSON.stringify({ kind, media, facebook: FACEBOOK_URL })})`,
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
      const bodies = {
        decision: 'Most of us think only the big decisions matter. Romans 5 reminds us that one choice can ripple through many lives. Small daily decisions become habits; habits become character; character becomes destiny. Choose wisely.',
        craft: 'Complexity is easy. Craft is choosing the simple path that still solves the real problem — then documenting it for the next teammate. That is how I try to build websites and systems.',
        music: 'Music keeps me honest about practice. You cannot fake presence. Showing up to the instrument — and to the work — beats waiting for a perfect mood.',
        network: 'A good office network is invisible when it works and obvious when it does not. Label the ports, test the links, and leave the site better than you found it.',
        coach: 'Coaching is less about giving answers and more about asking the question that unlocks the next faithful step. People change when they own the decision.'
      };
      api.setLayout({
        version: 1,
        sections: [
          section([12], [[
            heading(title, 1),
            spacer(),
            image(mediaId, title),
            spacer(),
            text(bodies[kind] || bodies.decision),
            spacer(),
            text('— Tomeng Sanchez')
          ]], '#ffffff')
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
      // Menus JS swaps the target cell after type change.
      await page.waitForTimeout(200);
      if (opts.type === 'page' && opts.pageTitle) {
        const select = row.locator('select[name="item_object_id[]"]');
        await expect(select).toBeVisible({ timeout: 10_000 });
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

    await fillRow(0, { label: pages[0].menuLabel, type: 'page', pageTitle: pages[0].title });
    await page.locator('#menuAddRow').click();
    await fillRow(1, { label: 'Blog', type: 'blog' });
    for (let i = 1; i < pages.length; i++) {
      await page.locator('#menuAddRow').click();
      await fillRow(1 + i, {
        label: pages[i].menuLabel,
        type: 'page',
        pageTitle: pages[i].title,
      });
    }
    await page.locator('#menuAddRow').click();
    await fillRow(1 + pages.length, {
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

  async function configureSite(page: Page, homePageId: number): Promise<void> {
    await page.goto(`${baseURL}/admin/system/general`);
    await expect(page.locator('input[name="app_name"]')).toBeVisible({ timeout: 30_000 });
    await page.fill('input[name="app_name"]', 'Tomeng Sanchez');
    await page.fill('input[name="company_name"]', 'Musician · Builder · Coach');
    const front = page.locator('select[name="reading_show_on_front"]');
    if ((await front.count()) > 0) {
      await front.selectOption('page');
    }
    const homeSelect = page.locator('select[name="reading_page_on_front"]');
    if ((await homeSelect.count()) > 0) {
      await homeSelect.selectOption(String(homePageId));
    }
    if (await page.locator('input[name="seo_facebook_url"]').count()) {
      await page.fill('input[name="seo_facebook_url"]', FACEBOOK_URL);
    }
    if (await page.locator('#pubApplyEditorialPack').count()) {
      await page.locator('#pubApplyEditorialPack').check();
    }
    if (await page.locator('input[name="pub_theme_blog_kicker"]').count()) {
      await page.fill('input[name="pub_theme_blog_kicker"]', "HERE'S WHAT'S NEW");
    }
    const perPage = page.locator('input[name="reading_posts_per_page"], select[name="reading_posts_per_page"]');
    if ((await perPage.count()) > 0) {
      const tag = await perPage.first().evaluate((el) => el.tagName.toLowerCase());
      if (tag === 'select') {
        await perPage.first().selectOption({ index: 0 }).catch(() => undefined);
      } else {
        await perPage.first().fill('12');
      }
    }
    await Promise.all([
      page.waitForURL(/\/admin\/system\/general/, { timeout: 60_000 }),
      page.locator('form[action*="system/general/save"] button[type="submit"]').click(),
    ]);
  }

  test('build Tomeng Sanchez site with pages, posts, menu, branding', async ({ page, request }) => {
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
    const homeId = createdPages.find((p) => p.slug === 'tomeng-sanchez')!.id;
    await configureSite(page, homeId);

    await page.goto(`${baseURL}/`);
    const nav = page.locator('nav.public-nav[aria-label="Public"]');
    await expect(nav.getByRole('link', { name: 'About' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Work' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Messages' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Connect' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Facebook' })).toBeVisible();
    await expect(page.getByText('Tomeng Sanchez').first()).toBeVisible();

    await page.goto(`${baseURL}/blog`);
    for (const post of POSTS) {
      await expect(page.getByRole('link', { name: post.title }).first()).toBeVisible({
        timeout: 20_000,
      });
    }

    console.log(`Tomeng Sanchez site ready at ${baseURL}/ (Facebook: ${FACEBOOK_URL})`);
  });
});
