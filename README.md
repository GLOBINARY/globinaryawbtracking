# GLOBINARY AWB Romania (PrestaShop 8)

## Overview
DPD-first AWB/tracking module for Romania. Sameday and Dragon Star Courier will be added later.

## Requirements
- PrestaShop 8.x
- PHP 8.1+
- cURL enabled

## Install
1. Upload the `globinaryawbtracking` folder to `/modules/`.
2. Install the module from PrestaShop back-office.
3. Configure DPD credentials and **map DPD status codes to PrestaShop order statuses**.

## Important: Status Mapping (Required)
Before issuing AWBs or calculating prices, you must map the required DPD codes:
- 148 (Shipment data received)
- -14 (Delivered)
- 111 (Return to Sender)
- 124 (Delivered Back to Sender)

Mapping is stored in PrestaShop configuration and is specific to DPD. Sameday/DSC mappings will be added later.

## Cron Setup (Status Refresh + Courier Pickup)
These actions only run when your server cron triggers them.

### Option A: Server cron (recommended)
Replace `/path/to/prestashop` with your shop root and adjust PHP path as needed.

```
# Update AWB statuses every 30 minutes
*/30 * * * * /usr/bin/php /path/to/prestashop/modules/globinaryawbtracking/cron_update_awb_statuses.php

# Call courier list daily at 15:05
5 15 * * * /usr/bin/php /path/to/prestashop/modules/globinaryawbtracking/cron_call_courier_list.php
```

### Option B: PrestaShop CronJobs module
If you use the official CronJobs module, add tasks pointing to the two PHP scripts:
- `modules/globinaryawbtracking/cron_update_awb_statuses.php`
- `modules/globinaryawbtracking/cron_call_courier_list.php`

## Logging
DPD API requests are logged to:
- `modules/globinaryawbtracking/logs/dpd_api.log`

Each line is JSON with timestamp, request, and response.

## Development Roadmap
See `TODO.md`.
