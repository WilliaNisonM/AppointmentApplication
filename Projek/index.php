<?php
session_start();

// session expiry check (1 jam)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 3600)) {
    session_unset();
    session_destroy();
    header('Location: index.php?err=Session+expired'); exit;
}

require 'db_connect.php';

// ambil user jika login
$user = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $s = $conn->prepare("SELECT id, name, username, COALESCE(preferred_timezone,'Asia/Jakarta') AS preferred_timezone FROM users WHERE id = ?");
    $s->bind_param('i', $uid);
    $s->execute();
    $res = $s->get_result();
    $user = $res->fetch_assoc();
    $s->close();
}

// helper: generate time options 08:00-17:00 per 10 min
function generateTimeOptions($selected = null) {
    $options = "";
    for ($h = 8; $h <= 17; $h++) {
        for ($m = 0; $m < 60; $m += 10) {
            if ($h === 17 && $m > 0) continue;
            $time = sprintf("%02d:%02d", $h, $m);
            $sel = ($time === $selected) ? "selected" : "";
            $options .= "<option value='$time' $sel>$time</option>";
        }
    }
    return $options;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Simple Appointments</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h1>Simple Appointment</h1>

  <?php if (!empty($_GET['err'])): ?>
    <div class="card err"><?=htmlspecialchars($_GET['err'])?></div>
  <?php endif; ?>
  <?php if (!empty($_GET['msg'])): ?>
    <div class="card msg"><?=htmlspecialchars($_GET['msg'])?></div>
  <?php endif; ?>

  <?php if (!$user): ?>
    <div class="card">
      <h2>Login / Create user</h2>
      <form method="post" action="login.php">
        <label>Username</label>
        <input name="username" required placeholder="username (single word)">
        <label>Name (optional)</label>
        <input name="name" placeholder="Your name">
        <label>Preferred timezone</label>
        <select name="preferred_timezone">
          <option>Asia/Jakarta</option>
          <option>UTC</option>
          <option>Asia/Tokyo</option>
          <option>Europe/London</option>
          <option>America/New_York</option>
        </select>
        <button type="submit">Login</button>
      </form>
    </div>

  <?php else: ?>
    <div class="top-bar">
      Logged in as <strong><?=htmlspecialchars($user['name'])?></strong>
      (@<?=htmlspecialchars($user['username'])?>) — TZ: <?=htmlspecialchars($user['preferred_timezone'])?>
      &nbsp; <a class="logout-link" href="logout.php">Logout</a>
    </div>

    <div class="card">
      <h2>Create Appointment</h2>
      <form method="post" action="create_appointment.php">
        <label>Title</label>
        <input name="title" required>
        <label>Date</label>
        <input type="date" name="date" required>

        <div class="time-row">
          <div>
            <label for="start_time">Start time</label>
            <select id="start_time" name="start_time" required>
              <?=generateTimeOptions()?>
            </select>
          </div>
          <div>
            <label for="end_time">End time</label>
            <select id="end_time" name="end_time" required>
              <?=generateTimeOptions()?>
            </select>
          </div>
        </div>
        <small class="note">Working hours only: 08:00 – 17:00 (10 min interval)</small>

        <label>Invite participants (usernames, comma separated)</label>
        <input name="participants" placeholder="alice,bob (optional)">
        <button type="submit">Create</button>
      </form>
    </div>

    <!-- LIST APPOINTMENTS: ambil semua appointment user (creator OR participant) -->
    <div class="card">
      <h2>Your appointments</h2>
      <?php
      $q = "
        SELECT a.*, u.username AS creator_username, u.name AS creator_name
        FROM appointments a
        JOIN users u ON u.id = a.creator_id
        WHERE (a.creator_id = ? OR a.id IN (SELECT appointment_id FROM appointment_participants WHERE user_id = ?))
        ORDER BY a.start_utc ASC
      ";
      $stmt = $conn->prepare($q);
      $stmt->bind_param('ii', $user['id'], $user['id']);
      $stmt->execute();
      $res = $stmt->get_result();

      $nowUtc = new DateTime('now', new DateTimeZone('UTC'));
      $upcoming = [];
      $past = [];

      while ($row = $res->fetch_assoc()) {
          $endUtcObj = DateTime::createFromFormat('Y-m-d H:i:s', $row['end_utc'], new DateTimeZone('UTC'));
          if ($endUtcObj === false) {
              // skip malformed
              continue;
          }
          if ($endUtcObj >= $nowUtc) $upcoming[] = $row;
          else $past[] = $row;
      }
      $stmt->close();

      // Tampilan yang akan datang
      if (count($upcoming) === 0) {
          echo "<div>No upcoming appointments.</div>";
      } else {
          echo "<h3>Upcoming</h3><ul>";
          foreach ($upcoming as $row) {
              $dtStart = DateTime::createFromFormat('Y-m-d H:i:s', $row['start_utc'], new DateTimeZone('UTC'));
              $dtEnd   = DateTime::createFromFormat('Y-m-d H:i:s', $row['end_utc'], new DateTimeZone('UTC'));
              $dtStart->setTimezone(new DateTimeZone($user['preferred_timezone']));
              $dtEnd->setTimezone(new DateTimeZone($user['preferred_timezone']));

              echo "<li>";
              echo "<strong>".htmlspecialchars($row['title'])."</strong><br>";
              echo "Creator: ".htmlspecialchars($row['creator_name'])." (@".htmlspecialchars($row['creator_username']).")<br>";
              echo $dtStart->format('Y-m-d H:i')." → ".$dtEnd->format('Y-m-d H:i')." (".$user['preferred_timezone'].")<br>";

              if ($row['creator_id'] == $user['id']) {
                  echo '<form method="post" action="delete_appointment.php" style="margin-top:6px">';
                  echo '<input type="hidden" name="id" value="'.intval($row['id']).'">';
                  echo '<button type="submit" onclick="return confirm(\'Delete this appointment?\')">Delete</button>';
                  echo '</form>';
              }
              echo "</li>";
          }
          echo "</ul>";
      }

      // Tampilan Yang sudah berlalu
      if (count($past) > 0) {
          echo "<h3 style='margin-top:18px'>Past</h3><ul>";
          foreach ($past as $row) {
              $dtStart = DateTime::createFromFormat('Y-m-d H:i:s', $row['start_utc'], new DateTimeZone('UTC'));
              $dtEnd   = DateTime::createFromFormat('Y-m-d H:i:s', $row['end_utc'], new DateTimeZone('UTC'));
              $dtStart->setTimezone(new DateTimeZone($user['preferred_timezone']));
              $dtEnd->setTimezone(new DateTimeZone($user['preferred_timezone']));

              echo "<li>";
              echo "<strong>".htmlspecialchars($row['title'])."</strong><br>";
              echo $dtStart->format('Y-m-d H:i')." → ".$dtEnd->format('Y-m-d H:i')." (".$user['preferred_timezone'].")<br>";
              echo "</li>";
          }
          echo "</ul>";
      }
      ?>
    </div>

  <?php endif; ?>
</div>
</body>
</html>
