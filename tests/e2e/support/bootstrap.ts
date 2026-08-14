import { expect, type Page } from "@playwright/test";
import {
  extractIdFromUrl,
  selectSelect2,
  uniqueSuffix,
  unwrapApiData,
} from "./helpers";

export type BootstrapLocation = {
  projectId: number;
  projectName: string;
  municipalityId: number;
  municipalityName: string;
  barangayId: number;
  barangayName: string;
  phaseId?: number;
  phaseName?: string;
};

/** Active phases for a project (ensures Unassigned via API). */
export async function fetchProjectPhases(
  page: Page,
  projectId: number,
): Promise<Array<{ id: number; name: string; description?: string }>> {
  const res = await page.request.get(`/api/projects/${projectId}/phases`);
  expect(res.ok(), await res.text()).toBeTruthy();
  const wrap = await unwrapApiData<{
    phases?: Array<{ id: number; name: string; description?: string }>;
  }>(await res.json());
  return (wrap.phases ?? []).map((p) => ({
    id: Number(p.id),
    name: String(p.name ?? ""),
    description: p.description != null ? String(p.description) : undefined,
  }));
}

/** Prefer Unassigned, else first active phase. */
export async function ensureProjectPhase(
  page: Page,
  projectId: number,
): Promise<{ id: number; name: string }> {
  const phases = await fetchProjectPhases(page, projectId);
  expect(phases.length, "project should have at least Unassigned phase").toBeGreaterThan(
    0,
  );
  const unassigned = phases.find((p) => p.name === "Unassigned");
  const pick = unassigned ?? phases[0];
  return { id: pick.id, name: pick.name };
}

/** Create project → municipality (linked) → barangay for profile/structure location. */
export async function bootstrapProjectWithLocation(
  page: Page,
  tag = uniqueSuffix(),
): Promise<BootstrapLocation> {
  const projectName = `E2E Project ${tag}`;
  const municipalityName = `E2E Mun ${tag}`;
  const barangayName = `E2E Brgy ${tag}`;
  const munCode = `E2M${tag.slice(-6).toUpperCase()}`;
  const brgyCode = `E2B${tag.slice(-6).toUpperCase()}`;

  await page.goto("/library/create");
  await page.fill('input[name="name"]', projectName);
  await page.fill(
    'textarea[name="description"]',
    "Playwright E2E bootstrap project",
  );
  await page.click('#libraryProjectForm button[type="submit"]');
  await page.waitForURL(/\/library\/view\/\d+/);
  const projectId = extractIdFromUrl(page.url());

  await page.goto("/library/municipalities/create");
  await page.fill('input[name="name"]', municipalityName);
  await page.fill('input[name="code"]', munCode);
  await selectSelect2(
    page,
    "municipalityProjectSelect",
    String(projectId),
    projectName,
  );
  await page.locator('main.content form button[type="submit"]').click();
  await page.waitForURL(/\/library\/municipalities(\/view\/\d+)?\/?(?:\?|$)/, {
    timeout: 60_000,
  });
  let municipalityId = 0;
  if (/\/library\/municipalities\/view\/\d+/.test(page.url())) {
    municipalityId = extractIdFromUrl(page.url());
  } else {
    await page.goto(
      `/library/municipalities?q=${encodeURIComponent(municipalityName)}`,
    );
    const viewHref = await page
      .locator('a[href*="/library/municipalities/view/"]')
      .first()
      .getAttribute("href");
    municipalityId = extractIdFromUrl(viewHref || "");
  }
  expect(municipalityId).toBeGreaterThan(0);

  await page.goto("/library/barangays/create");
  await selectSelect2(
    page,
    "barangayMunicipalitySelect",
    String(municipalityId),
    municipalityName,
  );
  await page.fill('input[name="name"]', barangayName);
  await page.fill('input[name="code"]', brgyCode);
  await page.locator('main.content form button[type="submit"]').click();
  await page.waitForURL(/\/library\/barangays\/?(?:\?|$)/, { timeout: 60_000 });
  let barangayId = 0;
  if (/\/library\/barangays\/view\/\d+/.test(page.url())) {
    barangayId = extractIdFromUrl(page.url());
  } else {
    await page.goto(
      `/library/barangays?q=${encodeURIComponent(barangayName)}`,
    );
    const viewHref = await page
      .locator('a[href*="/library/barangays/view/"]')
      .first()
      .getAttribute("href");
    barangayId = extractIdFromUrl(viewHref || "");
  }
  expect(barangayId).toBeGreaterThan(0);

  const phase = await ensureProjectPhase(page, projectId);

  return {
    projectId,
    projectName,
    municipalityId,
    municipalityName,
    barangayId,
    barangayName,
    phaseId: phase.id,
    phaseName: phase.name,
  };
}

