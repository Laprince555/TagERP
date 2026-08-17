---
paths:
  - 'Modules/**/System/**,app/Support/DynamicRecordView/**,app/Livewire/DynamicRecordView/**'
---

# Dynamic Record View

## Record view buttons go through RecordAction, never hand-rolled
Header buttons on a Dynamic Record View are declared in the definition's actions() as RecordAction objects. Three shapes: url() links out, form() opens the record's Dynamic Form modal in edit mode, anything else calls a handler method on the definition. A handler returning a string is treated as a redirect URL.

Each action is gated by one Spatie permission, {applicationCode}.{action}, defaulting to the action's own key (make('post') -> fin-gl-jou.post). These names already exist: SyncPermissionsCommand generates view/create/update/delete/export/print plus the Application's custom_actions. A key mapping to no permission renders nothing, so omission fails closed.

Two traps:
- The record a handler receives is a DISPLAY projection — relations are eager loaded column-narrowed, so ledger->chart reads as null. Re-fetch a full model before handing it to a service that walks relations.
- After a handler mutates the record, RecordView re-resolves it via RecordResolver::resolveFresh; without that the same request re-renders from the memoized pre-mutation copy and keeps showing actions that no longer apply.
