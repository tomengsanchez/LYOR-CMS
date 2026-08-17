# Simple CMS – Test strategy

Record significant runs under [test-results/](test-results/README.md). Playwright `BASE_URL` is configurable (ADR-0010).

## Pyramid

| Layer | What | Command / location |
|-------|------|--------------------|
| CLI smoke | Layout builder, theme, RSS, backup | `npm run test:cms-layout-builder`, other `test:cms-*` |
| E2E | Login + CMS paths | `tests/e2e/cms-*.spec.ts` |
| Backup/restore | Round-trip | `npm run test:backup-restore` |

## Environments

| Env | Use |
|-----|-----|
| Local | Playwright against `BASE_URL` |
| CI | Headless; `CI=1` |
| Staging | Restore drills |

Never run `truncate_fresh_install` or restore overwrite against production.

## Builder E2E

`npm run test:e2e:cms-builder-drag-column-size` uses `BASE_URL`, `E2E_SKIP_MIGRATE=1`. Do not use Playwright `dragTo` on Layers rows (HTML5 drag hangs).
