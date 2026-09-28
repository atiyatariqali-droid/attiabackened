![DAVS Logo](https://raw.githubusercontent.com/Minahilgul/minahilfrontendattendance/main/assets/images/davs_logo.jpg)
# 1. Project Title
Distributed Attendance Verification System (DAVS)
Final Year Project (FYP)

A secure attendance verification system designed to prevent proxy attendance by combining teacher device verification, campus geo-fencing and student confirmation.
## 2. About DAVS
The Distributed Attendance Verification System (DAVS) is a secure attendance management system designed to solve the problem of fake and proxy attendance in educational institutions.

Instead of relying on manual registers or basic attendance applications. DAVS verifies that the teacher is present inside the campus and allows students to mark attendance only during an active class session.

The system combines teacher device verification, campus geo-fencing, role-based authentication, active attendance sessions and student confirmation to improve the reliability and transparency of attendance records.


## 3. Problem Statement
Traditional attendance systems have several limitations:

- Proxy attendance by students
- Fake attendance records
- Attendance marked when the teacher is absent
- No verification of classroom or campus presence
- Limited attendance monitoring and reporting

DAVS addresses these problems through location verification, registered teacher devices, active attendance sessions and validation.
## 4. Objectives
- Prevent proxy attendance
- Verify teacher presence inside the campus
- Allow attendance only within the defined campus radius
- Generate accurate attendance reports
- Provide role-based access for Admin, Teacher and Student
- Improve the reliability and transparency of attendance records
- Provide attendance percentage calculations
## 4. Objectives
- Prevent proxy attendance
- Verify teacher presence inside the campus
- Allow attendance only within the defined campus radius
- Generate accurate attendance reports
- Provide role-based access for Admin, Teacher and Student
- Improve the reliability and transparency of attendance records
- Provide attendance percentage calculations
## 5. System Roles
Admin

- Manage teachers and students
- Create and manage classes
- Approve teacher accounts
- View attendance analytics
- Generate and export attendance reports
- Manage system settings

Teacher

- Login using a registered device
- Create attendance sessions
- Verify teacher location
- Monitor student attendance
- View class attendance reports
- Receive student verification responses

Student

- Login to the system
- Join active attendance sessions
- Verify location within the campus radius
- Mark attendance
- Respond to teacher presence notifications
- View personal attendance reports
     


## 6. Features

- Teacher Device Binding
- Campus Geo-Fencing
- 150-Meter Campus Radius
- Active and Inactive Attendance Sessions
- Role-Based Authentication
- Student Attendance Verification
- Random 20% Student Confirmation System
- Attendance Percentage Calculation
- PDF Report Generation
- Excel Report Generation
- Attendance Monitoring and Reporting

## 7. Technology Stack
| Layer            | Technology      |
| ---------------- | --------------- |
| Frontend         | Flutter         |
| Backend          | Laravel 12      |
| Database         | MySQL           |
| Authentication   | Laravel Sanctum |
| State Management | GetX            |
| Location Service | Geolocator      |

## 8. System Workflow
   1.  Admin registers teachers and students.
   2.  Teacher logs in using the registered device.
   3.  Teacher creates an attendance session.
   4.  The system verifies the teacher's location within the 150-meter campus radius.
   5.  Students join the active attendance session.
   6.  Students mark their attendance within the allowed campus area.
   7.  The system randomly selects 20% of students for teacher presence verification.
   8.  Selected students receive a verification notification.
   9.  Students respond with Yes or No.
   10.  Attendance records and verification responses are stored in the database.
   11.  Admin and Teacher can view and generate attendance reports.
## 9. Database Modules
| Module                         | Description                                 |
| ------------------------------ | ------------------------------------------- |
| Users                          | Stores Admin, Teacher, and Student accounts |
| Manage Classes                 | Manages classes and class information       |
| Class Groups                   | Groups students according to classes        |
| Attendance Sessions            | Manages active attendance sessions          |
| Attendance                     | Stores student attendance records           |
| Teacher Confirmation Responses | Stores student verification responses       |
| System Settings                | Stores system configuration                 |

## 10. Main API Modules
| Module          | Purpose                                            |
| --------------- | -------------------------------------------------- |
| Authentication  | Login and role verification                        |
| Create Session  | Teacher starts an attendance session               |
| Mark Attendance | Student attendance                                 |
| Reports         | Attendance reports for Admin, Teacher and Student |
| Verification    | Teacher presence confirmation                      |

## 11. Installation

Clone Repository


  https://github.com/Minahilgul/minahilfrontendattendance.git
  
  https://github.com/atiyatariqali-droid/attiabackened.git

  Backend Dependencies

    composer install
Environment Setup

    cp .env.example .env
    php artisan key:generate
Database Configuration

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=backend
    DB_USERNAME=root
    DB_PASSWORD=
Run Migrations

    php artisan migrate
Start Laravel Server

    php artisan serve
    


## 12. Flutter Setup
    flutter pub get
    flutter run -d chrome
## 13. Folder Structure

backend/
├── app/
├── routes/
├── database/
├── config/
└── ...

frontend/
├── lib/
├── models/
├── services/
├── screens/
├── widgets/
└── ...
## 14. Attendance Verification Flow

Admin
  |
  |-- Register Teachers & Students
  |
  v
Teacher
  |
  |-- Login from Registered Device
  |-- Location Verification
  |-- Create Attendance Session
  |
  v
Student
  |
  |-- Join Active Session
  |-- Location Verification
  |-- Mark Attendance
  |
  v
Random 20% Verification
  |
  |-- Student receives notification
  |-- Student responds Yes / No
  |
  v
Attendance Records
  |
  v
Reports
## 15. Future Improvements

- QR-based classroom verification
- Biometric verification
- Multi-campus support
- Advanced attendance analytics
- Real-time attendance notifications
##  16. Project Structure / Modules

Admin Panel

    |
    |-- Teacher Management
    |-- Student Management
    |-- Class Management
    |-- Attendance Analytics
    |-- Reports
    |

Teacher Module

    |
    |-- Device Verification
    |-- Location Verification
    |-- Session Management
    |-- Attendance Monitoring
    |

Student Module

    |
    |-- Active Sessions
    |-- Location Verification
    |-- Attendance Marking
    |-- Teacher Verification
    |-- Attendance Reports
## 17. Author
Distributed Attendance Verification System (DAVS)

Developed with Flutter, Laravel & MySQL.

Final Year Project (FYP)
## 18. License
This project is developed for educational purposes as a Final Year Project (FYP).