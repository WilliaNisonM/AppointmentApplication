<?php
session_start(); 
// mulai session untuk simpan data login user

// Atur session biar expired otomatis setelah 1 jam
ini_set('session.gc_maxlifetime', 3600); // garbage collector 1 jam
session_set_cookie_params(3600);         // cookie browser juga 1 jam

require 'db_connect.php'; 
// koneksi database

// Cek kalau akses bukan POST, langsung balikin ke index
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); 
    exit;
}

// Ambil data dari form login
$username = trim($_POST['username'] ?? '');
$name = trim($_POST['name'] ?? $username); // kalau nama kosong, pakai username
$tz = trim($_POST['preferred_timezone'] ?? 'Asia/Jakarta');

// Validasi: username wajib diisi
if ($username === '') {
    header('Location: index.php?err=Username+required'); 
    exit;
}

// Cek apakah user sudah ada di database
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->bind_result($uid);

// Kalau user ditemukan langsung simpan ke session & redirect ke index
if ($stmt->fetch()) {
    $_SESSION['user_id'] = $uid;
    $_SESSION['login_time'] = time(); // catat waktu login
    $stmt->close();
    header('Location: index.php'); 
    exit;
}
$stmt->close();

// Kalau user belum ada langsung buat user baru
$stmt = $conn->prepare("INSERT INTO users (name, username, preferred_timezone) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $name, $username, $tz);
if ($stmt->execute()) {
    // simpan session untuk user baru
    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['login_time'] = time();
    $stmt->close();
    header('Location: index.php'); 
    exit;
} else {
    $stmt->close();
    die('Create user failed.'); // kalau insert gagal
}

