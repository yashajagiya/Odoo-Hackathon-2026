# Project Summary

Hackathon

## Target Modules
- Authentication Manager: Secure login, email/password auth, RBAC middleware, role/permission seeding, session management
- Dashboard Engine: Real-time KPI aggregation (Active/Available/Maintenance vehicles, Active/Pending trips, Drivers on duty, Fleet Utilization %), filterable by vehicle type, status, region, chart rendering via Chart.js
- Vehicle Registry: CRUD for master vehicle list, unique registration validation, status enum (Available, On Trip, In Shop, Retired), region/type classification, odometer & cost tracking
- Driver Management: Profile CRUD with license category/expiry, safety score, status enum (Available, On Trip, Off Duty, Suspended), user account linking, expiry validation hooks
- Trip Orchestrator: Draft→Dispatched→Completed/Cancelled lifecycle, dispatch validation (capacity, license, availability), automatic status transitions for vehicle/driver, odometer/fuel capture on completion
- Maintenance Workflow: Log creation with cost/date, automatic vehicle status flip to In Shop (removing from dispatch pool), closure restores to Available unless Retired, history timeline
- Fuel & Expense Ledger: Fuel logs (liters, cost, date, odometer) linked to vehicle/trip, generic expense categories (tolls, repairs), automatic operational cost rollup per vehicle
- Analytics & Reporting: Fuel Efficiency (Distance/Fuel), Fleet Utilization, Operational Cost, Vehicle ROI formula, CSV export via Laravel Excel, optional PDF via DomPDF, date-range filtering
- Notification Service: Scheduled command for license expiry reminders (30/15/7 days), queued mail notifications, configurable templates
- Document Vault: Vehicle document upload (insurance, registration, inspection), expiry tracking, secure storage, list/download views