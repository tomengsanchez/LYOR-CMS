import { test, expect, type Page } from '@playwright/test';
import { adminPass, adminUser, baseURL } from './support/config';

/**
 * Headed sample: publish a Tomeng Sanchez post built with every column layout
 * in the visual frontend builder.
 */
test.describe('Sample post — Tomeng Sanchez visual builder', () => {
  test.setTimeout(180_000);

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

  test('create published post with all column layouts', async ({ page }) => {
    const stamp = Date.now().toString(36);
    const title = `Tomeng Sanchez - Musician, Builder, Coach (${stamp})`;
    const slug = `tomeng-sanchez-${stamp}`;

    await login(page);

    await page.goto(`${baseURL}/admin/posts/create`);
    await expect(page.locator('#postForm')).toBeVisible();
    await expect(page.locator('h2')).toContainText('Add Post');

    await page.fill('#postForm input[name="title"]', title);
    await page.fill('#postForm input[name="slug"]', slug);
    await page.selectOption('#postForm select[name="status"]', 'published');
    await page.fill(
      '#postForm textarea[name="excerpt"]',
      'Musician, Web Developer, Project Manager, Life Coach, Team POGI & Office Network Installer.',
    );
    await page.fill('#postForm input[name="tags"]', 'tomeng, musician, web-developer, life-coach, team-pogi');

    await Promise.all([
      page.waitForURL(/\/admin\/posts\/(view|edit)\/\d+/, { timeout: 60_000 }),
      page.locator('#postForm button[type="submit"]').click(),
    ]);

    const idMatch = page.url().match(/\/admin\/posts\/(?:view|edit)\/(\d+)/);
    expect(idMatch).toBeTruthy();
    const postId = idMatch![1];

    await page.goto(`${baseURL}/admin/posts/edit/${postId}`);
    await expect(page.getByRole('link', { name: 'Edit via Frontend editor' })).toBeVisible();
    await page.getByRole('link', { name: 'Edit via Frontend editor' }).click();
    await page.waitForURL(new RegExp(`/admin/builder/post/${postId}`));
    await expect(page.locator('#cmsBuilderCanvas')).toBeVisible();
    await expect(page.locator('#cmsBuilderSave')).toBeVisible();
    await page.waitForFunction('!!window.CmsBuilderApi');

    await page.evaluate(`(() => {
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
      const cta = (title, body, label) => ({
        id: uid(), type: 'cta',
        data: { title: title, text: body, label: label, url: '#' },
        design: {}, advanced: {}
      });
      const spacer = () => ({ id: uid(), type: 'spacer', data: { size: 'md' }, design: {}, advanced: {} });
      const divider = () => ({ id: uid(), type: 'divider', data: { style: 'solid' }, design: {}, advanced: {} });
      const col = (width, modules) => ({ id: uid(), width: width, settings: {}, modules: modules });
      const row = (widths, modulesPerCol) => ({
        id: uid(), settings: {},
        columns: widths.map(function (w, i) { return col(w, modulesPerCol[i] || []); })
      });
      const section = (label, widths, modulesPerCol, bg) => ({
        id: uid(), type: 'regular',
        settings: { bg_color: bg || '', padding: '2rem 0', css_class: '' },
        rows: [
          row([12], [[heading('Layout: ' + label, 3), spacer()]]),
          row(widths, modulesPerCol)
        ]
      });
      const layouts = api.columnLayouts;
      const byId = (id) => layouts.find(function (l) { return l.id === id; });

      api.setLayout({
        version: 1,
        sections: [
          section(byId('1').label, byId('1').widths, [[
            heading('Tomeng Sanchez', 1),
            text('Musician · Web Developer · Project Manager · Life Coach · Team POGI & Office Network Installer.\\n\\nA multi-hyphenate professional who builds people, products, and networks — on stage, in code, and in the office.', 'center'),
            divider()
          ]], '#f7fafc'),
          section(byId('2').label, byId('2').widths, [
            [blurb('Musician', 'Tomeng brings rhythm and presence to every set — crafting songs and live energy that move a room.', '🎸')],
            [blurb('Web Developer', 'From front-end polish to solid backends, Tomeng ships practical web products with care and craft.', '💻')]
          ]),
          section(byId('3').label, byId('3').widths, [
            [blurb('Project Manager', 'Keeps scope clear, teams aligned, and delivery honest — turning chaos into a plan people can run.', '📋')],
            [blurb('Life Coach', 'Helps people clarify goals, build habits, and show up with confidence in work and life.', '🌱')],
            [blurb('Team POGI', 'Proud collaborator with Team POGI — community, camaraderie, and getting good work done together.', '🤝')]
          ], '#eef6f1'),
          section(byId('4').label, byId('4').widths, [
            [blurb('Create', 'Music and ideas that feel alive.', '✨')],
            [blurb('Build', 'Web systems that people can trust.', '🛠️')],
            [blurb('Lead', 'Projects that finish well.', '🎯')],
            [blurb('Serve', 'Coaching and community first.', '❤️')]
          ]),
          section(byId('8-4').label, byId('8-4').widths, [
            [
              heading('Office Network Installer', 2),
              text('Tomeng also works hands-on with office networks — cabling, connectivity, and making sure teams stay online. The same discipline that shapes a setlist or a sprint plan shows up when a switch, AP, or workstation needs to just work.')
            ],
            [cta('Need a connector?', 'Music, web, projects, coaching, or networks — Tomeng bridges the gap.', 'Say hello')]
          ]),
          section(byId('4-8').label, byId('4-8').widths, [
            [blurb('One thread', 'Creativity, systems, and people — always connected.', '🔗')],
            [
              heading('How Tomeng shows up', 2),
              text('Whether producing a track, shipping a CMS feature, running a project board, coaching someone through a stuck season, or installing an office network with Team POGI energy — Tomeng Sanchez brings the same mix of craft, clarity, and care.')
            ]
          ], '#faf7f2'),
          section(byId('9-3').label, byId('9-3').widths, [
            [
              heading('A career in many keys', 2),
              text('Musician by heart. Web developer by craft. Project manager by discipline. Life coach by calling. Team POGI collaborator and office network installer by practical grit. Different roles — one person who keeps learning and helping.')
            ],
            [spacer(), blurb('POGI', 'People · Ownership · Grit · Integrity', '⭐')]
          ]),
          section(byId('3-9').label, byId('3-9').widths, [
            [button('Connect with Tomeng', '#')],
            [
              heading('At a glance', 3),
              text('• Musician — performance & creative direction\\n• Web Developer — full-stack delivery\\n• Project Manager — planning & shipping\\n• Life Coach — growth conversations\\n• Team POGI — collaborative culture\\n• Office Network Installer — reliable connectivity')
            ]
          ]),
          section(byId('3-6-3').label, byId('3-6-3').widths, [
            [blurb('Stage', 'Show up. Play true.', '🎤')],
            [
              heading('Thanks for reading', 2),
              text('This sample layout walks every column style in the visual builder — built for Tomeng Sanchez, a musician, web developer, project manager, life coach, Team POGI member, and office network installer.', 'center'),
              divider()
            ],
            [blurb('Network', 'Wire it. Own it.', '📡')]
          ], '#f0f4ff')
        ]
      });
    })()`);

    await expect(page.getByText('Layout: 1/4 + 1/2 + 1/4').first()).toBeVisible();

    const saveResponse = page.waitForResponse(
      (res) => res.url().includes('/admin/builder/post/') && res.url().includes('/save') && res.request().method() === 'POST',
      { timeout: 30_000 },
    );
    await page.locator('#cmsBuilderSave').click();
    const saved = await saveResponse;
    expect(saved.ok()).toBeTruthy();
    await expect(page.locator('#cmsBuilderStatus')).toContainText('Saved', { timeout: 15_000 });

    const previewHref = await page.locator('a', { hasText: 'Preview' }).getAttribute('href');
    const publicPath = previewHref && previewHref.startsWith('/') ? previewHref : `/blog/${slug}`;
    await page.goto(`${baseURL}${publicPath}`);
    await expect(page.locator('.cms-layout')).toBeVisible();
    await expect(page.getByText('Tomeng Sanchez').first()).toBeVisible();
    await expect(page.locator('.cms-mod-blurb-title').filter({ hasText: 'Musician' })).toBeVisible();
    await expect(page.locator('.cms-mod-blurb-title').filter({ hasText: 'Web Developer' })).toBeVisible();
    await expect(page.locator('.cms-mod-blurb-title').filter({ hasText: 'Project Manager' })).toBeVisible();
    await expect(page.locator('.cms-mod-blurb-title').filter({ hasText: 'Life Coach' })).toBeVisible();
    await expect(page.locator('.cms-mod-blurb-title').filter({ hasText: 'Team POGI' })).toBeVisible();
    await expect(page.getByText('Office Network Installer').first()).toBeVisible();
    await expect(page.getByText('Layout: 1 column')).toBeVisible();
    await expect(page.getByText('Layout: 2 equal')).toBeVisible();
    await expect(page.getByText('Layout: 3 equal')).toBeVisible();
    await expect(page.getByText('Layout: 4 equal')).toBeVisible();
    await expect(page.getByText('Layout: 2/3 + 1/3')).toBeVisible();
    await expect(page.getByText('Layout: 1/3 + 2/3')).toBeVisible();
    await expect(page.getByText('Layout: 3/4 + 1/4')).toBeVisible();
    await expect(page.getByText('Layout: 1/4 + 3/4')).toBeVisible();
    await expect(page.getByText('Layout: 1/4 + 1/2 + 1/4')).toBeVisible();

    console.log(`Published sample post: ${baseURL}${publicPath}`);
  });
});
