import { expect, type Page } from "@playwright/test";

export function uniqueSuffix(): string {
  return `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;
}

export function extractIdFromUrl(url: string): number {
  const m = url.match(/\/(\d+)\/?(?:\?|$)/);
  if (!m) {
    throw new Error(`No numeric id in URL: ${url}`);
  }
  return Number(m[1]);
}

/** Append option + trigger change for Select2-backed selects. */
export async function selectSelect2(
  page: Page,
  selectId: string,
  value: string,
  label: string,
): Promise<void> {
  await page.waitForFunction(
    (id) => {
      const w = window as unknown as { jQuery?: (sel: string) => unknown };
      return typeof w.jQuery === "function" && !!document.querySelector(`#${id}`);
    },
    selectId,
    { timeout: 20_000 },
  );
  await page.evaluate(
    ({ selectId, value, label }) => {
      const $ = (window as unknown as { jQuery: (sel: string) => { length: number; append: (o: HTMLOptionElement) => unknown; val: (v?: string) => unknown; trigger: (e: string) => unknown; find: (s: string) => { length: number } } }).jQuery;
      const $el = $(`#${selectId}`);
      if (!$el.length) {
        throw new Error(`Select #${selectId} not found`);
      }
      if (!$el.find(`option[value="${value}"]`).length) {
        $el.append(new Option(label, value, true, true));
      } else {
        $el.val(value);
      }
      $el.trigger("change");
    },
    { selectId, value, label },
  );
}

/** Append values on a Select2 multi-select without clearing existing picks. */
export async function appendSelect2Multiple(
  page: Page,
  selectId: string,
  value: string,
  label: string,
): Promise<void> {
  await page.waitForFunction(
    (id) => {
      const w = window as unknown as { jQuery?: (sel: string) => unknown };
      return typeof w.jQuery === "function" && !!document.querySelector(`#${id}`);
    },
    selectId,
    { timeout: 20_000 },
  );
  await page.evaluate(
    ({ selectId, value, label }) => {
      const $ = (window as unknown as {
        jQuery: (sel: string) => {
          length: number;
          append: (o: HTMLOptionElement) => unknown;
          val: (v?: string | string[]) => string | string[] | null;
          trigger: (e: string) => unknown;
          find: (s: string) => { length: number };
        };
      }).jQuery;
      const $el = $(`#${selectId}`);
      if (!$el.length) {
        throw new Error(`Select #${selectId} not found`);
      }
      if (!$el.find(`option[value="${value}"]`).length) {
        $el.append(new Option(label, value, false, false));
      }
      let current = $el.val();
      const next: string[] = Array.isArray(current)
        ? current.map(String)
        : current
          ? [String(current)]
          : [];
      if (!next.includes(value)) {
        next.push(value);
      }
      $el.val(next).trigger("change");
    },
    { selectId, value, label },
  );
}

export async function waitForDashboardWidgets(page: Page): Promise<void> {
  await expect(page.locator("#mainDashboard")).toBeVisible();
  await page.waitForFunction(() => {
    const el = document.querySelector("#profile-created");
    return el && el.textContent && el.textContent.trim() !== "—";
  }, { timeout: 30_000 });
}

export async function unwrapApiData<T>(payload: unknown): Promise<T> {
  if (
    payload &&
    typeof payload === "object" &&
    "data" in (payload as object)
  ) {
    return (payload as { data: T }).data;
  }
  return payload as T;
}
