# InternTrack

InternTrack is a web-based Internship Application Tracking System developed for students, companies, and administrators. It allows students to browse internships and submit applications, companies to post internships and manage applicants, and administrators to monitor system records. The system is developed for academic purpose as a project of SECV2223 Web Programming Course.

## Features

| User Role | Features                                                                                                                                                                                                                |
| --------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Student   | Register and log in, manage profile information, upload and replace resume, browse and search internship postings, apply for internships, track application status, withdraw applications, and create/manage reminders. |
| Company   | Register and log in, manage company profile, create/edit/delete internship postings, view applicants, download applicant resumes, and update application status and interview details.                                  |
| Admin     | Log in to admin dashboard, view system statistics, manage user records, monitor company accounts, view internship postings, view application records, and delete invalid or unnecessary records.                        |

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

### 2. Download the Project from GitHub

Open the GitHub repository for the project.

You can download the project in either of the following ways:

#### Option 1: Download ZIP

1. Open the project repository on GitHub.
2. Click the **Code** button.
3. Select **Download ZIP**.
4. Extract the downloaded ZIP file.

#### Option 2: Clone using Git

Open Command Prompt or terminal and run:

```bash
git clone <your-github-repository-link>
```

Example:

```bash
git clone https://github.com/username/internship_tracker.git
```

### 3. Move Project Folder

Place the project folder inside the XAMPP `htdocs` directory.

Example:

```text
C:/xampp/htdocs/internship_tracker/
```

Make sure the final folder path is similar to:

```text
C:/xampp/htdocs/internship_tracker/index.php
```

### 4. Create Database in phpMyAdmin

1. Open XAMPP Control Panel.
2. Start **Apache** and **MySQL**.
3. Open your browser.
4. Go to:

```text
http://localhost/phpmyadmin/
```

5. Click **New** on the left side.
6. Enter the database name:

```sql
internship_tracker
```

7. Click **Create**.

### 5. Import Database

Import the provided `database.sql` file into the newly created database.

Steps:

1. In phpMyAdmin, select the `internship_tracker` database.
2. Click the **Import** tab.
3. Click **Choose File**.
4. Select the `database.sql` file from the project folder.
5. Click **Go**.
6. Wait until phpMyAdmin shows a successful import message.

After importing, you should be able to see the database tables in phpMyAdmin, such as `users`, `jobs`, `applications`, `reminders`, and `password_resets`.

### 6. Configure Database Connection

Open:

```text
config/db_connect.php
```

Check that the database connection settings match your local XAMPP environment.

Example:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "internship_tracker";
```

For a default XAMPP installation, the username is usually `root` and the password is usually empty.

### 7. Run the System

Open the system in your browser:

```text
http://localhost/internship_tracker/
```

## Default Login Accounts

The system provides sample login accounts for testing each user role. After importing `database.sql`, users can log in using the accounts below.

| Role    | Email                        | Password   | Access                                                                                                 |
| ------- | ---------------------------- | ---------- | ------------------------------------------------------------------------------------------------------ |
| Admin   | `admin@interntrack.com`      | `password` | Admin dashboard, user management, internship monitoring, and application monitoring                    |
| Student | `ahmad.faris@student.utm.my` | `password` | Student dashboard, internship browsing, application submission, resume upload, and reminder management |
| Company | `hr@techcorp.com.my`         | `password` | Company dashboard, internship posting management, applicant viewing, and application status updates    |

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

| Folder / File  | Description                                                                                                                                                  |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `admin/`       | Contains pages used by administrators to manage and monitor the system, including users, companies, internship postings, and application records.            |
| `auth/`        | Contains authentication pages such as registration, login, logout, forgot password, and reset password.                                                      |
| `student/`     | Contains pages used by students to browse internships, apply for internships, manage profiles, upload resumes, track applications, and manage reminders.     |
| `company/`     | Contains pages used by companies to manage company profiles, create internship postings, view applicants, download resumes, and update application statuses. |
| `assets/`      | Contains front-end resources such as CSS files, JavaScript files, images, and design elements.                                                               |
| `config/`      | Contains system configuration files. The `db_connect.php` file is used to connect the PHP system to the MySQL database.                                      |
| `includes/`    | Contains reusable PHP files such as the header, navigation bar, footer, session checking function, and resume upload function.                               |
| `uploads/`     | Stores uploaded files such as student resumes.                                                                                                               |
| `database.sql` | Contains SQL statements used to create the database tables and insert sample data.                                                                           |
| `index.php`    | The public landing page of the system.                                                                                                                       |

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

**Team Name:** The Chaos Club

**Members:** Nicole Lee, Sam Wei Leng, Tan Jian Xin, Crystal Yap Wen Jing

**Project Name:** InternTrack

**Last Updated:** 29 June 2026
