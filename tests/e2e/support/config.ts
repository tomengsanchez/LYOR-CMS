/** Playwright base URL — override with BASE_URL env (default http://eco.local). */
export const baseURL = (process.env.BASE_URL || "http://eco.local").replace(/\/$/, "");

export const adminUser = process.env.ADMIN_USER || "admin";
export const adminPass = process.env.ADMIN_PASS || "admin123";
