import { expect, type Page } from "@playwright/test";
import { selectSelect2 } from "./helpers";

/** All RAP value_mode option values shown in Add RAP field. */
export const RAP_VALUE_MODES = [
  "first",
  "list",
  "sum",
  "average",
  "count",
  "count_in_range",
  "sum_in_range",
] as const;

export async function openRapMappingPage(
  page: Page,
  projectId?: number,
): Promise<void> {
  const qs =
    projectId != null && projectId > 0 ? `?project_id=${projectId}` : "";
  await page.goto(`/system/rap-mapping${qs}`);
  await expect(page.getByRole("heading", { name: /SES → RAP Mapping/i })).toBeVisible({
    timeout: 30_000,
  });
  await expect(page.locator("#rap-field-add-form")).toBeVisible();
}

export async function expectOperationModes(page: Page): Promise<void> {
  const select = page.locator("#new_value_mode");
  await expect(select).toBeVisible();
  for (const mode of RAP_VALUE_MODES) {
    await expect(select.locator(`option[value="${mode}"]`)).toHaveCount(1);
  }
}

export async function createRapField(
  page: Page,
  opts: {
    label: string;
    fieldKey: string;
    category?: string;
    valueMode: (typeof RAP_VALUE_MODES)[number];
    rangeMin?: number | string;
    rangeMax?: number | string;
  },
): Promise<void> {
  await page.fill("#new_label", opts.label);
  await page.fill("#new_field_key", opts.fieldKey);
  await page.fill("#new_category", opts.category ?? "general");
  await page.selectOption("#new_value_mode", opts.valueMode);

  const rangeVisible = ["count_in_range", "sum_in_range"].includes(opts.valueMode);
  if (rangeVisible) {
    await expect(page.locator("#rap-field-add-form .rap-range-fields").first()).toBeVisible();
    if (opts.rangeMin !== undefined) {
      await page.fill("#new_range_min", String(opts.rangeMin));
    }
    if (opts.rangeMax !== undefined) {
      await page.fill("#new_range_max", String(opts.rangeMax));
    }
  }

  await page.locator('#rap-field-add-form button[type="submit"]').click();
  await page.waitForURL(/\/system\/rap-mapping/, { timeout: 30_000 });
  await expect(page.locator(".alert-success")).toContainText(/RAP field created/i, {
    timeout: 15_000,
  });
  await expect(
    page.locator(`tbody.rap-field-group code`, { hasText: opts.fieldKey }),
  ).toBeVisible();
}

export async function fieldGroupByKey(page: Page, fieldKey: string) {
  return page.locator("tbody.rap-field-group").filter({
    has: page.locator("code", { hasText: fieldKey }),
  });
}

/** Pick RAP field in Add mapping by field_key substring in option text. */
export async function selectRapFieldForMap(
  page: Page,
  fieldKey: string,
): Promise<void> {
  const select = page.locator("#rap_field_id");
  const option = select.locator("option").filter({ hasText: fieldKey }).first();
  const value = await option.getAttribute("value");
  expect(value, `RAP field option for ${fieldKey}`).toBeTruthy();
  await select.selectOption(value!);
}

export async function addStructureMap(
  page: Page,
  fieldKey: string,
  structureField: string,
  label: string,
): Promise<void> {
  await selectRapFieldForMap(page, fieldKey);
  await page.selectOption("#source_entity", "structure");
  await expect(page.locator("#rap-entity-picker-wrap")).toBeVisible();
  await expect(page.locator("#rap-ses-picker-wrap")).toBeHidden();

  const value = `structure||${structureField}`;
  await selectSelect2(page, "entity_field_picker", value, label);
  // Ensure hidden/manual column is set even if Select2 change races.
  await page.fill("#ses_column", structureField);

  await page.locator('#rap-mapping-add-form button[type="submit"]').click();
  await page.waitForURL(/\/system\/rap-mapping/, { timeout: 30_000 });
  await expect(page.locator(".alert-success")).toContainText(/mapped/i, {
    timeout: 15_000,
  });

  const group = await fieldGroupByKey(page, fieldKey);
  await expect(group).toContainText(structureField);
  await expect(group).toContainText(/structure/i);
}

export async function addGrievanceMap(
  page: Page,
  fieldKey: string,
  grievanceField: string,
  label: string,
): Promise<void> {
  await selectRapFieldForMap(page, fieldKey);
  await page.selectOption("#source_entity", "grievance");
  await expect(page.locator("#rap-entity-picker-wrap")).toBeVisible();

  const value = `grievance||${grievanceField}`;
  await selectSelect2(page, "entity_field_picker", value, label);
  await page.fill("#ses_column", grievanceField);

  await page.locator('#rap-mapping-add-form button[type="submit"]').click();
  await page.waitForURL(/\/system\/rap-mapping/, { timeout: 30_000 });
  await expect(page.locator(".alert-success")).toContainText(/mapped/i, {
    timeout: 15_000,
  });

  const group = await fieldGroupByKey(page, fieldKey);
  await expect(group).toContainText(grievanceField);
  await expect(group).toContainText(/grievance/i);
}

export async function deactivateRapField(
  page: Page,
  fieldKey: string,
): Promise<void> {
  const group = await fieldGroupByKey(page, fieldKey);
  page.once("dialog", (d) => d.accept());
  await group.locator('form[action*="/fields/deactivate/"] button').click();
  await page.waitForURL(/\/system\/rap-mapping/, { timeout: 30_000 });
  await expect(page.locator(".alert-success")).toContainText(/deactivated/i, {
    timeout: 15_000,
  });
  const after = await fieldGroupByKey(page, fieldKey);
  await expect(after.locator(".badge", { hasText: /inactive/i })).toBeVisible();
}

export async function openProjectRapSummary(
  page: Page,
  projectId: number,
): Promise<void> {
  await page.goto(`/library/view/${projectId}/rap`);
  await expect(page.getByRole("heading", { name: /RAP summary/i })).toBeVisible({
    timeout: 30_000,
  });
}
