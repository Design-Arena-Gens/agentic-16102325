<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
ensure_session_started();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (!authenticate($email, $password)) {
        $error = 'Invalid credentials.';
    } else {
        header('Location: /dashboard.php');
        exit();
    }
}

if (current_user()) {
    header('Location: /dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onsite Lite ERP - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          integrity="sha384-ENjdO4Dr2bkBIFxQpeoYz1F5rY8gkHf6g9k6dBEM4ClFZC5iC7uPqzrF0yNnFEXD" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-gradient">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card border-0">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-4">Onsite Lite ERP</h1>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= esc($error) ?></div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
