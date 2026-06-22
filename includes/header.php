<?php //session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Tracker</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Your custom CSS (loads after Bootstrap so it can override) -->
    <link href="/internship_tracker/assets/css/style.css" rel="stylesheet">
    <link href="/internship_tracker/assets/css/admin.css" rel="stylesheet">
    
    <!-- ADDED: Student Dashboard CSS -->
    <link href="/internship_tracker/assets/css/student.css" rel="stylesheet">
    
    <!-- ADDED: Company Dashboard CSS -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'company'): ?>
        <link href="/internship_tracker/assets/css/company.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>