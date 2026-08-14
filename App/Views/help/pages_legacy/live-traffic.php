<?php
/**
 * Help: System → Live Traffic
 */
?>
<div class="help-section">
    <h3>Live Traffic</h3>
    <p>
        <strong>System → Traffic &amp; realtime → Live Traffic</strong> shows a live stream of HTTP requests to PAPeR, including humans,
        bots and crawlers, login attempts, 404 warnings, and blocked IPs. Events are stored in the database
        (<code>traffic_events</code>) and pruned by retention settings.
    </p>

    <h4>Who can access it</h4>
    <ul>
        <li><code>view_live_traffic</code> — open the page and read APIs (Whois, recent traffic).</li>
        <li><code>manage_live_traffic</code> — block/unblock IPs and change logging settings.</li>
        <li>Administrators have all capabilities.</li>
    </ul>

    <h4>What you see</h4>
    <ul>
        <li><strong>Type</strong> — Human, Bot, Crawler, Warning (404), or Blocked.</li>
        <li><strong>Location</strong> — City / region / country from Geo-IP cache (when available).</li>
        <li><strong>Message</strong> — e.g. visited a path, login success/failure, non-existent page.</li>
        <li><strong>IP / hostname / user agent</strong> — visitor identity details.</li>
    </ul>

    <h4>Actions</h4>
    <ul>
        <li><strong>Block IP</strong> — site-wide block (all requests return 403). Separate from login throttle.
            Supports exact IPs, wildcards (<code>124.123.4.*</code>), and CIDR (<code>124.123.4.0/24</code>).
            From a traffic row, Block IP lets you edit the value into a range before saving.</li>
        <li><strong>Blocked IPs (preview)</strong> — at the top of this page (latest 5). Use
            <a href="/system/blocked-ips">System → Blocked IPs</a> / <strong>View all blocked IPs</strong> for the full list and management.</li>
        <li><strong>Run Whois</strong> — RDAP lookup for the IP.</li>
        <li><strong>See recent traffic</strong> — filter the list to that IP.</li>
    </ul>

    <h4>Settings &amp; retention</h4>
    <ul>
        <li>Enable/disable logging and set retention days (default 30) on the page if you have manage permission.</li>
        <li>CLI prune: <code>php cli/prune_live_traffic.php</code> (optional <code>--days=N</code>).</li>
        <li><strong>Download CSV</strong> — exports all stored <code>traffic_events</code> (oldest first) via
            <a href="/system/live-traffic/export">System → Live Traffic → Download CSV</a>.</li>
        <li>Polling of Live Traffic APIs and presence heartbeats are not written to the log (noise exclusion).</li>
    </ul>

    <h4>Related</h4>
    <ul>
        <li><a href="/help?from=realtime-security">Realtime Security</a> — auth.log metrics and malware scan.</li>
        <li><a href="/help?from=realtime-dashboard">Realtime Dashboard</a> — who is signed in right now.</li>
        <li>Path: <a href="/system/live-traffic">System → Traffic &amp; realtime → Live Traffic</a>.</li>
    </ul>
</div>
