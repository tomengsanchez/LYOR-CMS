# CSV import samples

## Hub

**System → CSV Templates** (`/system/csv-templates`) — download samples and see Required / Needs clarification / Optional guides for Profile, Structure, and Grievance.

---

## Profile import

**File:** [profile-import-sample.csv](profile-import-sample.csv)  
**Download (browser):** `/system/csv-templates/profile` (requires `add_profiles`). Static copy: `/public/samples/profile-import-sample.csv`

### Before you import

1. Prefer `project_id` / location IDs; unknown `project_name` / place names need clarification (not auto-created).
2. Match: `id` → `papsid` → `control_number`. New rows need at least one of papsid/control_number plus identity fields.
3. When project is set, phase is required (`phase_id` or `phase_name`), same as web create. Municipality and barangay are required with project.
4. Invitation card columns are included: `invitation_rsvp`, reasons, `invitation_date_received_visit` (`First Visit` / `Second Visit`), **invitation 1st visit** (`invitation_first_visit_*`) and **2nd visit** (`invitation_second_visit_*`), distribution status, and `invitation_received_by`. Use exact option text from the Profile form for RSVP / distribution status.
5. Optional legacy field-visit columns: `visit_1_date` / `visit_1_remarks`, `visit_2_*`, `visit_3_*`. Address, representative, ownership, and civil-status columns are also mapped.
6. Cap: 2000 data rows. Updates require `edit_profiles`.

### Workflow

**Profile** list → **Import** → **Preview** → **Import ready rows**.

---

## Structure import

**File:** [structure-import-sample.csv](structure-import-sample.csv)  
**Download (browser):** `/structure/import/sample` (requires login and `add_structure`), or **System → CSV Templates → Structure**. Static copy: `/public/samples/structure-import-sample.csv`

### Before you import

1. Replace placeholders with values from your system:
   - `YOUR_PROJECT_NAME` — exact project name from **Library**
   - `YOUR_MUNICIPALITY` / `YOUR_BARANGAY` — existing names (prefer IDs; unknown names need clarification and are not auto-created)
   - Phase — `phase_id` or `phase_name` (required when project is set; e.g. Unassigned)
2. Match key order: `id` → `strid` → `project` + `structure_tag` (1 match = update, many = clarification, 0 = create).
3. Secondary rows may set `associated_primary_structure_id` or `associated_primary_structure_tag` (tag must resolve uniquely in the project).
4. Cap: 2000 data rows per file. Updates require `edit_structure`; project must be in your allowed scope.

### Workflow

**Structure** list → **Import** → choose file → **Preview** → review summary → **Import ready rows**.

---

## Grievance import

**File:** [grievance-import-sample.csv](grievance-import-sample.csv)  
**Download (browser):** `/grievance/import/sample` (requires login and `add_grievance`), or **System → CSV Templates → Grievance**. Static copy: `/public/samples/grievance-import-sample.csv`

### Before you import

1. Replace placeholders with values from your system:
   - `YOUR_PROJECT_NAME` — exact project name from **Library**
   - `YOUR_MUNICIPALITY` / `YOUR_BARANGAY` — names configured for that project
   - `YOUR_PAPS_ID` — PAPSID of an existing profile (row 2 only)
2. Option columns (`grm_channel_names`, `grievance_type_names`, etc.) must match **Grievance Options** names (run `php database/seeders/seed_grievance_options.php` for defaults).
3. Leave `grievance_case_number` blank on a row to auto-generate a case number on import.
4. `date_recorded` is required on every row and must contain a valid date/time.
5. Match order: prefer `id` when present and found; else existing `grievance_case_number` → **update**; otherwise **create**. Unknown names need clarification (not auto-created). Updates require `edit_grievance`. Cap: 2000 data rows per file.

### Workflow

**Grievances** list → **Import CSV** → choose file → **Preview** → review summary → **Import ready rows**.

You can also export existing grievances from the list and use that CSV as a template (same column names).
