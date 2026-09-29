![DAVS Logo](https://raw.githubusercontent.com/Minahilgul/minahilfrontendattendance/main/assets/images/davs_logo.jpg)
# 1. Project Title
Distributed Attendance Verification System (DAVS)
Final Year Project (FYP)

A secure attendance verification system designed to prevent proxy attendance by combining teacher device verification, campus geo-fencing and student confirmation.
## 2. Installation

Clone Repository


  
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
    


## 3. Folder Structure


backend/
├── app/
├── routes/
├── database/
├── config/
└── ...


## 4. Attendance Verification Flow

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
## 5. Future Improvements

- QR-based classroom verification
- Biometric verification
- Multi-campus support
- Advanced attendance analytics

##  6. Project Structure / Modules

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
## 7. Author
Distributed Attendance Verification System (DAVS)

Developed with Flutter, Laravel & MySQL.

Final Year Project (FYP)
## 8. License
This project is developed for educational purposes as a Final Year Project (FYP).