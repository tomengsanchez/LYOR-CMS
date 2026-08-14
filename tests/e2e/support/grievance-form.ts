import { expect, type Page } from "@playwright/test";
import { extractIdFromUrl } from "./helpers";
import type { BootstrapLocation } from "./bootstrap";
import { selectProfileLocation } from "./bootstrap";

export async function fillAndSubmitGrievance(
  page: Page,
  loc: Pick<
    BootstrapLocation,
    "projectId" | "projectName" | "municipalityId" | "barangayId"
  >,
  respondentLastName: string,
  opts?: {
    categoryNames?: string[];
    dateRecorded?: string;
    initialStatus?: {
      status: "open" | "in_progress" | "closed";
      progressLevel?: string;
      effectiveAt?: string;
      note?: string;
    };
  },
): Promise<number> {
  await page.goto("/grievance/create");
  await expect(page.locator("#grievanceForm")).toBeVisible();

  if (opts?.dateRecorded) {
    await page.fill('input[name="date_recorded"]', opts.dateRecorded);
  }

  await page.fill("#respondentFirstName", "Pedro");
  await page.fill("#respondentLastName", respondentLastName);
  await selectProfileLocation(page, loc);
  await page.check("#genderMale");
  await page.fill('input[name="mobile_number"]', "09987654321");
  await page.fill(
    'input[name="home_business_address"]',
    "123 Maharlika St, San Isidro",
  );

  const grmRadio = page.locator('#grmChannelGroup input[type="radio"]').first();
  await expect(grmRadio).toBeVisible();
  await grmRadio.check();

  await page
    .locator('#preferredLanguageGroup input[type="checkbox"]')
    .first()
    .check();
  await page
    .locator('#grievanceTypeGroup input[type="checkbox"]')
    .first()
    .check();
  if (opts?.categoryNames?.length) {
    const categoryBoxes = page.locator(
      '#grievanceCategoryGroup input[type="checkbox"]',
    );
    const boxCount = await categoryBoxes.count();
    for (let i = 0; i < boxCount; i++) {
      await categoryBoxes.nth(i).setChecked(false);
    }
    for (const categoryName of opts.categoryNames) {
      await page
        .locator("#grievanceCategoryGroup .form-check", {
          hasText: categoryName,
        })
        .locator('input[type="checkbox"]')
        .check();
    }
  } else {
    await page
      .locator('#grievanceCategoryGroup input[type="checkbox"]')
      .first()
      .check();
  }

  await page.check("#incidentOne");
  await page.fill('input[name="incident_date"]', "2026-05-01");
  await page.fill(
    'textarea[name="description_complaint"]',
    "E2E grievance: alingasaw mula sa konstruksyon malapit sa tahanan.",
  );
  await page.fill(
    'textarea[name="desired_resolution"]',
    "Magsagawa ng mitigating measures at regular monitoring.",
  );

  if (opts?.initialStatus) {
    await page.selectOption("#createStatusSelect", opts.initialStatus.status);
    if (opts.initialStatus.status === "in_progress") {
      await expect(page.locator("#createProgressLevelSelect")).toBeEnabled();
      await page
        .locator("#createProgressLevelSelect")
        .selectOption({ label: opts.initialStatus.progressLevel! });
    }
    if (opts.initialStatus.effectiveAt) {
      await page.fill(
        "#createStatusEffectiveAtInput",
        opts.initialStatus.effectiveAt,
      );
    }
    if (opts.initialStatus.note) {
      await page.fill(
        'textarea[name="status_note"]',
        opts.initialStatus.note,
      );
    }
  }

  await page.click('#grievanceForm button[type="submit"]');
  await page.waitForURL(/\/grievance\/view\/\d+/, { timeout: 60_000 });
  return extractIdFromUrl(page.url());
}
