import { execSync } from "node:child_process";
import path from "node:path";

const root = path.resolve(__dirname, "../../..");

function runPhp(args: string[]): void {
  const php = process.env.PHP_BINARY || "php";
  execSync([php, ...args].join(" "), {
    cwd: root,
    stdio: "inherit",
    env: process.env,
  });
}

/** Simple CMS Playwright global setup — optional migrate only. */
export default async function globalSetup(): Promise<void> {
  if (process.env.E2E_SKIP_MIGRATE === "1") {
    return;
  }
  try {
    runPhp(["cli/migrate.php", "--status"]);
  } catch {
    // Non-fatal: dev DB may already be migrated
  }
}
