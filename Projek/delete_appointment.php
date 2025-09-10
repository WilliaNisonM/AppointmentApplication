<?php
session_start();
require 'db_connect.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$user_id = (int)$_SESSION['user_id'];
$id = intval($_POST['id'] ?? 0);
if (!$id) { header('Location: index.php?err=Invalid+id'); exit; }

$stmt = $conn->prepare("SELECT creator_id FROM appointments WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($creator);
if (!$stmt->fetch()) { $stmt->close(); header('Location: index.php?err=Not+found'); exit; }
$stmt->close();

if ($creator != $user_id) { header('Location: index.php?err=Only+creator+can+delete'); exit; }

$stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header('Location: index.php?msg=Deleted');
exit;

