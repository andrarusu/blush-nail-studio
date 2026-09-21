<?php

declare(strict_types=1);

session_name('blush_admin');
session_start();

// Pagina este accesibilă numai administratorului autentificat.
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate');

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Valorile permise pentru tratamente și statusuri.
$treatments = [
    'Classic Manicure',
    'Gel Manicure',
    'Nail Art',
    'Nail Extensions',
    'Other'
];

$statuses = ['pending', 'confirmed', 'cancelled'];

// Citim filtrele din adresa paginii.
$nameFilter = isset($_GET['name']) && is_string($_GET['name'])
    ? trim($_GET['name'])
    : '';

$treatmentFilter = isset($_GET['treatment']) && is_string($_GET['treatment'])
    ? $_GET['treatment']
    : '';

if (!in_array($treatmentFilter, $treatments, true)) {
    $treatmentFilter = '';
}

$queryString = http_build_query(array_filter(
    [
        'name' => $nameFilter,
        'treatment' => $treatmentFilter
    ],
    static fn($value) => $value !== ''
));

$appointments = [];
$error = '';

$success = (string) ($_SESSION['admin_flash'] ?? '');
unset($_SESSION['admin_flash']);

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

    // Schimbarea statusului se face numai prin POST.
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['update_status'])
    ) {
        $submittedToken = (string) ($_POST['csrf_token'] ?? '');
        $newStatus = (string) ($_POST['status'] ?? '');
        $appointmentId = filter_input(
            INPUT_POST,
            'appointment_id',
            FILTER_VALIDATE_INT
        );

        if (
            !hash_equals($_SESSION['csrf_token'], $submittedToken) ||
            !$appointmentId ||
            !in_array($newStatus, $statuses, true)
        ) {
            $error = 'Could not update the appointment.';
        } else {
            $update = $pdo->prepare(
                'UPDATE appointment_requests
                 SET status = :status
                 WHERE id = :id'
            );

            $update->execute([
                ':status' => $newStatus,
                ':id' => $appointmentId
            ]);

            $_SESSION['admin_flash'] = 'Appointment status updated.';

            header(
                'Location: admin.php' .
                ($queryString !== '' ? '?' . $queryString : '')
            );
            exit;
        }
    }

    // Construim interogarea în funcție de filtrele completate.
    $sql = '
        SELECT
            id,
            client_name,
            email,
            phone,
            treatment,
            preferred_date,
            preferred_time,
            message,
            status,
            created_at
        FROM appointment_requests
    ';

    $conditions = [];
    $parameters = [];

    if ($nameFilter !== '') {
        $conditions[] = 'client_name LIKE :name';
        $parameters[':name'] = '%' . $nameFilter . '%';
    }

    if ($treatmentFilter !== '') {
        $conditions[] = 'treatment = :treatment';
        $parameters[':treatment'] = $treatmentFilter;
    }

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT 100';

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    $appointments = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $error = 'Could not load or update appointment requests.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Appointments | Blush &amp; Co.</title>

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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 32px 20px;
            font-family: Arial, sans-serif;
            color: #4b3c38;
            background: #fcf9f6;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 8px;
            font-family: Georgia, serif;
            font-weight: normal;
        }

        .subtitle {
            color: #75645f;
        }

        .panel {
            padding: 24px;
            margin-top: 24px;
            background: white;
            border: 1px solid #eadbd6;
            border-radius: 8px;
        }

        .filters {
            display: flex;
            align-items: end;
            flex-wrap: wrap;
            gap: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-size: 13px;
        }

        input,
        select,
        button {
            padding: 10px 12px;
            font: inherit;
        }

        input,
        select {
            border: 1px solid #d9c7c0;
            border-radius: 4px;
            background: white;
        }

        button {
            color: white;
            background: #bd8779;
            border: 0;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #a66f62;
        }

        a {
            color: #9b6255;
        }

        .message {
            padding: 12px;
            margin-top: 18px;
            background: #f7ebe6;
            border-radius: 4px;
        }

        .error {
            background: #ffe9e9;
        }

        .table-wrap {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 13px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #eadbd6;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f7ebe6;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-form select {
            min-width: 110px;
        }

        .status-form button {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <form action="login.php" method="post">
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
    >
    <button type="submit" name="logout" value="1">
        Log out
    </button>
</form>
    <a href="index.html" class="back-to-site">← Back to website</a>
<main>

    <h1>Blush &amp; Co. — Appointments</h1>

    <p class="subtitle">
        Logged in as
        <?= e((string) ($_SESSION['admin_username'] ?? 'administrator')) ?>.
        <a href="login.php">Account / Log out</a>
    </p>

    <section class="panel">

        <h2>Search appointments</h2>

        <form method="get" action="admin.php" class="filters">

            <div class="field">
                <label for="clientSearch">Client name</label>

                <input
                    type="search"
                    id="clientSearch"
                    name="name"
                    value="<?= e($nameFilter) ?>"
                    placeholder="e.g. Test Client"
                >
            </div>

            <div class="field">
                <label for="treatmentFilter">Treatment</label>

                <select id="treatmentFilter" name="treatment">
                    <option value="">All treatments</option>

                    <?php foreach ($treatments as $treatment): ?>
                        <option
                            value="<?= e($treatment) ?>"
                            <?= $treatmentFilter === $treatment ? 'selected' : '' ?>
                        >
                            <?= e($treatment) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Search</button>

            <a href="admin.php">Clear</a>

        </form>

    </section>

    <?php if ($success !== ''): ?>
        <p class="message" role="status"><?= e($success) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="message error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <section class="panel">

        <h2>Appointment requests</h2>

        <?php if ($error === '' && count($appointments) === 0): ?>

            <p>
                <?= ($nameFilter !== '' || $treatmentFilter !== '')
                    ? 'No matching appointments found.'
                    : 'No appointment requests yet.' ?>
            </p>

        <?php elseif ($error === '' && count($appointments) > 0): ?>

            <p>
                Showing <?= count($appointments) ?> appointment(s),
                up to the latest 100 matches.
            </p>

            <div class="table-wrap">

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Treatment</th>
                            <th>Preferred date</th>
                            <th>Preferred time</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($appointments as $appointment): ?>

                        <tr>
                            <td><?= e((string) $appointment['id']) ?></td>
                            <td><?= e($appointment['client_name']) ?></td>
                            <td><?= e($appointment['email']) ?></td>
                            <td><?= e($appointment['phone']) ?></td>
                            <td><?= e($appointment['treatment']) ?></td>
                            <td><?= e($appointment['preferred_date']) ?></td>
                            <td><?= e($appointment['preferred_time']) ?></td>
                            <td><?= e($appointment['message']) ?></td>

                            <td>
                                <form
                                    method="post"
                                    action="admin.php<?= $queryString !== ''
                                        ? '?' . e($queryString)
                                        : '' ?>"
                                    class="status-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= e((string) $appointment['id']) ?>"
                                    >

                                    <select
                                        name="status"
                                        aria-label="Status for appointment <?= e((string) $appointment['id']) ?>"
                                    >
                                        <?php foreach ($statuses as $status): ?>
                                            <option
                                                value="<?= e($status) ?>"
                                                <?= $appointment['status'] === $status
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                <?= e(ucfirst($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button
                                        type="submit"
                                        name="update_status"
                                        value="1"
                                    >
                                        Save
                                    </button>
                                </form>
                            </td>

                            <td><?= e($appointment['created_at']) ?></td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    </section>

</main>
</body>
</html>