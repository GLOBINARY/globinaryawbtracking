# Globinary AWB Tracking - Progress

## Done
- Sameday status sync improved: queries both `/api/client/status-sync` and `/api/client/xb-status-sync`, paginates all pages, and merges results before AWB match.
- DPD print UI error now surfaces raw backend response snippet when JSON parsing fails (for faster diagnostics).
- Prevented duplicate order-history entries: mapped status updates now skip when order already has target status.
- BO AWB table courier logos switched to provided PNG assets (`sameday.png`, `dsc_logo.png`).
- Sameday status sync updated to send `startTimestamp`/`endTimestamp` first (seconds and milliseconds fallback).
- DPD print parser improved to accept non-standard PDF responses and include response snippet in error message for diagnostics.
- DPD print hardened: accepts PDF by header (`%PDF-`) and base64 PDF payloads from JSON responses.
- Fixed AWB status lookup SQL for update action (removed malformed LIKE pattern causing MariaDB 1064).
- AWB status update SQL fix: corrected WHERE clause for main AWB + parcel rows (removed MariaDB syntax error on update).
- AWB tracking links now per courier: DPD, Sameday and DSC URLs are generated correctly in BO list and FO hook output.
- Status update now supports DSC via `GET /awb/status/:awb` and returns concrete error messages for Sameday/DSC instead of generic failure.
- Print AWB UI now validates response content-type/size and surfaces API error messages instead of downloading empty PDFs.
- DPD issue: friendly Romanian validation message for incomplete street/number address errors.
- Sameday single-parcel issuance now keeps one AWB row in BO (no duplicate parcel line).
- Pickup interval fields moved to General tab (applies globally).
- DSC password persistence on config save fixed (empty password fields no longer overwrite saved credentials).
- New module base (PS 8.x, PHP 8.1), MIT.
- DPD full flow: issue AWB, calculate price, print A4/A6, delete AWB via API, status sync.
- DPD localities import from bundled CSV + manual sync.
- DPD status-to-order-state mapping (configurable, required codes prioritized).
- Sameday: credentials, token refresh, issue AWB, calculate price, print, delete via API, status sync endpoint integrated.
- Admin order panel: DPD/Sameday sections, county/city auto-map with manual override.
- Buttons disabled until county/city auto-mapped (prevents early submit).
- SmartBill auto-issue toggle (General tab) with silent click on SmartBill button if present.
- Order status after AWB issue (General tab select).
- Config tabbed UI (General/DPD/Sameday).
- Logging for DPD/Sameday API requests.
- DSC config tab + live/test credentials (options are per order).
- Order page header cleaned (removed "DPD și Sameday active..." info line).
- AWB table courier visuals aligned to logos for all 3 couriers (DPD/DSC/Sameday).
- Added network diagnostic utility: `/Users/globinary/apps/globinaryawbtracking/dist/dsc-network-diagnostic.php`.
- Added temporary DSC diagnostics during integration; removed after validation.

## Known/Open
- DPD print still returning empty PDF in some cases (latest fix added content-type check). Needs real-world confirmation.
- Sameday status list imported from local JSON and mapping fields added in config (top 4 prioritized).
- Sameday status sync uses status-to-order-state mapping (configurable).
- DSC connectivity unblocked after IP whitelist.
- DSC cost, issue, print and delete are working in live tests.

## Next (Planned)
- DSC integration:
  - Credentials config (done)
  - Calculate price (done)
  - Issue AWB (done)
  - Print (done)
  - Delete AWB (done)
  - Status sync + mapping to order states (pending)
- Sameday status sync -> order-state update hookup (done).
- Next execution order:
  1. DSC status sync
  2. DSC status-to-order-state mapping in module config
  3. End-to-end status update test (DSC webhook/sync -> order state)
  4. End-to-end Sameday status mapping test on a real AWB

## Notes
- Current version: 1.6.9
- ZIP build: /Users/globinary/apps/globinaryawbtracking/dist/globinaryawbtracking.zip
