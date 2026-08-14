import { expect, type Page } from "@playwright/test";
import {
  appendSelect2Multiple,
  extractIdFromUrl,
  uniqueSuffix,
  unwrapApiData,
} from "./helpers";
import {
  type BootstrapLocation,
  ensureProjectPhase,
  selectProfileLocation,
  selectStructureLocation,
} from "./bootstrap";

export type ProfileCreateResult = {
  profileId: number;
  structureTag: string;
  structureTags: string[];
  lastName: string;
};

export async function createStructureViaApi(
  page: Page,
  loc: BootstrapLocation,
  structureTag: string,
): Promise<number> {
  const phaseId =
    loc.phaseId && loc.phaseId > 0
      ? loc.phaseId
      : (await ensureProjectPhase(page, loc.projectId)).id;
  const res = await page.request.post("/api/structure/store", {
    multipart: {
      project_id: String(loc.projectId),
      municipality_id: String(loc.municipalityId),
      barangay_id: String(loc.barangayId),
      phase_id: String(phaseId),
      structure_tag: structureTag,
      description: "E2E profile structure tags test",
    },
  });
  expect(res.ok(), await res.text()).toBeTruthy();
  const body = await res.json();
  const data = await unwrapApiData<{ id: number }>(body);
  const id = Number(data.id);
  expect(id).toBeGreaterThan(0);

  const getRes = await page.request.get(`/api/structure/${id}`);
  expect(getRes.ok(), await getRes.text()).toBeTruthy();
  const structure = await unwrapApiData<{
    municipality_id?: number | null;
    barangay_id?: number | null;
    phase_id?: number | null;
    structure_tag?: string | null;
  }>(await getRes.json());
  expect(structure.structure_tag).toBe(structureTag);
  expect(Number(structure.municipality_id)).toBe(loc.municipalityId);
  expect(Number(structure.barangay_id)).toBe(loc.barangayId);
  expect(Number(structure.phase_id)).toBe(phaseId);

  return id;
}

export async function selectProfileStructureTags(
  page: Page,
  tags: string[],
): Promise<void> {
  const $sel = page.locator("#profileStructureTagSelect");
  await expect($sel).toBeEnabled({ timeout: 20_000 });
  for (const tag of tags) {
    await appendSelect2Multiple(page, "profileStructureTagSelect", tag, tag);
  }
  await expect
    .poll(async () => {
      return page.evaluate(() => {
        const el = document.querySelector(
          "#profileStructureTagSelect",
        ) as HTMLSelectElement | null;
        return el ? Array.from(el.selectedOptions).map((o) => o.value) : [];
      });
    })
    .toEqual(expect.arrayContaining(tags));
}

export async function createProfileViaForm(
  page: Page,
  loc: BootstrapLocation,
  opts: { structureTag?: string; structureTags?: string[] } = {},
): Promise<ProfileCreateResult> {
  const tag = uniqueSuffix();
  const structureTag = opts.structureTag ?? "";
  const structureTags = opts.structureTags ?? (structureTag ? [structureTag] : []);
  const lastName = `DelosReyes${tag}`;
  const firstName = "Maria";

  await page.goto("/profile/create");
  await expect(page.locator("#profileForm")).toBeVisible();
  await selectProfileLocation(page, loc);

  await page.fill('input[name="last_name"]', lastName);
  await page.fill('input[name="first_name"]', firstName);
  await page.fill('input[name="age"]', "35");
  await page.fill('input[name="date_of_invitation"]', "2026-05-28");
  await page.fill('input[name="contacts_number[]"]', "09171234567");
  if (structureTags.length) {
    await selectProfileStructureTags(page, structureTags);
  }

  await page.selectOption("#profileInvitationRsvp", {
    label: "Attend (Dadalo)",
  });
  await page.selectOption("#profileInvitationDistributionStatus", {
    label: "Received",
  });

  await page.click('#profileForm button[type="submit"]');
  await page.waitForURL(/\/profile\/view\/\d+/, { timeout: 45_000 });

  return {
    profileId: extractIdFromUrl(page.url()),
    structureTag: structureTags[0] ?? "",
    structureTags,
    lastName,
  };
}

export async function createStructureViaForm(
  page: Page,
  loc: BootstrapLocation,
  structureTag: string,
): Promise<number> {
  await page.goto("/structure/create");
  await selectStructureLocation(page, loc);

  await page.fill('input[name="structure_tag"]', structureTag);
  await page.fill('input[name="location_of_structure"]', "Along national highway, Barangay center");
  await page.fill("#structureGpsLatitude", "N15°58.209");
  await page.fill("#structureGpsLongitude", "E120°9.687");
  await page.fill('textarea[name="description"]', "E2E structure tagging record");

  const saveBtn = page.locator("#structureFormSubmitBtn");
  await expect(saveBtn).toBeEnabled({ timeout: 15_000 });
  await saveBtn.click();
  await page.waitForURL(/\/structure\/view\/\d+/, { timeout: 45_000 });
  return extractIdFromUrl(page.url());
}
