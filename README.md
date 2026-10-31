# ⚡ HP TechPulse - Enterprise IT Asset Lifecycle & Gadget Health Intelligence Platform

![HP TechPulse](uploads/HP%20TechPulse%20logo.png)

[![PHP](https://img.shields.io/badge/PHP-5.4%20--%208.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-techpulse369-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![FPDF](https://img.shields.io/badge/PDF_Engine-FPDF-E44D26?style=for-the-badge&logo=adobe-acrobat-reader&logoColor=white)](http://www.fpdf.org/)
[![CSS3](https://img.shields.io/badge/Styling-Custom%20Modern%20CSS-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-success?style=for-the-badge)]()
[![Author](https://img.shields.io/badge/Developer-Meet%20Chauhan-6366f1?style=for-the-badge&logo=github)](https://github.com/MeetAj369)

---

## 📌 Executive Summary

**HP TechPulse** is a full-featured, enterprise-grade IT Asset Management (ITAM) and Hardware Lifecycle Intelligence platform engineered by **Meet Chauhan**. Designed to replace fragmented spreadsheets and legacy ticketing systems, HP TechPulse centralizes hardware inventory, tracks device health algorithms in real time, generates printable cryptographic Gadget Passports with QR codes, models Written Down Value (WDV) financial depreciation, and automates multi-stage IT support ticket lifecycles.

---

## ✨ Key Enterprise Capabilities

### 1. 🛡️ Digital Gadget Passport™ & QR Engine
* Every provisioned device is issued a verifiable digital passport (`gadget_passport.php`).
* Generates on-demand cryptographic QR codes linked to live hardware profile telemetry.
* Instant generation of official **PDF Gadget Passports** using embedded **FPDF engine** (`gadget_passport_pdf.php`) featuring complete hardware specifications, repair log history, warranty validity certificates, and asset tag codes.

### 2. 🧠 Device Health Intelligence Scoring (0 - 100)
* Proprietary algorithmic health score calculation based on multi-variable telemetry:
  * **Age Decay Factor**: Gradual point deductions based on operational months.
  * **Fault Frequency Penalty**: Dynamic deductions for resolved and recurring hardware issues.
  * **Critical Fault Impact**: Immediate 15-point penalty for unresolved active hardware alerts.
  * **Overdue Maintenance Penalty**: Real-time deduction for missed scheduled servicing.
  * **Warranty & AMC Bonuses**: Score uplift for active vendor warranty coverage and annual maintenance contracts.
* Automated categorization: `Excellent` (80-100), `Good` (60-79), `Needs Attention` (40-59), `Replace Soon` (<40).

### 3. 📉 Written Down Value (WDV) Depreciation Engine
* Automated financial compliance engine calculating multi-year asset value decay.
* Provides year-by-year opening value, depreciation deduction, closing book value, and percentage remaining.
* Integrates with Total Cost of Ownership (TCO) analytics by combining purchase capital expenditure (CapEx) with accumulated maintenance OpEx.

### 4. 🎫 5-Stage IT Support Ticket Workflow
* Complete issue lifecycle pipeline:
  1. `Open` (Ticket raised by employee)
  2. `Assigned` (Routed to specialized IT hardware technician)
  3. `In-Progress` (Technician diagnostic & repair)
  4. `Testing` (Quality assurance verification)
  5. `Resolved` / `Closed` (Device handed back with full audit stamp)
* Granular timeline tracking with chronological comments and status transitions (`issue_updates`).

### 5. 📦 Real-Time Spares & Stock Inventory
* Comprehensive catalog for spare parts (RAM, SSDs, displays, keyboards, batteries).
* Threshold alerts for low stock quantities to prevent IT downtime during emergency repairs.
* Complete vendor directory tracking active suppliers, contact details, and procurement history.

### 6. 🌱 Green Pulse Sustainability Analytics
* Built-in carbon footprint computation based on hardware energy profiles and lifecycle longevity.
* Calculates e-waste reduction percentage achieved by proactive repair vs. premature hardware replacement.

### 7. 🔐 Role-Based Access Control (RBAC) & Security
* Three distinct access tiers:
  * **System Administrator**: Full control over asset provisioning, vendor contracts, stock inventory, user privileges, and global analytics.
  * **Hardware Support Specialist**: Assigned ticket resolution, repair logging, maintenance scheduling, and diagnostics.
  * **Employee / End User**: Dedicated self-service portal (`user_dashboard.php`) for viewing assigned devices, downloading Gadget Passports, raising issues, and tracking repair progress.
* BCrypt hashing compatibility engine with timing-attack mitigation.
* Complete database-backed audit trails (`audit_log`) recording every sign-in, modification, and ticket action.

---

## 🗄️ Database Architecture (`techpulse369.sql`)

The system is powered by a relational MySQL schema comprising **14 integrated tables**:

| Table | Purpose |
|---|---|
| `gadgets` | Master hardware registry (asset tags, serial numbers, models, specs, purchase prices, statuses) |
| `users` | Multi-role user accounts with department mapping and credential hashing |
| `issues` | Support ticket registry with priority, category, stage, and resolution metrics |
| `issue_updates` | Audit timeline of comments, stage transitions, and technician notes |
| `maintenance` | Preventative maintenance schedules, technician assignments, and cost logging |
| `warranties` | Vendor warranty and AMC (Annual Maintenance Contract) records |
| `stock` | Spare parts inventory management with minimum reorder quantities |
| `vendors` | Authorized hardware suppliers, contacts, and warranty providers |
| `audit_log` | Security audit trail recording all administrative and user operations |
| `depreciation_log` | Historical depreciation records and book values |
| `contact_messages` | Inquiries and support messages submitted through the public portal |
| `feedback` | End-user satisfaction ratings and device feedback |
| `password_resets` | Secure token-based password recovery verification |
| `tp_sessions` | Database-backed session security and token authentication |

---

## 🚀 Installation & Local Setup

### Prerequisites
* **Web Server**: Apache (via WAMP, XAMPP, Laragon, or LAMP)
* **PHP**: Version 5.4 to 8.2+
* **Database**: MySQL 5.6+ / MariaDB 10.3+

### Step-by-Step Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/MeetAj369/HP-TechPulse.git
   ```
   Place the project folder inside your web server's document root (e.g., `C:\wamp\www\HP TechPulse` or `C:\xampp\htdocs\HP TechPulse`).

2. **Import Database Schema**:
   * Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   * Create a new database named `techpulse369`.
   * Click **Import** and select the file `techpulse369.sql` included in this repository.
   * Execute the import.

3. **Configure Database Connection**:
   * Check `db.php` and verify your local MySQL credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'techpulse369');
     ```

4. **Run the Application**:
   * Launch your browser and navigate to:
     ```
     http://localhost/HP%20TechPulse/
     ```

---

## 💻 Tech Stack

* **Backend**: Object-Oriented & Procedural PHP with BCrypt password security
* **Database**: MySQL with relational foreign keys and audit indexing
* **PDF Generation**: FPDF Engine (`fpdf.php`)
* **Frontend**: Responsive HTML5, CSS3 with Glassmorphism / Dark Mode themes, Vanilla JavaScript
* **Authentication**: Multi-tier session slot verification with RBAC protection

---

## 👨‍💻 Author

**Meet Chauhan**
* **Portfolio**: [meetaj369.github.io/Meet-Portfolio](https://meetaj369.github.io/Meet-Portfolio/)
* **GitHub**: [@MeetAj369](https://github.com/MeetAj369)
* **LinkedIn**: [linkedin.com/in/meet-chauhan-41b183375](https://linkedin.com/in/meet-chauhan-41b183375)
* **Education**: BCA Undergraduate, Charutar Vidya Mandal University (CVMU), Anand, Gujarat

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).
