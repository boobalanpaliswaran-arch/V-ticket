<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "vsmart");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch data
$stmt = $conn->prepare("SELECT * FROM warranty_info ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html>
<head>
  <title>Warranty Records</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
    header { display: flex; align-items: center; justify-content: space-between;
             background: #2c3e50; color: white; padding: 10px 20px; }
    nav a { color: white; margin-left: 15px; text-decoration: none; font-weight: bold; }
    nav a:hover { text-decoration: underline; }
    main { padding: 20px; }
    table { border-collapse: collapse; width: 100%; background: white; margin-top: 20px;
            border-radius: 6px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    th, td { padding: 10px; border: 1px solid #ddd; font-size: 14px; }
    th { background: #2c3e50; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .status-Warranty { color: green; font-weight: bold; }
    .status-Expired { color: red; font-weight: bold; }
    .edit-btn { background: #3498db; color: white; padding: 5px 10px; border: none; border-radius: 4px; }
    .delete-btn { background: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 4px; }
  </style>
</head>
<body>
<header>
  <h2>Vsmart - Warranty Records</h2>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="WarrantyRecords.php">Warranty Records</a>
    <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
  </nav>
</header>
<main>
  <h2>Warranty Records</h2>
  <table>
    <tr>
      <th>ID</th><th>Customer</th><th>Product</th><th>Model</th><th>Order No</th>
      <th>Order Date</th><th>Invoice No</th><th>Invoice Date</th>
      <th>Start</th><th>End</th><th>Status</th><th>Remarks</th><th>Actions</th>
    </tr>
    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): 
          $status = (strtotime($row['warranty_end']) >= time()) ? "Warranty" : "Expired";
          $class = ($status == "Warranty") ? "status-Warranty" : "status-Expired";
      ?>
        <tr>
          <td><?= htmlspecialchars($row['id']) ?></td>
          <td><?= htmlspecialchars($row['customer_name']) ?></td>
          <td><?= htmlspecialchars($row['product_type']) ?></td>
          <td><?= htmlspecialchars($row['model']) ?></td>
          <td><?= htmlspecialchars($row['customer_order_no']) ?></td>
          <td><?= htmlspecialchars($row['order_date']) ?></td>
          <td><?= htmlspecialchars($row['vsmart_invoice_no']) ?></td>
          <td><?= htmlspecialchars($row['vsmart_invoice_date']) ?></td>
          <td><?= htmlspecialchars($row['warranty_start']) ?></td>
          <td><?= htmlspecialchars($row['warranty_end']) ?></td>
          <td class="<?= $class ?>"><?= $status ?></td>
          <td><?= htmlspecialchars($row['remarks']) ?></td>
          <td>
            <form method="get" action="warranty_edit.php" style="display:inline;">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
              <button class="edit-btn">Edit</button>
            </form>
            <form method="post" action="warranty_delete.php" style="display:inline;"
                  onsubmit="return confirm('Delete this record?');">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
              <button class="delete-btn">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="13" style="text-align:center;">No records found.</td></tr>
    <?php endif; ?>
  </table>
</main>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
