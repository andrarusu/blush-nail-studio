<?php

declare(strict_types=1);

session_name('blush_admin');

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);

session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Please refresh the page and try again.';

    } elseif (isset($_POST['logout'])) {

        // Închidem sesiunea administratorului.
        unset($_SESSION['admin_id'], $_SESSION['admin_username']);

        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    } elseif (isset($_POST['login'])) {

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        try {
            $config = require __DIR__ . '/config.local.php';

            $pdo = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Căutăm administratorul după username.
            $statement = $pdo->prepare(
                'SELECT id, username, password_hash
                 FROM admin_users
                 WHERE username = :username
                 LIMIT 1'
            );

            $statement->execute([
                ':username' => $username
            ]);

            $admin = $statement->fetch(PDO::FETCH_ASSOC);

            // Verificăm parola introdusă folosind hash-ul salvat.
            if (
                $admin !== false &&
                password_verify($password, $admin['password_hash'])
            ) {
                session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: admin.php');
            exit;

            } else {
                $error = 'Incorrect username or password.';
            }

        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $error = 'Login is temporarily unavailable.';
        }
    }
}

if (isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$loggedIn = false;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Blush &amp; Co.</title>

    <style>
        .back-to-site {
            display: inline-block;
            margin-bottom: 20px;
            color: #9b6255;
            text-decoration: none;
            font-size: 14px;
        }

        .back-to-site:hover {
            text-decoration: underline;
        }

        body {
            max-width: 460px;
            margin: 70px auto;
            padding: 30px;
            background: #fcf9f6;
            color: #4b3c38;
            font-family: Arial, sans-serif;
        }

        h1, h2 {
            font-family: Georgia, serif;
            font-weight: normal;
            color: #654b45;
        }

        form {
            margin-top: 24px;
            padding: 28px;
            background: #ffffff;
            border: 1px solid #eadbd6;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(75, 60, 56, 0.06);
        }

        label {
            display: block;
            margin: 14px 0 7px;
            font-size: 14px;
        }

        input:not([type="hidden"]) {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #d9c7c0;
            border-radius: 6px;
            font: inherit;
        }

        input:focus {
            outline: 2px solid #bd8779;
            outline-offset: 2px;
        }

        button {
            margin-top: 20px;
            padding: 12px 22px;
            background: #bd8779;
            color: white;
            border: 0;
            border-radius: 6px;
            font: inherit;
            cursor: pointer;
        }

        button:hover {
            background: #a66f62;
        }

        a {
            color: #9b6255;
        }

        @media (max-width: 540px) {
            body {
                margin: 30px auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <a href="index.html" class="back-to-site">← Back to website</a>

    <h1>Blush &amp; Co. — Admin</h1>

    <?php if ($error !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($loggedIn): ?>

        <p>
            Logged in as
            <?= htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8') ?>.
        </p>

        <p>Login successful. The admin dashboard is our next step.</p>

        <form method="post">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <button type="submit" name="logout" value="1">
                Log out
            </button>
        </form>

    <?php else: ?>

        <form method="post">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <p>
                <label for="username">Username</label><br>
                <input
                    id="username"
                    name="username"
                    type="text"
                    autocomplete="username"
                    maxlength="50"
                    required
                >
            </p>

            <p>
                <label for="password">Password</label><br>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </p>

            <button type="submit" name="login" value="1">
                Log in
            </button>

        </form>

    <?php endif; ?>

</body>
</html>