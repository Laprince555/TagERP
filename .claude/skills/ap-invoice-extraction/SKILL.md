---
name: ap-invoice-extraction
description: Extract and prepare Accounts Payable vendor invoices (scanned images, PDFs, or pasted text) for TagERP's finance_ap_invoices / finance_ap_invoice_lines import. Use whenever the user shares a vendor invoice, bill, or receipt and wants it parsed, resolved against a vendor financial profile, and turned into an import-ready payload. Also use for "process this invoice", "extract this bill", "match this invoice to a vendor profile", or any AP intake task. This skill NEVER writes to the database and NEVER activates/posts an invoice — it only produces a reviewable payload for TagERP's existing HR/finance approval workflow, where a human makes the final save and activation.
---

# AP Invoice Extraction

Turn one vendor invoice into a structured, human-reviewable payload for TagERP's Accounts Payable intake. This skill is the intake/preparation step only — it stops short of any database write. TagERP's own approval chain (a human, ending with someone who activates the invoice) owns the actual save.

## Why this shape

The riskiest part of AP intake isn't reading the numbers off a PDF — OCR is mostly solved. It's **routing the invoice to the right financial identity**. A single legal vendor (`procurement_vendors`) can have several `finance_ap_vendor_profiles` rows — one per financial role (raw-material supplier, subcontractor, consulting service), each with its own currency and ledger routing. Post an invoice to the wrong profile and the double-entry ledger reports correctly but *means* the wrong thing. So this skill is deliberately conservative: it never invents a vendor or profile ID, and it downgrades to `needs_review` rather than guessing when the evidence is thin.

## Workflow

### 1. Extract header and line items

Read the invoice (image/PDF via OCR, or pasted text) and pull out:

**Header**: `vendor_name_raw`, `invoice_number`, `issue_date`, `due_date`, `currency`, `subtotal`, `tax_amount`, `total_amount`, `po_reference` (nullable).

**Line items**: array of `{ description, quantity, unit_price, line_total }`.

Tag every extracted field with a `confidence_score` (0–1). Low-confidence fields aren't blockers by themselves, but they feed the validation step below and should be visible to whoever reviews the payload — a human catching a misread total in ten seconds beats them discovering it after posting.

Dates: normalize to `YYYY-MM-DD`. If the invoice's date format is ambiguous (e.g. `03/04/2026`), don't guess — lower that field's confidence and note the ambiguity.

### 2. Resolve the vendor and financial profile

This step must use real lookups, never invented IDs — a plausible-looking `vendor_profile_id` that doesn't exist is worse than no ID at all, because it can silently corrupt the ledger if a downstream step trusts it.

1. Fuzzy-match `vendor_name_raw` against `procurement_vendors` (name, known aliases, tax/registration number if present on the invoice). Use whatever lookup tool or query mechanism is available in the current session (e.g. a `resolve_vendor_profile` tool, a database query, or an MCP call) — do not fabricate a match.
2. If the vendor matches and has **exactly one** related `finance_ap_vendor_profiles` row, auto-select it.
3. If the vendor has **multiple** profiles, rank them using the line items: match item descriptions/keywords against what each profile represents (materials vs. services vs. subcontracting, etc.) and pick the single best-fitting profile. Report *why* — which keywords or signals drove the choice — and a confidence score for that choice. Don't present a menu; commit to one best-effort answer with visible reasoning, so the human reviewer can agree or override in one glance.
4. If no vendor match is found at all, do not create one and do not guess a profile. Leave `vendor_id`/`vendor_profile_id` null and flag it (see validation).

### 3. Validate before marking anything "ready"

Check all of the following. Any failure sets `status: "needs_review"` instead of `"ready_for_import"` — this status only ever gets safer (more review), never skips review:

- **Vendor matched** — a real `vendor_id` and `vendor_profile_id` were resolved in step 2.
- **Currency match** — invoice `currency` equals the resolved profile's currency.
- **Totals reconcile** — `subtotal + tax_amount == total_amount` (within a small rounding tolerance).
- **PO present when required** — if the resolved vendor's profile type requires a PO reference, `po_reference` is non-null.
- **Profile selection confidence** — if step 2.3 (multi-profile ranking) produced a low-confidence pick, flag it even though a profile was technically selected.

List every failed check by name in `validation_flags` — don't just emit a boolean. The reviewer needs to know *what* to check, not just *that* something's off.

### 4. Emit the payload — never insert it

Output JSON shaped like this. Do not call any database-write tool, migration, or Eloquent create/save on `finance_ap_invoices` or `finance_ap_invoice_lines` — this skill's job ends at producing the payload below for a human to carry through TagERP's approval workflow.

```json
{
  "invoice_header": {
    "vendor_name_raw": "string",
    "invoice_number": "string",
    "issue_date": "YYYY-MM-DD",
    "due_date": "YYYY-MM-DD",
    "currency": "EGP",
    "subtotal": 0.00,
    "tax_amount": 0.00,
    "total_amount": 0.00,
    "po_reference": null
  },
  "line_items": [
    { "description": "string", "quantity": 0, "unit_price": 0.00, "line_total": 0.00 }
  ],
  "field_confidence": { "...field_name": 0.0 },
  "vendor_resolution": {
    "vendor_id": null,
    "vendor_profile_id": null,
    "match_confidence": 0.0,
    "match_reasoning": "string explaining the match and, if multiple profiles existed, why this one was picked",
    "candidate_profiles": []
  },
  "validation_flags": ["currency_mismatch", "total_mismatch", "unmatched_vendor", "po_required_missing", "low_confidence_profile_match"],
  "status": "ready_for_import"
}
```

`status` is `"ready_for_import"` only when `validation_flags` is empty. Otherwise `"needs_review"`.

## Note on today's schema

`procurement_vendors` and `finance_ap_vendor_profiles` don't exist in TagERP yet — they're planned. Until they land, do the vendor-resolution step (2) against whatever the current session actually has available (ask the user, or check `.ai/rules` / migrations for the latest schema) rather than assuming file paths. When those tables are built, the lookup should go through TagERP's dynamic engines conventions (see the project's `.ai/rules/dynamic-record-view.md` and `dynamic-table.md`) rather than raw queries, once a real query/tool interface exists.
