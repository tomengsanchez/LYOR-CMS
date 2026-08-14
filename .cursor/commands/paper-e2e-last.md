# paper-e2e-last

Add or extend **Playwright** end-to-end tests for the **latest profile/UI changes** (structure ownership, spouse when Owner, invitation received by, remarks, name display order, **Structure Tag #** auto-create).

**Run locally** (set `BASE_URL` if not using default `http://eco.local`):

```bash
npm run test:e2e:profile-ownership
npm run test:e2e:profile-structure-tag
npm run test:e2e:structure-list-paps
npm run test:e2e:profile-structure-linking
```

**All profile ↔ structure linking specs (tag, list PAPS, integration):**

```bash
npm run test:e2e:profile-structure-all
```

Runs with a single worker to avoid rare duplicate PAPSID errors when many profiles are created at once.

**Full profile suite:**

```bash
npm run test:e2e:profile-regression
```

Requires `ADMIN_USER` / `ADMIN_PASS` (defaults to `admin` / `admin123` when not CI). Some tests skip if no project with barangays exists in the target environment.
