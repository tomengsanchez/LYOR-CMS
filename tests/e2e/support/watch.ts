import type { Page } from "@playwright/test";

function watchPauseMs(): number {
  const raw = process.env.PW_WATCH_PAUSE_MS ?? "4000";
  const n = Number(raw);
  return Number.isFinite(n) && n > 0 ? n : 0;
}

/** Hold the screen during headed runs so badges and transitions are easy to follow. */
export async function pauseToWatch(page: Page, note?: string): Promise<void> {
  const ms = watchPauseMs();
  if (ms <= 0) {
    return;
  }

  if (note) {
    await page.evaluate((msg) => {
      let el = document.getElementById("pw-watch-banner");
      if (!el) {
        el = document.createElement("div");
        el.id = "pw-watch-banner";
        el.style.cssText =
          "position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:99999;" +
          "background:#212529;color:#fff;padding:10px 18px;border-radius:8px;" +
          "font:600 14px system-ui,sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.35);" +
          "pointer-events:none;";
        document.body.appendChild(el);
      }
      el.textContent = msg;
    }, note);
  }

  await page.waitForTimeout(ms);
}
