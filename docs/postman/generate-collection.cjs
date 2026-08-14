/**
 * Regenerates PAPeR-API.postman_collection.json from public/index.php route surface.
 * Run from repo root: node docs/postman/generate-collection.cjs
 */
const fs = require('fs');
const path = require('path');

const outPath = path.join(__dirname, 'PAPeR-API.postman_collection.json');

const cookieHeader = [
  { key: 'Cookie', value: '{{session_cookie}}', disabled: true },
];

const apiClientHeaders = [
  {
    key: 'X-App-Id',
    value: '{{api_app_id}}',
    disabled: true,
    description: 'Required when System → API Clients gate is enabled (non-session callers).',
  },
  {
    key: 'X-App-Secret',
    value: '{{api_app_secret}}',
    disabled: true,
    description: 'Secret shown once when the client is created/regenerated.',
  },
  {
    key: 'X-Device-Model',
    value: '{{api_device_model}}',
    disabled: true,
    description: 'Optional. Recorded on Security logs / Usage (e.g. Samsung SM-S918B).',
  },
  {
    key: 'X-Device-OS',
    value: '{{api_device_os}}',
    disabled: true,
    description: 'Optional. Recorded on Security logs / Usage (e.g. Android 14). Alias: X-OS-Version.',
  },
];

function bearerAuth() {
  return {
    type: 'bearer',
    bearer: [{ key: 'token', value: '{{api_token}}', type: 'string' }],
  };
}

function folder(name, description, items) {
  const f = { name, item: items };
  if (description) f.description = description;
  return f;
}

function apiGet(name, url, description) {
  const r = {
    name,
    request: {
      auth: bearerAuth(),
      method: 'GET',
      header: [...apiClientHeaders],
      url,
    },
    response: [],
  };
  if (description) r.request.description = description;
  return r;
}

function apiPostJson(name, url, rawBody, description, extraHeaders = []) {
  return {
    name,
    request: {
      auth: bearerAuth(),
      method: 'POST',
      header: [{ key: 'Content-Type', value: 'application/json' }, ...apiClientHeaders, ...extraHeaders],
      body: { mode: 'raw', raw: rawBody },
      url,
      description: description || '',
    },
    response: [],
  };
}

function webGet(name, url, description) {
  return {
    name,
    request: {
      method: 'GET',
      header: [...cookieHeader],
      url,
      description:
        description ||
        'HTML. Enable Cookie header and set session_cookie after browser login. POST web routes need CSRF — prefer REST /api/* for automation.',
    },
    response: [],
  };
}

function webPost(name, url, description) {
  return {
    name,
    request: {
      method: 'POST',
      header: [
        ...cookieHeader,
        {
          key: 'X-CSRF-Token',
          value: '{{csrf_token}}',
          disabled: true,
          description: 'Obtain from meta[name=csrf-token] or login form; enable when testing web POST.',
        },
      ],
      url,
      description:
        description ||
        'Requires valid session + CSRF. Prefer equivalent POST /api/* for API testing.',
    },
    response: [],
  };
}

const idempotencyHeader = {
  key: 'Idempotency-Key',
  value: '{{idempotency_key}}',
  description: 'Optional. Replays the first successful response for 24h (grievance/profile/structure store & update).',
};

const b = '{{baseUrl}}';

const loginRequest = {
  name: 'Login',
  event: [
    {
      listen: 'test',
      script: {
        exec: [
          'if (pm.response.code === 200) {',
          '  var json = pm.response.json();',
          '  var data = json && json.data ? json.data : {};',
          "  if (data.pending_2fa && data.challenge_id) {",
          "    pm.environment.set('twofa_challenge_id', data.challenge_id);",
          '  }',
          "  var token = data.token || json.token || '';",
          "  if (token) { pm.environment.set('api_token', token); }",
          '}',
        ],
        type: 'text/javascript',
      },
    },
  ],
  request: {
    method: 'POST',
    header: [{ key: 'Content-Type', value: 'application/json' }, ...apiClientHeaders],
    body: {
      mode: 'raw',
      raw: '{\n  "username": "admin",\n  "password": "your_password"\n}',
    },
    url: `${b}/api/auth/login`,
    description:
      'When 2FA is OFF: data.token is set on api_token. When 2FA is ON: data.pending_2fa=true and data.challenge_id is captured into twofa_challenge_id; submit the emailed OTP via "2FA verify". Enable X-App-Id/X-App-Secret when the API client gate is on. See docs/API_AUTH.md §3.1.',
  },
  response: [],
};

const twofaVerifyRequest = {
  name: '2FA verify',
  event: [
    {
      listen: 'test',
      script: {
        exec: [
          'if (pm.response.code === 200) {',
          '  var json = pm.response.json();',
          "  var token = (json.data && json.data.token) ? json.data.token : (json.token || '');",
          "  if (token) { pm.environment.set('api_token', token); }",
          '}',
        ],
        type: 'text/javascript',
      },
    },
  ],
  request: {
    method: 'POST',
    header: [{ key: 'Content-Type', value: 'application/json' }, ...apiClientHeaders],
    body: {
      mode: 'raw',
      raw: '{\n  "challenge_id": "{{twofa_challenge_id}}",\n  "code": "000000"\n}',
    },
    url: `${b}/api/auth/2fa/verify`,
    description:
      'Step 2 of API 2FA. Submits the emailed 6-digit OTP for {{twofa_challenge_id}}. Sets api_token on success.',
  },
  response: [],
};

