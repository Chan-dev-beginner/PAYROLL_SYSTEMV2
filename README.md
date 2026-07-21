# Payroll System

Payroll System is a PHP/MySQL web application designed for small HR teams to manage employees, attendance, leave requests, incentives, and payroll in a single dashboard.

## Features

- User authentication with role-based access control
- Employee attendance tracking with time in, time out, and attendance history
- Leave request submission for employees and leave approval by HR
- Payroll viewing for employees with the latest payslip
- HR/Admin dashboard showing employee counts, pending leave requests, and incentive summaries
- Employee management: add, edit, and delete employee records
- Incentive type management with add/update/delete functionality
- Attendance overview for HR with filtering by employee and date
- Secure session handling and access restrictions for HR/admin features

## Technology Stack

- PHP
- MySQL
- HTML, CSS
- XAMPP / Apache

## Installation

1. Copy the project folder to your XAMPP `htdocs` directory.
2. Import `payroll_system.sql` into your MySQL database.
3. Update `model/database.php` with your MySQL credentials.
4. Open the project in your browser, for example:
   - `http://localhost/Payroll_system/`
5. The root `index.php` redirects to the login page.

## Project Structure

- `index.php` - Application entry point and redirect to login
- `model/` - Database and session helper files
- `controller/` - Main application pages and logic for HR, attendance, leave, payroll, and reports
- `view/` - CSS stylesheets for dashboard and pages
- `payroll_system.sql` - Database schema and seed data

## Usage

- Login as an employee or HR/admin to access the dashboard.
- Employees can manage attendance, request leave, and view payslips.
- HR/Admin users can manage employees, approve or reject leave requests, handle incentives, and view payroll records.

## Notes

- The project uses relative paths for includes and redirects to simplify deployment inside the `Payroll_system` folder.
- If you rename the folder, update any absolute redirects or links in `index.php` if needed.

 ## Contributors

 - Christian Roy V Bejerano
 - Angel Grace Ramirez
 - Jo-ann Blanco
 - Anna Tricia Sagadal
 - Mark Angelo Sapnu
 - Leanne Ubaldo

