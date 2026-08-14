import { expect, type Page } from "@playwright/test";

export type GrievanceEscalationSettings = {
  excludeWeekends?: boolean;
  excludeHolidays?: boolean;
};

export async function saveGrievanceEscalationSettings(
  page: Page,
  opts: GrievanceEscalationSettings,
): Promise<void> {
  await page.goto("/grievance/settings");
  await expect(page.locator("#exclude_weekends")).toBeVisible();

  if (opts.excludeWeekends) {
    await page.check("#exclude_weekends");
  } else {
    await page.uncheck("#exclude_weekends");
  }

  if (opts.excludeHolidays) {
    await page.check("#exclude_holidays");
  } else {
    await page.uncheck("#exclude_holidays");
  }

  await page.click('form[action="/grievance/settings/save"] button[type="submit"]');
  await expect(page.locator(".alert-success")).toContainText("Grievance settings saved");
}

export async function addHolidayViaOperational(
  page: Page,
  opts: { name: string; date: string; description?: string },
): Promise<void> {
  await page.goto("/system/operational");
  await page.click("#holidayAddBtn");
  await expect(page.locator("#holidayModal")).toBeVisible();
  await page.fill("#holiday_name", opts.name);
  await page.fill("#holiday_date", opts.date);
  if (opts.description) {
    await page.fill("#holiday_description", opts.description);
  }
  await page.click("#holidayFormSubmit");
  await page.waitForLoadState("networkidle");
  await expect(page.locator("#holidaysTable", { hasText: opts.name })).toBeVisible();
}

export async function deleteHolidayViaOperational(
  page: Page,
  holidayName: string,
): Promise<void> {
  await page.goto("/system/operational");
  const row = page.locator("#holidaysTable tr", { hasText: holidayName });
  if ((await row.count()) === 0) {
    return;
  }
  page.once("dialog", (dialog) => dialog.accept());
  await row.locator('form[action*="/holidays/delete/"] button[type="submit"]').click();
  await page.waitForLoadState("networkidle");
}

/** Remove all holidays on a given ISO date (cleanup between runs). */
export async function deleteHolidaysOnDate(
  page: Page,
  isoDate: string,
): Promise<void> {
  await page.goto("/system/operational");
  const rows = page.locator("#holidaysTable tr", { hasText: isoDate });
  const count = await rows.count();
  for (let i = 0; i < count; i++) {
    const row = page.locator("#holidaysTable tr", { hasText: isoDate }).first();
    if ((await row.count()) === 0) {
      break;
    }
    page.once("dialog", (dialog) => dialog.accept());
    await row.locator('form[action*="/holidays/delete/"] button[type="submit"]').click();
    await page.waitForLoadState("networkidle");
  }
}

export async function fetchEscalationSettingsApi(
  page: Page,
): Promise<{ exclude_weekends: boolean; exclude_holidays: boolean }> {
  const res = await page.request.get("/api/grievance/options");
  expect(res.ok()).toBeTruthy();
  const body = await res.json();
  const settings = body.data?.escalation_settings ?? body.escalation_settings;
  expect(settings).toBeTruthy();
  return {
    exclude_weekends: Boolean(settings.exclude_weekends),
    exclude_holidays: Boolean(settings.exclude_holidays),
  };
}