/** Create a library project only (no municipality/barangay). Enough for grievance create. */
export async function createLibraryProject(
  page: Page,
  projectName: string,
): Promise<Pick<BootstrapLocation, "projectId" | "projectName">> {
  await page.goto("/library/create");
  await page.fill('input[name="name"]', projectName);
  await page.fill(
    'textarea[name="description"]',
    "Playwright E2E project (grievance escalation)",
  );
  await page.click('#libraryProjectForm button[type="submit"]');
  await page.waitForURL(/\/library\/view\/\d+/, { timeout: 60_000 });
  return {
    projectId: extractIdFromUrl(page.url()),
    projectName,
  };
}

/** Pick first project that already has municipality + barangay via API, or bootstrap. */
export async function ensureProjectWithLocation(
  page: Page,
): Promise<BootstrapLocation> {
  for (const q of ["E2E", ""]) {
    const res = await page.request.get(
      q ? `/api/projects?q=${encodeURIComponent(q)}` : "/api/projects",
    );
    if (!res.ok()) {
      continue;
    }
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
      const munPayload = await munRes.json();
      const munWrap = await unwrapApiData<{
        municipalities?: Array<{ id: number; name: string }>;
      }>(munPayload);
      const muns = munWrap.municipalities ?? [];

      for (const mun of muns) {
        const mid = Number(mun.id);
        if (!mid) continue;
        const brRes = await page.request.get(
          `/api/projects/${projectId}/barangays?municipality_id=${mid}`,
        );
        if (!brRes.ok()) continue;
        const brPayload = await brRes.json();
        const brWrap = await unwrapApiData<{
          barangays?: Array<{ id: number; name: string }>;
        }>(brPayload);
        const brgys = brWrap.barangays ?? [];
        const br = brgys[0];
        if (br?.id) {
          const phase = await ensureProjectPhase(page, projectId);
          return {
            projectId,
            projectName: String(row.name),
            municipalityId: mid,
            municipalityName: String(mun.name),
            barangayId: Number(br.id),
            barangayName: String(br.name),
            phaseId: phase.id,
            phaseName: phase.name,
          };
        }
      }
    }
  }

  return bootstrapProjectWithLocation(page);
}

