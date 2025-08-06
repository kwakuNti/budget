# Enhanced Cycle Management System

## Overview

The enhanced cycle management system provides a comprehensive solution for managing monthly contribution cycles with proper state transitions and data preservation.

## Key Features

### 1. Proper Cycle States
- **Active Cycle**: Normal operation, contributions allowed
- **Closed Cycle**: Month ended, contributions blocked, waiting for new month
- **Waiting for New Month**: Transition state between closed cycle and new cycle

### 2. Automatic Cycle Management
- Auto-close cycles after grace period (3 days after month end)
- Auto-create new cycles at the beginning of each month (first 5 days)
- Preserve historical data and analytics

### 3. Manual Cycle Control
- **Close Cycle**: Manually close current month's cycle
- **Start New Cycle**: Manually start next month's cycle (only when appropriate)

## How It Works

### Monthly Cycle Flow
1. **Month Begins**: New cycle automatically created
2. **During Month**: Members make contributions
3. **Month Ends**: Cycle can be closed manually or auto-closes after grace period
4. **Between Months**: System waits in "closed" state
5. **New Month**: New cycle can be started (manual or automatic)

### Data Handling
- When cycle closes: All contribution data is preserved in `member_debt_history`
- Member monthly contributions reset to 0
- Historical analytics remain intact
- Debt tracking continues for incomplete members

### User Interface
- **Dashboard Banner**: Shows current cycle status and progress
- **Contribution Buttons**: Disabled when cycle is closed/waiting
- **Cycle Actions**: 
  - "Close Cycle" button (active cycles only)
  - "Start New Cycle" button (waiting state only)
  - "Summary" button (always available)

## Files Modified/Created

### Core Files
- `includes/cycle_functions.php` - Enhanced cycle management functions
- `api/dashboard_data.php` - Updated to handle cycle states
- `public/js/dashboard.js` - Enhanced UI with state management
- `templates/dashboard.php` - Added start cycle button

### New Files
- `ajax/start_new_cycle.php` - AJAX endpoint for starting new cycles
- `cron/comprehensive_cycle_maintenance.php` - Automated maintenance system

## Key Functions

### `closeMonthlyCycle($conn, $cycle_id, $closed_by)`
- Closes active cycle and records debts
- Does NOT create new cycle immediately
- Preserves monthly analytics

### `startNewCycle($conn, $family_id, $year, $month)`
- Creates new cycle for specified month
- Only if no cycle exists for that month
- Logs the creation

### `checkAndStartNewMonthCycles($conn)`
- Auto-creates cycles for new months
- Only runs in first 5 days of month
- Checks all families

## Automation Setup

### Cron Job (Recommended)
Add to your crontab to run daily at 2 AM:
```bash
0 2 * * * /usr/bin/php /path/to/budget-app/cron/comprehensive_cycle_maintenance.php
```

### Manual Execution
Via web browser:
```
http://localhost/budget-app/cron/comprehensive_cycle_maintenance.php?run=maintenance
```

Via command line:
```bash
php /path/to/budget-app/cron/comprehensive_cycle_maintenance.php
```

## User Experience

### Normal Flow
1. Members see active cycle with days remaining
2. Members can contribute normally
3. Admin can close cycle when ready
4. System shows "Waiting for New Month" state
5. Admin can start new cycle when new month begins

### Automatic Flow
1. System auto-closes overdue cycles after 3 days
2. System auto-creates new cycles in first 5 days of month
3. No manual intervention needed

## Benefits

1. **Data Integrity**: Monthly analytics are preserved when cycles close
2. **Clear State Management**: Users know exactly what state the system is in
3. **Flexible Control**: Both manual and automatic cycle management
4. **Proper Transitions**: No premature resets or data loss
5. **Historical Tracking**: Complete audit trail of all cycle operations

## Troubleshooting

### No Cycle Showing
- Check if cycle exists in database
- Run maintenance script to create missing cycles
- Check family_id associations

### Contributions Not Working
- Verify cycle is active (not closed or waiting)
- Check user permissions
- Verify member_id parameters

### Auto-functions Not Working
- Ensure cron job is set up correctly
- Check server timezone settings
- Verify database connections

## Database Schema

The system uses these key tables:
- `monthly_cycles`: Main cycle information
- `member_monthly_performance`: Individual member progress per cycle
- `member_debt_history`: Historical debt records
- `activity_logs`: System activity tracking

This enhanced system provides a robust, automated solution for managing monthly contribution cycles while preserving data integrity and providing clear user feedback about system state.
