<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Acceptăm doar date trimise prin formular.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

// Preluăm câmpurile existente din formular.
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$treatment = trim((string) ($_POST['treatment'] ?? ''));
$date = trim((string) ($_POST['date'] ?? ''));
$time = trim((string) ($_POST['time'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

// Verificăm datele și pe server, nu doar în JavaScript.
$allowedTreatments = [
    'Classic Manicure',
    'Gel Manicure',
    'Nail Art',
    'Nail Extensions',
    'Other'
];

$allowedTimes = ['Morning', 'Afternoon', 'Evening'];

$cleanPhone = preg_replace('/[\s()-]/', '', $phone);

$timezone = new DateTimeZone('Europe/London');
$preferredDate = DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $date,
    $timezone
);

$validDate = $preferredDate !== false
    && $preferredDate->format('Y-m-d') === $date
    && $preferredDate >= new DateTimeImmutable('today', $timezone);

if (
    strlen($name) < 2 ||
    strlen($name) > 100 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 254 ||
    !preg_match('/^(?:0\d{10}|\+44\d{10})$/', $cleanPhone) ||
    !in_array($treatment, $allowedTreatments, true) ||
    !in_array($time, $allowedTimes, true) ||
    !$validDate ||
    strlen($message) > 2000
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please check your appointment details.'
    ]);
    exit;
}

try {
    // Citim datele de conectare din fișierul local.
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

    // Salvăm solicitarea folosind o interogare parametrizată.
    $statement = $pdo->prepare(
        'INSERT INTO appointment_requests
        (client_name, email, phone, treatment, preferred_date, preferred_time, message)
        VALUES
        (:name, :email, :phone, :treatment, :date, :time, :message)'
    );

    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':treatment' => $treatment,
        ':date' => $date,
        ':time' => $time,
        ':message' => $message
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Appointment request saved successfully.'
    ]);

} catch (PDOException $error) {
    error_log($error->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not save the appointment request.'
    ]);
}