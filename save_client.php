<?php
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Dozwolona jest tylko metoda POST.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(400, ['error' => 'Nieprawidłowe dane wejściowe.']);
}

$firstName = trim((string) ($input['imie'] ?? ''));
$lastName = trim((string) ($input['nazwisko'] ?? ''));
$height = filter_var($input['wzrost'] ?? null, FILTER_VALIDATE_FLOAT);
$weight = filter_var($input['masa'] ?? null, FILTER_VALIDATE_FLOAT);
$plans = [
    'sylwetka' => 'Rzeźba',
    'masa' => 'Masa mięśniowa',
    'redukcja' => 'Redukcja',
    'kondycja' => 'Kondycja',
];
$goal = $input['cel'] ?? '';

if ($firstName === '' || mb_strlen($firstName) > 50 || $lastName === '' || mb_strlen($lastName) > 50) {
    respond(422, ['error' => 'Podaj imię i nazwisko (maksymalnie 50 znaków).']);
}

if ($height === false || $height < 120 || $height > 230 || $weight === false || $weight < 35 || $weight > 250) {
    respond(422, ['error' => 'Wzrost lub masa ciała są poza dozwolonym zakresem.']);
}

if (!is_string($goal) || !isset($plans[$goal])) {
    respond(422, ['error' => 'Nieprawidłowy rodzaj planu.']);
}

$bmi = round($weight / (($height / 100) ** 2), 2);
if ($bmi > 99.99) {
    respond(422, ['error' => 'Wyliczone BMI przekracza zakres kolumny bmi w tabeli.']);
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $con = new mysqli("localhost", "root", "", "baza");
    $con->set_charset('utf8mb4');

    $statement = $con->prepare(
        'INSERT INTO klienci (imie, nazwisko, bmi, rodzaj_planu) VALUES (?, ?, ?, ?)'
    );
    $plan = $plans[$goal];
    $statement->bind_param('ssds', $firstName, $lastName, $bmi, $plan);
    $statement->execute();

    respond(201, ['id' => $con->insert_id, 'bmi' => $bmi, 'rodzaj_planu' => $plan]);
} catch (Throwable $error) {
    error_log($error->getMessage());
    respond(500, ['error' => 'Nie udało się zapisać danych. Sprawdź konfigurację połączenia z bazą.']);
}
