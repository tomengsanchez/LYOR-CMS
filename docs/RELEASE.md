# PAPeR – Release process

How to cut a release candidates and production deploys. Versioning is currently **calendar / CHANGES-driven** (no strict SemVer tags required); if you tag git releases, use `YYYY.MM.DD` or SemVer and record it in CHANGES.

---

## 1. Pre-release checklist

### Code & schema
- [ ] Feature branch merged; CI / local `npm run test:e2e:all` (or agreed smoke) green  
- [ ] New migrations have `up` + `down`; tested `migrate` / rollback on a copy  
- [ ] MySQL **and** MariaDB considerations reviewed  
- [ ] Backup/restore impact checked (new tables/files)  
- [ ] Gate run logged under [test-results/](test-results/README.md) (copy TEMPLATE → `YYYY-MM/…`)  

### Docs & contracts
- [ ] [changes/YYYY-MM/CHANGES.md](changes/) entry written  
- [ ] DevelopmentHistory JSON if substantial  
- [ ] ADR if durable decision  
- [ ] ERD / UML / capability matrix / config catalog updated if needed  
- [ ] `API_CONTRACT` + Postman (+ mobile docs) if API changed  
- [ ] Help / Admin Guide updated if users need guidance  

### Security
- [ ] No secrets committed  
- [ ] New routes have authZ + CSRF where required  
- [ ] Default passwords not left on staging/prod  

---

## 2. Staging deploy

Follow [DEPLOYMENT.md](DEPLOYMENT.md) §3 on staging:

1. Backup staging DB + uploads  
2. Deploy code; `composer install`  
3. `php cli/migrate.php`  
4. Smoke: login, one profile, one grievance, one API list  
5. If restore-related change: restore drill on a clone  

---

## 3. Production deploy

1. Announce maintenance window if needed  
2. **Backup** (`php cli/backup.php`) + confirm offsite copy  
3. Deploy code  
4. `php cli/migrate.php`  
5. Clear any opcode cache if used  
6. Smoke checklist (below)  
7. Monitor `logs/`, email queue, failed logins for ~1 hour  

### Production smoke
- [ ] `/login` works  
- [ ] Profile list + open one record  
- [ ] Grievance list + dashboard  
- [ ] `GET /api/meta/error-codes` (no auth)  
- [ ] Authenticated API list with Bearer or session  
- [ ] Notification bell loads  
- [ ] Backup UI still creates a ZIP (optional)  

---

## 4. Rollback

| Failure | Action |
|---------|--------|
| Bad code only | Redeploy previous release artifact; no migrate rollback if schema unchanged |
| Bad migration | Restore from pre-deploy ZIP (`cli/restore.php`) on a planned window; or migrate `--rollback` only if `down` is safe and tested |
| Data corruption | Restore from last known-good offsite backup; completion audit on |

Never run `truncate_fresh_install` on production.

---

## 5. Post-release

- [ ] Confirm CHANGES entry matches what shipped  
- [ ] Close related issues / notify stakeholders  
- [ ] Schedule next restore drill if backup tooling changed  
