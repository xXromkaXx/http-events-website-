<?php
function columnExists(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
        ");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('columnExists error: ' . $e->getMessage());
        return false;
    }
}

function ensureUsersProfileColumns(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $required = [
        'city' => "ADD COLUMN city VARCHAR(120) NULL AFTER phone",
        'instagram' => "ADD COLUMN instagram VARCHAR(120) NULL AFTER city",
        'bio' => "ADD COLUMN bio TEXT NULL AFTER instagram",
    ];

    try {
        $missing = [];
        foreach ($required as $name => $alterSql) {
            if (!columnExists($pdo, 'users', $name)) {
                $missing[] = $alterSql;
            }
        }

        if ($missing) {
            $pdo->exec("ALTER TABLE users " . implode(', ', $missing));
        }
    } catch (Throwable $e) {
        error_log('ensureUsersProfileColumns error: ' . $e->getMessage());
    }
}

function ensureUsersRoleColumns(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $required = [
        'role' => "ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash",
        'organizer_status' => "ADD COLUMN organizer_status VARCHAR(20) NOT NULL DEFAULT 'none' AFTER role",
    ];

    try {
        $missing = [];
        foreach ($required as $name => $alterSql) {
            if (!columnExists($pdo, 'users', $name)) {
                $missing[] = $alterSql;
            }
        }

        if ($missing) {
            $pdo->exec("ALTER TABLE users " . implode(', ', $missing));
        }

        $pdo->exec("UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");
        $pdo->exec("
            UPDATE users 
            SET organizer_status = CASE 
                WHEN role IN ('organizer', 'admin') THEN 'approved'
                ELSE 'none'
            END
            WHERE organizer_status IS NULL OR organizer_status = ''
        ");
    } catch (Throwable $e) {
        error_log('ensureUsersRoleColumns error: ' . $e->getMessage());
    }
}

function ensureEventsModerationColumns(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $required = [
        'moderation_status' => "ADD COLUMN moderation_status VARCHAR(20) NOT NULL DEFAULT 'published' AFTER location",
        'rejection_reason' => "ADD COLUMN rejection_reason TEXT NULL AFTER moderation_status",
        'moderated_by' => "ADD COLUMN moderated_by INT NULL AFTER rejection_reason",
        'moderated_at' => "ADD COLUMN moderated_at DATETIME NULL AFTER moderated_by",
    ];

    try {
        $missing = [];
        foreach ($required as $name => $alterSql) {
            if (!columnExists($pdo, 'events', $name)) {
                $missing[] = $alterSql;
            }
        }

        if ($missing) {
            $pdo->exec("ALTER TABLE events " . implode(', ', $missing));
        }

        $statusMetaStmt = $pdo->query("
            SELECT DATA_TYPE, COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'events'
              AND COLUMN_NAME = 'moderation_status'
            LIMIT 1
        ");
        $statusMeta = $statusMetaStmt ? $statusMetaStmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($statusMeta) {
            $dataType = strtolower((string)($statusMeta['DATA_TYPE'] ?? ''));
            $columnType = strtolower((string)($statusMeta['COLUMN_TYPE'] ?? ''));

            // Якщо у старій схемі ENUM без 'draft' — чернетка може ламатися і стати опублікованою.
            // Переводимо колонку в VARCHAR, щоб підтримувати всі робочі стани.
            if ($dataType === 'enum' && strpos($columnType, "'draft'") === false) {
                $pdo->exec("
                    ALTER TABLE events
                    MODIFY COLUMN moderation_status VARCHAR(20) NOT NULL DEFAULT 'published'
                ");
            }
        }

        $pdo->exec("
            UPDATE events
            SET moderation_status = 'pending'
            WHERE moderation_status IS NULL
               OR moderation_status = ''
               OR moderation_status NOT IN ('draft', 'pending', 'published', 'rejected')
        ");
    } catch (Throwable $e) {
        error_log('ensureEventsModerationColumns error: ' . $e->getMessage());
    }
}

function getUsersColumns(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    try {
        $rows = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
        $cache = array_map(static fn($r) => $r['Field'], $rows);
    } catch (Throwable $e) {
        error_log('getUsersColumns error: ' . $e->getMessage());
        $cache = [];
    }

    return $cache;
}

function hasUsersColumn(PDO $pdo, string $column): bool
{
    return in_array($column, getUsersColumns($pdo), true);
}

function formatEventDate(?string $date): string {
    if (empty($date)) {
        return 'Дата не вказана';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return 'Дата не вказана';
    }
    return date('d.m.Y', $ts);
}

function formatEventTime(?string $time): string {
    if (empty($time)) {
        return '';
    }
    $ts = strtotime($time);
    if ($ts === false) {
        return '';
    }
    return date('H:i', $ts);
}

function shortDescription(string $desc, int $limit = 120): string {
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($desc) > $limit
            ? mb_substr($desc, 0, $limit) . '...'
            : $desc;
    }

    return strlen($desc) > $limit
        ? substr($desc, 0, $limit) . '...'
        : $desc;
}
function formatPhone(?string $digits): string
{
    if (!$digits || strlen($digits) !== 12) return '—';

    return sprintf(
        '+%s (%s) %s-%s-%s',
        substr($digits, 0, 2),
        substr($digits, 2, 3),
        substr($digits, 5, 3),
        substr($digits, 8, 2),
        substr($digits, 10, 2)
    );
}
