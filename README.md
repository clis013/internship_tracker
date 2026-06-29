# InternTrack

InternTrack is a web-based Internship Application Tracking System developed for students, companies, and administrators. It allows students to browse internships and submit applications, companies to post internships and manage applicants, and administrators to monitor system records.

## Features

### Student

* Register and log in
* Manage profile information
* Upload and replace resume
* Browse and search internship postings
* Apply for internships
* Track application status
* Withdraw applications
* Create and manage reminders

### Company

* Register and log in
* Manage company profile
* Create, edit, and delete internship postings
* View applicants
* Download applicant resumes
* Update application status and interview details

### Admin

* Log in to admin dashboard
* View system statistics
* Manage user records
* Monitor company accounts
* View internship postings
* View application records
* Delete invalid or unnecessary records

## Technologies Used

* PHP
* MySQL
* HTML
* CSS
* JavaScript
* Bootstrap
* XAMPP
* phpMyAdmin

## Project Structure

```text
internship_tracker/
├── admin/
├── auth/
├── company/
├── student/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
│   └── db_connect.php
├── includes/
├── uploads/
│   └── resumes/
├── database.sql
└── index.php
```

## Installation

### 1. Install XAMPP

Download and install XAMPP. Start **Apache** and **MySQL** from the XAMPP Control Panel.

### 2. Move Project Folder

Place the project folder inside the XAMPP `htdocs` directory.

Example:

```text
C:/xampp/htdocs/internship_tracker/
```

### 3. Create Database

Open phpMyAdmin and create a new database:

```sql
internship_tracker
```

### 4. Import Database

Import the provided `database.sql` file into the newly created database using phpMyAdmin.

Steps:

1. Open phpMyAdmin.
2. Select the `internship_tracker` database.
3. Click the **Import** tab.
4. Choose the `database.sql` file.
5. Click **Go**.

### 5. Configure Database Connection

Open:

```text
config/db_connect.php
```

Check that the database connection settings match your local environment.

Example:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "internship_tracker";
```

### 6. Run the System

Open the system in your browser:

```text
http://localhost/internship_tracker/
```

### Login Steps

1. Open the system in the browser:

```text
http://localhost/internship_tracker/
```

2. Click **Login**.
3. Enter the email and password based on the selected role.
4. After successful login, the system redirects the user to the correct dashboard.

### Role-Based Redirection

| Role    | Redirected Page         |
| ------- | ----------------------- |
| Admin   | `admin/dashboard.php`   |
| Student | `student/dashboard.php` |
| Company | `company/dashboard.php` |

Note: If the login details do not work, check the sample user records in `database.sql` and make sure the database has been imported correctly.

## Main Folder Description

### `admin/`

Contains pages used by administrators to manage and monitor the system, including users, companies, internship postings, and application records.

### `auth/`

Contains authentication pages such as registration, login, logout, forgot password, and reset password.

### `student/`

Contains pages used by students to browse internships, apply for internships, manage profiles, upload resumes, track applications, and manage reminders.

### `company/`

Contains pages used by companies to manage company profiles, create internship postings, view applicants, download resumes, and update application statuses.

### `assets/`

Contains front-end resources such as CSS files, JavaScript files, images, and design elements.

### `config/`

Contains system configuration files. The `db_connect.php` file is used to connect the PHP system to the MySQL database.

### `includes/`

Contains reusable PHP files such as the header, navigation bar, footer, session checking function, and resume upload function.

### `uploads/`

Stores uploaded files such as student resumes.

## Important Notes

* The system is designed to run locally using XAMPP.
* Password reset email delivery is simulated because the project runs in a local environment.
* Uploaded resumes are stored in `uploads/resumes/`.
* Resume file paths are stored in the database.
* Admin accounts are not created through public registration.
* Students and companies can register through the public registration page.
* Role-based access control is handled using PHP sessions.
* The system uses `database.sql` to set up the database structure and sample data.

## Limitations

* The system is developed for academic project purposes.
* Password reset email sending is simulated locally.
* The system does not include real email server integration.
* The system should be further improved with stronger security measures before being used in a real production environment.

## Team

Team Name: The Chaos Club
Project Name: InternTrack
Last Updated: 29 June 2026
