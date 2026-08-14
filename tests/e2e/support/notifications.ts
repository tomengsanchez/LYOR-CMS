import { execSync } from "node:child_process";
import path from "node:path";
import { expect, type Page } from "@playwright/test";
import { loginAsAdmin, loginAs, logout } from "./auth";
import { extractIdFromUrl, uniqueSuffix, unwrapApiData } from "./helpers";
import {
  createLibraryProject,
  type BootstrapLocation,
} from "./bootstrap";

const root = path.resolve(__dirname, "../../..");

export type E2EUser = {
  username: string;
  password: string;
  email: string;
  userId: number;
};

export type NotificationRow = {
  id: number;
  type: string;
  related_type: string;
  related_id: number;
  message: string;
  created_at: string;
  url: string;
};

export type NotificationPrefs = {
  notify_page_published: boolean;
  notify_post_published: boolean;
  notify_media_uploaded: boolean;
};

export const DEFAULT_PASSWORD = "E2eNotify!2026";

export function dbSelect<T extends Record<string, unknown>>(
  sql: string,
  params: unknown[] = [],
): T[] {
  const php = process.env.PHP_BINARY || "php";
  const payload = JSON.stringify({ sql, params });
  const out = execSync(`${php} tests/e2e/support/db-query.php`, {
    cwd: root,
    input: payload,
    encoding: "utf8",
  });
  return JSON.parse(out) as T[];
}

export function migrationApplied(namePart: string): boolean {
  const php = process.env.PHP_BINARY || "php";
  try {
    const out = execSync(`${php} cli/migrate.php --status`, {
      cwd: root,
      encoding: "utf8",
    });
    return out.includes(namePart);
  } catch {
    return false;
  }
}

export async function createUserWithProjects(
  page: Page,
  opts: {
    username: string;
    email: string;
    password?: string;
    projectIds: number[];
    roleName?: string;
  },
): Promise<E2EUser> {
  await loginAsAdmin(page);
  await page.goto("/users/create");
  await page.fill('input[name="username"]', opts.username);
  await page.fill('input[name="display_name"]', opts.username);
  await page.fill('input[name="email"]', opts.email);
  await page.fill('input[name="password"]', opts.password ?? DEFAULT_PASSWORD);

  const roleName = opts.roleName ?? "Field Team";
  const roleOptions = page.locator('select[name="role_id"] option');
  const roleTexts = await roleOptions.allTextContents();
  if (roleTexts.some((t) => t.includes(roleName))) {
    await page.selectOption('select[name="role_id"]', { label: roleName });
  } else if (roleTexts.some((t) => t.includes("Administrator"))) {
    await page.selectOption('select[name="role_id"]', { label: "Administrator" });
  } else if ((await roleOptions.count()) > 1) {
    await page.selectOption('select[name="role_id"]', { index: 1 });
  }

  await page.evaluate((projectIds) => {
    const list = document.getElementById("linkedProjectsList");
    if (!list) {
      throw new Error("linkedProjectsList not found");
    }
    projectIds.forEach((id) => {
      const badge = document.createElement("span");
      badge.className = "badge bg-primary";
      badge.innerHTML = `<input type="hidden" name="project_ids[]" value="${id}">`;
      list.appendChild(badge);
    });
  }, opts.projectIds);

  await page.click('#userForm button[type="submit"]');
  await page.waitForURL(/\/users\/view\/\d+/, { timeout: 45_000 });

  return {
    username: opts.username,
    password: opts.password ?? DEFAULT_PASSWORD,
    email: opts.email,
    userId: extractIdFromUrl(page.url()),
  };
}

export async function saveNotificationPrefs(
  page: Page,
  prefs: NotificationPrefs,
): Promise<void> {
  await page.goto("/admin/settings");
  await expect(page.locator('form[action="/admin/settings/notifications"]')).toBeVisible();

  const fields = [
    "notify_page_published",
    "notify_post_published",
    "notify_media_uploaded",
  ] as const;

  for (const field of fields) {
    const locator = page.locator(`input[name="${field}"]`);
    if (prefs[field]) {
      await locator.check();
    } else {
      await locator.uncheck();
    }
  }

  await Promise.all([
    page.waitForURL(/\/admin\/settings(?:\?|$)/, { timeout: 30_000 }),
    page.click('form[action="/admin/settings/notifications"] button[type="submit"]'),
  ]);
}

export async function getNotificationsApi(
  page: Page,
): Promise<NotificationRow[]> {
  const res = await page.request.get("/api/notifications");
  expect(res.ok()).toBeTruthy();
  const json = await res.json();
  return (await unwrapApiData<NotificationRow[]>(json)) ?? [];
}

