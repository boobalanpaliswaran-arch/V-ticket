<?php


$host = "localhost";
$user = "root";
$pass = "";
$db   = "vsmart";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB Connection failed: " . $conn->connect_error);

$successMsg = "";
$errorMsg = "";

// ✅ Fetch customers dynamically
$customers = [];
$result = $conn->query("SELECT customer_name FROM Newcustomer ORDER BY customer_name ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row['customer_name'];
    }
}

// Add or Update Warranty
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id                   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $customer_name        = $conn->real_escape_string($_POST['customer_name']);
    $product_type         = $conn->real_escape_string($_POST['product_type']);
    $model                = $conn->real_escape_string($_POST['model']);
    $customer_order_no    = $conn->real_escape_string($_POST['customer_order_no']);
    $order_date           = $conn->real_escape_string($_POST['order_date']);
    $vsmart_invoice_no    = $conn->real_escape_string($_POST['vsmart_invoice_no']);
    $vsmart_invoice_date  = $conn->real_escape_string($_POST['vsmart_invoice_date']);
    $warranty_start       = $conn->real_escape_string($_POST['warranty_start']);
    $warranty_end         = $conn->real_escape_string($_POST['warranty_end']);
    $remarks              = $conn->real_escape_string($_POST['remarks']);

    if ($id > 0) {
        $sql = "UPDATE warranty_info SET 
                customer_name='$customer_name',
                product_type='$product_type',
                model='$model',
                customer_order_no='$customer_order_no',
                order_date='$order_date',
                vsmart_invoice_no='$vsmart_invoice_no',
                vsmart_invoice_date='$vsmart_invoice_date',
                warranty_start='$warranty_start',
                warranty_end='$warranty_end',
                remarks='$remarks'
                WHERE id=$id";
        $successMsg = "Warranty updated successfully!";
    } else {
        $sql = "INSERT INTO warranty_info 
                (customer_name, product_type, model, customer_order_no, order_date, vsmart_invoice_no, vsmart_invoice_date, warranty_start, warranty_end, remarks)
                VALUES ('$customer_name','$product_type','$model','$customer_order_no','$order_date','$vsmart_invoice_no','$vsmart_invoice_date','$warranty_start','$warranty_end','$remarks')";
        $successMsg = "Warranty added successfully!";
    }

    if ($conn->query($sql) !== TRUE) $errorMsg = "Error: " . $conn->error;
}

// Fetch record for edit
$editWarranty = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM warranty_info WHERE id=$edit_id");
    if ($res && $res->num_rows > 0) $editWarranty = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Warranty Information - Ticket Management</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <img src="VSP001.png" alt="Ticket Logo" class="logo" style="height:50px;">
  <div class="company-info">
    <h1>Vsmart Technologies Pvt Ltd</h1>
    <h2>Ticket Management System</h2>
  </div>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="WarrantyRecords.php">Warranty Records</a>
    <a href="login.php" id="logoutBtn">Logout</a>
  </nav>
</header>

<main>
<h2 style="text-align:center;">Warranty Information</h2>

<?php if($successMsg) echo "<p class='msg success'>$successMsg</p>"; ?>
<?php if($errorMsg) echo "<p class='msg error'>$errorMsg</p>"; ?>

<form method="POST">
    <input type="hidden" name="id" value="<?= $editWarranty['id'] ?? '' ?>">

    <!-- ✅ Dynamic Dropdown -->
    <label>Customer Name</label>
    <select name="customer_name" required>
        <option value="">-- Select Customer --</option>
        <?php foreach($customers as $customer): ?>
            <option value="<?= htmlspecialchars($customer) ?>" 
                <?= (isset($editWarranty['customer_name']) && $editWarranty['customer_name'] == $customer) ? 'selected' : '' ?>>
                <?= htmlspecialchars($customer) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Product Type</label>
    <input type="text" name="product_type" value="<?= htmlspecialchars($editWarranty['product_type'] ?? '') ?>" required>

    <label>Model</label>
    <input type="text" name="model" value="<?= htmlspecialchars($editWarranty['model'] ?? '') ?>" required>

    <label>Customer Order No</label>
    <input type="text" name="customer_order_no" value="<?= htmlspecialchars($editWarranty['customer_order_no'] ?? '') ?>">

    <label>Order Date</label>
    <input type="date" name="order_date" value="<?= $editWarranty['order_date'] ?? '' ?>">

    <label>Vsmart Invoice No</label>
    <input type="text" name="vsmart_invoice_no" value="<?= htmlspecialchars($editWarranty['vsmart_invoice_no'] ?? '') ?>">

    <label>Vsmart Invoice Date</label>
    <input type="date" name="vsmart_invoice_date" value="<?= $editWarranty['vsmart_invoice_date'] ?? '' ?>">

    <label>Warranty Start Date</label>
    <input type="date" name="warranty_start" value="<?= $editWarranty['warranty_start'] ?? '' ?>">

    <label>Warranty End Date</label>
    <input type="date" name="warranty_end" value="<?= $editWarranty['warranty_end'] ?? '' ?>">

    <label>Remarks</label>
    <textarea name="remarks"><?= htmlspecialchars($editWarranty['remarks'] ?? '') ?></textarea>

    <button type="submit"><?= $editWarranty ? 'Update Warranty' : 'Add Warranty' ?></button>
</form>
</main>
</body>
</html>
<?php $conn->close(); ?>
