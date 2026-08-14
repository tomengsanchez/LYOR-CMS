# QA Regression Checklist: Grievance Escalation

Goal: verify escalation timing does not reset on note-only updates, and only resets on real status/level transitions.

Scope: `grievance/list` escalation badge + `needs_escalation=1` filter + **effective date** on status changes.

## Quick Setup (one-time per run)

1. Ensure at least one `grievance_progress_levels` row has `days_to_address > 0`.
2. Pick a test grievance in `in_progress` with a valid `progress_level`.
3. Optional but recommended: set a simulated date in Development so QA is deterministic (`App\DevClock` behavior).

## Test Case 0: Backdated effective date (paper workflow)

Expected: escalation clock starts from **Effective date** on status update, not the day the grievance was encoded in the system.

Steps:
1. Create grievance with **Date recorded** = Jan 1 (example).
2. On status update to `in_progress` + level with SLA, set **Effective date** = Jan 2 (before today).
3. Set Development simulated date so `(today − effective date) > days_to_address`.
4. Open `/grievance/list` or grievance view.

Pass criteria:
- Overdue / days-left badge reflects time since **Jan 2**, not encode day.
- Status history shows effective date; if different from system time, **Recorded in system** line appears.

## Test Case 0c: Note-only update with backdated effective date (paper catch-up)

Expected: history shows the paper date; escalation clock unchanged.

Steps:
1. Take an `in_progress` grievance with a known SLA segment start (e.g. Level 1 effective Jan 3).
2. Submit status update with **same** status and level, a note, and **Effective date** = Jan 9 (before encode day).
3. Refresh grievance view and `/grievance/list`.

Pass criteria:
- Status History lists the new entry at **Jan 9** (and “Recorded in system” if encode day differs).
- Escalation badge unchanged (still based on Jan 3 segment start, not Jan 9).

## Test Case 0b: Admin edit effective date (Phase 2)

Steps:
1. As admin, open grievance view → Status History → **Edit effective date** on a past entry.
2. Save a valid date (≥ date recorded, between neighbors if applicable).
3. Refresh list/view.

Pass criteria:
- Escalation badge updates to match new effective date.
- `current_level_started_at` on grievance reflects recomputed segment.

## Test Case 1: Note-only update should NOT clear escalation

Expected: if grievance is already overdue, escalation badge remains visible after posting a note with same status/level.

Steps:
1. Open a grievance currently `in_progress` and overdue for its current level.
2. Confirm escalation badge is visible on `/grievance/list`.
3. Submit status update form with:
   - same `status = in_progress`
   - same `progress_level`
   - non-empty note
4. Refresh `/grievance/list`.

Pass criteria:
- Red escalation badge still shows (`N days overdue — Should be escalated to ...` or `N days overdue — Should be closed`).
- Grievance still appears when `Needs escalation / close` filter is set to `Yes`.

## Test Case 1b: New in-progress grievance shows days left

Expected: any `in_progress` grievance on a stage with `days_to_address > 0` shows a yellow **X days left** badge (including just after create).

Steps:
1. Create or open an `in_progress` grievance on a stage with `days_to_address = 10` (example).
2. Open `/grievance/list` before the SLA is exceeded.

Pass criteria:
- Yellow badge shows e.g. `10 days left` (full allowance when no status-log segment yet).
- Grievance does **not** appear when `Needs escalation / close` filter is `Yes`.

## Test Case 2: Real level change should reset escalation timer

Expected: when `progress_level` changes (still `in_progress`), overdue state clears and **days left** resets for the new stage.

Steps:
1. Take an overdue `in_progress` grievance.
2. Change to another valid `progress_level`.
3. Refresh `/grievance/list`.

Pass criteria:
- Red overdue badge is replaced by yellow **X days left** for the new stage (full allowance from transition).
- `Needs escalation / close` filter no longer includes this grievance right after transition.

## Test Case 3: Status change away from in_progress should clear escalation

Expected: escalation is only evaluated for `in_progress`.

Steps:
1. Take an overdue `in_progress` grievance.
2. Change status to `closed` (or `open`).
3. Refresh `/grievance/list`.

