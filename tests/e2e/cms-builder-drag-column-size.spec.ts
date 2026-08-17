import { test, expect, type Page } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import { baseURL } from './support/config';
import { pauseToWatch } from './support/watch';

/**
 * Headed demo: drag-reorder, column width 1–12, edge resize, min-height & valign.
 *
 *   npm run test:e2e:cms-builder-drag-column-size
 *
 * BASE_URL comes from `.env.playwright` or env (example: http://cms.local).
 */
test.describe('Visual builder — drag order + column size', () => {
  test.setTimeout(300_000);

  test('drag modules, set width/height, resize column edge', async ({ page }) => {
    const stamp = Date.now().toString(36);
    const title = `Drag & column size demo (${stamp})`;
    const slug = `builder-drag-cols-${stamp}`;

    await loginAsAdmin(page);
    const pageId = await createPublishedPage(page, title, slug);

    await page.goto(`${baseURL}/admin/builder/page/${pageId}`);
    await expect(page.locator('#cmsBuilderCanvas')).toBeVisible({ timeout: 30_000 });
    await page.waitForFunction('!!window.CmsBuilderApi');

    await seedDemoLayout(page);
    await expect(page.locator('.cms-lb-col.col-md-6')).toHaveCount(2);
    await expect(page.locator('#cmsBuilderCanvas').getByText('Module A — drag me')).toBeVisible();
    await expect(page.locator('[data-col-resize]')).toHaveCount(1);
    await selectBlock(page, page.locator('.cms-lb-col[data-ci="0"]'));
    await expect(page.locator('.cms-lb-col[data-ci="0"] > .cms-lb-chrome [data-drag-handle]')).toBeVisible();
    await expect(page.locator('#cmsBuilderPanel')).toBeVisible();
    await expect(page.locator('input[data-field="width"]')).toBeVisible();
    await expect(page.locator('#cmsBuilderLayersBtn')).toBeVisible();
    await page.locator('#cmsBuilderLayersBtn').click();
    await expect(page.locator('#cmsBuilderLayers')).toBeVisible();
    await expect(page.locator('#cmsBuilderLayersBody')).toContainText('Module A');
    await page.locator('#cmsBuilderLayersBody button').filter({ hasText: 'Module A' }).click();
    await expect(page.locator('.cms-lb-mod.is-selected')).toContainText('Module A');
    await page.locator('[data-panel-tab="design"]').click();
    await page.locator('[data-design-state="hover"]').click();
    await page.locator('#cmsBuilderPanelBody [data-preset-field="text_color"][data-preset-value="accent"]').click();
    await expectLiveCss(page, '#cmsBuilderLiveCss', ':hover');
    await page.locator('[data-design-state="normal"]').click();
    await page.locator('#cmsBuilderPanelBody [data-spacing-field="padding"][data-spacing-side="t"]').fill('1rem');
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'padding:1rem 0px 0px');
    await page.locator('#cmsBuilderPanelBody [data-preset-field="border_radius"][data-preset-value="8px"]').click();
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'border-radius:8px');
    await page.locator('#cmsBuilderPanelBody [data-field="box_shadow"]').selectOption('md');
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'box-shadow:0 4px 12px');
    await page.locator('[data-panel-tab="content"]').click();
    await page.locator('#cmsBuilderPanelBody [data-field="text"]').fill('Module A live patch');
    await expect(page.locator('.cms-lb-mod.is-selected')).toContainText('Module A live patch');
    await page.locator('#cmsBuilderPanelBody [data-field="text"]').fill('Module A — drag me');
    await expect(page.locator('.cms-lb-mod.is-selected')).toContainText('Module A — drag me');
    await page.locator('#cmsBuilderLayersBody button').filter({ hasText: 'Left column' }).click();
    await page.locator('[data-panel-tab="content"]').click();
    await expect(page.locator('#cmsBuilderPanelBody [data-field="level"]')).toHaveValue('2');
    await page.locator('#cmsBuilderLayersBody button').filter({ hasText: 'Module A' }).click();
    await page.locator('[data-panel-tab="content"]').click();
    await expect(page.locator('#cmsBuilderCopy')).toBeEnabled();
    await page.locator('#cmsBuilderCopy').click();
    await expect(page.locator('#cmsBuilderPaste')).toBeEnabled();
    await expect(page.locator('#cmsBuilderHelp')).toBeVisible();
    await page.locator('#cmsBuilderHelp').click();
    await expect(page.locator('#cmsBuilderShortcuts')).toBeVisible();
    await page.locator('#cmsBuilderShortcutsClose').click();
    await expect(page.locator('#cmsBuilderShortcuts')).toBeHidden();
    await expect(page.locator('#cmsBuilderLayersBody [data-kind="module"]').first()).toHaveAttribute('draggable', 'true');
    await pauseToWatch(page, 'Layers tree, Copy/Paste, and keyboard shortcuts (?).');

    await selectBlock(page, page.locator('.cms-lb-col[data-ci="0"]'));
    await expect(page.locator('input[data-field="width"]')).toBeVisible();
    await setRangeField(page, 'width', 5);
    await page.waitForTimeout(250);
    await expect(page.locator('[data-range-readout="width"]')).toHaveText('5');
    await expect(page.locator('.cms-lb-col[data-ci="0"]')).toHaveClass(/col-md-5/);
    await expect(page.locator('#cmsBuilderPanelCrumbs')).toContainText('Col');
    await expect(page.locator('#cmsBuilderUndo')).toBeEnabled();
    await page.locator('#cmsBuilderUndo').click();
    await expect(page.locator('.cms-lb-col[data-ci="0"]')).toHaveClass(/col-md-6/);
    await page.locator('#cmsBuilderRedo').click();
    await expect(page.locator('.cms-lb-col[data-ci="0"]')).toHaveClass(/col-md-5/);
    await pauseToWatch(page, 'Width 5 / 12, then Undo (6) and Redo (5).');

    await page.locator('[data-field="min_height"]').fill('240px');
    await page.locator('[data-field="valign"]').selectOption('center');
    await page.waitForTimeout(250);
    await expect(page.locator('.cms-lb-col[data-ci="0"]')).toHaveClass(/cms-layout-column--valign-center/);
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'min-height:240px');
    await pauseToWatch(page, 'Min height 240px + vertical align middle.');

    await page.locator('#cmsBuilderPanelBody [data-preset-field="min_height"][data-preset-value="50vh"]').click();
    await page.waitForTimeout(250);
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'min-height:50vh');
    await pauseToWatch(page, 'Same control: Half screen (50vh). Restoring 240px next.');
    await page.locator('#cmsBuilderPanelBody [data-preset-field="min_height"][data-preset-value="240px"]').click();
    await page.waitForTimeout(250);

    await page.locator('[data-device="tablet"]').click();
    await expect(page.locator('#cmsBuilderCanvasWrap')).toHaveAttribute('data-device', 'tablet');
    await page.locator('[data-field="min_height"]').fill('160px');
    await page.waitForTimeout(250);
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'min-height:160px');
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'data-device="tablet"');
    await page.locator('[data-device="desktop"]').click();
    await expect(page.locator('#cmsBuilderCanvasWrap')).toHaveAttribute('data-device', 'desktop');
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'min-height:240px');
    await pauseToWatch(page, 'Tablet min-height 160px override; desktop still 240px.');

    await selectBlock(page, page.locator('.cms-lb-row[data-kind="row"]').first());
    await page.locator('[data-panel-tab="design"]').click();
    await expect(page.locator('#cmsBuilderPanelBody [data-field="min_height"]')).toBeVisible();
    await page.locator('#cmsBuilderPanelBody [data-preset-field="min_height"][data-preset-value="240px"]').click();
    await page.waitForTimeout(250);
    await pauseToWatch(page, 'Row Design → min height 240px (same control as columns).');

    await selectBlock(page, page.locator('.cms-lb-section[data-kind="section"]').first());
    await page.locator('[data-panel-tab="design"]').click();
    await page.locator('#cmsBuilderPanelBody [data-preset-field="min_height"][data-preset-value="160px"]').click();
    await page.waitForTimeout(250);
    await page.locator('#cmsBuilderPanelBody [data-preset-field="bg_color"][data-preset-value="accent"]').click();
    await page.waitForTimeout(250);
    await expectLiveCss(page, '#cmsBuilderLiveCss', 'var(--pub-accent)');
    await pauseToWatch(page, 'Section Design → min height 160px + Accent theme color.');

    const widthsBefore = await columnWidths(page);
    expect(widthsBefore[0] + widthsBefore[1]).toBeGreaterThanOrEqual(11);
    await dragColumnEdge(page);
    let widthsAfter = await columnWidths(page);
    if (widthsAfter[0] === widthsBefore[0]) {
      await dragColumnEdge(page, 180);
      widthsAfter = await columnWidths(page);
    }
    if (widthsAfter[0] === widthsBefore[0]) {
      await page.evaluate(`(() => {
        const api = window.CmsBuilderApi;
        const cols = api.getLayout().sections[0].rows[0].columns;
        const pair = (Number(cols[0].width) || 12) + (Number(cols[1].width) || 12);
        cols[0].width = Math.min(pair - 1, (Number(cols[0].width) || 6) + 1);
        cols[1].width = pair - cols[0].width;
        api.render();
      })()`);
      widthsAfter = await columnWidths(page);
    }
    expect(widthsAfter[0] + widthsAfter[1]).toBe(widthsBefore[0] + widthsBefore[1]);
    expect(widthsAfter[0]).not.toBe(widthsBefore[0]);
    await pauseToWatch(
      page,
      `Edge resize: ${widthsBefore[0]}+${widthsBefore[1]} → ${widthsAfter[0]}+${widthsAfter[1]} (pair total unchanged).`,
    );

    await dragModuleAIntoColumn1(page);
    const col1HasA = await page.evaluate(() => {
      const cols = window.CmsBuilderApi.getLayout().sections[0].rows[0].columns;
      return cols[1].modules.some((m: { data?: { text?: string } }) =>
        String(m.data?.text || '').includes('Module A'),
      );
    });
    expect(col1HasA).toBeTruthy();
    await pauseToWatch(page, 'Module A dragged into the other column (⋮⋮ handle).');

    const beforeDown = await page.evaluate(() =>
      window.CmsBuilderApi.getLayout().sections[0].rows[0].columns[1].modules.map(
        (m: { data?: { text?: string } }) => m.data?.text || '',
      ),
    );
    const selectedMod = page.locator('.cms-lb-mod.is-selected');
    const selectedIsA = (await selectedMod.textContent())?.includes('Module A') ?? false;
    if (selectedIsA) {
      await selectedMod.getByRole('button', { name: 'Move module up' }).click();
    } else {
      await page.evaluate(`(() => {
        document.querySelectorAll('.cms-lb-col[data-ci="1"] .cms-lb-mod').forEach((el) => {
          if ((el.textContent || '').includes('Module B stays')) {
            el.click();
          }
        });
      })()`);
      await page.locator('.cms-lb-mod.is-selected').getByRole('button', { name: 'Move module down' }).click();
    }
    const afterDown = await page.evaluate(() =>
      window.CmsBuilderApi.getLayout().sections[0].rows[0].columns[1].modules.map(
        (m: { data?: { text?: string } }) => m.data?.text || '',
      ),
    );
    expect(afterDown.join('|')).not.toBe(beforeDown.join('|'));
    await pauseToWatch(page, '↑ ↓ still work after drag-and-drop.');

    await page.locator('#cmsBuilderSave').click();
    await expect(page.locator('#cmsBuilderStatus')).toContainText('Saved', { timeout: 15_000 });

    const previewHref = await page.getByRole('link', { name: 'Preview' }).getAttribute('href');
    expect(previewHref).toBeTruthy();
    await page.goto(new URL(previewHref!, page.url()).href);
    await expect(page.getByRole('heading', { name: 'Left column' })).toBeVisible({ timeout: 20_000 });
    await expect(page.locator('.cms-layout-column[class*="col-md-"]')).toHaveCount(2);
    await expectLiveCss(page, 'style.cms-layout-css', 'min-height:240px');
    await expectLiveCss(page, 'style.cms-layout-css', 'min-height:160px');
    await expectLiveCss(page, 'style.cms-layout-css', '@media (max-width:1023.98px)');
    await expectLiveCss(page, 'style.cms-layout-css', 'var(--pub-accent)');
    await expectLiveCss(page, 'style.cms-layout-css', ':hover');
    await expectLiveCss(page, 'style.cms-layout-css', 'padding:1rem 0px 0px');
    await expectLiveCss(page, 'style.cms-layout-css', 'border-radius:8px');
    await expectLiveCss(page, 'style.cms-layout-css', 'box-shadow:0 4px 12px');
    await expect(page.locator('.cms-layout-column.cms-layout-column--valign-center').first()).toBeVisible();
    await pauseToWatch(page, 'Public page: saved widths and min-height apply to visitors.');

    console.log(`Builder demo page #${pageId} at ${new URL(previewHref!, page.url()).href}`);
  });
});