export async function selectProfileLocation(
  page: Page,
  loc: Pick<
    BootstrapLocation,
    "projectId" | "projectName" | "municipalityId" | "barangayId" | "phaseId"
  >,
): Promise<void> {
  await page.evaluate(
    ({ projectId, projectName }) => {
      const $ = (window as unknown as { jQuery: (s: string) => { length: number; append: (o: HTMLOptionElement) => unknown; val: (v?: string) => unknown; trigger: (e: string) => unknown; find: (sel: string) => { length: number } } }).jQuery;
      const $proj = $("#projectSelect");
      if (!$proj.find(`option[value="${projectId}"]`).length) {
        $proj.append(new Option(projectName, String(projectId), true, true));
      }
      $proj.val(String(projectId)).trigger("change");
    },
    { projectId: loc.projectId, projectName: loc.projectName },
  );

  await page.waitForFunction(
    (mid) => {
      const sel = document.querySelector(
        "#municipalitySelect",
      ) as HTMLSelectElement | null;
      return (
        sel &&
        !sel.disabled &&
        Array.from(sel.options).some((o) => o.value === String(mid))
      );
    },
    loc.municipalityId,
    { timeout: 20_000 },
  );
  await page.selectOption("#municipalitySelect", String(loc.municipalityId));

  await page.waitForFunction(
    (bid) => {
      const sel = document.querySelector(
        "#barangaySelect",
      ) as HTMLSelectElement | null;
      return (
        sel &&
        !sel.disabled &&
        Array.from(sel.options).some((o) => o.value === String(bid))
      );
    },
    loc.barangayId,
    { timeout: 20_000 },
  );
  await page.selectOption("#barangaySelect", String(loc.barangayId));

  const phaseId =
    loc.phaseId && loc.phaseId > 0
      ? loc.phaseId
      : (await ensureProjectPhase(page, loc.projectId)).id;

  await page.waitForFunction(
    (pid) => {
      const sel = document.querySelector(
        "#phaseSelect",
      ) as HTMLSelectElement | null;
      return (
        sel &&
        !sel.disabled &&
        Array.from(sel.options).some((o) => o.value === String(pid))
      );
    },
    phaseId,
    { timeout: 20_000 },
  );
  await page.selectOption("#phaseSelect", String(phaseId));
}

export async function selectStructureLocation(
  page: Page,
  loc: Pick<
    BootstrapLocation,
    "projectId" | "projectName" | "municipalityId" | "barangayId" | "phaseId"
  >,
): Promise<void> {
  await page.evaluate(
    ({ projectId, projectName }) => {
      const $ = (window as unknown as { jQuery: (s: string) => { length: number; append: (o: HTMLOptionElement) => unknown; val: (v?: string) => unknown; trigger: (e: string) => unknown; find: (sel: string) => { length: number } } }).jQuery;
      const $proj = $("#structureProjectSelect");
      if (!$proj.find(`option[value="${projectId}"]`).length) {
        $proj.append(new Option(projectName, String(projectId), true, true));
      }
      $proj.val(String(projectId)).trigger("change");
    },
    { projectId: loc.projectId, projectName: loc.projectName },
  );

  await page.waitForFunction(
    (mid) => {
      const sel = document.querySelector(
        "#structureMunicipalitySelect",
      ) as HTMLSelectElement | null;
      return (
        sel &&
        !sel.disabled &&
        Array.from(sel.options).some((o) => o.value === String(mid))
      );
    },
    loc.municipalityId,
    { timeout: 20_000 },
  );
  await page.selectOption(
    "#structureMunicipalitySelect",
    String(loc.municipalityId),
  );

  await page.waitForFunction(
    (bid) => {
      const sel = document.querySelector(
        "#structureBarangaySelect",
      ) as HTMLSelectElement | null;
      return (
        sel &&
        !sel.disabled &&
        Array.from(sel.options).some((o) => o.value === String(bid))
      );
    },
    loc.barangayId,
    { timeout: 20_000 },
  );
  await page.selectOption(
    "#structureBarangaySelect",
    String(loc.barangayId),
  );

  const phaseId =
    loc.phaseId && loc.phaseId > 0
      ? loc.phaseId
      : (await ensureProjectPhase(page, loc.projectId)).id;

  await page.waitForFunction(
    (pid) => {
      const sel = document.querySelector(
        "#structurePhaseSelect",
      ) as HTMLSelectElement | null;
      return (
        sel &&
        !sel.disabled &&
        Array.from(sel.options).some((o) => o.value === String(pid))
      );
    },
    phaseId,
    { timeout: 20_000 },
  );
  await page.selectOption("#structurePhaseSelect", String(phaseId));
}
