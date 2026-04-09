<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: account.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ch = curl_init("https://ddrop.net/api/login.php");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'email' => $_POST['email'],
            'password' => $_POST['password']
        ]),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        die("CURL ERROR: " . curl_error($ch));
    }

    curl_close($ch);

    $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);

    $data = json_decode($response, true);

    if (!is_array($data)) {
        die("JSON ERROR");
    }

    if (!empty($data['error'])) {
        $error = $data['error'];
    } else {
        $_SESSION['user'] = $data['user'];
        header('Location: account.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení | DDrop</title>
    <link rel="icon" href="assets/img/logo.ico">
    <link rel="stylesheet" href="assets/css/styla.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <a href="index.php">DDrop</a>
    </div>
</nav>

<section class="auth-page">
    <div class="auth-card">
        <h1>Přihlášení</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Heslo" required>
            <button type="submit">Přihlásit</button>
        </form>
    </div>
</section>

</body>
</html>