const twofaResendRequest = {
  name: '2FA resend',
  request: {
    method: 'POST',
    header: [{ key: 'Content-Type', value: 'application/json' }, ...apiClientHeaders],
    body: {
      mode: 'raw',
      raw: '{\n  "challenge_id": "{{twofa_challenge_id}}"\n}',
    },
    url: `${b}/api/auth/2fa/resend`,
    description: 'Re-send the OTP for an active challenge (resets attempts, extends expiry).',
  },
  response: [],
};

const metaFolder = folder(
  'Meta (REST)',
  'No auth. Error code registry for mobile clients — docs/API_ERROR_CODES.md',
  [
    apiGet('Error codes', `${b}/api/meta/error-codes`, 'data.codes[] with code, http_status, mobile_action'),
  ]
);

const authFolder = folder(
  'Auth (REST)',
  'Token auth. Run Login first. When email 2FA is on, run "2FA verify" with the emailed code (challenge_id captured in environment).',
  [
    loginRequest,
    twofaVerifyRequest,
    twofaResendRequest,
    {
      name: 'Me',
      request: {
        auth: bearerAuth(),
        method: 'GET',
        header: [...apiClientHeaders],
        url: `${b}/api/auth/me`,
        description: 'data includes capabilities[] for the current user.',
      },
      response: [],
    },
    {
      name: 'Logout',
      request: {
        auth: bearerAuth(),
        method: 'POST',
        header: [...apiClientHeaders],
        url: `${b}/api/auth/logout`,
      },
      response: [],
    },
  ]
);

const dropdownsFolder = folder(
  'Dropdowns & search (REST)',
  'Bearer. Used by web Select2 / AJAX.',
  [
    apiGet('Projects', `${b}/api/projects?q=`, 'Optional q for search.'),
    apiGet('Municipalities search', `${b}/api/municipalities?q=`, 'Master list for Library barangay form; optional q.'),
    apiGet('Project municipalities', `${b}/api/projects/{{project_id}}/municipalities`),
    apiGet(
      'Project barangays (by municipality)',
      `${b}/api/projects/{{project_id}}/barangays?municipality={{municipality_name}}`
    ),
    apiGet('Project barangays (flat, legacy)', `${b}/api/projects/{{project_id}}/barangays`),
    apiGet('Project phases', `${b}/api/projects/{{project_id}}/phases`, 'Active project-scoped phases (Name, Description).'),
    apiGet('Project users', `${b}/api/projects/{{project_id}}/users?q=`),
    apiGet('Field personnel users', `${b}/api/users/field-personnel?q=`),
    apiGet('Linked project users', `${b}/api/users/linked-projects?q=&project_id=`),
    apiGet('User projects', `${b}/api/users/{{user_id}}/projects`),
    apiGet(
      'User access activity',
      `${b}/api/users/{{user_id}}/access/activity?module=profile&page=1&per_page=15`,
      'Self or view_users. Paginated audit rows for one module (excludes viewed).'
    ),
    apiGet('Profiles (search)', `${b}/api/profiles?q=&project_id=`),
  ]
);

const respondentFolder = folder(
  'Respondent autocomplete (REST)',
  'Bearer. First name min 3 chars for most endpoints. docs/API_CONTRACT.md.',
  [
    apiGet('first-names', `${b}/api/respondents/first-names?q=abc`),
    apiGet('middle-names', `${b}/api/respondents/middle-names?first_name=abcdef&q=`),
    apiGet('last-names', `${b}/api/respondents/last-names?first_name=abcdef&middle_name=&q=`),
    apiGet('history', `${b}/api/respondents/history?first_name=abcdef&middle_name=&last_name=Smith`),
    apiGet(
      'latest-details',
      `${b}/api/respondents/latest-details?first_name=abcdef&middle_name=&last_name=Smith`
    ),
  ]
);

const notificationsHistoryFolder = folder(
  'Notifications & audit (REST)',
  'Bearer.',
  [
    apiGet('Notifications', `${b}/api/notifications`),
    apiGet(
      'History (grievance)',
      `${b}/api/history?entity_type=grievance&entity_id={{grievance_id}}&page=1&per_page=20`,
      'entity_type / entity_id / page / per_page. created_at in org System→General timezone; response may include timezone.'
    ),
    apiGet(
      'History (project)',
      `${b}/api/history?entity_type=project&entity_id={{project_id}}&page=1&per_page=20`,
      'entity_type=project for Library project activity sidebar'
    ),
  ]
);