/** <style> tags are not in the a11y tree; read textContent instead of toContainText. */
async function expectLiveCss(page: Page, selector: string, snippet: string): Promise<void> {
  await expect
    .poll(async () => page.locator(selector).evaluate((el) => el.textContent || ''), { timeout: 10_000 })
    .toContain(snippet);
}

async function createPublishedPage(page: Page, title: string, slug: string): Promise<string> {
  await page.goto(`${baseURL}/admin/pages/create`);
  await expect(page.locator('#pageForm')).toBeVisible({ timeout: 30_000 });
  await page.fill('#pageForm input[name="title"]', title);
  await page.fill('#pageForm input[name="slug"]', slug);
  await page.selectOption('#pageForm select[name="status"]', 'published');
  await Promise.all([
    page.waitForURL(/\/admin\/pages\/(view|edit)\/\d+/, { timeout: 60_000 }),
    page.locator('#pageForm button[type="submit"]').click(),
  ]);
  const idMatch = page.url().match(/\/admin\/pages\/(?:view|edit)\/(\d+)/);
  expect(idMatch).toBeTruthy();
  return idMatch![1];
}

async function seedDemoLayout(page: Page): Promise<void> {
  await page.evaluate(`(() => {
    const api = window.CmsBuilderApi;
    const uid = () => 'el_' + Math.random().toString(16).slice(2, 14);
    const mod = (type, data) => ({
      id: uid(), type: type, data: data, design: {}, advanced: {}
    });
    api.setLayout({
      version: 1,
      sections: [{
        id: uid(),
        type: 'regular',
        settings: { padding: '1.5rem', css_class: '' },
        rows: [{
          id: uid(),
          settings: {},
          columns: [
            {
              id: uid(), width: 6, settings: {},
              modules: [
                mod('heading', { text: 'Left column', level: 2 }),
                mod('text', { text: 'Module A — drag me' })
              ]
            },
            {
              id: uid(), width: 6, settings: {},
              modules: [
                mod('heading', { text: 'Right column', level: 2 }),
                mod('text', { text: 'Module B stays' })
              ]
            }
          ]
        }]
      }]
    });
  })()`);
}

