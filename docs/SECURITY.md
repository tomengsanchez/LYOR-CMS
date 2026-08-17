# Simple CMS – Security policy & threat model sketch

**Related:** ADR-0004, ADR-0007, [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md), [CONFIGURATION.md](CONFIGURATION.md), [NFR.md](NFR.md).

Root [`SECURITY.md`](../SECURITY.md) points here for vulnerability reporting.

## Principles

1. Least privilege via **capabilities**.
2. Session + CSRF (web), hashed Bearer tokens (API), throttling, optional email 2FA.
3. Secrets never in git; backup ZIPs are secrets.
4. Sanitize uploads and HTML modules; allowlisted CSS in the layout compiler.
5. Durable decisions as ADRs.

## Trust boundaries

```text
[Browser] --HTTPS--> [PHP]
                       |-- session / Bearer --> Auth
                       |-- PDO --> [Database]
                       |-- files --> [public/uploads]
[Operator CLI] --> backup / restore / migrate
```

Untrusted: all HTTP input, uploads, HTML module markup, email content.

## Assets

| Asset | Sensitivity |
|-------|-------------|
| User accounts / sessions | High |
| Backup ZIPs (DB + uploads) | High |
| Media uploads | Medium |
| Public pages/posts | Low–medium |

## Layout builder

`layout_json` is normalized on save. Design CSS uses allowlists (`safeColor`, `safeSpacing`, box-shadow keys). Custom HTML modules strip script/iframe/form tags on public render. Do not store raw user CSS.

## Reporting

Follow the root `SECURITY.md` contact process. Do not file public issues with exploit details.