const dashboardsFolder = folder(
  'Dashboards (REST)',
  'Bearer.',
  [
    apiGet(
      'Main dashboard',
      `${b}/api/dashboard?date_from=&date_to=`,
      'data.profile, data.structure, data.grievance, data.users. Optional date_from, date_to (YYYY-MM-DD) filter activity counts.'
    ),
    apiGet(
      'Grievance dashboard',
      `${b}/api/grievance/dashboard?project_id=&date_from=&date_to=`,
    apiGet(
      'Calendar month',
      `${b}/api/grievance/calendar?month=2026-06&project_id=&status=`,
      'Counts per day for month grid.'
    ),
    apiGet(
      'Calendar day',
      `${b}/api/grievance/calendar/day?date=2026-06-01&project_id=&status=`,
      'Tickets for one date.'
    ),
      'Optional project_id, date_from, date_to (YYYY-MM-DD).'
    ),
  ]
);

const grievanceRestFolder = folder(
  'Grievance (REST)',
  'Bearer. Envelope { success, data, error }.',
  [
    apiGet(
      'Options library',
      `${b}/api/grievance/options?project_id=`,
      'GRM channels, preferred languages, types, categories, vulnerabilities, respondent_types (with type/guide), respondent_type_categories, progress_levels (project-scoped or defaults), progress_levels_scope, grm_channels_scope, preferred_languages_scope. Pass project_id for GRM/language/stage pickers.'
    ),
    apiGet(
      'List',
      `${b}/api/grievance/list?q=&columns=&sort=id&order=desc&page=1&per_page=15&status=&project_id=&progress_level=&respondent_id=&needs_escalation=&date_from=&date_to=&show_deleted=`,
      'show_deleted: admin only with|only. Filters match web list.'
    ),
    apiGet('Get by id', `${b}/api/grievance/{{grievance_id}}`),
    apiGet(
      'Status log (history)',
      `${b}/api/grievance/status-log/{{grievance_id}}`,
      'Status history: notes, effective dates, progress level labels, attachment URLs.'
    ),
    apiGet('Check case number (duplicate)', `${b}/api/grievance/check-case-number?grievance_case_number=&exclude_id=`),
    apiPostJson(
      'Store',
      `${b}/api/grievance/store`,
      '{\n  "_comment": "Replace with full grievance JSON per docs/API_CONTRACT.md",\n  "date_recorded": "2026-06-16T14:00:00",\n  "status": "in_progress",\n  "progress_level": 1,\n  "status_note": "Initial status note",\n  "status_effective_at": "2026-06-16T14:30:00"\n}',
      'date_recorded is required. Initial status is optional (defaults to open). In Progress requires a project-scoped progress_level. Non-default initial status/history requires edit_grievance or change_grievance_status. Use multipart for status_attachments[]. Optional Idempotency-Key.',
      [idempotencyHeader]
    ),
    apiPostJson(
      'Update',
      `${b}/api/grievance/update/{{grievance_id}}`,
      '{\n  "status": "in_progress",\n  "progress_level": 1,\n  "status_note": "Optional note on status change",\n  "status_effective_at": "2026-01-02T10:00:00",\n  "expected_updated_at": "2026-06-17 10:00:00"\n}',
      'Partial JSON. Optional expected_updated_at for optimistic lock (409 CONFLICT if stale). Optional Idempotency-Key. Field changes are written to Activity History (from→to) with source: api; phase_id preserved when omitted.',
      [idempotencyHeader]
    ),
    apiPostJson(
      'Status update (change or note-only)',
      `${b}/api/grievance/status-update/{{grievance_id}}`,
      '{\n  "status": "in_progress",\n  "progress_level": 1,\n  "status_note": "Follow-up note on current stage",\n  "status_effective_at": "2026-06-16T14:30:00"\n}',
      'Recommended for mobile status form. Always writes status history (including note-only). Multipart for status_attachments[]. Optional Idempotency-Key.',
      [idempotencyHeader]
    ),
    apiPostJson(
      'Status log effective at (admin)',
      `${b}/api/grievance/status-log-effective-at/{{grievance_id}}/{{status_log_id}}`,
      '{\n  "effective_at": "2026-01-02T10:00:00"\n}',
      'Admin only. Edits past status log effective date and recomputes current_level_started_at.'
    ),
    apiPostJson('Delete', `${b}/api/grievance/delete/{{grievance_id}}`, '{}', 'Soft delete.'),
    apiPostJson('Restore', `${b}/api/grievance/restore/{{grievance_id}}`, '{}', 'Admin + delete capability.'),
  ]
);

