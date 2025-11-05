<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// ===== Database Connection (SQL Server) =====
$serverName = "BOOBALAN\\SQLEXPRESS";
$dbName     = "vsmart";
$dbUser     = "sa";
$dbPass     = "admin@123";

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ DB Connection Failed: " . htmlspecialchars($e->getMessage()));
}

// ===== Get Ticket Data =====
$ticketCode = $_GET['code'] ?? '';
if (empty($ticketCode)) die("⚠️ Invalid Ticket Code.");

$stmt = $conn->prepare("SELECT * FROM servicetickets WHERE [ticket_code] = ?");
$stmt->execute([$ticketCode]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) die("⚠️ Ticket not found.");

// ===== Update Action Taken =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionTaken = trim($_POST['action_taken'] ?? '');
    $attachmentPath = $ticket['attached_file'] ?? null;

    if (!empty($_FILES['attachment']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['attachment']['name']);
        $targetPath = $uploadDir . $fileName;
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

        if (in_array($_FILES['attachment']['type'], $allowedTypes)) {
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                $attachmentPath = $targetPath;
            }
        }
    }

    $stmtUpdate = $conn->prepare("
        UPDATE servicetickets
        SET [Action_Taken] = ?, [attached_file] = ?
        WHERE [ticket_code] = ?
    ");
    $stmtUpdate->execute([$actionTaken, $attachmentPath, $ticketCode]);

    $stmt = $conn->prepare("SELECT * FROM servicetickets WHERE [ticket_code] = ?");
    $stmt->execute([$ticketCode]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service Call Report</title>
<style>
/* ============ Global ============ */
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
body { background: #e9eef5; color: #2c3e50; }

/* ============ Header ============ */
header {
  background: linear-gradient(135deg, #1a2533, #2c3e50);
  color: #fff;
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 10px 25px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.3);
  position: sticky;
  top: 0;
  z-index: 100;
}
header img.logo {
  height: 50px;
  width: auto;
  border-radius: 8px;
  box-shadow: 0 0 12px rgba(255,255,255,0.3);
}
header h1 {
  font-size: 20px;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* ============ Container ============ */
.container {
  width: 90%;
  max-width: 950px;
  margin: 40px auto;
  background: rgba(255,255,255,0.9);
  border-radius: 16px;
  padding: 35px 40px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  backdrop-filter: blur(8px);
  transition: all 0.3s ease;
}
.container:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(0,0,0,0.2);
}

/* ============ Titles ============ */
h2 {
  text-align: center;
  font-size: 24px;
  color: #2c3e50;
  margin-bottom: 25px;
  border-bottom: 3px solid #3498db;
  display: inline-block;
  padding-bottom: 6px;
}

/* ============ Table ============ */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
th, td {
  padding: 14px 16px;
  border-bottom: 1px solid #e3e6ec;
  text-align: left;
  vertical-align: top;
}
th {
  background: #f8f9fb;
  color: #2c3e50;
  font-weight: 600;
  width: 220px;
  border-radius: 8px 0 0 8px;
}
tr:hover td {
  background: #f1f7ff;
}

/* ============ Textarea + File ============ */
textarea {
  width: 100%;
  height: 120px;
  padding: 12px;
  border: 1px solid #ccc;
  border-radius: 10px;
  resize: vertical;
  font-size: 14px;
  transition: 0.3s;
}
textarea:focus {
  border-color: #3498db;
  box-shadow: 0 0 5px rgba(52,152,219,0.4);
}
input[type="file"] {
  margin-top: 10px;
  font-size: 14px;
}

/* ============ Buttons ============ */
.btn {
  background: #3498db;
  color: white;
  padding: 12px 24px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  font-size: 15px;
  transition: all 0.3s ease;
  margin-right: 8px;
}
.btn:hover {
  background: #2c80b4;
  transform: translateY(-2px);
}
.btn-secondary {
  background: #95a5a6;
}
.btn-secondary:hover {
  background: #7f8c8d;
}
.btn-back {
  background: #e67e22;
}
.btn-back:hover {
  background: #d35400;
}

/* ============ Attachments ============ */
.attachment-link a {
  color: #2980b9;
  text-decoration: none;
  font-weight: 500;
}
.attachment-link a:hover {
  text-decoration: underline;
}

/* ============ Footer Buttons ============ */
.actions {
  text-align: center;
  margin-top: 25px;
}

/* ============ Responsive ============ */
@media (max-width: 768px) {
  header { flex-direction: column; text-align: center; }
  header h1 { font-size: 18px; }
  .container { padding: 25px; }
  th { width: 160px; }
}

/* ============ Print Fix ============ */
@media print {
  body {
    background: #fff !important;
  }
  header {
    position: static !important;
    box-shadow: none !important;
    background: #2c3e50 !important;
    color: #fff !important;
    text-align: center;
    padding: 10px 0 !important;
  }
  header img.logo {
    height: 40px !important;
    box-shadow: none !important;
  }
  .container {
    box-shadow: none !important;
    margin: 10px auto !important;
    padding: 20px !important;
    width: 100% !important;
  }
  .actions, .btn, input[type="file"] {
    display: none !important;
  }
  textarea {
    border: none !important;
    resize: none !important;
  }
  table, th, td {
    border: 1px solid #ccc !important;
    border-collapse: collapse !important;
  }
}
</style>
</head>
<body>
  <header>
    <img src="VSP001.png" alt="Logo" class="logo">
    <h1>Vsmart Technologies Pvt Ltd — Ticket Management</h1>
  </header>

  <div class="container">
    <h2>📋 Service Call Report</h2>
    <table>
      <tr><th>Ticket Code</th><td><?= htmlspecialchars($ticket['ticket_code']) ?></td></tr>
      <tr><th>Title</th><td><?= htmlspecialchars($ticket['title']) ?></td></tr>
      <tr><th>Customer</th><td><?= htmlspecialchars($ticket['customer_name']) ?></td></tr>
      <tr><th>Description</th><td><?= nl2br(htmlspecialchars($ticket['customer_Description'])) ?></td></tr>
      <tr><th>Priority</th><td><?= htmlspecialchars($ticket['customer_priority']) ?></td></tr>
      <tr><th>Status</th><td><?= htmlspecialchars($ticket['Call_status']) ?></td></tr>
      <tr><th>Open Date</th><td><?= htmlspecialchars($ticket['open_date']) ?></td></tr>
      <tr><th>Closed Date</th><td><?= htmlspecialchars($ticket['ClosedDateTime'] ?? '-') ?></td></tr>

      <tr>
        <th>Action Taken</th>
        <td>
          <form method="post" enctype="multipart/form-data">
            <textarea name="action_taken"><?= htmlspecialchars($ticket['Action_Taken'] ?? '') ?></textarea><br>
            <input type="file" name="attachment" accept=".pdf,image/*"><br>
            <?php if (!empty($ticket['attached_file'])): ?>
              <p class="attachment-link">📎 <a href="<?= htmlspecialchars($ticket['attached_file']) ?>" target="_blank">View Attachment</a></p>
            <?php endif; ?>
            <br>
            <button type="submit" class="btn">💾 Save</button>
          </form>
        </td>
      </tr>
    </table>

    <div class="actions">
      <button onclick="window.history.back();" class="btn btn-back">⬅️ Back</button>
      <button onclick="window.print();" class="btn btn-secondary">🖨 Print Report</button>
    </div>
  </div>
</body>
</html>
