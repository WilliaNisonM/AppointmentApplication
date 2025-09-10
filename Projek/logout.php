<?php
// Mulai atau lanjutkan session yang sedang aktif.
// Wajib dipanggil dulu sebelum bisa mengakses atau menghapus data session.
session_start();

// Hapus semua variabel session yang tersimpan.
// Setelah ini, $_SESSION akan kosong.
session_unset();

// Hancurkan session di server (hapus ID session).
// Setelah ini, session benar-benar tidak bisa digunakan lagi.
session_destroy();

// Redirect (alihkan) user ke halaman index.php atau bisa diubah sesuai kemauan kita nanti setelah logout.
header('Location: index.php');

// Pastikan script berhenti setelah melakukan redirect.
// Supaya tidak ada kode lain yang ikut dieksekusi.
exit;