const settingsSystemRestFolder = folder(
  'Settings & system (REST)',
  'Bearer. Admin or capability-gated per endpoint.',
  [
    apiGet('Settings UI', `${b}/api/settings/ui`),
    apiGet('Settings email', `${b}/api/settings/email`),
    apiGet('Settings security', `${b}/api/settings/security`),
    apiGet('System general', `${b}/api/system/general`),
    apiGet('System development', `${b}/api/system/development`),
    apiGet('System operational', `${b}/api/system/operational`),
    apiGet('Realtime security', `${b}/api/system/realtime-security`),
    apiPostJson('Realtime security malware-check', `${b}/api/system/realtime-security/malware-check`, '{}', 'Admin-only scan.'),
    apiGet('Realtime dashboard', `${b}/api/system/realtime-dashboard`, 'Admin-only web + API/mobile presence + ops KPI snapshot.'),
    apiPostJson(
      'Realtime dashboard revoke session',
      `${b}/api/system/realtime-dashboard/sessions/{{session_id}}/revoke`,
      '{"csrf_token":"{{csrf_token}}"}',
      'Admin-only; CSRF required. Force-end a browser session.'
    ),
    apiPostJson(
      'Realtime dashboard revoke token',
      `${b}/api/system/realtime-dashboard/tokens/{{api_token_id}}/revoke`,
      '{"csrf_token":"{{csrf_token}}"}',
      'Admin-only; CSRF required. Revoke a Bearer API token.'
    ),
    apiGet(
      'Live traffic list',
      `${b}/api/system/live-traffic`,
      'Requires view_live_traffic. Query: filter, ip, q, since_id, limit.'
    ),
    apiGet('Live traffic by IP', `${b}/api/system/live-traffic/by-ip?ip={{client_ip}}`, 'Requires view_live_traffic.'),
    apiGet('Live traffic Whois', `${b}/api/system/live-traffic/whois?ip={{client_ip}}`, 'Requires view_live_traffic. RDAP lookup.'),
    apiGet('Live traffic blocks', `${b}/api/system/live-traffic/blocks`, 'Requires view_live_traffic.'),
    apiPostJson(
      'Live traffic block IP',
      `${b}/api/system/live-traffic/block`,
      '{"ip":"{{client_ip}}","reason":"Blocked from Live Traffic","csrf_token":"{{csrf_token}}"}',
      'Requires manage_live_traffic + CSRF.'
    ),
    apiPostJson(
      'Live traffic unblock IP',
      `${b}/api/system/live-traffic/unblock`,
      '{"ip":"{{client_ip}}","csrf_token":"{{csrf_token}}"}',
      'Requires manage_live_traffic + CSRF.'
    ),
    apiPostJson(
      'Presence heartbeat',
      `${b}/api/presence/heartbeat`,
      '{"page_key":"dashboard","path":"/"}',
      'Any signed-in user; updates own session presence only.'
    ),
    apiGet('Ask Help status', `${b}/api/help/chat/status`, 'Signed-in; whether Ask Help is enabled and local vs llm mode.'),
    apiPostJson(
      'Ask Help chat',
      `${b}/api/help/chat`,
      '{"message":"How do I create a profile?","page_key":"profile","csrf_token":"{{csrf_token}}"}',
      'Session cookie + CSRF preferred for browser; answers from in-app Help for page_key.'
    ),
    apiGet('System log (tail)', `${b}/api/system/log`, 'Debug log stream / tail for admin UI.'),
  ]
);

const contactsRestFolder = folder(
  'Contacts (REST)',
  'Bearer. Read-only directory; writes happen via Profile/User APIs.',
  [
    apiGet('List', `${b}/api/contact/list?q=&entity_type=&project_id=&page=1&per_page=20`),
  ]
);

const profileRestFolder = folder(
  'Profile (REST)',
  'Bearer.',
  [
    apiGet('List', `${b}/api/profile/list?q=&page=1&per_page=15&show_deleted=`),
    apiGet('Check control number (duplicate)', `${b}/api/profile/check-control-number?control_number=&exclude_id=`),
    apiGet('Get', `${b}/api/profile/{{profile_id}}`),
    apiGet('Structures (list for profile)', `${b}/api/profile/{{profile_id}}/structures`),
    apiPostJson(
      'Store',
      `${b}/api/profile/store`,
      '{\n  "_comment": "Full profile payload — see API_CONTRACT PAP profiles (REST)"\n}',
      'Large JSON; use web form or docs. Optional Idempotency-Key header.',
      [idempotencyHeader]
    ),
    apiPostJson(
      'Update',
      `${b}/api/profile/update/{{profile_id}}`,
      '{\n  "_comment": "Partial fields",\n  "expected_updated_at": "2026-06-17 10:00:00"\n}',
      'Merge update. Response profile includes updated_at. Optional Idempotency-Key.',
      [idempotencyHeader]
    ),
    apiGet('Get socio-economic (SES versions/sections)', `${b}/api/profile/{{profile_id}}/socio-economic?version_id=`),
    apiPostJson('Socio-economic section (legacy placeholder)', `${b}/api/profile/{{profile_id}}/section/socio-economic`, '{}', 'SES data is imported via System ZIP; this POST does not write survey fields.'),
    apiGet('SES → RAP maps', `${b}/api/system/rap-mapping`, 'Requires view_rap_mapping or manage_rap_mapping.'),
    apiGet('SES → RAP discover columns', `${b}/api/system/rap-mapping/columns?project_id={{project_id}}`, 'Optional project_id filter.'),
    apiGet('Project RAP summary', `${b}/api/library/{{project_id}}/rap-summary`, 'Requires view_projects + view_rap_mapping|manage_rap_mapping|view_socio_economic.'),
    apiPostJson('Validation section', `${b}/api/profile/{{profile_id}}/section/validation`, '{}'),
    apiPostJson('Delete', `${b}/api/profile/delete/{{profile_id}}`, '{}'),
    apiPostJson('Restore', `${b}/api/profile/restore/{{profile_id}}`, '{}', 'Admin + delete capability.'),
  ]
);

