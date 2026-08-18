/** Playwright base URL — override with BASE_URL env (default http://cms.local). */
export const baseURL = (process.env.BASE_URL || "http://cms.local").replace(/\/$/, "");

export const adminUser = process.env.ADMIN_USER || "admin";
export const adminPass = process.env.ADMIN_PASS || "admin123";
