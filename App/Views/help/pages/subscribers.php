<h5>Subscribers</h5>
<p>The public newsletter list (Content → Subscribers). Visitors sign up at <code>/subscribe</code> or via the <strong>Newsletter signup</strong> widget. Requires <code>view_subscribers</code> (export needs <code>export_subscribers</code>; Confirm / Unsubscribe / Delete need <code>manage_subscribers</code>).</p>
<ul>
<li><strong>Pending</strong> — Waiting for the confirmation link (double opt-in, default). Admins can Confirm without the email.</li>
<li><strong>Confirmed</strong> — On the list. CSV export includes this status unless you filter.</li>
<li><strong>Unsubscribed</strong> — Opted out via the email link (<code>/unsubscribe/{token}</code>) or an admin action. Tokens do not leak other addresses.</li>
<li><strong>Delete</strong> — Permanently removes the row (use for GDPR erasure). Addresses stay in the SQL backup until the next dump.</li>
</ul>
<p>Turn signups off, require confirmation, and set a per-IP hourly limit under <strong>System → General → Newsletter</strong>. Confirm emails use the same SMTP / MailerSend settings as 2FA. Help / Email. Playwright: <code>npm run test:e2e:cms-newsletter</code> (<code>BASE_URL</code>).</p>
