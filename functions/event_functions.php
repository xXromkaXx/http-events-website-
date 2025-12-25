<?php
/**
 * Створення нової події
 */

function createEvent($userId, $title, $description, $category, $event_date, $event_time, $imagePath, $location) {
    if (empty($event_date)) {
        throw new Exception("Будь ласка, вкажіть дату події!");
    }
    if (empty($title)) {
        throw new Exception("Будь ласка, вкажіть назву події!");
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO events (user_id, title, description, category, event_date, event_time, image, location)
        VALUES (:user_id, :title, :description, :category, :event_date, :event_time, :image, :location)
    ");
    return $stmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':category' => $category,
        ':event_date' => $event_date,
        ':event_time' => $event_time ?: null,
        ':image' => $imagePath,
        ':location' => $location
    ]);
}


/**
 * Завантаження зображення події
 * @return string|null шлях до зображення або null
 */
function uploadEventImage(array $file, string $category, ?string $oldImage = null)
{
    $imagePath = null;

    // 1️⃣ Якщо завантажили нове фото
    if (!empty($file['name'])) {
        $targetDir = __DIR__ . '/../uploads/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($file['name']);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $imagePath = 'uploads/' . $fileName;
            }
        }
    }

    // 2️⃣ Якщо НЕ завантажили нове, але є старе фото → залишаємо
    if (($imagePath === null || $imagePath === '') && !empty($oldImage)) {
        return $oldImage;
    }

    // 3️⃣ Якщо взагалі немає фото → автокартинка по категорії
    if ($imagePath === null || $imagePath === '') {
        $categoryImages = [
            'Футбол' => 'assets/img/categories/football.jpg',
            'Концерт' => 'assets/img/categories/concert.jpg',
            'Зустріч' => 'assets/img/categories/meeting.jpg',
            'Навчання' => 'assets/img/categories/learning.jpg',
            'Прогулянка' => 'assets/img/categories/walk.jpg',
            'Вечірка' => 'assets/img/categories/party.jpg',
            'Волейбол' => 'assets/img/categories/volleyball.jpg',
            'Мистецтво' => 'assets/img/categories/workmanship.jpg',
        ];

        return $categoryImages[$category] ?? 'assets/img/categories/other.jpg';
    }

    return $imagePath;
}


function getEventById(int $eventId, int $userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT *
        FROM events
        WHERE id = :id AND user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'id' => $eventId,
        'user_id' => $userId
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function updateEvent(
    int $eventId,
    int $userId,
    string $title,
    string $description,
    string $category,
    string $eventDate,
    ?string $eventTime,
    ?string $image,
    string $location
) {
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE events
        SET
            title = :title,
            description = :description,
            category = :category,
            event_date = :event_date,
            event_time = :event_time,
            image = :image,
            location = :location
        WHERE id = :id AND user_id = :user_id
    ");

    return $stmt->execute([
        'title' => $title,
        'description' => $description,
        'category' => $category,
        'event_date' => $eventDate,
        'event_time' => $eventTime,
        'image' => $image,
        'location' => $location,
        'id' => $eventId,
        'user_id' => $userId
    ]);
}
