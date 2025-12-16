<?php
function formatEventDate(string $date): string {
    return date('d.m.Y', strtotime($date));
}

function formatEventTime(?string $time): string {
    if (empty($time)) return '';
    return date('H:i', strtotime($time));
}

function shortDescription(string $desc, int $limit = 120): string {
    return mb_strlen($desc) > $limit
        ? mb_substr($desc, 0, $limit) . '...'
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

