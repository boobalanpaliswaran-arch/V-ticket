<?php


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vsmart";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Invalid record ID.");

$successMsg = "";
$errorMsg = "";

// fetch record
$result = $conn->query("SELECT * FROM warranty_info WHERE id=$id");
if (!$result || $result->num_rows === 0) die("Record not found.");
$record = $result->fetch_assoc();

// update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $customer_name = $conn->real_escape_string($_POST['customer_name']);
    $product_type  = $conn->real_escape_string($_POST['product_type']);
    $model         = $conn->real_escape_string($_POST['model']);
    $customer_order_no = $conn->real_escape_string($_POST['customer_order_no']);
    $order_date    = $conn->real_escape_string($_POST['order_date']);
    $vsmart_invoice_no = $conn->real_escape_string($_POST['vsmart_invoice_no']);
    $vsmart_invoice_date = $conn->real_escape_string($_POST['vsmart_invoice_date']);
    $warranty_start= $conn->real_escape_string($_POST['warranty_start']);
    $warranty_end  = $conn->real_escape_string($_POST['warranty_end']);
    $remarks       = $conn->real_escape_string($_POST['remarks']);

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

    if ($conn->query($sql) === TRUE) {
        $successMsg = "✅ Warranty updated successfully!";
        // refresh record
        $result = $conn->query("SELECT * FROM warranty_info WHERE id=$id");
        $record = $result->fetch_assoc();
    } else {
        $errorMsg = "❌ Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Warranty</title>
  <style>
    body { font-family: Arial, sans-serif; margin:20px; }
    header { display:flex; align-items:center; justify-content:space-between; background:#2c3e50; padding:10px; color:white; }
    nav a { margin: 0 10px; text-decoration:none; color:white; font-weight:bold; }
    form { max-width:600px; margin:20px auto; background:#f9f9f9; padding:20px; border-radius:6px; border:1px solid #ccc; }
    label { display:block; margin-top:10px; font-weight:bold; }
    input, textarea { width:100%; padding:8px; margin-top:5px; }
    button { margin-top:15px; padding:10px; background:#3498db; color:#fff; border:none; border-radius:5px; cursor:pointer; }
    button:hover { background:#2980b9; }
    .msg { text-align:center; font-weight:bold; margin:10px; }
    .success { color:green; }
    .error { color:red; }
  </style>
</head>
<body>

<header>
  <img src="VSP001.png" alt="Ticket Logo" class="logo" style="height:50px;">
  <div class="company-info">
    <h1>Vsmart Technologies Pvt Ltd</h1>
    <h2>Ticket Management System</h2>
  </div>
  <nav>
    <a href="Warranty Records.php">Warranty Records</a>
    <a href="login.php" id="logoutBtn">Logout</a>
  </nav>
</header>

<h2 style="text-align:center;">Edit Warranty Record</h2>
<?php if($successMsg) echo "<p class='msg success'>$successMsg</p>"; ?>
<?php if($errorMsg) echo "<p class='msg error'>$errorMsg</p>"; ?>

<form method="POST">
    <label>Customer Name</label>
    <input type="text" name="customer_name" value="<?= htmlspecialchars($record['customer_name']) ?>" required>

    <label>Product Type</label>
    <input type="text" name="product_type" value="<?= htmlspecialchars($record['product_type']) ?>" required>

    <label>Model</label>
    <input type="text" name="model" value="<?= htmlspecialchars($record['model']) ?>" required>

    <label>Customer Order No</label>
    <input type="text" name="customer_order_no" value="<?= htmlspecialchars($record['customer_order_no']) ?>">

    <label>Order Date</label>
    <input type="date" name="order_date" value="<?= htmlspecialchars($record['order_date']) ?>">

    <label>Vsmart Invoice No</label>
    <input type="text" name="vsmart_invoice_no" value="<?= htmlspecialchars($record['vsmart_invoice_no']) ?>">

    <label>Vsmart Invoice Date</label>
    <input type="date" name="vsmart_invoice_date" value="<?= htmlspecialchars($record['vsmart_invoice_date']) ?>">

    <label>Warranty Start Date</label>
    <input type="date" name="warranty_start" value="<?= htmlspecialchars($record['warranty_start']) ?>">

    <label>Warranty End Date</label>
    <input type="date" name="warranty_end" value="<?= htmlspecialchars($record['warranty_end']) ?>">

    <label>Remarks</label>
    <textarea name="remarks"><?= htmlspecialchars($record['remarks']) ?></textarea>

    <button type="submit">Update Warranty</button>
</form>

</body>
</html>
<?php $conn->close(); ?>
