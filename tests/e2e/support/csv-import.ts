import { expect, type Page } from "@playwright/test";

function csvEscape(value: string): string {
  if (/[",\n\r]/.test(value)) {
    return `"${value.replace(/"/g, '""')}"`;
  }
  return value;
}

/** Build a one-or-more-row CSV from row objects (keys = header names). */
export function buildCsvFromRows(rows: Array<Record<string, string>>): string {
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

export async function setImportFile(
  page: Page,
  inputSelector: string,
  csvContent: string,
  fileName: string,
): Promise<void> {
  await page.locator(inputSelector).setInputFiles({
    name: fileName,
    mimeType: "text/csv",
    buffer: Buffer.from(csvContent, "utf-8"),
  });
}

export async function expectImportPreviewCounts(
  page: Page,
  summarySelector: string,
  counts: {
    new?: number;
    update?: number;
    needsClarification?: number;
    failed?: number;
  },
): Promise<void> {
  const summary = page.locator(summarySelector);
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