const structureRestFolder = folder(
  'Structure (REST)',
  'Bearer.',
  [
    apiGet('List structures', `${b}/api/structure/list?page=1&per_page=15&q=&project_id={{project_id}}`),
    apiGet('Options (tagging status / actual usage)', `${b}/api/structure/options`),
    apiGet('Next STRID', `${b}/api/structure/next-strid`),
    apiGet('Find by tag', `${b}/api/structure/find-by-tag?tag=TEST&project_id={{project_id}}`),
    apiGet('Tag search (profile multi-select)', `${b}/api/structure/tag-search?q=&project_id={{project_id}}&municipality_id=&barangay_id=`),
    apiGet('Primary search (Secondary link)', `${b}/api/structure/primary-search?q=test&exclude_id=`),
    apiGet('Get by id', `${b}/api/structure/{{structure_id}}`),
    apiPostJson(
      'Store',
      `${b}/api/structure/store`,
      '{\n  "_comment": "multipart: optional owner_id, project_id, municipality_id, barangay_id (Library location), structure_tag, description, other_details, pn_number + tagging fields; tagging_images[], structure_images[]"\n}',
      'Use form-data in Postman for real uploads. Optional Idempotency-Key header.',
      [idempotencyHeader]
    ),
    apiPostJson(
      'Update',
      `${b}/api/structure/update/{{structure_id}}`,
      '{\n  "_comment": "multipart: include project_id/municipality_id/barangay_id only when replacing location; record_updated_at for optimistic lock"\n}',
      'Merge update. Optional Idempotency-Key. 409 CONFLICT if record_updated_at is stale.',
      [idempotencyHeader]
    ),
    apiPostJson('Delete', `${b}/api/structure/delete/{{structure_id}}`, '{}'),
    apiPostJson('Restore', `${b}/api/structure/restore/{{structure_id}}`, '{}', 'Admin + delete capability.'),
  ]
);

// --- Web (session cookie) — catalog of first-party routes from public/index.php ---

const webCore = folder(
  'Web — Core & account',
  'HTML. Cookie header (disabled by default).',
  [
    webGet('Dashboard home', `${b}/`),
    webGet('Account', `${b}/account`),
    webGet('Sessions', `${b}/account/sessions`),
    webGet('Notifications', `${b}/notifications`),
    webGet('Notification click', `${b}/notifications/click/1`),
    webGet('Help', `${b}/help`),
    webGet('Admin guide', `${b}/admin-guide`),
  ]
);

const webAuthForms = folder(
  'Web — Public auth (no API token)',
  'HTML forms; not for Bearer.',
  [
    {
      name: 'Login form (GET)',
      request: { method: 'GET', header: [], url: `${b}/login` },
      response: [],
    },
    {
      name: 'Login (POST)',
      request: {
        method: 'POST',
        header: [
          { key: 'Content-Type', value: 'application/x-www-form-urlencoded', disabled: true },
        ],
        url: `${b}/login`,
        description: 'Web session login; body is form fields + CSRF from GET /login page.',
      },
      response: [],
    },
    {
      name: '2FA form (GET)',
      request: { method: 'GET', header: [], url: `${b}/login/2fa` },
      response: [],
    },
    {
      name: '2FA verify (POST)',
      request: {
        method: 'POST',
        header: [
          { key: 'Content-Type', value: 'application/x-www-form-urlencoded', disabled: true },
          ...cookieHeader,
        ],
        url: `${b}/login/2fa/verify`,
        description: 'Form POST after 2FA challenge; CSRF + session.',
      },
      response: [],
    },
    {
      name: 'Logout (GET)',
      request: {
        method: 'GET',
        header: [...cookieHeader],
        url: `${b}/logout`,
        description: 'Clears session when Cookie enabled.',
      },
      response: [],
    },
  ]
);

const webProfile = folder(
  'Web — Profile',
  'HTML + exports.',
  [
    webGet('List / index', `${b}/profile`),
    webGet('Export CSV', `${b}/profile/export?q=`),
    webGet('PDF index', `${b}/profile/pdf?q=`),
    webGet('View', `${b}/profile/view/{{profile_id}}`),
    webGet('View PDF', `${b}/profile/view/{{profile_id}}/pdf`),
    webGet('Create form', `${b}/profile/create`),
    webGet('Edit form', `${b}/profile/edit/{{profile_id}}`),
    webPost('Store (POST)', `${b}/profile/store`, 'Multipart form + CSRF from browser.'),
    webPost('Update (POST)', `${b}/profile/update/{{profile_id}}`),
    webPost('Delete (POST)', `${b}/profile/delete/{{profile_id}}`),
    webPost('Restore (POST)', `${b}/profile/restore/{{profile_id}}`),
    webPost('Import (POST)', `${b}/profile/import`),
    webPost('Attachment store (POST)', `${b}/profile/attachment/store/{{profile_id}}`),
  ]
);

