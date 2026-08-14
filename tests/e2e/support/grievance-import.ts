import { expect, type Page } from "@playwright/test";
import type { BootstrapLocation } from "./bootstrap";
import { uniqueSuffix, unwrapApiData } from "./helpers";

export type GrievanceImportOptions = {
  grmChannelName: string;
  preferredLanguageName: string;
  grievanceTypeName: string;
};

function csvEscape(value: string): string {
  if (/[",\n\r]/.test(value)) {
    return `"${value.replace(/"/g, '""')}"`;
  }
  return value;
}

/** Build a one-or-more-row grievance import CSV from row objects (keys = header names). */
export function buildGrievanceImportCsv(
  rows: Array<Record<string, string>>,
): string {
  if (rows.length === 0) {
    return "";
  }
  const headers = Object.keys(rows[0]);
  const lines = [headers.join(",")];
  for (const row of rows) {
    lines.push(headers.map((h) => csvEscape(row[h] ?? "")).join(","));
  }
  return lines.join("\n");
}

export async function fetchGrievanceImportOptions(
  page: Page,
): Promise<GrievanceImportOptions> {
  const res = await page.request.get("/api/grievance/options");
  expect(res.ok()).toBeTruthy();
  const options = await unwrapApiData<{
    grm_channels?: Array<{ name: string }>;
    preferred_languages?: Array<{ name: string }>;
    grievance_types?: Array<{ name: string }>;
  }>(await res.json());

  const grmChannelName = options.grm_channels?.[0]?.name;
  const preferredLanguageName = options.preferred_languages?.[0]?.name;
  const grievanceTypeName = options.grievance_types?.[0]?.name;

  expect(grmChannelName, "GRM channel option required for import E2E").toBeTruthy();
  expect(
    preferredLanguageName,
    "Preferred language option required for import E2E",
  ).toBeTruthy();
  expect(
    grievanceTypeName,
    "Grievance type option required for import E2E",
  ).toBeTruthy();

  return {
    grmChannelName: String(grmChannelName),
    preferredLanguageName: String(preferredLanguageName),
    grievanceTypeName: String(grievanceTypeName),
  };
}

export async function buildValidGrievanceImportCsv(
  page: Page,
  loc: BootstrapLocation,
  overrides: Record<string, string> = {},
): Promise<{ csv: string; caseNumber: string; lastName: string }> {
  const options = await fetchGrievanceImportOptions(page);
  const caseNumber = overrides.grievance_case_number ?? `E2E-GRV-${uniqueSuffix()}`;
  const lastName = overrides.respondent_last_name ?? `Import${uniqueSuffix().slice(-6)}`;

  const row: Record<string, string> = {
    grievance_case_number: caseNumber,
    date_recorded: "2026-04-01 10:00:00",
    project_name: loc.projectName,
    municipality_name: loc.municipalityName,
    barangay_name: loc.barangayName,
    is_paps: "no",
    respondent_first_name: "E2E",
    respondent_last_name: lastName,
    grm_channel_names: options.grmChannelName,
    preferred_language_names: options.preferredLanguageName,
    grievance_type_names: options.grievanceTypeName,
    description_complaint: "E2E CSV import grievance complaint.",
    desired_resolution: "E2E CSV import desired resolution.",
    status: "open",
    ...overrides,
  };

  return {
    csv: buildGrievanceImportCsv([row]),
    caseNumber,
    lastName,
  };
}

export async function openGrievanceImportModal(page: Page): Promise<void> {
  await page.goto("/grievance/list");
  await page.getByRole("button", { name: "Import CSV" }).click();
  await expect(page.locator("#grievanceImportModal")).toBeVisible();
  await expect(page.locator("#grievance-import-form")).toBeVisible();
}

export async function setGrievanceImportFile(
  page: Page,
  csvContent: string,
  fileName = "grievance-import-e2e.csv",
): Promise<void> {
  await page.locator("#grievances_file").setInputFiles({
    name: fileName,
    mimeType: "text/csv",
    buffer: Buffer.from(csvContent, "utf-8"),
  });
}

export async function clickGrievanceImportPreview(page: Page): Promise<void> {
  await page.locator("#grievance-import-preview").click();
  await expect(page.locator("#grievance-import-summary")).toContainText(
    "File summary",
    { timeout: 60_000 },
  );
}

export async function expectGrievanceImportPreviewCounts(
  page: Page,
  counts: {
    new?: number;
    update?: number;
    needsClarification?: number;
    failed?: number;
  },
): Promise<void> {
  const summary = page.locator("#grievance-import-summary");
  if (counts.new !== undefined) {
    await expect(summary).toContainText(`New: ${counts.new}`);
  }
  if (counts.update !== undefined) {
    await expect(summary).toContainText(`Update: ${counts.update}`);
  }
  if (counts.needsClarification !== undefined) {
    await expect(summary).toContainText(
      `Needs clarification: ${counts.needsClarification}`,
    );
  }
  if (counts.failed !== undefined) {
    await expect(summary).toContainText(`Failed: ${counts.failed}`);
  }
}

export async function submitGrievanceImport(page: Page): Promise<void> {
  await expect(page.locator("#grievance-import-submit")).toBeEnabled();
  await page.locator("#grievance-import-submit").click();
  await expect(page.locator("#grievance-import-result")).toContainText(
    /Import completed/i,
    { timeout: 60_000 },
  );
}

export async function expectGrievanceCaseInList(
  page: Page,
  caseNumber: string,
): Promise<void> {
  await page.goto(`/grievance/list?q=${encodeURIComponent(caseNumber)}`);
  await expect(page.locator("table tbody")).toContainText(caseNumber, {
    timeout: 30_000,
  });
}
