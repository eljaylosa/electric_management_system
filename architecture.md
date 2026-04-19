# Web-based Electric Management, Billing, and Collection System

## System Overview
A comprehensive web application designed to manage electricity consumers, meter readings, billing cycles, and payment collections.

## Core Modules

### 1. User Authentication & Authorization
- Admin Login/Logout
- Role-based access (Admin, Customer)

### 2. Consumer Management
- Add, Edit, Delete, and View Consumers
- Consumer details: Name, Address, Contact, Meter Number, Category (Residential/Commercial)

### 3. Meter Management
- Record monthly meter readings
- Track previous and current readings
- Automatic consumption calculation (Current - Previous)

### 4. Billing System
- Generate bills based on consumption and predefined rates
- Bill details: Due date, Penalty for late payment, Total amount
- Print/View Bill functionality

### 5. Collection & Payment
- Record payments from consumers
- Payment history tracking
- Status updates (Paid/Unpaid)

### 6. Dashboard & Reports
- Summary statistics (Total consumers, Total bills, Total collections)
- Monthly collection reports
- Unpaid bills list

## Technical Stack
- **Frontend:** HTML5, CSS3 (Bootstrap for styling), JavaScript (jQuery for AJAX)
- **Backend:** PHP (Procedural or OOP)
- **Database:** MySQL
- **Development Environment:** Visual Studio Code

## Database Schema (Draft)
- `users`: id, username, password, role
- `consumers`: id, name, address, contact, meter_no, category_id
- `categories`: id, name, rate_per_kwh
- `readings`: id, consumer_id, prev_reading, curr_reading, reading_date
- `bills`: id, reading_id, amount, due_date, status (paid/unpaid)
- `payments`: id, bill_id, amount_paid, payment_date