export async function waitForNotification(
  page: Page,
  predicate: (row: NotificationRow) => boolean,
  timeoutMs = 20_000,
): Promise<NotificationRow> {
  let found: NotificationRow | undefined;
  await expect
    .poll(
      async () => {
        const rows = await getNotificationsApi(page);
        found = rows.find(predicate);
        return found ? 1 : 0;
      },
      { timeout: timeoutMs },
    )
    .toBe(1);
  return found!;
}

export async function expectNoNotification(
  page: Page,
  predicate: (row: NotificationRow) => boolean,
): Promise<void> {
  const rows = await getNotificationsApi(page);
  expect(rows.find(predicate)).toBeUndefined();
}

export async function getNotificationBadgeCount(page: Page): Promise<number> {
  const badge = page.locator("#notification-count:visible").first();
  if (!(await badge.count())) {
    return 0;
  }
  if (!(await badge.isVisible())) {
    return 0;
  }
  const text = (await badge.textContent())?.trim() ?? "";
  if (!text || text === "0") {
    return 0;
  }
  if (text === "99+") {
    return 99;
  }
  return Number(text);
}

export async function openNotificationDropdown(page: Page): Promise<void> {
  await page.locator(".notification-dropdown .dropdown-toggle").first().click();
  await expect(page.locator("#notification-list")).toBeVisible();
}

export async function createProfileViaApi(
  page: Page,
  loc: BootstrapLocation,
  tag: string,
): Promise<number> {
  const res = await page.request.post("/api/profile/store", {
    data: {
      project_id: loc.projectId,
      first_name: "Notify",
      last_name: `Api${tag}`,
      full_name: `Notify Api${tag}`,
      age: 30,
      contacts_number: ["09171234567"],
      custom_municipality_id: loc.municipalityId,
      custom_barangay_id: loc.barangayId,
      date_of_invitation: "2026-05-28",
    },
  });
  expect(res.ok(), await res.text()).toBeTruthy();
  const body = await res.json();
  const data = await unwrapApiData<{ id: number }>(body);
  return Number(data.id);
}

export async function createStructureViaApi(
  page: Page,
  loc: BootstrapLocation,
  structureTag: string,
): Promise<number> {
  const res = await page.request.post("/api/structure/store", {
    form: {
      project_id: String(loc.projectId),
      municipality_id: String(loc.municipalityId),
      barangay_id: String(loc.barangayId),
      structure_tag: structureTag,
      description: "E2E notification structure",
    },
  });
  expect(res.ok(), await res.text()).toBeTruthy();
  const body = await res.json();
  const data = await unwrapApiData<{ id: number }>(body);
  return Number(data.id);
}

export async function createGrievanceViaApi(
  page: Page,
  loc: BootstrapLocation,
  tag: string,
  initialStatus?: {
    status: "open" | "in_progress" | "closed";
    progressLevel?: number;
    effectiveAt?: string;
    note?: string;
  },
): Promise<number> {
  const optionsRes = await page.request.get("/api/grievance/options");
  expect(optionsRes.ok()).toBeTruthy();
  const options = await unwrapApiData<{
    grm_channels?: Array<{ id: number }>;
    preferred_languages?: Array<{ id: number }>;
    grievance_types?: Array<{ id: number }>;
    grievance_categories?: Array<{ id: number }>;
  }>(await optionsRes.json());

  const grmId = options.grm_channels?.[0]?.id ?? 1;
  const langId = options.preferred_languages?.[0]?.id ?? 1;
  const typeId = options.grievance_types?.[0]?.id ?? 1;
  const catId = options.grievance_categories?.[0]?.id ?? 1;

  const res = await page.request.post("/api/grievance/store", {
    data: {
      project_id: loc.projectId,
      municipality_id: loc.municipalityId,
      barangay_id: loc.barangayId,
      respondent_first_name: "Pedro",
      respondent_last_name: `Notify${tag}`,
      gender: "male",
      mobile_number: "09987654321",
      home_business_address: "123 Test St",
      grm_channel_id: grmId,
      preferred_language_ids: [langId],
      grievance_type_ids: [typeId],
      grievance_category_ids: [catId],
      incident_count: 1,
      incident_date: "2026-05-01",
      description_complaint: "E2E notification grievance",
      desired_resolution: "Resolution requested",
      date_recorded: "2026-06-01T10:00",
      ...(initialStatus
        ? {
            status: initialStatus.status,
            progress_level: initialStatus.progressLevel,
            status_effective_at: initialStatus.effectiveAt,
            status_note: initialStatus.note,
          }
        : {}),
    },
  });
  expect(res.ok(), await res.text()).toBeTruthy();
  const body = await res.json();
  const data = await unwrapApiData<{ id: number }>(body);
  return Number(data.id);
}

