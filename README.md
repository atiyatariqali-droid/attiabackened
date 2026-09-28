```{=html}
<p align="center">
```
```{=html}
<h1 align="center">
```
Distributed Trust-Based Attendance Verification System (DAVS)
```{=html}
</h1>
```
```{=html}
</p>
```
```{=html}
<p align="center">
```
`<strong>`{=html}Final Year Project (FYP)`</strong>`{=html}
```{=html}
</p>
```
```{=html}
<p align="center">
```
`<img src="https://img.shields.io/badge/Frontend-Flutter-blue" alt="Flutter">`{=html}
`<img src="https://img.shields.io/badge/Backend-Laravel%2012-red" alt="Laravel">`{=html}
`<img src="https://img.shields.io/badge/Database-MySQL-orange" alt="MySQL">`{=html}
`<img src="https://img.shields.io/badge/State%20Management-GetX-purple" alt="GetX">`{=html}
`<img src="https://img.shields.io/badge/Authentication-Laravel%20Sanctum-green" alt="Sanctum">`{=html}
```{=html}
</p>
```

------------------------------------------------------------------------

## About DAVS

The **Distributed Trust-Based Attendance Verification System (DAVS)** is
a secure attendance verification system designed to prevent proxy and
fake attendance by combining **teacher device verification, campus
geo-fencing, and student confirmation**.

Instead of relying on manual registers or basic attendance applications,
DAVS verifies that the teacher is present inside the campus and allows
students to mark attendance only during an active class session.

## Problem Statement

Traditional attendance systems have several limitations:

-   Proxy attendance by students
-   Fake attendance records
-   Attendance marked when the teacher is absent
-   No verification of classroom or campus presence

DAVS addresses these issues through location verification, active
attendance sessions, registered teacher devices, and trust-based
validation.

## Objectives

-   Prevent proxy attendance
-   Verify teacher presence
-   Allow attendance only inside the campus
-   Generate accurate attendance reports
-   Provide role-based access for Admin, Teacher, and Student
-   Improve the reliability and transparency of attendance records

## System Roles

### Admin

-   Manage teachers and students
-   Create and manage classes
-   Approve teacher accounts
-   View attendance analytics
-   Export attendance reports in PDF and Excel

### Teacher

-   Login using a registered device
-   Create attendance sessions
-   Mark and monitor student attendance
-   View class attendance reports
-   Receive student verification responses

### Student

-   Join active attendance sessions
-   Mark attendance within the campus radius
-   Respond to teacher presence notifications
-   View personal attendance reports

## Key Features

-   🔐 **Teacher Device Binding**
-   📍 **100-Meter Campus Geo-Fencing**
-   🟢 **Active/Inactive Attendance Sessions**
-   👥 **Role-Based Authentication**
-   ✅ **Student Attendance Verification**
-   🎲 **Random 20% Student Confirmation System**
-   📊 **Attendance Percentage Calculation**
-   📄 **PDF and Excel Report Generation**

## Technology Stack

  Layer              Technology
  ------------------ -----------------
  Frontend           Flutter
  Backend            Laravel 12
  Database           MySQL
  Authentication     Laravel Sanctum
  State Management   GetX
  Location Service   Geolocator

## System Workflow

1.  Admin registers teachers and students.
2.  Teacher logs in from the registered device.
3.  Teacher creates an attendance session.
4.  The system verifies the teacher's location within the 100-meter
    campus radius.
5.  Students mark attendance during the active session.
6.  Random 20% of students receive a teacher verification notification.
7.  Students submit their Yes/No verification response.
8.  Admin and Teacher can generate attendance reports.

## Database Modules

  -----------------------------------------------------------------------
  Module                              Description
  ----------------------------------- -----------------------------------
  Users                               Admin, Teacher, and Student
                                      accounts

  Manage Classes                      Class creation and management

  Class Groups                        Student grouping and class
                                      association

  Attendance Sessions                 Active attendance session
                                      management

  Attendance                          Student attendance records

  Teacher Confirmation Responses      Student responses for teacher
                                      presence verification

  System Settings                     System configuration and settings
  -----------------------------------------------------------------------

## Main API Modules

  Module            Purpose
  ----------------- ------------------------------------------------
  Authentication    Login and role verification
  Create Session    Teacher starts an attendance session
  Mark Attendance   Student attendance
  Reports           Admin, Teacher, and Student attendance reports
  Verification      Teacher presence confirmation

## Installation

### Clone Repository

``` bash
git clone https://github.com/your-repository/davs.git
cd davs
```

### Backend Dependencies

``` bash
composer install
```

### Environment Setup

``` bash
cp .env.example .env
php artisan key:generate
```

### Database Configuration

Update the `.env` file:

``` env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=attia_backend
DB_USERNAME=root
DB_PASSWORD=
```

### Run Database Migrations

``` bash
php artisan migrate
```

### Start Laravel Server

``` bash
php artisan serve
```

## Flutter Setup

Navigate to the Flutter frontend directory and run:

``` bash
flutter pub get
flutter run
```

## Folder Structure

``` text
backend/
├── app/
│   ├── Http/
│   ├── Models/
│   └── ...
├── routes/
├── database/
└── config/

frontend/
├── lib/
│   ├── screens/
│   ├── services/
│   ├── models/
│   └── widgets/
└── ...
```

## Attendance Verification Flow

``` text
Admin
  │
  ├── Registers Teachers & Students
  │
  ▼
Teacher
  │
  ├── Login from Registered Device
  ├── Location Verification
  └── Create Attendance Session
  │
  ▼
Student
  │
  ├── Join Active Session
  ├── Location Verification
  └── Mark Attendance
  │
  ▼
Random 20% Verification
  │
  ├── Student receives notification
  └── Student responds Yes / No
  │
  ▼
Attendance Records & Reports
```

## Future Improvements

-   QR-based classroom verification
-   Firebase push notifications
-   Biometric verification
-   Multi-campus support
-   Advanced attendance analytics
-   Real-time attendance notifications

## Author

```{=html}
<p align="center">
```
`<strong>`{=html}Distributed Trust-Based Attendance Verification System
(DAVS)`</strong>`{=html}`<br>`{=html} Developed with Flutter, Laravel &
MySQL
```{=html}
</p>
```
## License

This project is developed for **educational purposes as a Final Year
Project (FYP)**.

------------------------------------------------------------------------

```{=html}
<p align="center">
```
`<strong>`{=html}DAVS --- Distributed Trust-Based Attendance
Verification System`</strong>`{=html}
```{=html}
</p>
```
