<?php
/** Help: System → Realtime Dashboard (admin). */
?>
<div class="help-section">
    <h3>Realtime Dashboard</h3>
    <p>Administrators use this page to see <strong>who is signed in on the web</strong>, <strong>who is active via mobile/API</strong>, light operational KPIs, and to <strong>force-end a browser session</strong> or <strong>revoke an API token</strong> when needed.</p>

    <h4>How web presence works</h4>
    <ul>
        <li>Every authenticated browser page quietly sends a presence heartbeat (about every 45 seconds, and when the tab becomes visible again).</li>
        <li>The heartbeat stores the current page key, path, and label on that user’s session row.</li>
        <li>A web session appears as online if it is not revoked and its last presence or activity is within the active window (default 10 minutes).</li>
        <li>Failed heartbeats are ignored and do not break normal use of the app.</li>
    </ul>

    <h4>How API / mobile presence works</h4>
    <ul>
        <li>Mobile and other API clients authenticate with a Bearer token (<code>api_tokens</code>). They do not send the browser presence heartbeat.</li>
        <li>The dashboard treats a token as active when it is not expired and <code>last_used_at</code> is within the same online window (default 10 minutes). Token use is updated as API requests succeed (throttled).</li>
        <li>When API client event logging is on, the table also shows the latest client id, device model / OS, path, and IP from <code>api_client_events</code>.</li>
        <li><strong>Revoke token</strong> deletes that Bearer token so the app must sign in again. This does not end the user’s web browser sessions.</li>
    </ul>

    <h4>KPIs</h4>
    <ul>
        <li><strong>Web users / sessions</strong> – distinct users and browser sessions currently online.</li>
        <li><strong>API / mobile users</strong> and <strong>API tokens</strong> – distinct users and Bearer tokens with recent API use.</li>
        <li><strong>Failed logins (24h)</strong> – from <code>logs/auth.log</code> (same source as Realtime Security telemetry).</li>
        <li><strong>Email queue</strong> – pending outbound notification emails.</li>
        <li><strong>Last backup age</strong> – hours since the newest backup archive record.</li>
        <li><strong>Open grievances</strong> – grievances with status open or in progress (not deleted).</li>
    </ul>

    <h4>End session / revoke token</h4>
    <p>Use <strong>End session</strong> to revoke another user’s browser session. They are signed out on their next request. Your own current session cannot be ended from this table. Use <strong>Revoke token</strong> for an API/mobile login. Both actions are written to the audit log.</p>

    <h4>Related pages</h4>
    <ul>
        <li><strong>Realtime Security</strong> – login threat telemetry and malware scan (separate from presence).</li>
        <li><strong>API Clients</strong> – client credentials, security logs, and usage analytics for gated API traffic.</li>
        <li><strong>Account → Access &amp; activity</strong> – each user can manage their own browser sessions and API tokens.</li>
        <li><strong>Users → View user</strong> – managers can review and revoke another user’s sessions/tokens (Edit Users).</li>
        <li><strong>Audit Trail</strong> – full history including admin session and token revokes.</li>
    </ul>

    <h4>Troubleshooting</h4>
    <ul>
        <li><strong>I don’t see another web user.</strong> – They may not have loaded an authenticated page recently, or their heartbeat window expired. Ask them to refresh a page and wait about a minute.</li>
        <li><strong>I don’t see a mobile user.</strong> – They need a valid Bearer token that has been used within the online window. Idle tokens drop off after the window even if absolute expiry is later. Device/path columns need API client event logging.</li>
        <li><strong>I can’t open this page.</strong> – Only Administrators can use Realtime Dashboard.</li>
        <li><strong>Ended session / revoked token still looks logged in.</strong> – Browser revoke takes effect on their next navigation or AJAX request; API revoke takes effect on the next Bearer call.</li>
    </ul>
</div>
