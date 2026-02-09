<?php
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
            $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
            $stmt->execute([$name]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
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
            $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
            $stmt->execute([$name]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
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
            $stmt = $pdo->prepare("SHOW COLUMNS FROM events LIKE ?");
            $stmt->execute([$name]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $missing[] = $alterSql;
            }
        }

        if ($missing) {
            $pdo->exec("ALTER TABLE events " . implode(', ', $missing));
        }

        $pdo->exec("
            UPDATE events
            SET moderation_status = 'published'
            WHERE moderation_status IS NULL OR moderation_status = ''
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
