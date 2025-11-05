<?php
// ======================================
// DB Connection (SQL Server)
// ======================================
$serverName = "BOOBALAN\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "vsmart",
    "Uid" => "sa",
    "PWD" => "admin@123",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die("❌ DB Connection failed: " . print_r(sqlsrv_errors(), true));
}

// ======================================
// Fetch Customer by ID
// ======================================
if (!isset($_GET['id'])) {
    die("❌ No customer ID provided.");
}

$customer_id = intval($_GET['id']);

$sql = "SELECT * FROM dbo.Customers WHERE customer_id = ?";
$params = [$customer_id];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die("❌ Query Error: " . print_r(sqlsrv_errors(), true));
}

$customer = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$customer) {
    die("❌ Customer not found.");
}

// ======================================
// Update Customer
// ======================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['customer_name'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $Address  = $_POST['customer_Address'];
    $Location = $_POST['customer_Location'];

    $updateSql = "UPDATE dbo.Customers 
                  SET customer_name = ?, 
                      email = ?, 
                      phone = ?, 
                      customer_Address = ?,
                      customer_Location = ?
                  WHERE customer_id = ?";
    $updateParams = [$name, $email, $phone, $Address, $Location, $customer_id];
    $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);

    if ($updateStmt === false) {
        die("❌ Update Error: " . print_r(sqlsrv_errors(), true));
    } else {
        echo "<script>alert('✅ Customer updated successfully!'); window.location='viewcustomer.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Customer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #1a5276;
            color: white;
            padding: 15px 0;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        h2 {
            text-align: center;
            margin: 15px 0;
            color: #34495e;
            font-size: 16px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        form {
            background: white;
            width: 400px;
            margin: 30px auto;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }

        label {
            font-weight: 600;
            color: #2c3e50;
            display: block;
            margin-bottom: 6px;
            margin-top: 12px;
        }

        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }

        button {
            background-color: #1a5276;
            color: white;
            border: none;
            padding: 10px 18px;
            margin-top: 20px;
            width: 100%;
            font-size: 15px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.25s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        button:hover {
            background-color: #154360;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .back-link a {
            color: #1a5276;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<header>Vsmart Technologies Pvt Ltd</header>
<h2>✏️ Edit Customer</h2>

<form method="POST">
    <label>Customer Name:</label>
    <input type="text" name="customer_name" value="<?php echo htmlspecialchars($customer['customer_name']); ?>" required>

    <label>Email:</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>

    <label>Phone:</label>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>" required>

    <label>Address:</label>
    <input type="text" name="customer_Address" value="<?php echo htmlspecialchars($customer['customer_Address']); ?>" required>

    <label>Location:</label>
    <input type="text" name="customer_Location" value="<?php echo htmlspecialchars($customer['customer_Location']); ?>" required>

    <button type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Customer</button>

    <div class="back-link">
        <a href="viewcustomer.php">← Back to List</a>
    </div>
</form>

</body>
</html>
