<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onsite Lite ERP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          integrity="sha384-ENjdO4Dr2bkBIFxQpeoYz1F5rY8gkHf6g9k6dBEM4ClFZC5iC7uPqzrF0yNnFEXD" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-light">
<?php if ($user): ?>
<?php include __DIR__ . '/nav.php'; ?>
<main class="container-fluid py-4">
<?php endif; ?>