const webStructure = folder(
  'Web — Structure',
  'HTML + exports.',
  [
    webGet('List', `${b}/structure`),
    webGet('Export', `${b}/structure/export?q=`),
    webGet('PDF index', `${b}/structure/pdf?q=`),
    webGet('View', `${b}/structure/view/{{structure_id}}`),
    webGet('View PDF', `${b}/structure/view/{{structure_id}}/pdf`),
    webGet('Create', `${b}/structure/create`),
    webGet('Edit', `${b}/structure/edit/{{structure_id}}`),
    webPost('Store', `${b}/structure/store`),
    webPost('Update', `${b}/structure/update/{{structure_id}}`),
    webPost('Delete', `${b}/structure/delete/{{structure_id}}`),
    webPost('Restore', `${b}/structure/restore/{{structure_id}}`),
    webGet('Options — tagging statuses', `${b}/structure/options/tagging-statuses`),
    webGet('Options — actual usages', `${b}/structure/options/actual-usages`),
    webGet('Tagging status create', `${b}/structure/options/tagging-statuses/create`),
    webPost('Tagging status store', `${b}/structure/options/tagging-statuses/store`),
    webGet('Actual usage create', `${b}/structure/options/actual-usages/create`),
    webPost('Actual usage store', `${b}/structure/options/actual-usages/store`),
    webGet('Serve image', `${b}/serve/structure?subdir=images&file=example.jpg`, 'subdir: tagging|images; session + view_structure.'),
  ]
);

const webGrievance = folder(
  'Web — Grievance',
  'HTML module + options library.',
  [
    webGet('Dashboard', `${b}/grievance`),
    webGet('Calendar', `${b}/grievance/calendar?month=&project_id=&status=`),
    webPost('Dashboard config save', `${b}/grievance/dashboard-config`),
    webPost('Dashboard report config save', `${b}/grievance/dashboard-report-config`),
    webGet('Dashboard report print', `${b}/grievance/dashboard/report?project_id=&date_from=&date_to=`),
    webGet('Dashboard report PDF', `${b}/grievance/dashboard/pdf?project_id=&date_from=&date_to=`),
    webGet('List', `${b}/grievance/list?q=&status=&project_id=`),
    webGet('List PDF', `${b}/grievance/list/pdf?q=`),
    webGet('Export CSV', `${b}/grievance/export?q=`),
    webGet('Create', `${b}/grievance/create`),
    webGet('View', `${b}/grievance/view/{{grievance_id}}`),
    webGet('View PDF', `${b}/grievance/view/{{grievance_id}}/pdf`),
    webGet('Edit', `${b}/grievance/edit/{{grievance_id}}`),
    webPost('Store', `${b}/grievance/store`),
    webPost('Update', `${b}/grievance/update/{{grievance_id}}`),
    webPost('Delete', `${b}/grievance/delete/{{grievance_id}}`),
    webPost('Restore', `${b}/grievance/restore/{{grievance_id}}`),
    webPost('Status update', `${b}/grievance/status-update/{{grievance_id}}`),
    webPost('Status log effective at (admin)', `${b}/grievance/status-log-effective-at/{{grievance_id}}/{{status_log_id}}`),
    webGet('Respondents list', `${b}/grievance/respondents`),
    webGet('Respondents filtered', `${b}/grievance/respondents?q=&page=1&per_page=15&sort=latest_desc&is_paps=yes&linked=1&gender=Male`),
    webGet('Respondent singular redirect', `${b}/grievance/respondent`),
    webGet('Respondents PDF', `${b}/grievance/respondents/pdf?q=&sort=latest_desc`),
    webGet('Grievance settings', `${b}/grievance/settings`),
    webPost('Grievance settings save', `${b}/grievance/settings/save`),
    webGet('Options — vulnerabilities', `${b}/grievance/options/vulnerabilities`),
    webGet('Options — respondent types', `${b}/grievance/options/respondent-types`),
    webGet('Options — GRM channels', `${b}/grievance/options/grm-channels`),
    webGet('Options — preferred languages', `${b}/grievance/options/preferred-languages`),
    webGet('Options — grievance types', `${b}/grievance/options/types`),
    webGet('Options — categories', `${b}/grievance/options/categories`),
    webGet('Options — progress levels', `${b}/grievance/options/progress-levels`),
    webPost('Options — progress init project', `${b}/grievance/options/progress-levels/initialize-project`),
    webPost('Options — progress remap project', `${b}/grievance/options/progress-levels/remap-project`),
  ]
);

