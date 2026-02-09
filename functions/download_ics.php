<?php
require_once __DIR__ . '/../init.php';

$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($eventId <= 0) {
    http_response_code(400);
    exit('Некоректний ID події');
}

try {
    $stmt = $pdo->prepare("
        SELECT e.id, e.user_id, e.title, e.description, e.location, e.event_date, e.event_time, e.moderation_status, u.username
        FROM events e
        LEFT JOIN users u ON u.id = e.user_id
        WHERE e.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        exit('Подію не знайдено');
    }

    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $userRole = (string)($_SESSION['user']['role'] ?? 'user');
    $isAdmin = ($userRole === 'admin');
    $isOwner = $userId > 0 && $userId === (int)$event['user_id'];

    if (($event['moderation_status'] ?? 'published') !== 'published' && !$isAdmin && !$isOwner) {
        http_response_code(403);
        exit('Подія недоступна');
    }

    if (empty($event['event_date'])) {
        http_response_code(422);
        exit('Для цієї події не задана дата');
    }

    $escapeIcs = static function (string $value): string {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(';', '\;', $value);
        $value = str_replace(',', '\,', $value);
        return str_replace(["\r\n", "\n", "\r"], '\n', $value);
    };

    $formatDate = static function (DateTime $date): string {
        return $date->format('Ymd\THis');
    };

    $tz = new DateTimeZone('Europe/Kyiv');
    $startDate = DateTime::createFromFormat('Y-m-d H:i:s', trim($event['event_date']) . ' 00:00:00', $tz);

    if (!$startDate) {
        http_response_code(422);
        exit('Некоректна дата події');
    }

    $hasTime = !empty($event['event_time']);
    $dtStartLine = '';
    $dtEndLine = '';

    if ($hasTime) {
        $time = substr((string)$event['event_time'], 0, 5);
        $dateTime = DateTime::createFromFormat('Y-m-d H:i', $event['event_date'] . ' ' . $time, $tz);
        if (!$dateTime) {
            $dateTime = clone $startDate;
            $dateTime->setTime(12, 0, 0);
        }
        $endDateTime = clone $dateTime;
        $endDateTime->modify('+2 hours');
        $dtStartLine = 'DTSTART;TZID=Europe/Kyiv:' . $formatDate($dateTime);
        $dtEndLine = 'DTEND;TZID=Europe/Kyiv:' . $formatDate($endDateTime);
    } else {
        $allDayStart = clone $startDate;
        $allDayEnd = clone $startDate;
        $allDayEnd->modify('+1 day');
        $dtStartLine = 'DTSTART;VALUE=DATE:' . $allDayStart->format('Ymd');
        $dtEndLine = 'DTEND;VALUE=DATE:' . $allDayEnd->format('Ymd');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = BASE_URL ?? '';
    $eventUrl = $scheme . '://' . $host . $basePath . '/index.php#event-' . (int)$event['id'];

    $title = trim((string)($event['title'] ?? 'Подія'));
    $description = trim((string)($event['description'] ?? ''));
    $location = trim((string)($event['location'] ?? ''));
    $organizer = trim((string)($event['username'] ?? 'Events YC'));

    $safeTitle = preg_replace('/[^a-zA-Z0-9_-]+/u', '_', $title);
    $safeTitle = trim((string)$safeTitle, '_');
    if ($safeTitle === '') {
        $safeTitle = 'event_' . (int)$event['id'];
    }

    $uidHost = preg_replace('/[^a-zA-Z0-9.-]/', '', $host) ?: 'localhost';
    $uid = 'event-' . (int)$event['id'] . '@' . $uidHost;
    $dtStamp = gmdate('Ymd\THis\Z');

    $ics = [];
    $ics[] = 'BEGIN:VCALENDAR';
    $ics[] = 'VERSION:2.0';
    $ics[] = 'PRODID:-//Events YC//Event Calendar//UK';
    $ics[] = 'CALSCALE:GREGORIAN';
    $ics[] = 'METHOD:PUBLISH';
    $ics[] = 'BEGIN:VEVENT';
    $ics[] = 'UID:' . $uid;
    $ics[] = 'DTSTAMP:' . $dtStamp;
    $ics[] = $dtStartLine;
    $ics[] = $dtEndLine;
    $ics[] = 'SUMMARY:' . $escapeIcs($title);
    if ($description !== '') {
        $ics[] = 'DESCRIPTION:' . $escapeIcs($description);
    }
    if ($location !== '') {
        $ics[] = 'LOCATION:' . $escapeIcs($location);
    }
    $ics[] = 'ORGANIZER;CN=' . $escapeIcs($organizer) . ':MAILTO:no-reply@events-yc.local';
    $ics[] = 'URL:' . $escapeIcs($eventUrl);
    $ics[] = 'STATUS:CONFIRMED';
    $ics[] = 'END:VEVENT';
    $ics[] = 'END:VCALENDAR';

    $body = implode("\r\n", $ics) . "\r\n";

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $safeTitle . '.ics"');
    header('Content-Length: ' . strlen($body));
    echo $body;
    exit;
} catch (Throwable $e) {
    error_log('download_ics error: ' . $e->getMessage());
    http_response_code(500);
    exit('Помилка генерації календаря');
}

