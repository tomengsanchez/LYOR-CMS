import { expect, type Page } from "@playwright/test";

export type LevelSla = { name: string; days: number };

export function todayIso(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

export function addDays(isoDate: string, days: number): string {
  const [y, m, d] = isoDate.split("-").map(Number);
  const dt = new Date(y, m - 1, d);
  dt.setDate(dt.getDate() + days);
  const ny = dt.getFullYear();
  const nm = String(dt.getMonth() + 1).padStart(2, "0");
  const nd = String(dt.getDate()).padStart(2, "0");
  return `${ny}-${nm}-${nd}`;
}

export async function clearSimulatedDate(page: Page): Promise<void> {
  await page.goto("/system/development");
  const clearBtn = page.locator(
    'form[action="/system/development/clear-simulated-time"] button[type="submit"]',
  );
  if (await clearBtn.isVisible()) {
    await clearBtn.click();
    await page.waitForLoadState("networkidle");
  }
}

export async function setSimulatedDate(
  page: Page,
  isoDate: string,
): Promise<void> {
  await page.goto("/system/development");
  await page.fill("#simulated_date", isoDate);
  await page.click(
    'form[action="/system/development/set-simulated-time"] button[type="submit"]',
  );
  await page.waitForLoadState("networkidle");
  await expect(page.locator("#simulated_date")).toHaveValue(isoDate);
}

/** Set days_to_address on default-scoped progress levels (Level 1/2/3). */
export async function configureDefaultLevelSlas(
  page: Page,
  levels: LevelSla[],
): Promise<void> {
  for (const level of levels) {
    await page.goto("/grievance/options/progress-levels");
    const row = page
      .locator("tr")
      .filter({
        has: page.locator("span.badge.bg-secondary", {
          hasText: "Default stage",
        }),
      })
      .filter({ hasText: level.name })
      .first();
    await expect(row).toBeVisible();
    await row
      .locator('a[href*="/grievance/options/progress-levels/edit/"]')
      .click();
    await page.fill('input[name="days_to_address"]', String(level.days));
    await page.click('form button[type="submit"]');
    await page.waitForLoadState("networkidle");
  }
}

/** Copy default stages into a project when it has no project-specific levels yet. */
export async function initializeProjectProgressLevels(
  page: Page,
  projectId: number,
): Promise<void> {
  await page.goto(`/grievance/options/progress-levels?project_id=${projectId}`);
  const initForm = page.locator(
    'form[action="/grievance/options/progress-levels/initialize-project"]',
  );
  if (await initForm.isVisible()) {
    page.once("dialog", (dialog) => dialog.accept());
    await initForm.locator('button[type="submit"]').click();
    await page.waitForLoadState("networkidle");
  }
}

/** Edit a stage under a project scope (project_id query filter). */
export async function configureProjectLevel(
  page: Page,
  projectId: number,
  levelName: string,
  opts: { days?: number; renameTo?: string },
): Promise<void> {
  await page.goto(`/grievance/options/progress-levels?project_id=${projectId}`);
  const row = page.locator("tr").filter({ hasText: levelName }).first();
  await expect(row).toBeVisible();
  await row
    .locator('a[href*="/grievance/options/progress-levels/edit/"]')
    .click();
  if (opts.renameTo !== undefined) {
    await page.fill('input[name="name"]', opts.renameTo);
  }
  if (opts.days !== undefined) {
    await page.fill('input[name="days_to_address"]', String(opts.days));
  }
  await page.click('form button[type="submit"]');
  await page.waitForLoadState("networkidle");
}

export async function expectListEscalationBadge(
  page: Page,
  respondentSnippet: string,
  pattern: RegExp,
  variant: "warning" | "danger",
): Promise<void> {
  await page.goto("/grievance/list");
  const row = page.locator("tr", { hasText: respondentSnippet });
  const badgeClass =
    variant === "warning" ? ".badge.bg-warning" : ".badge.bg-danger";
  await expect(row.locator(badgeClass, { hasText: pattern })).toBeVisible({
    timeout: 15_000,
  });
}

export async function submitGrievanceStatusUpdate(
  page: Page,
  grievanceId: number,
  opts: {
    status?: "open" | "in_progress" | "closed";
    progressLevel?: string;
    note?: string;
    effectiveAt?: string;
  },
): Promise<void> {
  await page.goto(`/grievance/view/${grievanceId}`);
  if (opts.status) {
    await page.selectOption("#statusSelect", opts.status);
  }
  if (opts.progressLevel) {
    await page
      .locator('#progressLevelBlock select[name="progress_level"]')
      .waitFor({ state: "visible" });
    await page
      .locator('select[name="progress_level"]')
      .selectOption({ label: opts.progressLevel });
  }
  if (opts.effectiveAt) {
    await page.fill('input[name="status_effective_at"]', opts.effectiveAt);
  }
  if (opts.note !== undefined) {
    await page.fill('textarea[name="status_note"]', opts.note);
  }
  await page.click(
    'form[action*="/grievance/status-update/"] button[type="submit"]',
  );
  await page.waitForLoadState("networkidle");
}

export async function expectViewEscalation(
  page: Page,
  grievanceId: number,
  variant: "warning" | "danger",
  pattern: RegExp,
): Promise<void> {
  await page.goto(`/grievance/view/${grievanceId}`, { waitUntil: "networkidle" });
  const sel = variant === "warning" ? ".alert-warning" : ".alert-danger";
  await expect(page.locator(sel, { hasText: pattern })).toBeVisible({
    timeout: 15_000,
  });
}

export async function expectNoViewEscalation(
  page: Page,
  grievanceId: number,
): Promise<void> {
  await page.goto(`/grievance/view/${grievanceId}`);
  await expect(page.locator(".alert-warning, .alert-danger")).toHaveCount(0);
}

export async function expectInNeedsEscalationFilter(
  page: Page,
  respondentSnippet: string,
): Promise<void> {
  await page.goto("/grievance/list?needs_escalation=1");
  await expect(page.locator("tr", { hasText: respondentSnippet })).toBeVisible();
}

export async function expectNotInNeedsEscalationFilter(
  page: Page,
  respondentSnippet: string,
): Promise<void> {
  await page.goto("/grievance/list?needs_escalation=1");
  await expect(page.locator("tr", { hasText: respondentSnippet })).toHaveCount(
    0,
  );
}

export async function readViewEscalationText(
  page: Page,
  grievanceId: number,
): Promise<string> {
  await page.goto(`/grievance/view/${grievanceId}`);
  const alert = page.locator(".alert-warning, .alert-danger").first();
  await expect(alert).toBeVisible();
  return ((await alert.textContent()) ?? "").trim();
}

export type NeedsEscalationDashboardRow = {
  project_id: number;
  progress_level: number;
  action: "escalate" | "close";
  count: number;
  display_level_name?: string;
};

export {
  fetchGrievanceDashboard,
  type GrievanceDashboardPayload,
} from "./grievance-dashboard";
