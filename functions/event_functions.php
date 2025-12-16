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
function uploadEventImage($file, $category) {
    $imagePath = null;

    if (!empty($file['name'])) {
        $targetDir = __DIR__ . '/../uploads/';
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($file['name']);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                // Відносний шлях для зберігання в БД
                $imagePath = 'uploads/' . $fileName;
            }
        }
    }

    // 🖼️ Якщо не було завантажено — автозображення по категорії
    if ($imagePath === null || $imagePath === '') {
        $categoryImages = [
            'Футбол' => 'assets/img/categories/football.jpg',
            'Концерт' => 'assets/img/categories/concert.jpg',
            'Зустріч' => 'assets/img/categories/meeting.jpg',
            'Навчання' => 'assets/img/categories/learning.jpg',
            'Прогулянка' => 'assets/img/categories/walk.jpg',
            'Вечірка' => 'assets/img/categories/party.jpg',
            'Волейбол' => 'assets/img/categories/volleyball.jpg',
            'Інше' => 'assets/img/categories/other.jpg'
        ];
        $imagePath = $categoryImages[$category] ?? 'assets/img/categories/other.jpg';
    }

    return $imagePath;
}

