import { existsSync, readFileSync } from "node:fs";
import path from "node:path";
import { defineConfig } from "@playwright/test";

/** Load gitignored `.env.playwright` (or `env.playwright`) into process.env when keys are unset. */
function loadPlaywrightEnvFile(): void {
  const dotted = path.resolve(__dirname, ".env.playwright");
  const undotted = path.resolve(__dirname, "env.playwright");
  const envPath = existsSync(dotted) ? dotted : undotted;
  if (!existsSync(envPath)) {
    return;
  }
  for (const line of readFileSync(envPath, "utf8").split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#")) {
      continue;
    }
    const eq = trimmed.indexOf("=");
    if (eq <= 0) {
      continue;
    }
    const key = trimmed.slice(0, eq).trim();
    let value = trimmed.slice(eq + 1).trim();
    if (
      (value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'"))
    ) {
      value = value.slice(1, -1);
    }
    if (key && process.env[key] === undefined) {
      process.env[key] = value;
    }
  }
}

loadPlaywrightEnvFile();

// Local runs: Playwright does not load a shell profile, so admin credentials are often unset.
if (!process.env.CI) {
  process.env.ADMIN_USER ||= "admin";
  process.env.ADMIN_PASS ||= "admin123";
}

const baseURL = process.env.BASE_URL || "http://eco.local";

/** Headed + 1200ms slowMo unless HEADLESS=true or PW_SLOW_MO_MS is set. Use `npm run test:e2e` (--headed). */
const headless = process.env.HEADLESS === "true";
const slowMoMs = Number(process.env.PW_SLOW_MO_MS ?? "1200");
const slowRunForTimeouts =
  process.env.PW_SLOW_RUN === "1" ||
  String(process.env.PW_SLOW_RUN || "").toLowerCase() === "true" ||
  (Number.isFinite(slowMoMs) && slowMoMs > 0);
const slowRunMultiplier = slowRunForTimeouts ? 4 : 1;
const defaultTestTimeoutMs = 45_000 * slowRunMultiplier;
const defaultExpectTimeoutMs = 5_000 * slowRunMultiplier;
const navigationTimeoutMs = Math.min(120_000, 45_000 * slowRunMultiplier);

export default defineConfig({
  testDir: "./tests/e2e",
  globalSetup: "./tests/e2e/support/global-setup.ts",
  timeout: defaultTestTimeoutMs,
  expect: {
    timeout: defaultExpectTimeoutMs,
  },
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [["html", { open: "never" }], ["list"]],
  use: {
    baseURL,
    navigationTimeout: navigationTimeoutMs,
    headless,
    launchOptions: {
      slowMo: Number.isFinite(slowMoMs) && slowMoMs > 0 ? slowMoMs : 0,
    },
    trace: "on-first-retry",
    screenshot: "only-on-failure",
    video: "retain-on-failure",
  },
});
