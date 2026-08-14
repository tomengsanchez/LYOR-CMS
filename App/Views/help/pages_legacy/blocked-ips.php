<?php
/**
 * Help: System → Blocked IPs
 */
?>
<div class="help-section">
    <h3>Blocked IPs</h3>
    <p>
        <strong>System → Traffic &amp; realtime → Blocked IPs</strong> (<code>/system/blocked-ips</code>) is the dedicated page to view and manage
        the site-wide blocklist. Blocked clients receive HTTP 403 before login.
    </p>
    <ul>
        <li>Supports exact IPs, wildcards (<code>124.123.4.*</code>), and CIDR (<code>124.123.4.0/24</code>).</li>
        <li><code>view_live_traffic</code> — view the list; <code>manage_live_traffic</code> — add / unblock.</li>
        <li><a href="/system/live-traffic">Live Traffic</a> shows a short preview (latest 5) with
            <strong>View all blocked IPs</strong>.</li>
    </ul>
</div>
