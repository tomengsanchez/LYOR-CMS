<h5>Widgets</h5>
<p>Manage public widget areas (WordPress-style). Areas are stored as <code>cms_widgets.area</code> in the database backup — not a separate file.</p>
<ul>
<li><strong>Header</strong> — Slim bar above the site header. Always visible when it has widgets (no Discussion toggle). Search and social links work well here.</li>
<li><strong>After header</strong> — Full-width band under the navigation. Good for an announcement or call-to-action.</li>
<li><strong>Homepage</strong> — Homepage only, below the page or latest-posts feed. Featured posts work well here.</li>
<li><strong>Sidebar</strong> — Shown beside content when enabled in <strong>General → Discussion → Show sidebar</strong>.</li>
<li><strong>After content</strong> — Below the page or post on public pages. CTA, pages list, or more posts.</li>
<li><strong>Footer</strong> — Columns above the copyright line.</li>
</ul>
<p>Widget types: Recent posts, Featured posts (cards with excerpt/image; sticky posts list first; excerpts stay hidden on password-protected posts until the visitor unlocks), Call to action, Pages, Social links (one <code>Label|URL</code> per line), Categories, Tag cloud, Monthly archives, Search box (site search at <code>/search</code>), Newsletter signup (posts to <code>/subscribe</code>; honeypot + consent checkbox), Custom HTML.</p>
<p>The bundled <strong>Pulse</strong> style pack can fill empty Header / After header / Homepage / After content areas on activate. Occupied areas are left unchanged. Sidebar and Footer are not filled by Pulse.</p>
<p>Drag widgets by the ⋮⋮ handle to reorder (top to bottom). Order is saved when you click <strong>Save widgets</strong>.</p>
<p>Admin: <code>/admin/widgets?area=header</code> (administrators).</p>
