# LinkageClock (Work in Progress)
Linkage - Payroll Time Tracking Theme

## Overview
LinkageClock is a payroll time tracking theme designed for efficient employee time management and payroll processing.

## Project Objective

Develop a WordPress site for desktop-only employee time tracking, including Time In, Time Out, Lunch Start, Lunch End, individual employee profiles, and payroll-ready reporting with export capability. The system must feature a main dashboard displaying real-time clock-in and clock-out status of all employees.

## Deliverables

1. **Main Dashboard**
    - Displays a live list of all employees showing their current status (Clocked In or Clocked Out) and the time of their last action.
    - Search and filter options by name, role, or status.
2. **Desktop-Only Employee Clocking Portal**
    - Time In, Lunch Start, Lunch End, and Time Out buttons.
    - Prevents clock-in from mobile devices.
    - Displays most recent event timestamp for the individual.
3. **Individual Employee Profiles**
    - Displays employee name, ID, position, hire date, and profile photo.
    - Shows detailed attendance logs with date, time in/out, lunch times, and total hours.
    - Summary section with total hours worked, average daily hours, days worked, and overtime hours for a selected period.
    - Employee can access own profile; payroll/admin can access all profiles.
4. **Payroll Dashboard**
    - Filter by employee and date range (with quick presets for biweekly and monthly).
    - View detailed logs with exact timestamps.
    - Export filtered data in CSV and XLSX format.
5. **Data Management**
    - Accurate server-based timestamp recording for all events.
    - Automatic total hours calculation per shift (subtracting lunch duration).
    - Admin and Manager ability to manually correct entries with audit notes.
6. **Security & Access Control**
    - Role-based permissions to control visibility and actions.

## Roles

- **Admin**: Full access to all features, settings, and reports. Can manage all users, view main dashboard, and make manual corrections.
- **Manager**: Can view main dashboard and manage logs for employees they supervise, run reports, and request corrections.
- **Accounting | Payroll**: Can view main dashboard, access and export all attendance records, filter by date range and employee, and generate payroll reports.
- **Employee**: Can clock in/out, log lunch breaks, view their own profile, and see their status on the main dashboard.
- **Contractors**: Can clock in/out, log lunch breaks, view their own profile, and see their status on the main dashboard.

## Exports

- Generate CSV/XLSX exports for any date range and employee.
- Export includes: Employee Name, Employee ID, Date, Time In, Lunch Start, Lunch End, Time Out, Total Hours.

## Out of Scope

- Mobile device clocking.
- Integration with third-party payroll systems.
- Geolocation or IP restrictions.

## Acceptance Criteria

- Main dashboard shows real-time clock-in/clock-out status for all employees.
- Employees can clock in/out and log lunch breaks only from desktop.
- All time logs use server time.
- Payroll can filter, view, and export logs.
- Each employee has a profile with attendance history and summaries.
- Admin and Manager only - Manual corrections are logged with notes for audit purposes.
- Role permissions function as defined above.
