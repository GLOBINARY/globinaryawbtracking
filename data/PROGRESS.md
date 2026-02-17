# Globinary AWB Tracking - Progress

## Done
- DSC delete AWB aligned with docs: uses `DELETE /awb/:awb` with `Content-Type: application/x-www-form-urlencoded`.
- DSC delete now tries normalized AWB candidates (raw / without alpha prefix / digits-only) and only confirms success when API returns delete confirmation.
- Repository initialized and structured with conventional commit history for OSS publishing.
- Sameday status sync improved: queries both `/api/client/status-sync` and `/api/client/xb-status-sync`, paginates pages, and merges results before AWB match.
- DPD print UI error surfaces backend response snippet when JSON parsing fails (better diagnostics).
- Prevented duplicate order-history entries: mapped status updates now skip if order already has target status.
- BO AWB table logos switched to provided PNG assets (`sameday.png`, `dsc_logo.png`).
- Sameday status sync requests now send `startTimestamp` / `endTimestamp` first (seconds + milliseconds fallback).
- DPD print parser hardened:
  - accepts PDF by header (`%PDF-`),
  - accepts base64 PDF from JSON payload,
  - returns readable error message when response is invalid.
- Fixed AWB status lookup SQL and update WHERE clauses (removed malformed LIKE patterns causing MariaDB 1064 errors).
- Tracking links now courier-specific in AWB table:
  - DPD -> `tracking.dpd.ro`
  - Sameday -> `sameday.ro/#awb=`
  - DSC -> `dragonstarcurier.ro/tracking-awb?awb=`
- Status update supports all couriers:
  - DPD status update
  - Sameday status update via sync endpoints
  - DSC status update via `GET /awb/status/:awb`
- Print AWB UI validates content-type/size and avoids empty/invalid silent downloads.
- DPD issue: friendly Romanian validation message for incomplete address (street+number).
- Sameday single-parcel issuance no longer duplicates AWB row.
- Pickup interval fields moved to General tab (global for couriers).
- DSC password persistence fixed (empty password fields no longer overwrite saved credentials).
- Core module base established (PS 8.x, PHP 8.1, MIT).
- DPD full flow implemented: issue, cost, print A4/A6, delete via API, status sync.
- DPD localities import from bundled CSV + manual sync button.
- DPD status-to-order-state mapping (configurable; required codes prioritized).
- Sameday flow implemented: credentials/token refresh, issue, cost, print, delete, status sync integration.
- Admin order panel implemented with county/city auto-map + manual override.
- Action buttons disabled until county/city are mapped (prevents early invalid requests).
- SmartBill auto-issue toggle (General tab) with safe/silent click behavior.
- Order status after AWB issue implemented (General tab select).
- Config tabbed UI present (General / DPD / Sameday / DSC).
- Logging and diagnostics added for DPD/Sameday/DSC integration work.
- Network diagnostic utility added: `/Users/globinary/apps/globinaryawbtracking/dist/dsc-network-diagnostic.php`.

## Known / Open
- DPD print still fails in live test on BO with generic UI message in some scenarios. Need one final backend response-shape alignment from live endpoint behavior.
- Sameday manual single-AWB status update should be switched to parcel endpoint for precision:
  - `GET /api/client/parcel/{parcelAwbNumber}/status-history`
  - keep `status-sync` endpoints for cron/bulk updates.
- DSC status-to-PrestaShop-order-state mapping UI/config is not yet finalized (current status update works, but mapping parity with DPD/Sameday is pending).

## Next (Planned)
1. Implement Sameday manual status refresh using `/api/client/parcel/{awb}/status-history`.
2. Keep hourly cron status updates on `status-sync` / `xb-status-sync` with 2h window.
3. Finalize DPD print response handling after one more live payload capture.
4. Add DSC status mapping fields in config (same pattern as DPD/Sameday), then wire order-state updates.
5. End-to-end validation pass for all couriers: issue / cost / print / delete / status / status-mapping.

## Notes
- Current version: 1.6.10
- ZIP build: `/Users/globinary/apps/globinaryawbtracking/dist/globinaryawbtracking.zip`
- Local git workflow:
  - `upstream` -> org repo (`GLOBINARY/globinaryawbtracking`)
  - `origin` -> personal fork (`Gl0deanR/globinaryawbtracking`)
