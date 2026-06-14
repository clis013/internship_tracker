# Internship Tracker

## Setup Instructions

# 1. Clone the repo into htdocs
cd C:/xampp/htdocs
git clone https://github.com/yourteam/internship_tracker.git

# 2. Create their own branch
cd internship_tracker
git checkout -b feature/student-pages   # each person uses their own name

### 3. Run the project and phpadmin
Open http://localhost/internship_tracker/
Open http://localhost/phpmyadmin/

## Test Login Credentials
| Role    | Email                  | Password  |
|---------|------------------------|-----------|
| Admin   | admin@example.com      | admin123  |
| Company | company@example.com    | company123|
| Student | student@example.com    | student123|

## Who is working on what
- Person 1 (you): auth/, includes/, config/, database
- Person 2: student/ pages
- Person 3: company/ pages
- Person 4: admin/ pages, index.php
