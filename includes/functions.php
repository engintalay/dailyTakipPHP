<?php
/**
 * Utility functions for dailyTakip
 * PHP 5.3 compatible
 */

function escapeHtml($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo unicodeJsonEncode($data);
    exit;
}

function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// PHP 5.3 compatible hash_equals
if (!function_exists('hash_equals')) {
    function hash_equals($a, $b) {
        if (strlen($a) !== strlen($b)) return false;
        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $result === 0;
    }
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(strip_tags($input));
}

function formatDateShort($date) {
    if (empty($date)) return '';
    if (is_string($date)) $d = new DateTime($date);
    elseif ($date instanceof DateTime) $d = $date;
    else return '';
    $months = array('', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık');
    return $d->format('d') . ' ' . $months[(int)$d->format('n')] . ' ' . $d->format('Y');
}

function formatDateOnly($date) {
    if (empty($date)) return '';
    $d = is_string($date) ? new DateTime($date) : $date;
    return $d->format('Y-m-d');
}

function formatDateTime($date) {
    if (empty($date)) return '';
    if ($date instanceof DateTime) $d = $date;
    elseif (is_string($date)) $d = new DateTime($date);
    else return '';
    return $d->format('d.m.Y H:i');
}

function formatDateOnlyInput($date) {
    if (empty($date)) return date('Y-m-d');
    $d = is_string($date) ? new DateTime($date) : $date;
    return $d->format('Y-m-d');
}

function getWeekRange($date = null) {
    if (!$date) $date = new DateTime();
    elseif (is_string($date)) $date = new DateTime($date);

    $start = clone $date;
    $start->modify('this week')->setTime(0, 0, 0);

    $end = clone $start;
    $end->modify('+6 days')->setTime(23, 59, 59);

    return array(
        'start' => $start,
        'end' => $end
    );
}

function getMonthRange($date = null) {
    if (!$date) $date = new DateTime();
    elseif (is_string($date)) $date = new DateTime($date);

    $start = clone $date;
    $start->modify('first day of this month')->setTime(0, 0, 0);

    $end = clone $start;
    $end->modify('last day of this month')->setTime(23, 59, 59);

    return array(
        'start' => $start,
        'end' => $end
    );
}

function getDaysInMonth($year, $month) {
    return (int)date('t', mktime(0, 0, 0, $month, 1, $year));
}

function getFirstDayOfMonth($year, $month) {
    $day = date('w', mktime(0, 0, 0, $month, 1, $year));
    return $day === 0 ? 6 : $day - 1; // Convert Sunday=0 to Monday=0
}

function getStatusLabel($type) {
    $labels = array(
        STATUS_OFFICE => 'Ofiste',
        STATUS_REMOTE => 'Remote',
        STATUS_LEAVE => 'İzinli',
        STATUS_SICK => 'Hasta'
    );
    return isset($labels[$type]) ? $labels[$type] : $type;
}

function getStatusEmoji($type) {
    $emojis = array(
        STATUS_OFFICE => '🏢',
        STATUS_REMOTE => '🏠',
        STATUS_LEAVE => '🌴',
        STATUS_SICK => '🤒'
    );
    return isset($emojis[$type]) ? $emojis[$type] : '';
}

function getStatusBadge($type, $small = false) {
    if (empty($type)) return '<span class="text-xs text-muted-foreground">Belirtilmemiş</span>';

    $sizeClass = $small ? 'text-xs px-2 py-0.5' : 'text-sm px-2.5 py-1';
    $colorClass = getStatusColorClass($type);
    $emoji = getStatusEmoji($type);
    $label = getStatusLabel($type);

    return '<span class="inline-flex items-center gap-1 ' . $sizeClass . ' rounded-full font-medium border ' . $colorClass . '">' . $emoji . ' ' . $label . '</span>';
}

function getStatusColorClass($type) {
    $classes = array(
        STATUS_OFFICE => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400',
        STATUS_REMOTE => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        STATUS_LEAVE => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400',
        STATUS_SICK => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400'
    );
    return isset($classes[$type]) ? $classes[$type] : 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400';
}

function getUserAvatar($name, $size = 'w-8 h-8') {
    $initial = '';
    if (function_exists('mb_substr')) {
        $initial = mb_substr($name, 0, 1, 'UTF-8');
    }
    if ($initial === '') {
        $initial = substr($name, 0, 1);
    }
    if ($initial === '') $initial = '?';
    $colors = array('bg-blue-600', 'bg-emerald-600', 'bg-amber-600', 'bg-red-600', 'bg-purple-600', 'bg-pink-600', 'bg-indigo-600', 'bg-teal-600');
    $colorIndex = abs(crc32($name)) % count($colors);
    $color = $colors[$colorIndex];

    return '<div class="' . $size . ' rounded-full ' . $color . ' flex items-center justify-center text-white text-xs font-bold flex-shrink-0">' . escapeHtml(strtoupper($initial)) . '</div>';
}

function flash($type, $message) {
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatFileSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

function getUploadedFilesHtml($filesJson) {
    if (empty($filesJson) || $filesJson === '[]') return '';

    $files = json_decode($filesJson, true);
    if (!is_array($files) || empty($files)) return '';

    $html = '<div class="mt-2 space-y-1">';
    foreach ($files as $f) {
        $name = escapeHtml($f['name']);
        $url = escapeHtml($f['url']);
        $size = isset($f['size']) ? formatFileSize($f['size']) : '';

        $html .= '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline mr-3">';
        $html .= '📎 ' . $name;
        if ($size) $html .= ' <span class="text-muted-foreground">(' . $size . ')</span>';
        $html .= '</a>';
    }
    $html .= '</div>';
    return $html;
}

function getTagsHtml($tags) {
    if (empty($tags)) return '';

    $tagArray = array_filter(array_map('trim', explode(',', $tags)));
    if (empty($tagArray)) return '';

    $html = '<div class="flex gap-1 mt-2">';
    foreach ($tagArray as $tag) {
        $tag = escapeHtml($tag);
        $html .= '<span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">' . $tag . '</span>';
    }
    $html .= '</div>';
    return $html;
}

function getJiraLinkHtml($jiraLink) {
    if (empty($jiraLink)) return '';

    $links = preg_split('/[\r\n,]+/', $jiraLink, -1, PREG_SPLIT_NO_EMPTY);
    $html = '<div class="mt-2 space-y-1">';
    foreach ($links as $link) {
        $link = trim($link);
        if (empty($link)) continue;
        $display = preg_replace('/^https?:\/\//', '', $link);
        $display = rtrim($display, '/');
        $html .= '<a href="' . escapeHtml($link) . '" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-xs text-blue-600 hover:underline">🔗 ' . escapeHtml($display) . '</a>';
    }
    $html .= '</div>';
    return $html;
}

function renderFlash() {
    $flash = getFlash();
    if (!$flash) return '';

    $alertClass = $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800' : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800';

    return '<div class="mb-4 p-4 rounded-lg border ' . $alertClass . '" role="alert">' . escapeHtml($flash['message']) . '</div>';
}
