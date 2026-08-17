# Postman collections

## Simple CMS (current)

1. Postman → **Import** → [`Simple-CMS-API.postman_collection.json`](Simple-CMS-API.postman_collection.json)
2. Set collection variable **`baseUrl`** (example `http://cms.local`)
3. Run **Auth → Login**; copy **`data.token`** into **`token`**

REST lives under `/api/*` (not prefixed by `/admin`). Update this collection when API routes change.
