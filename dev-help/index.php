<?php
declare(strict_types=1);

/**
 * PAPeR developer guide (standalone). Open e.g. http://eco.local/dev-help/ when document root is project root.
 */
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/dev-help/index.php'));
$devHelpBase = preg_replace('#/index\.php$#', '', $scriptName);
$devHelpBase = rtrim($devHelpBase, '/') ?: '/dev-help';
$assetBase = $devHelpBase . '/assets';
$projectRoot = str_replace('\\', '/', dirname(__DIR__));
$docsBase = $devHelpBase . '/../docs';
$pageTitle = 'PAPeR · Developer guide';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBase) ?>/style.css">
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
        <div class="site-header-inner">
            <div class="brand">PAPeR <span>Developer guide</span></div>
            <nav class="nav-main" aria-label="Sections">
                <a href="#overview">Overview</a>
                <a href="#stack">Stack</a>
                <a href="#request-flow">Request flow</a>
                <a href="#http">HTTP layers</a>
                <a href="#auth">Auth</a>
                <a href="#frontend">Frontend</a>
                <a href="#rest">REST API</a>
                <a href="#modules">Modules</a>
                <a href="#testing">Testing</a>
                <a href="#cli">CLI &amp; DB</a>
                <a href="#erd">ERD</a>
                <a href="#uml">UML</a>
                <a href="#ses-uml">SES UML</a>
                <a href="#docs">Full docs</a>
            </nav>
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme">Light</button>
        </div>
    </header>

    <main id="main" class="wrap">
        <header class="hero" id="overview">
            <h1>How this application is put together</h1>
            <p class="lead">
                <strong>PAPeR</strong> (Project Affected Profiles and Redress) is a custom <strong>PHP MVC-style</strong> application:
                flat MySQL tables, server-rendered HTML for staff workflows, a parallel <strong>JSON REST API</strong> under
                <code>/api/*</code> for integrations and tests, and <strong>Playwright</strong> for browser automation.
                This page explains the moving parts so you can navigate the repo without reading every file first.
            </p>
            <p class="lead">
                This guide lives in <code>dev-help/</code> and is <strong>not</strong> behind the app login.
                Document root is the <strong>project root</strong> (not <code>public/</code>). Apache rules allow
                <strong>localhost only</strong> for this folder (see <code>docs/DEPLOYMENT.md</code>).
            </p>
            <p class="note">
                <strong>Hosting path:</strong> serve the repository root so URLs like
                <code><?= htmlspecialchars($devHelpBase) ?>/</code> resolve. Do not point the vhost at
                <code>public/</code> for this app.
            </p>
        </header>

        <article class="section-block" id="stack">
            <h2>Technology stack</h2>
            <div class="prose">
                <p>
                    The backend is <strong>PHP 8+</strong> with no heavy ORM: controllers and models use <strong>PDO</strong> directly.
                    The schema evolves through numbered PHP migration files in <code>database/migration_*.php</code>, executed by
                    <code>php cli/migrate.php</code>.
                </p>
                <p>
                    The staff UI uses <strong>Bootstrap 5</strong>, <strong>jQuery</strong> where legacy patterns exist, and
                    <strong>Select2</strong> for searchable dropdowns. Business logic stays on the server; JavaScript is kept in
                    <code>public/assets/js/&hellip;</code> as <strong>external files</strong> (avoid large inline scripts in views).
                </p>
                <p>
                    PDFs use <strong>mPDF</strong> via Composer. End-to-end tests use <strong>Node</strong> and <strong>Playwright</strong>
                    (<code>playwright.config.ts</code>, specs under <code>tests/e2e/</code>).
                </p>
            </div>
            <aside class="doc-ref">
                <strong>Long-form reference:</strong>
                <a href="<?= htmlspecialchars($docsBase) ?>/DEVELOPMENTGUIDE.md">DEVELOPMENTGUIDE.md</a> (tree, migrations, routing),
                <a href="<?= htmlspecialchars($docsBase) ?>/DOCUMENTATION.md">DOCUMENTATION.md</a> (full SE doc map),
                <a href="<?= htmlspecialchars($docsBase) ?>/ERD.md">ERD.md</a> (schema),
                <a href="<?= htmlspecialchars($docsBase) ?>/UML.md">UML.md</a> (behaviour),
                <a href="<?= htmlspecialchars($docsBase) ?>/RUNBOOK.md">RUNBOOK.md</a> (ops),
                <a href="<?= htmlspecialchars($docsBase) ?>/SECURITY.md">SECURITY.md</a>,
                <a href="<?= htmlspecialchars($docsBase) ?>/FrameworksGuide.txt">FrameworksGuide.txt</a>, and
                <a href="<?= htmlspecialchars($docsBase) ?>/adr/README.md">docs/adr/</a> (ADRs).
            </aside>
        </article>

        <article class="section-block" id="request-flow">
            <h2>Request flow and code layout</h2>
            <div class="prose">
                <p>Every browser or API hit goes through the same front controller:</p>
                <ol class="flow-list">
                    <li><strong><code>public/index.php</code></strong> (or the root <code>index.php</code> that forwards to it) bootstraps the app and builds a <strong>Router</strong>.</li>
                    <li>Routes are explicit <code>$router->get(...)</code> / <code>post(...)</code> lines: path pattern and a string like <code>'ProfileController@index'</code>.</li>
                    <li><strong><code>Core\Router</code></strong> loads <code>App\Controllers\&hellip;</code>, instantiates the controller, and calls the method. Path parameters such as <code>{id}</code> become method arguments.</li>
                    <li>Controllers extend <code>Core\Controller</code> and call <code>$this->view('module/name', $data)</code> for HTML, or <code>$this->json(...)</code> for API responses.</li>
                    <li>Views live under <code>App/Views/</code>; shared chrome is in <code>App/Views/layout/main.php</code>. Models live under <code>App/Models/</code>.</li>
                </ol>
                <p>
                    <strong><code>Core/</code></strong> holds framework-style pieces: Auth, Database, Csrf, Router, Controller base class.
                    <strong><code>App/</code></strong> is all product code (controllers, models, views, helpers such as list columns and PDF helpers).
                </p>
            </div>
        </article>

        <article class="section-block" id="http">
            <h2>Two HTTP surfaces: HTML and JSON</h2>
            <div class="prose">
                <p>
                    <strong>First-party pages</strong> use traditional form posts and full page loads (or partial AJAX to <code>/api/*</code>).
                    URLs look like <code>/profile/view/42</code>, <code>/grievance/list</code>, <code>/system/audit-trail</code>.
                    Authentication is a <strong>PHP session</strong> cookie after web login. Mutating routes expect a <strong>CSRF token</strong>
                    (meta tag or hidden field) on forms.
                </p>
                <p>
                    The <strong>REST API</strong> lives under <code>/api/&hellip;</code>. Responses are wrapped in a standard JSON envelope
                    <code>{ &quot;success&quot;: true|false, &quot;data&quot;: &hellip;, &quot;error&quot;: null | { &quot;code&quot;, &quot;message&quot; } }</code>.
                    Clients authenticate with <code>Authorization: Bearer &lt;token&gt;</code> from <code>POST /api/auth/login</code>, unless the browser already has a session (both can be supported depending on endpoint).
                </p>
                <p>
                    Keep behaviour aligned: if you add a filter to the grievance <strong>web</strong> list, consider whether <strong><code>GET /api/grievance/list</code></strong>
                    and exports should follow the same rules, and document the contract in <code>docs/API_CONTRACT.md</code>.
                </p>
            </div>
            <aside class="doc-ref">
                <strong>Contract details:</strong>
                <a href="<?= htmlspecialchars($docsBase) ?>/API_CONTRACT.md">API_CONTRACT.md</a> (envelope, route table, domain notes).
            </aside>
        </article>

        <article class="section-block" id="auth">
            <h2>Users, roles, and capabilities</h2>
            <div class="prose">
                <p>
                    Authorization is <strong>capability-based</strong>: each user has a role, and <code>role_capabilities</code> maps
                    <code>view_profiles</code>, <code>add_grievance</code>, <code>export_grievance</code>, and so on. Controllers call
                    <code>$this->requireCapability('name')</code> or <code>Auth::can('name')</code> before showing UI or returning JSON.
                </p>
                <p>
                    <strong>API tokens</strong> are stored hashed; the raw token is returned once at login for integrators to save.
                    Web <strong>2FA</strong> flows use the normal login pages; the token API returns an error code when 2FA is required instead of issuing a token.
                </p>
            </div>
            <aside class="doc-ref">
                <strong>Postman and Bearer usage:</strong>
                <a href="<?= htmlspecialchars($docsBase) ?>/API_AUTH.md">API_AUTH.md</a>.
            </aside>
        </article>

        <article class="section-block" id="frontend">
            <h2>Frontend conventions</h2>
            <div class="prose">
                <p>
                    Views should stay mostly markup and PHP echo of escaped data. When JavaScript is needed, load a single external file
                    per page (for example <code>public/assets/js/profile/form.js</code>) and, only when necessary, pass a small
                    <code>window.someConfig = {...}</code> object from PHP for ids and flags.
                </p>
                <p>
                    This keeps behaviour testable and avoids scattering logic across inline <code>&lt;script&gt;</code> blocks.
                </p>
            </div>
            <aside class="doc-ref">
                <strong>Team checklist:</strong>
                <a href="<?= htmlspecialchars($docsBase) ?>/FRONTEND_JS_CONVENTIONS.md">FRONTEND_JS_CONVENTIONS.md</a>.
            </aside>
        </article>

        <article class="section-block" id="rest">
            <h2>REST API at a glance</h2>
            <div class="prose">
                <p>
                    All routes are registered in <code>public/index.php</code>. Groupings include: <strong>auth</strong> (login, me, logout),
                    <strong>dropdowns</strong> (projects, municipalities search, project-scoped municipalities/barangays, profiles search), <strong>grievance respondent autocomplete</strong>
                    (first/middle/last names, history, latest-details with server-side minimum first-name length for performance),
                    <strong>dashboards</strong> (<code>/api/dashboard</code>, <code>/api/grievance/dashboard</code>),
                    <strong>grievance CRUD</strong>, <strong>profile</strong> and <strong>structure</strong> REST, <strong>settings/system</strong> read-only JSON,
                    and <strong>history</strong> / <strong>notifications</strong>.
                </p>
                <p>
                    A generated <strong>Postman collection</strong> lists every <code>/api/*</code> route plus a catalog of first-party web URLs for manual testing with a session cookie.
                </p>
            </div>
            <p class="cmd">npm run postman:collection</p>
            <div class="link-row">
                <a href="<?= htmlspecialchars($docsBase) ?>/postman/README.md">Import instructions</a>
                <span style="color:var(--muted)">·</span>
                <a href="<?= htmlspecialchars($docsBase) ?>/postman/PAPeR-API.postman_collection.json">Collection JSON</a>
            </div>
        </article>

        <article class="section-block" id="modules">
            <h2>Major product modules (web UI)</h2>
            <div class="prose">
                <p>These map to menu areas and controller families. Each module has list/view/edit routes and often CSV or PDF exports.</p>
            </div>
            <div class="simple-table-wrap">
                <table class="simple-table">
                    <thead>
                        <tr>
                            <th scope="col">Module</th>
                            <th scope="col">Purpose</th>
                            <th scope="col">Typical paths</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Profiles</strong></td>
                            <td>PAPS and household data; structures tab; exports.</td>
                            <td><code>/profile</code>, <code>/profile/view/{id}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Structures</strong></td>
                            <td>Physical structures; tagging images; link to profiles.</td>
                            <td><code>/structure</code>, <code>/structure/view/{id}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Grievances</strong></td>
                            <td>Cases, status/history, attachments, respondent normalization.</td>
                            <td><code>/grievance</code>, <code>/grievance/list</code>, <code>/grievance/respondents</code></td>
                        </tr>
                        <tr>
                            <td><strong>Library</strong></td>
                            <td>Projects plus municipality master data used across the app.</td>
                            <td><code>/library</code>, <code>/library/municipalities</code>, <code>/library/barangays</code></td>
                        </tr>
                        <tr>
                            <td><strong>Users &amp; roles</strong></td>
                            <td>Accounts and capability templates.</td>
                            <td><code>/users</code>, <code>/users/roles</code></td>
                        </tr>
                        <tr>
                            <td><strong>Settings</strong></td>
                            <td>UI prefs, notifications, email SMTP, security policies.</td>
                            <td><code>/settings</code>, <code>/settings/email</code></td>
                        </tr>
                        <tr>
                            <td><strong>System</strong></td>
                            <td>Admin: general app settings, audit trail, backups, dev clock, realtime security.</td>
                            <td><code>/system/*</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="lead" style="margin-top:1rem;font-size:0.95rem;">
                Quick links on this host (same origin as this page):
                <span class="link-row">
                    <a href="/">Dashboard</a>
                    <a href="/grievance/list">Grievances</a>
                    <a href="/grievance/respondents">Respondents</a>
                    <a href="/profile">Profiles</a>
                </span>
            </p>
        </article>

        <article class="section-block" id="testing">
            <h2>Automated testing</h2>
            <div class="prose">
                <p>
                    <strong>Playwright</strong> drives a real browser against your local base URL. Tests live under <code>tests/e2e/</code>
                    grouped by area (<code>profile/</code>, <code>grievance/</code>, <code>structure/</code>, etc.). NPM scripts in
                    <code>package.json</code> wrap <code>playwright test</code>; on Windows, scripts use <code>playwright.cmd</code> so
                    ExecutionPolicy does not block <code>.ps1</code> shims.
                </p>
                <p>
                    Set <code>BASE_URL</code> in the environment (or rely on the default in <code>playwright.config.ts</code>) so it matches
                    your vhost. Optional <code>E2E_DB_SEED=1</code> can reset and seed the database before a run (see global setup and docs).
                </p>
            </div>
            <p class="cmd">npm run test:e2e</p>
            <aside class="doc-ref">
                <strong>Suite design and commands:</strong>
                <a href="<?= htmlspecialchars($docsBase) ?>/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md">AUTOMATED_FUNCTIONAL_TEST_DESIGN.md</a>
                and <code>.env.playwright.example</code>.
            </aside>
        </article>

        <article class="section-block" id="cli">
            <h2>CLI, database, and dependencies</h2>
            <div class="prose">
                <p>
                    <strong>Migrations</strong> apply or roll back schema changes. Always run from the project root so relative paths resolve.
                </p>
                <p class="cmd">php cli/migrate.php</p>
                <p>
                    A <strong>fresh reset</strong> script clears Simple CMS content and most ops data while keeping migrations, roles, and the admin user.
                    By default it re-seeds Welcome, Hello World, and the Primary Menu. It is destructive; use only on local or disposable databases.
                </p>
                <p class="cmd">php cli/truncate_fresh_install.php --yes</p>
                <p>
                    Flags: <code>--no-reseed</code> (empty content), <code>--keep-uploads</code> (leave <code>public/uploads/media</code> files). Details: DEVELOPMENTGUIDE → Fresh-install truncate.
                </p>
                <p>
                    Install PHP libraries (mPDF, etc.) with Composer from the project root:
                </p>
                <p class="cmd">composer install</p>
            </div>
            <aside class="doc-ref">
                <strong>Paths on this machine:</strong> project root <code><?= htmlspecialchars($projectRoot) ?></code>.
                Database credentials: <code>config/database.php</code> (often not committed—copy from a local template if your repo omits it).
            </aside>
        </article>

        <article class="section-block section-block--erd" id="erd">
            <h2>Entity relationship diagrams</h2>
            <div class="prose">
                <p>
                    Interactive <strong>Mermaid ER diagrams</strong> of the MySQL / MariaDB schema (migrations 000–091).
                    Switch domains below; scroll inside the canvas for large graphs. Soft-delete columns and full field lists
                    are omitted — see <a href="<?= htmlspecialchars($docsBase) ?>/ERD.md">docs/ERD.md</a> and
                    <a href="<?= htmlspecialchars($docsBase) ?>/DEVELOPMENTGUIDE.md">DEVELOPMENTGUIDE.md</a> §4.
                </p>
            </div>
            <div class="erd-toolbar">
                <div class="erd-tabs" id="erdTabs" role="tablist" aria-label="ERD domains"></div>
            </div>
            <p class="erd-status" id="erdStatus" hidden></p>
            <div class="erd-canvas-wrap" role="tabpanel" aria-labelledby="erd-tab-overview">
                <div class="erd-canvas" id="erdCanvas"></div>
            </div>
            <p class="erd-note" id="erdNote"></p>
            <aside class="doc-ref">
                <strong>Source of truth:</strong>
                <a href="<?= htmlspecialchars($docsBase) ?>/ERD.md">docs/ERD.md</a>
                (keep <code>dev-help/assets/erd-diagrams.js</code> in sync when the schema changes).
                Diagrams require the Mermaid CDN; if offline, open the Markdown file in an editor with Mermaid preview.
            </aside>
        </article>

        <article class="section-block section-block--erd" id="uml">
            <h2>UML diagrams</h2>
            <div class="prose">
                <p>
                    Interactive <strong>Mermaid UML</strong> views of structure and behaviour (components, core classes,
                    web/API/restore sequences, grievance state, authorization flow). These complement the
                    <a href="#erd">ERD</a> (data) — see <a href="<?= htmlspecialchars($docsBase) ?>/UML.md">docs/UML.md</a>
                    and <a href="<?= htmlspecialchars($docsBase) ?>/adr/README.md">docs/adr/</a> for decisions.
                </p>
            </div>
            <div class="erd-toolbar">
                <div class="erd-tabs" id="umlTabs" role="tablist" aria-label="UML diagrams"></div>
            </div>
            <p class="erd-status" id="umlStatus" hidden></p>
            <div class="erd-canvas-wrap" role="tabpanel" aria-labelledby="uml-tab-components">
                <div class="erd-canvas" id="umlCanvas"></div>
            </div>
            <p class="erd-note" id="umlNote"></p>
            <aside class="doc-ref">
                <strong>Source of truth:</strong>
                <a href="<?= htmlspecialchars($docsBase) ?>/UML.md">docs/UML.md</a>
                (keep <code>dev-help/assets/uml-diagrams.js</code> in sync when architecture changes).
                Diagrams require the Mermaid CDN; if offline, open the Markdown file in an editor with Mermaid preview.
            </aside>
        </article>

        <article class="section-block section-block--erd" id="ses-uml">
            <h2>SES ZIP import UML</h2>
            <div class="prose">
                <p>
                    Feature UML for <strong>PAPS Socio-Economic (SES) ZIP import</strong>: match by
                    <code>profiles.control_number</code> (not PAPSID), preview → import, version snapshots, and profile tab read API.
                    Main profile fields are never written. Full write-up:
                    <a href="<?= htmlspecialchars($docsBase) ?>/UML_SES_IMPORT.md">docs/UML_SES_IMPORT.md</a>.
                </p>
            </div>
            <div class="erd-toolbar">
                <div class="erd-tabs" id="sesUmlTabs" role="tablist" aria-label="SES UML diagrams"></div>
            </div>
            <p class="erd-status" id="sesUmlStatus" hidden></p>
            <div class="erd-canvas-wrap" role="tabpanel" aria-labelledby="ses-uml-tab-context">
                <div class="erd-canvas" id="sesUmlCanvas"></div>
            </div>
            <p class="erd-note" id="sesUmlNote"></p>
            <aside class="doc-ref">
                <strong>Source of truth:</strong>
                <a href="<?= htmlspecialchars($docsBase) ?>/UML_SES_IMPORT.md">docs/UML_SES_IMPORT.md</a>
                (keep <code>dev-help/assets/ses-uml-diagrams.js</code> in sync).
            </aside>
        </article>

        <article class="section-block" id="docs">
            <h2>Full documentation files</h2>
            <div class="prose">
                <p>
                    The sections above are a <strong>curated summary</strong>. The authoritative, versioned detail lives in Markdown and text
                    under <code>docs/</code>. Open these in your editor or in the browser if your server serves static files from <code>/docs/</code>.
                </p>
            </div>
            <div class="link-row">
                <a href="<?= htmlspecialchars($docsBase) ?>/DOCUMENTATION.md">DOCUMENTATION.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/DEVELOPMENTGUIDE.md">DEVELOPMENTGUIDE.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/REQUIREMENTS.md">REQUIREMENTS.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/GLOSSARY.md">GLOSSARY.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/CAPABILITY_MATRIX.md">CAPABILITY_MATRIX.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/RUNBOOK.md">RUNBOOK.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/CONFIGURATION.md">CONFIGURATION.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/DEPLOYMENT.md">DEPLOYMENT.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/SECURITY.md">SECURITY.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/TEST_STRATEGY.md">TEST_STRATEGY.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/TESTING_HISTORY_PREREQUISITES.md">TESTING_HISTORY_PREREQUISITES.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/test-results/README.md">test-results/</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/RELEASE.md">RELEASE.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/ERD.md">ERD.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/UML.md">UML.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/UML_SES_IMPORT.md">UML_SES_IMPORT.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/adr/README.md">adr/README.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/API_CONTRACT.md">API_CONTRACT.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/API_AUTH.md">API_AUTH.md</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/CHANGES.md">CHANGES.md (index)</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/changes/2026-08/CHANGES.md">changes/2026-08</a>
                <a href="<?= htmlspecialchars($docsBase) ?>/QA_ESCALATION_REGRESSION.md">QA_ESCALATION_REGRESSION.md</a>
            </div>
        </article>

        <footer class="site-footer">
            <p>PAPeR developer guide · <?= htmlspecialchars((string) date('Y-m-d')) ?> · PHP <?= htmlspecialchars(PHP_VERSION) ?></p>
        </footer>
    </main>

    <script src="<?= htmlspecialchars($assetBase) ?>/site.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@11.6.0/dist/mermaid.min.js" defer></script>
    <script src="<?= htmlspecialchars($assetBase) ?>/erd-diagrams.js" defer></script>
    <script src="<?= htmlspecialchars($assetBase) ?>/erd.js" defer></script>
    <script src="<?= htmlspecialchars($assetBase) ?>/uml-diagrams.js" defer></script>
    <script src="<?= htmlspecialchars($assetBase) ?>/uml.js" defer></script>
    <script src="<?= htmlspecialchars($assetBase) ?>/ses-uml-diagrams.js" defer></script>
    <script src="<?= htmlspecialchars($assetBase) ?>/ses-uml.js" defer></script>
</body>
</html>
