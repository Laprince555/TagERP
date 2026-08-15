# Managing Employee Permissions

A practical guide for system administrators: how to give an employee access to something in
the system, and how to take it away.

## The core idea

An employee's permissions in TagERP are **never set on the employee directly** — they're an
automatic result of five things on their employment record:

1. **Company** they work for
2. **Branch** they work at
3. **Department** they belong to
4. **Job title**
5. **Job grade**

To grant someone access, you don't go looking for a "permissions screen" and tick boxes — you
**set these five fields correctly**, and the system computes everything else on its own. If an
employee moves from Procurement to Finance, their permissions change automatically, the moment
you save — no extra step.

## The two things that get decided

Every employee has two completely separate dimensions, and both need to be set correctly:

### 1. Which data they'll see (scope)

Set via two fields when adding/editing the employee:

| Field | Available values | What it means |
|---|---|---|
| **Company scope** | Own records only / Own branch / Whole company / Company + subsidiaries | A regular employee is usually "own branch". A group-level department manager is usually "company + subsidiaries" |
| **Department scope** | Own records only / This department / Department + sub-departments / Every department | A department manager is usually "department + sub-departments" |

**Worked example:** A finance manager at the parent company, with company scope "company +
subsidiaries" and department scope "department + sub-departments", sees Finance data across
**every** company in the group. The same manager, if stationed at a subsidiary instead, with
company scope "whole company" only (not "+ subsidiaries"), sees Finance data for **that company
only** — not the rest of the group.

### 2. Which screens they can open (functional permissions)

This is decided automatically by the employee's **department**, **job title**, and **grade** —
never set on the person directly.

- An employee in the **Finance** department can open the Finance module automatically. Someone
  outside it cannot open it at all.
- A job title like "General Accountant" carries specific permissions (e.g. viewing journal
  entries) that apply to everyone holding that title.
- Some permissions are also tied to grade — e.g. a "General Accountant" at **senior** grade can
  post a journal entry; the same title at junior grade cannot, even though both can see the
  same screen.

## How to grant an employee access

1. Open the **Employees** screen (HR → Employee Management).
2. Add the employee (or open their existing record) and set:
   - Company and branch
   - Department
   - Job title
   - Job grade
   - Company scope and department scope (see the table above)
3. Save. Permissions take effect **immediately** — no extra step, no waiting period.

**If the job title, department, or grade you need doesn't exist yet?** It has to be added
first, from its own screen (Organization Structure → Departments / Job Titles / Job Grades),
before you can select it on an employee.

## How to revoke an employee's access

Three approaches depending on the situation:

| Situation | What to do |
|---|---|
| **Employee isn't working right now** (temporary leave/suspension) | Set status to **"Suspended"** — every permission is lost immediately, restored the moment status returns to "Active" |
| **Employee's contract ended / resigned permanently** | Set status to **"Terminated"** — same effect, permanent |
| **Employee is still working but needs different/reduced access** | Change their job title/department/grade to match the new situation — permissions update automatically the moment you save |

⚠️ **Important:** Deleting the employee record is **not** the correct way to revoke access —
always use "Suspended"/"Terminated" so their history stays on record.

## FAQ

**Q: I want an employee to see everything in Finance but not edit anything.**
This is decided by the **job title** assigned to them — there needs to be a job title already
defined with "view only" permissions. If none exists, ask the development/admin team to add one.

**Q: I want to give a manager access to two specific branches, not the whole company.**
The system currently supports "own branch" or "whole company" — there's no "two specific
branches" option yet. If you genuinely need this, report it to the development team.

**Q: I made the change but the employee still sees/doesn't see the old screen.**
Make sure the employee logged out and back in — or wait a moment; permissions update
immediately in the database, but the browser may still be holding an old copy of the sidebar.

**Q: Someone has a permission they shouldn't, and I'm sure I set their record up correctly.**
This is rare, but can happen if someone edited the job title's own permissions directly without
saving any employee record. Ask the development team to run the permission-reconciliation
command (`hr:permissions:reconcile`), which detects and fixes any drift automatically.
