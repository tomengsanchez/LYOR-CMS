<h5>Customize (theme Customizer)</h5>
<p>Open <strong>Appearance → Customize</strong> (<code>/admin/customize</code>) for a WordPress-style live theme editor: controls on the left, your public site in an iframe on the right.</p>
<ul>
    <li><strong>Live preview:</strong> Changes update the iframe immediately (postMessage) and sync to an admin session so refresh / Home / Blog still show unsaved settings.</li>
    <li><strong>Options:</strong> presets (including <strong>Editorial (crimson)</strong>), typography, layout, nav/blog/images, motion/focus, sidebar/footer, and visibility toggles.</li>
    <li><strong>Editorial magazine:</strong> Apply an optional style pack (crimson preset, magazine grid, uppercase nav, section kicker), or set chrome, blog kicker, date format, and company tagline individually.</li>
    <li><strong>Style packs:</strong> Upload zips into a <strong>library</strong>, then choose one from <em>Installed packs</em> and click <strong>Activate</strong>. Bundled packs (Sample template, Play · Build · Sound, <strong>Manly</strong>) can be installed into the library. Clear active does not delete installed packs. WordPress theme zips map colors only — PHP templates are never executed.</li>
    <li><strong>Publish:</strong> Writes the theme to site settings (same as System → General → Public site theme).</li>
    <li><strong>Close:</strong> Leaves the Customizer and clears the unsaved preview session (does not undo a Publish).</li>
</ul>
<p>Download ready-made packs from Customize / General: <strong>Sample template</strong>, <strong>Play · Build · Sound</strong>, and <strong>Manly</strong> (oak / iron / leather, dark magazine) — or <code>/admin/customize/sample-style-pack?pack=manly</code>. Rebuild with <code>php cli/build_style_pack.php</code>. Installed pack CSS lives under <code>public/uploads/theme-packs/library/</code> and is included in normal backup/restore of uploads; settings keys live in <code>app_settings</code>.</p>
<p>For a posts feed homepage, set <strong>Reading → Your homepage displays</strong> to <strong>Your latest posts</strong>. You can still edit the same fields under <strong>System → General</strong>.</p>
<p>Admin only.</p>