export async function resolveProjectWithLocation(
  page: Page,
): Promise<BootstrapLocation> {
  const res = await page.request.get("/api/projects?q=");
  expect(res.ok()).toBeTruthy();
  const rows = await unwrapApiData<Array<{ id: number; name: string }>>(
    await res.json(),
  );
  for (const row of rows) {
    const projectId = Number(row.id);
    if (!projectId) continue;
    const munRes = await page.request.get(
      `/api/projects/${projectId}/municipalities`,
    );
    if (!munRes.ok()) continue;
    const munWrap = await unwrapApiData<{
      municipalities?: Array<{ id: number; name: string }>;
    }>(await munRes.json());
    for (const mun of munWrap.municipalities ?? []) {
      const mid = Number(mun.id);
      if (!mid) continue;
      const brRes = await page.request.get(
        `/api/projects/${projectId}/barangays?municipality_id=${mid}`,
      );
      if (!brRes.ok()) continue;
      const brWrap = await unwrapApiData<{
        barangays?: Array<{ id: number; name: string }>;
      }>(await brRes.json());
      const br = (brWrap.barangays ?? [])[0];
      if (br?.id) {
        return {
          projectId,
          projectName: String(row.name),
          municipalityId: mid,
          municipalityName: String(mun.name),
          barangayId: Number(br.id),
          barangayName: String(br.name),
        };
      }
    }
  }
  throw new Error(
    "No project with municipality + barangay found. Seed library data or run E2E bootstrap first.",
  );
}

export async function bootstrapTwoProjects(page: Page): Promise<{
  p1: BootstrapLocation;
  p2: BootstrapLocation;
  tag: string;
}> {
  const tag = uniqueSuffix();
  await loginAsAdmin(page);
  const p1 = await resolveProjectWithLocation(page);
  const p2Base = await createLibraryProject(
    page,
    `E2E Notify P2 ${tag}`,
  );
  const p2: BootstrapLocation = {
    projectId: p2Base.projectId,
    projectName: p2Base.projectName,
    municipalityId: p1.municipalityId,
    municipalityName: p1.municipalityName,
    barangayId: p1.barangayId,
    barangayName: p1.barangayName,
  };
  return { p1, p2, tag };
}

export async function updateGrievanceStatusWeb(
  page: Page,
  grievanceId: number,
  status: "open" | "in_progress" | "closed",
): Promise<void> {
  await page.goto(`/grievance/view/${grievanceId}`);
  await page.selectOption("#statusSelect", status);
  if (status === "in_progress") {
    await page.waitForSelector('select[name="progress_level"]', {
      state: "visible",
      timeout: 10_000,
    });
    const levelOptions = page.locator('select[name="progress_level"] option');
    const count = await levelOptions.count();
    if (count > 1) {
      await page.selectOption('select[name="progress_level"]', { index: 1 });
    }
  }
  const effectiveDefault = await page
    .locator("#statusEffectiveAtInput")
    .getAttribute("data-server-default");
  if (effectiveDefault) {
    await page.fill("#statusEffectiveAtInput", effectiveDefault);
  }
  await page
    .locator('form[action*="/grievance/status-update/"] button[type="submit"]')
    .click();
  await page.waitForURL(/\/grievance\/view\/\d+/, { timeout: 45_000 });
}

export async function updateProfileContactWeb(
  page: Page,
  profileId: number,
  contactNumber: string,
): Promise<void> {
  await page.goto(`/profile/edit/${profileId}`);
  await page.fill('input[name="contacts_number[]"]', contactNumber);
  const saveBtn = page.locator("#profileEditAjaxSaveMain");
  if (await saveBtn.count()) {
    await Promise.all([
      page.waitForResponse(
        (r) =>
          r.url().includes("/api/profile/update/") &&
          r.request().method() === "POST" &&
          r.ok(),
        { timeout: 30_000 },
      ),
      saveBtn.click(),
    ]);
    return;
  }
  await page.click('#profileForm button[type="submit"]');
  await page.waitForURL(/\/profile\/view\/\d+/, { timeout: 45_000 });
}

export async function saveProfileEditWithoutChanges(
  page: Page,
  profileId: number,
): Promise<void> {
  await page.goto(`/profile/edit/${profileId}`);
  const saveBtn = page.locator("#profileEditAjaxSaveMain");
  if (await saveBtn.count()) {
    await saveBtn.click();
    await page.waitForTimeout(1500);
    return;
  }
  await page.click('#profileForm button[type="submit"]');
  await page.waitForURL(/\/profile\/view\/\d+/, { timeout: 45_000 });
}

export { loginAs, logout, uniqueSuffix };
