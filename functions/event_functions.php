<?php
/**
 * Створення нової події
 */

function createEvent($userId, $title, $description, $category, $event_date, $event_time, $imagePath, $location, $moderationStatus = 'pending') {
    if (empty($event_date)) {
        throw new Exception("Будь ласка, вкажіть дату події!");
    }
    if (empty($title)) {
        throw new Exception("Будь ласка, вкажіть назву події!");
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO events (user_id, title, description, category, event_date, event_time, image, location, moderation_status, rejection_reason, moderated_by, moderated_at)
        VALUES (:user_id, :title, :description, :category, :event_date, :event_time, :image, :location, :moderation_status, NULL, NULL, NULL)
    ");
    return $stmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':category' => $category,
        ':event_date' => $event_date,
        ':event_time' => $event_time ?: null,
        ':image' => $imagePath,
        ':location' => $location,
        ':moderation_status' => $moderationStatus
    ]);
}


/**
 * Завантаження зображення події
 * @return string|null шлях до зображення або null
 */
function uploadEventImage(array $file, string $category, ?string $oldImage = null): string
{
    // 1️⃣ Якщо файл не завантажений
    if (
        empty($file['name']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        return $oldImage ?? getDefaultCategoryImage($category);
    }

    // 2️⃣ Папка збереження
    $uploadDir = __DIR__ . '/../uploads/event/';
    $publicPath = 'uploads/event/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 3️⃣ Валідація
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        throw new Exception('Недопустимий формат файлу');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Файл перевищує 5MB');
    }

    // 4️⃣ Безпечне імʼя
    $fileName = uniqid('event_', true) . '.' . $ext;
    $target = $uploadDir . $fileName;

    // 5️⃣ Переміщення з tmp → uploads/event
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('Помилка збереження файлу');
    }


    // 6️⃣ Видалення старого фото (ТІЛЬКИ якщо воно було завантажене)
    if (
        $oldImage &&
        (strpos($oldImage, 'uploads/event/') === 0) &&
        file_exists(__DIR__ . '/../' . $oldImage)
    ) {
        unlink(__DIR__ . '/../' . $oldImage);
    }


    return $publicPath . $fileName;
}
function getDefaultCategoryImage(string $category): string
{
    $images = [
        'Футбол' => 'assets/img/categories/football.jpg',
        'Концерт' => 'assets/img/categories/concert.jpg',
        'Зустріч' => 'assets/img/categories/meeting.jpg',
        'Навчання' => 'assets/img/categories/learning.jpg',
        'Прогулянка' => 'assets/img/categories/walk.jpg',
        'Вечірка' => 'assets/img/categories/party.jpg',
        'Волейбол' => 'assets/img/categories/volleyball.jpg',
        'Мистецтво' => 'assets/img/categories/workmanship.jpg',
    ];

    return $images[$category] ?? 'assets/img/categories/other.jpg';
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
    string $location,
    string $moderationStatus = 'pending'
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
            location = :location,
            moderation_status = :moderation_status,
            rejection_reason = NULL,
            moderated_by = NULL,
            moderated_at = NULL
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
        'moderation_status' => $moderationStatus,
        'id' => $eventId,
        'user_id' => $userId
    ]);
}
