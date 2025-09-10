<?php
header('Content-Type: application/json');
require '../db_connect.php';

// Ambil semua user
$result = $conn->query("SELECT id, name, username, preferred_timezone FROM users ORDER BY id ASC");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode([
    'status' => 'success',
    'count' => count($users),
    'users' => $users
], JSON_PRETTY_PRINT);