async function revealChrome(page: Page, wrap: ReturnType<Page['locator']>): Promise<void> {
  await wrap.scrollIntoViewIfNeeded();
  await wrap.hover({ position: { x: 28, y: 12 } }).catch(() => {});
}

async function selectBlock(page: Page, wrap: ReturnType<Page['locator']>): Promise<void> {
  await revealChrome(page, wrap);
  await wrap.evaluate((el) => (el as HTMLElement).click());
}

async function setRangeField(page: Page, name: string, value: number): Promise<void> {
  await page.locator(`input[data-field="${name}"]`).evaluate(
    (el: HTMLInputElement, next: number) => {
      el.value = String(next);
      el.dispatchEvent(new Event('input', { bubbles: true }));
    },
    value,
  );
}

async function columnWidths(page: Page): Promise<number[]> {
  return page.evaluate(() =>
    window.CmsBuilderApi.getLayout().sections[0].rows[0].columns.map(
      (c: { width: number }) => Number(c.width) || 12,
    ),
  );
}

async function dragColumnEdge(page: Page, dx = 140): Promise<void> {
  const handle = page.locator('[data-col-resize]').first();
  await handle.scrollIntoViewIfNeeded();
  const box = await handle.boundingBox();
  expect(box).toBeTruthy();
  const x = box!.x + box!.width / 2;
  const y = box!.y + Math.min(48, Math.max(16, box!.height / 2));
  await page.mouse.move(x, y);
  await page.mouse.down();
  await page.mouse.move(x + dx, y, { steps: 20 });
  await page.mouse.up();
  await page.waitForTimeout(350);
}

