import * as fs from "fs";
import * as os from "os";
import * as path from "path";
import { execFileSync } from "child_process";
import { expect, type Page } from "@playwright/test";

/** Build a flat-root ZIP of CSV text files (Windows Compress-Archive). */
export function buildFlatSesZip(files: Record<string, string>): string {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), "ses-e2e-csv-"));
  for (const [name, content] of Object.entries(files)) {
    const safe = path.basename(name);
    fs.writeFileSync(path.join(dir, safe), content, "utf8");
  }
  const zipPath = path.join(
    os.tmpdir(),
    `ses-e2e-${Date.now()}-${Math.random().toString(36).slice(2, 7)}.zip`,
  );
  if (fs.existsSync(zipPath)) {
    fs.unlinkSync(zipPath);
  }
  // Compress only files at root of dir (no nested folders)
  execFileSync(
    "powershell.exe",
    [
      "-NoProfile",
      "-Command",
      `Compress-Archive -Path (Join-Path '${dir.replace(/'/g, "''")}' '*') -DestinationPath '${zipPath.replace(/'/g, "''")}' -Force`,
    ],
    { stdio: "pipe" },
  );
  expect(fs.existsSync(zipPath), `ZIP not created: ${zipPath}`).toBeTruthy();
  return zipPath;
}

/** Nested ZIP (CSV under a folder) — importer must reject. */
export function buildNestedSesZip(files: Record<string, string>): string {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), "ses-e2e-nested-"));
  const nested = path.join(root, "subdir");
  fs.mkdirSync(nested);
  for (const [name, content] of Object.entries(files)) {
    fs.writeFileSync(path.join(nested, path.basename(name)), content, "utf8");
  }
  const zipPath = path.join(
    os.tmpdir(),
    `ses-e2e-nested-${Date.now()}.zip`,
  );
  execFileSync(
    "powershell.exe",
    [
      "-NoProfile",
      "-Command",
      `Compress-Archive -Path '${root.replace(/'/g, "''")}\\*' -DestinationPath '${zipPath.replace(/'/g, "''")}' -Force`,
    ],
    { stdio: "pipe" },
  );
  return zipPath;
}

export function buildStartCsv(
  controlId: string,
  extra: Record<string, string> = {},
): string {
  const headers = [
    "Phase",
    "CONTROL ID",
    "Entry Date",
    "Project Name",
    "Barangay",
    "City/Municipality",
    "Notes",
    "EntryID",
  ];
  const row = {
    Phase: "1",
    "CONTROL ID": controlId,
    "Entry Date": "2026-07-01",
    "Project Name": "E2E",
    Barangay: extra.Barangay ?? "Test Brgy",
    "City/Municipality": extra["City/Municipality"] ?? "Test Mun",
    Notes: extra.Notes ?? "",
    EntryID: "1",
  };
  const line = headers.map((h) => String((row as Record<string, string>)[h] ?? "")).join(",");
  return `${headers.join(",")}\n${line}\n`;
}

export async function openSesImportPage(page: Page): Promise<void> {
  await page.goto("/system/socio-economic");
  await expect(page.locator("h2")).toContainText(/Socio Economic/i);
  await expect(page.locator("#ses-import-form")).toBeVisible();
}

export async function setSesZipFile(page: Page, zipPath: string): Promise<void> {
  await page.locator("#ses_zip").setInputFiles(zipPath);
}

export async function clickSesPreview(page: Page): Promise<void> {
  await page.locator("#ses-preview-btn").click();
  await Promise.race([
    expect(page.locator("#ses-summary")).toBeVisible({ timeout: 60_000 }),
    expect(page.locator("#ses-result.alert-danger")).toBeVisible({ timeout: 60_000 }),
  ]);
  if (await page.locator("#ses-result.alert-danger").isVisible()) {
    const err = (await page.locator("#ses-result").innerText()).trim();
    throw new Error(`SES preview failed: ${err}`);
  }
  await expect(page.locator("#ses-summary")).toBeVisible();
}

export async function submitSesImport(page: Page): Promise<void> {
  await page.locator("#ses-import-btn").click();
  // Success flash then redirect to audit detail, or stay with result text
  await Promise.race([
    page.waitForURL(/\/system\/socio-economic\/view\/\d+/, { timeout: 90_000 }),
    page
      .locator("#ses-result")
      .waitFor({ state: "visible", timeout: 90_000 })
      .then(async () => {
        await expect(page.locator("#ses-result")).toContainText(
          /Import completed|Import complete/i,
          { timeout: 5_000 },
        );
      }),
  ]);
}
