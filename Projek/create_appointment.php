<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?err=Not+logged+in'); exit;
}

// refresh session activity (extend 1 jam from latest action)
$_SESSION['login_time'] = time();

$user_id = (int)$_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$date = $_POST['date'] ?? '';
$start = $_POST['start_time'] ?? '';
$end = $_POST['end_time'] ?? '';
$participants_raw = trim($_POST['participants'] ?? '');

if ($title === '' || $date === '' || $start === '' || $end === '') {
    header('Location: index.php?err=Fill+all+fields'); exit;
}

// validasi tanggal
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
$errDate = DateTime::getLastErrors();
if (!$dateObj || $errDate['warning_count'] || $errDate['error_count']) {
    header('Location: index.php?err=Invalid+date'); exit;
}

// ambil username & tz creator
$stmt = $conn->prepare("SELECT username, COALESCE(preferred_timezone,'Asia/Jakarta') AS tz FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($creator_username, $creator_tz);
if (!$stmt->fetch()) {
    $stmt->close();
    header('Location: index.php?err=Creator+not+found'); exit;
}
$stmt->close();

// buat DateTime di timezone creator
$tzCreator = new DateTimeZone($creator_tz);
$startCreator = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $start, $tzCreator);
$endCreator   = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $end, $tzCreator);
$errTime = DateTime::getLastErrors();
if (!$startCreator || !$endCreator || $errTime['warning_count'] || $errTime['error_count']) {
    header('Location: index.php?err=Invalid+time+format'); exit;
}
if ($endCreator <= $startCreator) {
    header('Location: index.php?err=End+must+be+after+start'); exit;
}

// convert to UTC strings for DB
$startUtcObj = clone $startCreator; $startUtcObj->setTimezone(new DateTimeZone('UTC'));
$endUtcObj   = clone $endCreator;   $endUtcObj->setTimezone(new DateTimeZone('UTC'));
$startUtc = $startUtcObj->format('Y-m-d H:i:s');
$endUtc   = $endUtcObj->format('Y-m-d H:i:s');

// simpan appointment
$ins = $conn->prepare("INSERT INTO appointments (title, creator_id, start_utc, end_utc) VALUES (?, ?, ?, ?)");
if ($ins === false) { header('Location: index.php?err=DB+prepare+failed'); exit; }
$ins->bind_param('siss', $title, $user_id, $startUtc, $endUtc);
if (!$ins->execute()) {
    $ins->close();
    header('Location: index.php?err=Insert+failed'); exit;
}
$appt_id = $ins->insert_id;
$ins->close();

// handle participants (cek tiap username; jika tidak ada -> kumpulkan missing)
$missing = [];
if ($participants_raw !== '') {
    $parts = array_filter(array_map('trim', preg_split('/,/', $participants_raw)));
    $selStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $insStmt = $conn->prepare("INSERT IGNORE INTO appointment_participants (appointment_id, user_id) VALUES (?, ?)");
    foreach ($parts as $p) {
        if ($p === '') continue;
        $selStmt->bind_param('s', $p);
        $selStmt->execute();
        $selStmt->bind_result($pid);
        if ($selStmt->fetch()) {
            $insStmt->bind_param('ii', $appt_id, $pid);
            $insStmt->execute();
        } else {
            $missing[] = $p;
        }
        // reset result so next fetch works
        $selStmt->free_result();
    }
    $selStmt->close();
    $insStmt->close();
}

// juga simpan creator as participant (optional) - skip if you don't want creator in appointment_participants
$insCreatorPart = $conn->prepare("INSERT IGNORE INTO appointment_participants (appointment_id, user_id) VALUES (?, ?)");
$insCreatorPart->bind_param('ii', $appt_id, $user_id);
$insCreatorPart->execute();
$insCreatorPart->close();

$qs = $missing ? '?msg=Created+with+missing:'.urlencode(implode(',', $missing)) : '?msg=Created';
header('Location: index.php' . $qs);
exit;