Pass criteria:
- Escalation badge is absent.
- Grievance is excluded from `Needs escalation / close`.

---

## SQL Spot Checks (copy/paste)

Replace `:gid` with grievance id.

### A) Timeline with transition markers

```sql
SELECT
  id,
  grievance_id,
  status,
  progress_level,
  created_at,
  CASE
    WHEN LAG(status) OVER w IS NULL THEN 1
    WHEN LAG(status) OVER w <> status THEN 1
    WHEN COALESCE(LAG(progress_level) OVER w, 0) <> COALESCE(progress_level, 0) THEN 1
    ELSE 0
  END AS is_transition
FROM grievance_status_log
WHERE grievance_id = :gid
WINDOW w AS (PARTITION BY grievance_id ORDER BY created_at, id)
ORDER BY created_at, id;
```

Use this to confirm note-only entries are not transitions (`is_transition = 0`).

### B) Start of current in-progress segment (reference timestamp)

```sql
SELECT
  g.id AS grievance_id,
  g.status,
  g.progress_level,
  (
    SELECT MIN(s1.created_at)
    FROM grievance_status_log s1
    WHERE s1.grievance_id = g.id
      AND s1.status = 'in_progress'
      AND s1.progress_level = g.progress_level
      AND NOT EXISTS (
        SELECT 1
        FROM grievance_status_log s2
        WHERE s2.grievance_id = s1.grievance_id
          AND (
            s2.created_at > s1.created_at
            OR (s2.created_at = s1.created_at AND s2.id > s1.id)
          )
          AND (
            s2.status <> 'in_progress'
            OR COALESCE(s2.progress_level, 0) <> COALESCE(s1.progress_level, 0)
          )
      )
  ) AS current_segment_started_at
FROM grievances g
WHERE g.id = :gid;
```

Use this to verify the segment start does not move forward after note-only updates.

### C) Overdue check for current level

```sql
SELECT
  g.id AS grievance_id,
  pl.name AS level_name,
  pl.days_to_address,
  DATEDIFF(CURDATE(), DATE((
    SELECT MIN(s1.created_at)
    FROM grievance_status_log s1
    WHERE s1.grievance_id = g.id
      AND s1.status = 'in_progress'
      AND s1.progress_level = g.progress_level
      AND NOT EXISTS (
        SELECT 1
        FROM grievance_status_log s2
        WHERE s2.grievance_id = s1.grievance_id
          AND (
            s2.created_at > s1.created_at
            OR (s2.created_at = s1.created_at AND s2.id > s1.id)
          )
          AND (
            s2.status <> 'in_progress'
            OR COALESCE(s2.progress_level, 0) <> COALESCE(s1.progress_level, 0)
          )
      )
  ))) AS days_in_current_level
FROM grievances g
LEFT JOIN grievance_progress_levels pl ON pl.id = g.progress_level
WHERE g.id = :gid
  AND g.status = 'in_progress';
```

Interpretation:
- overdue if `days_in_current_level > days_to_address`

## Automated: Grievance Settings — escalation calendar (Playwright)

Run: `npm run test:e2e:grievance-settings-escalation:fast`  
CLI unit check: `php tests/cli/grievance_escalation_business_days_test.php`

| ID | Scenario | Pass criteria |
|----|----------|---------------|
| GS-01 | Save **Exclude weekends** on `/grievance/settings` | Success alert; checkbox checked |
| GS-02 | `GET /api/grievance/options` | `escalation_settings.exclude_weekends === true` |
| GS-03 | Create in-progress grievance (Fri effective) | Status `in_progress` + Level 1 |
| GS-04 | Calendar mode, simulated Mon | Overdue badge; in `needs_escalation=1` filter |
| GS-05 | Exclude weekends on, same case | `0 days left` warning; not in filter |
| GS-06 | Holiday on Thu; exclude holidays toggled | Calendar overdue first, then `0 days left` after holiday excluded |

Notes:
- Uses Development **simulated date** (`2026-06-08` weekend case, `2026-06-05` holiday case).
- Holiday row created under **System → Operational → Holidays**.
- Overdue copy uses singular **day** when count is 1 (`1 day overdue`).

