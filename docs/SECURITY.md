# PAPeR – Security policy & threat model sketch

**Related:** ADR-0004, ADR-0007, [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md), [CONFIGURATION.md](CONFIGURATION.md), [NFR.md](NFR.md) §4, `docs/SecurityAudit/`, hardening notes in CHANGES.

Root [`SECURITY.md`](../SECURITY.md) points here for vulnerability reporting.

---

## 1. Security principles

1. Least privilege via **capabilities** + **project scope**.  
2. Defense in depth: session + CSRF (web), hashed Bearer tokens (API), throttling, optional 2FA.  
3. Secrets never in git; backup ZIPs are secrets.  
4. Prefer portable, reviewed SQL; validate uploads; soft-delete over silent hard delete.  
5. Document durable security decisions as ADRs.

---

## 2. Trust boundaries

```text
[Browser / Mobile] --HTTPS--> [Web/API PHP]
                                |-- session cookie / Bearer --> Auth
                                |-- PDO --> [Database]
                                |-- files --> [public/uploads]
                                |-- optional --> [SMTP / MailerSend / LLM API]
[Operator shell] --> [cli backup/restore/migrate] --> DB + uploads
```

- **Untrusted:** all HTTP input, uploaded files, email content, LLM responses.  
- **Trusted admin:** users with Administrator bypass + System capabilities.  
- **Host operators:** filesystem and DB credentials — higher privilege than app roles.

---

## 3. Assets

| Asset | Sensitivity |
|-------|-------------|
| Profile / grievance PII | High |
| Uploads (IDs, photos, docs) | High |
| Password hashes / tokens | Critical |
| `app_settings` API keys (email, help chat) | Critical |
| Backup ZIPs | Critical (full dump) |
| Audit / traffic logs | Medium–High |
| Capability grants | High |

---

## 4. STRIDE-style notes (summary)

| Threat | Mitigations in PAPeR | Residual risk |
|--------|----------------------|---------------|
| **Spoofing** | Password auth, optional 2FA, session regenerate on login, hashed API tokens | Weak passwords; stolen Bearer token until idle/absolute expiry |
| **Tampering** | CSRF on web POST; capability checks; prepared statements | XSS if unsafe HTML echo — keep escaping discipline |
| **Repudiation** | `audit_log`, status log, sessions with IP/UA | Clock skew; shared admin accounts |
| **Information disclosure** | AuthZ on routes; serve routes gated; `dev-help` must not be public | Misconfigured docroot exposing `config/` or backups |
| **Denial of service** | Login throttle, help chat rate limit, traffic IP blocks | App-layer only — need edge WAF/rate limits for large attacks |
| **Elevation of privilege** | Capability matrix; admin bypass intentional | Over-granted roles; forgotten `requireCapability` on new routes |

---

## 5. AuthN / AuthZ checklist

- [ ] Web mutating routes call `validateCsrf()`  
- [ ] Sensitive actions call `requireCapability` (or admin-only controller)  
- [ ] API uses `requireAuthApi()` + capability  
- [ ] Project-scoped queries use `UserProjects`  
- [ ] Tokens hashed at rest; revoke on logout/compromise  
- [ ] Default admin password changed in non-dev  

---

## 6. Data handling

- Soft delete retains data until ops purge policy (define per org).  
- Ask Help: help content only — no row-level PII to LLM.  
- Prune traffic logs to limit retention.  
- Production DB dumps and ZIPs: encrypt at rest offsite if required by org policy.

---

## 7. Vulnerability disclosure

Report suspected vulnerabilities **privately** to the project maintainers / hosting organization security contact (fill in below). Do not file public issues with exploit details.

| Contact | Value |
|---------|--------|
| Security email | TBD |
| Response target | Acknowledge within **5 business days** (adjust as needed) |

Include: affected version/commit, environment, steps to reproduce, impact.

Point-in-time reviews may appear under `docs/SecurityAudit/`.

---

## 8. Hardening baseline (production)

1. HTTPS only; secure session cookies.  
2. Block web access to `config/`, `cli/`, `storage/`, `logs/`, `database/`.  
3. **`dev-help/`:** prefer DocumentRoot = `public/` so it is not served; if project-root docroot, repo `.htaccess` + `dev-help/.htaccess` allow **localhost only** (see [DEPLOYMENT.md](DEPLOYMENT.md) §4.1). Verify externally → 403/404.  
4. Enable 2FA for admins; strong password policy.  
5. Cron email worker; monitor failed logins.  
6. Daily backups + offsite + restore drill.  
7. Keep PHP and OS packages updated.  
