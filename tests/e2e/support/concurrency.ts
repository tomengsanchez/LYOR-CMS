import { expect, type Page } from "@playwright/test";
import { dbSelect } from "./notifications";
import { uniqueSuffix } from "./helpers";

export async function expectFlashSuccess(
  page: Page,
  messagePart: string,
): Promise<void> {
  await expect(page.locator("main.content .alert-success")).toContainText(
    messagePart,
    { timeout: 30_000 },
  );
}

export async function expectFlashError(
  page: Page,
  messagePart: string,
): Promise<void> {
  await expect(page.locator("main.content .alert-danger")).toContainText(
    messagePart,
    { timeout: 30_000 },
  );
}

/** Simulate another tab saving the same library project with the current lock token. */
export async function saveLibraryProjectInBackground(
  page: Page,
  projectId: number,
  payload: {
    name: string;
    description: string;
    recordUpdatedAt: string;
    escalationCountStart: string;
  },
): Promise<void> {
  const csrf =
    (await page.locator('meta[name="csrf-token"]').getAttribute("content")) ??
    "";
  const ok = await page.evaluate(
    async ({ projectId, csrf, payload }) => {
      const fd = new FormData();
      fd.append("csrf_token", csrf);
      fd.append("name", payload.name);
      fd.append("description", payload.description);
      fd.append("record_updated_at", payload.recordUpdatedAt);
      fd.append("escalation_count_start", payload.escalationCountStart);
      const res = await fetch(`/library/update/${projectId}`, {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        redirect: "follow",
      });
      return res.ok || res.redirected;
    },
    { projectId, csrf, payload },
  );
  expect(ok).toBeTruthy();
}

export function pickGrievanceIdForApi(): number | null {
  const rows = dbSelect<{ id: number }>(
    "SELECT id FROM grievances WHERE is_deleted = 0 ORDER BY id DESC LIMIT 1",
  );
  if (!rows.length) {
    return null;
  }
  return Number(rows[0].id);
}

export async function postGrievanceUpdateJson(
  page: Page,
  grievanceId: number,
  body: Record<string, unknown>,
  headers: Record<string, string> = {},
): Promise<import("@playwright/test").APIResponse> {
  return page.request.post(`/api/grievance/update/${grievanceId}`, {
    headers: {
      "Content-Type": "application/json",
      ...headers,
    },
    data: body,
  });
}

export function newIdempotencyKey(): string {
  return `e2e-idem-${uniqueSuffix()}`;
}