const webLibrary = folder(
  'Web — Library (projects)',
    'HTML CRUD for library projects, municipalities, and barangays.',
  [
    webGet('List', `${b}/library`),
    webGet('Export', `${b}/library/export?q=`),
    webGet('PDF', `${b}/library/pdf?q=`),
    webGet('View', `${b}/library/view/{{library_project_id}}`),
    webGet('View PDF', `${b}/library/view/{{library_project_id}}/pdf`),
    webGet('RAP summary', `${b}/library/view/{{library_project_id}}/rap`, 'SES → RAP project preview.'),
    webGet('Create', `${b}/library/create`),
    webGet('Edit', `${b}/library/edit/{{library_project_id}}`),
    webGet('Edit phase', `${b}/library/edit/{{library_project_id}}/phases/{{phase_id}}`),
    webPost('Store', `${b}/library/store`),
    webPost('Update', `${b}/library/update/{{library_project_id}}`),
    webPost('Phase store', `${b}/library/{{library_project_id}}/phases/store`),
    webPost('Phase update', `${b}/library/{{library_project_id}}/phases/update/{{phase_id}}`),
    webPost('Phase delete', `${b}/library/{{library_project_id}}/phases/delete/{{phase_id}}`),
    webPost('Delete', `${b}/library/delete/{{library_project_id}}`),
    webPost('Restore', `${b}/library/restore/{{library_project_id}}`),
    webGet('Municipalities list', `${b}/library/municipalities`),
    webGet('Municipalities view', `${b}/library/municipalities/view/{{municipality_id}}`),
    webGet('Municipalities create', `${b}/library/municipalities/create`),
    webGet('Municipalities edit', `${b}/library/municipalities/edit/{{municipality_id}}`),
    webPost('Municipalities store', `${b}/library/municipalities/store`),
    webPost('Municipalities update', `${b}/library/municipalities/update/{{municipality_id}}`),
    webPost('Municipalities delete', `${b}/library/municipalities/delete/{{municipality_id}}`),
    webGet('Barangays list', `${b}/library/barangays`),
    webGet('Barangays view', `${b}/library/barangays/view/{{barangay_id}}`),
    webGet('Barangays create', `${b}/library/barangays/create`),
    webGet('Barangays edit', `${b}/library/barangays/edit/{{barangay_id}}`),
    webPost('Barangays store', `${b}/library/barangays/store`),
    webPost('Barangays update', `${b}/library/barangays/update/{{barangay_id}}`),
    webPost('Barangays delete', `${b}/library/barangays/delete/{{barangay_id}}`),
  ]
);

const webSettings = folder(
  'Web — Settings',
  'HTML + POST handlers.',
  [
    webGet('Index', `${b}/settings`),
    webPost('UI save', `${b}/settings/ui`),
    webPost('Notifications save', `${b}/settings/notifications`),
    webGet('Email', `${b}/settings/email`),
    webPost('Email update', `${b}/settings/email/update`),
    webPost('Email test', `${b}/settings/email/test`),
    webGet('Security', `${b}/settings/security`),
    webPost('Security update', `${b}/settings/security/update`),
  ]
);

const webSystem = folder(
  'Web — System (admin)',
  'HTML admin tools.',
  [
    webGet('General', `${b}/system/general`),
    webPost('General save', `${b}/system/general/save`),
    webPost('General Ask Help save', `${b}/system/general/help-chat`),
    webGet('Operational', `${b}/system/operational`),
    webPost('Operational save', `${b}/system/operational/save`),
    webPost('Holiday store', `${b}/system/operational/holidays/store`),
    webPost('Holiday update', `${b}/system/operational/holidays/update/1`),
    webPost('Holiday delete', `${b}/system/operational/holidays/delete/1`),
    webGet('Socio Economic', `${b}/system/socio-economic`),
    webGet('Remap Audit', `${b}/system/remap-audit`),
    webGet('SES → RAP Mapping', `${b}/system/rap-mapping`),
    webPost('SES → RAP Mapping store', `${b}/system/rap-mapping/store`),
    webPost('SES → RAP Mapping delete', `${b}/system/rap-mapping/delete/1`),
    webGet('Backup / restore', `${b}/system/backup-restore`),
    webPost('Create backup', `${b}/system/backup-restore/backup`),
    webGet('Download backup', `${b}/system/backup-restore/download?file=`),
    webGet('Audit trail', `${b}/system/audit-trail`),
    webGet('Debug log', `${b}/system/debug-log`),
    webGet('Development', `${b}/system/development`),
    webPost('Development save', `${b}/system/development/save`),
    webPost('Dev set simulated time', `${b}/system/development/set-simulated-time`),
    webPost('Dev clear simulated time', `${b}/system/development/clear-simulated-time`),
    webGet('Realtime security', `${b}/system/realtime-security`),
    webPost('Realtime security update', `${b}/system/realtime-security/update`),
    webPost('Realtime security malware-check', `${b}/system/realtime-security/malware-check`),
    webGet('Realtime dashboard', `${b}/system/realtime-dashboard`),
    webGet('Live traffic', `${b}/system/live-traffic`),
    webGet('Live traffic CSV export', `${b}/system/live-traffic/export`, 'Requires view_live_traffic. Downloads all traffic_events as CSV.'),
    webGet('Blocked IPs', `${b}/system/blocked-ips`),
  ]
);

