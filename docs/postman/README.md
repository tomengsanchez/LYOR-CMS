# Postman collections

Versioned **Postman v2.1** exports live here (not under the gitignored top-level `postman/` folder used for private scratch files).

## Simple CMS (current)

1. Postman → **Import** → **`Simple-CMS-API.postman_collection.json`**.
2. Set collection variable **`baseUrl`** (default in docs: `http://cms.local` or your host).
3. Run **Auth → Login**; copy **`data.token`** into variable **`token`** for Bearer requests.
4. REST routes live under **`/api/*`** (unchanged by the `/admin` UI prefix).

Legacy **PAPeR** exports (`PAPeR-API.postman_collection.json`) remain for historical reference but do not match the current CMS codebase.

## Legacy PAPeR import (archived)

1. Postman → **Import** → choose **`PAPeR-API.postman_collection.json`**.
2. Import **`PAPeR-Local.postman_environment.json`**, then select environment **PAPeR Local**.
3. Adjust **`baseUrl`** if your host differs (default `http://eco.local`).

## Regenerate legacy PAPeR collection

After routes change in `public/index.php`, run from the repository root:

```bash
node docs/postman/generate-collection.cjs
```

Or: `npm run postman:collection` from the repository root.

This overwrites **`PAPeR-API.postman_collection.json`** from **`generate-collection.cjs`** (REST + web module catalog).

## Auth (REST)

- Run **Auth → Login** first. The request **Tests** script saves **`api_token`** to the active environment (reads `data.token` per `docs/API_AUTH.md`).
- Other **`/api/*`** requests use **Bearer {{api_token}}**.

See **`docs/API_AUTH.md`** for login, 2FA, and logout flows.