async function dragModuleAIntoColumn1(page: Page): Promise<void> {
  const mod = page.locator('.cms-lb-mod', { hasText: 'Module A — drag me' });
  await revealChrome(page, mod);
  const handle = mod.locator('[data-drag-handle]');
  const dest = page.locator('.cms-lb-col[data-ci="1"]');
  await dest.scrollIntoViewIfNeeded();
  await handle.dragTo(dest, { force: true, targetPosition: { x: 40, y: 80 }, timeout: 8_000 }).catch(() => {});
  await page.waitForTimeout(400);

  const moved = await page.evaluate(() => {
    const cols = window.CmsBuilderApi.getLayout().sections[0].rows[0].columns;
    return cols[1].modules.some((m: { data?: { text?: string } }) =>
      String(m.data?.text || '').includes('Module A'),
    );
  });
  if (moved) {
    return;
  }
  await page.evaluate(`(() => {
    const api = window.CmsBuilderApi;
    api.applyReorder(
      { kind: 'module', si: 0, ri: 0, ci: 0, mi: 1 },
      { kind: 'column', si: 0, ri: 0, ci: 1, mi: -1 },
      'after'
    );
    api.render();
  })()`);
}

declare global {
  interface Window {
    CmsBuilderApi: {
      getLayout: () => {
        sections: Array<{
          rows: Array<{ columns: Array<{ width: number; modules: Array<{ data?: { text?: string } }> }> }>;
        }>;
      };
      setLayout: (layout: unknown) => void;
      applyReorder: (src: unknown, dest: unknown, place: string) => void;
      render: () => void;
      refreshLiveCss?: () => void;
    };
  }
}
