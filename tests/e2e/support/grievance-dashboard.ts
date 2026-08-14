import { expect, type Page } from "@playwright/test";
import { unwrapApiData } from "./helpers";

export type CategoryBreakdownRow = {
  id?: number;
  name: string;
  cnt: number;
};

export type GrievanceDashboardPayload = {
  totalGrievances?: number;
  byCategory?: CategoryBreakdownRow[];
  byType?: CategoryBreakdownRow[];
  monthlyTrend?: Array<{ label: string; count: number }>;
  needsEscalationRows?: unknown[];
  closedByStage?: ClosedByStageRow[];
  closedInRangeTotal?: number;
};

export type ClosedByStageRow = {
  progress_level?: number;
  level_name?: string;
  display_level_name?: string;
  project_name?: string;
  cnt?: number;
};

export function closedStageCount(
  rows: ClosedByStageRow[] | undefined,
  stageLabel: string,
): number {
  const needle = stageLabel.trim().toLowerCase();
  const row = (rows ?? []).find((r) => {
    const display = (r.display_level_name ?? r.level_name ?? "").trim().toLowerCase();
    return display === needle || display.endsWith(` - ${needle}`);
  });
  return row?.cnt ?? 0;
}

export async function fetchGrievanceDashboard(
  page: Page,
  query?: Record<string, string | number>,
): Promise<GrievanceDashboardPayload> {
  const params = new URLSearchParams();
  if (query) {
    for (const [key, value] of Object.entries(query)) {
      params.set(key, String(value));
    }
  }
  const qs = params.toString();
  const url = `/api/grievance/dashboard${qs ? `?${qs}` : ""}`;
  const res = await page.request.get(url);
  expect(res.ok()).toBeTruthy();
  return unwrapApiData<GrievanceDashboardPayload>(await res.json());
}

export function categoryCount(
  rows: CategoryBreakdownRow[] | undefined,
  name: string,
): number {
  const row = (rows ?? []).find((r) => (r.name ?? "").trim() === name);
  return row?.cnt ?? 0;
}

export async function saveGrievanceDashboardConfig(
  page: Page,
  opts: {
    widgets: string[];
    widgetDisplay?: Record<string, string>;
  },
): Promise<void> {
  await page.goto("/grievance");
  const token = await page
    .locator('input[name="csrf_token"]')
    .first()
    .inputValue();
  const body = new URLSearchParams();
  body.append("csrf_token", token);
  opts.widgets.forEach((widget) => body.append("widgets[]", widget));
  if (opts.widgetDisplay) {
    for (const [key, value] of Object.entries(opts.widgetDisplay)) {
      body.append(`widget_display[${key}]`, value);
    }
  }
  const res = await page.request.post("/grievance/dashboard-config", {
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    data: body.toString(),
    maxRedirects: 0,
  });
  expect([302, 303, 200]).toContain(res.status());
}

export async function waitForCategoryWidgetLoaded(page: Page): Promise<void> {
  const surface = page.locator("#dg-surface-by_category");
  await expect(surface).toBeVisible();
  await page.waitForFunction(() => {
    const el = document.getElementById("dg-surface-by_category");
    if (!el) return false;
    if (el.querySelector("canvas")) return true;
    if (el.querySelector("table")) return true;
    if (el.querySelector("ul li")) return true;
    const text = (el.textContent ?? "").trim();
    return text.length > 0 && !/^Loading/i.test(text);
  }, { timeout: 20_000 });
}

export async function expectCategoryWidgetListRow(
  page: Page,
  categoryName: string,
  count: number,
): Promise<void> {
  const surface = page.locator("#dg-surface-by_category");
  const row = surface.locator("li", { hasText: categoryName });
  await expect(row).toBeVisible();
  await expect(row.locator(".badge", { hasText: String(count) })).toBeVisible();
}

export async function expectCategoryWidgetTable(
  page: Page,
): Promise<void> {
  const surface = page.locator("#dg-surface-by_category");
  await expect(surface.locator("table")).toBeVisible();
  await expect(surface.locator("th", { hasText: "Name" })).toBeVisible();
  await expect(surface.locator("th", { hasText: "Count" })).toBeVisible();
}

export async function expectCategoryWidgetChart(
  page: Page,
): Promise<void> {
  const surface = page.locator("#dg-surface-by_category");
  await expect(surface.locator("canvas")).toBeVisible();
}

export async function waitForClosedByStageWidgetLoaded(page: Page): Promise<void> {
  const surface = page.locator("#dg-surface-closed_by_stage");
  await expect(surface).toBeVisible();
  await page.waitForFunction(() => {
    const el = document.getElementById("dg-surface-closed_by_stage");
    if (!el) return false;
    if (el.querySelector("canvas")) return true;
    if (el.querySelector("table")) return true;
    if (el.querySelector("ul li")) return true;
    const text = (el.textContent ?? "").trim();
    return text.length > 0 && !/^Loading/i.test(text);
  }, { timeout: 20_000 });
}

export async function expectClosedByStageWidgetListRow(
  page: Page,
  stageLabel: string,
  minCount = 1,
): Promise<void> {
  const surface = page.locator("#dg-surface-closed_by_stage");
  const row = surface.locator("li").filter({ hasText: stageLabel });
  await expect(row.first()).toBeVisible();
  const badge = row.first().locator(".badge.bg-primary");
  await expect(badge).toBeVisible();
  const text = ((await badge.textContent()) ?? "").replace(/,/g, "");
  expect(Number(text)).toBeGreaterThanOrEqual(minCount);
}