const webUsers = folder(
  'Web — Users & roles',
  'HTML admin user/role management.',
  [
    webGet('Users list', `${b}/users`),
    webGet('Users export', `${b}/users/export?q=`),
    webGet('Users PDF', `${b}/users/pdf?q=`),
    webGet('User view', `${b}/users/view/{{user_id}}`),
    webGet('User view PDF', `${b}/users/view/{{user_id}}/pdf`),
    webGet('User create', `${b}/users/create`),
    webGet('User edit', `${b}/users/edit/{{user_id}}`),
    webPost('User store', `${b}/users/store`),
    webPost('User update', `${b}/users/update/{{user_id}}`),
    webPost('User delete', `${b}/users/delete/{{user_id}}`),
    webGet('Roles list', `${b}/users/roles`),
    webGet('Roles PDF', `${b}/users/roles/pdf`),
    webGet('Role view', `${b}/users/roles/view/{{role_id}}`),
    webGet('Role view PDF', `${b}/users/roles/view/{{role_id}}/pdf`),
    webGet('Role create', `${b}/users/roles/create`),
    webGet('Role edit', `${b}/users/roles/edit/{{role_id}}`),
    webPost('Role store', `${b}/users/roles/store`),
    webPost('Role update', `${b}/users/roles/update/{{role_id}}`),
  ]
);

const webSessions = folder(
  'Web — Account sessions (POST)',
  'POST with CSRF.',
  [
    webPost('Logout other sessions', `${b}/account/sessions/logout-others`),
    webPost('Logout one session', `${b}/account/sessions/logout/1`),
  ]
);

const webServe = folder(
  'Web — Static / serve',
  'Binary or file responses.',
  [
    webGet('App logo', `${b}/serve/app-logo`),
    webGet(
      'Grievance status attachment',
      `${b}/serve/grievance?grievance_id={{grievance_id}}&file=example.pdf`,
      'file must be basename allowed on grievance status log attachments.'
    ),
    webGet('Grievance card attachment', `${b}/serve/grievance-card-attachment?id=1`, 'id = grievance_attachments.id'),
    webGet('Profile attachment file', `${b}/serve/profile?subdir=attachments&file=example.pdf`),
  ]
);

const webGrievanceOptionsCrud = folder(
  'Web — Grievance options (CRUD stubs)',
  'GET forms + POST store/update/delete for each lookup table — same pattern; sample URLs only.',
  [
    webGet('Vulnerability create', `${b}/grievance/options/vulnerabilities/create`),
    webPost('Vulnerability store', `${b}/grievance/options/vulnerabilities/store`),
    webGet('Vulnerability edit', `${b}/grievance/options/vulnerabilities/edit/1`),
    webPost('Vulnerability update', `${b}/grievance/options/vulnerabilities/update/1`),
    webPost('Vulnerability delete', `${b}/grievance/options/vulnerabilities/delete/1`),
    webGet('Respondent type create', `${b}/grievance/options/respondent-types/create`),
    webPost('Respondent type store', `${b}/grievance/options/respondent-types/store`),
    webGet('GRM channel create', `${b}/grievance/options/grm-channels/create`),
    webPost('GRM channel store', `${b}/grievance/options/grm-channels/store`),
    webGet('Language create', `${b}/grievance/options/preferred-languages/create`),
    webPost('Language store', `${b}/grievance/options/preferred-languages/store`),
    webGet('Grievance type create', `${b}/grievance/options/types/create`),
    webPost('Grievance type store', `${b}/grievance/options/types/store`),
    webGet('Category create', `${b}/grievance/options/categories/create`),
    webPost('Category store', `${b}/grievance/options/categories/store`),
    webGet('Progress level create', `${b}/grievance/options/progress-levels/create`),
    webPost('Progress level store', `${b}/grievance/options/progress-levels/store`),
  ]
);

const collection = {
  info: {
    _postman_id: '8a1b2c3d-4e5f-6789-abcd-ef0123456789',
    name: 'PAPeR API',
    description:
      'PAPeR: all REST /api/* routes from public/index.php (Bearer after Login) plus a Web (session) catalog of first-party pages and POST targets. Token envelope: docs/API_CONTRACT.md. Regenerate: npm run postman:collection or node docs/postman/generate-collection.cjs',
    schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
  },
  variable: [
    { key: 'baseUrl', value: 'http://eco.local' },
    { key: 'api_app_id', value: 'paper-mobile' },
    { key: 'api_app_secret', value: '' },
    { key: 'project_id', value: '1' },
    { key: 'profile_id', value: '1' },
    { key: 'structure_id', value: '1' },
    { key: 'grievance_id', value: '1' },
    { key: 'user_id', value: '1' },
    { key: 'role_id', value: '1' },
    { key: 'library_project_id', value: '1' },
    { key: 'municipality_id', value: '1' },
    { key: 'barangay_id', value: '1' },
    { key: 'session_id', value: '1' },
    { key: 'api_token_id', value: '1' },
    { key: 'csrf_token', value: '' },
    { key: 'client_ip', value: '203.0.113.10' },
  ],
  item: [
    metaFolder,
    authFolder,
    dropdownsFolder,
    respondentFolder,
    notificationsHistoryFolder,
    dashboardsFolder,
    grievanceRestFolder,
    settingsSystemRestFolder,
    contactsRestFolder,
    profileRestFolder,
    structureRestFolder,
    webAuthForms,
    webCore,
    webProfile,
    webStructure,
    webGrievance,
    webGrievanceOptionsCrud,
    webLibrary,
    webSettings,
    webSystem,
    webUsers,
    webSessions,
    webServe,
  ],
};

fs.writeFileSync(outPath, JSON.stringify(collection, null, 2) + '\n', 'utf8');
console.log('Wrote', outPath);
