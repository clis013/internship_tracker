<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Tracker</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="/internship_tracker/assets/css/style.css?v=7" rel="stylesheet">
    <link href="/internship_tracker/assets/css/admin.css?v=7" rel="stylesheet">
    
    <link href="/internship_tracker/assets/css/student.css?v=7" rel="stylesheet">
    
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'company'): ?>
        <link href="/internship_tracker/assets/css/company.css?v=7" rel="stylesheet">
    <?php endif; ?>
</head>
<body>