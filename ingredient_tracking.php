<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['restaurant_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];

$conn = new mysqli("localhost", "root", "", "traysure");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch only ingredients for this restaurant
$sql = "SELECT ingredient_id, name, category, unit, initial_quantity, expiry_date, cost_per_unit 
        FROM ingredients 
        WHERE restaurant_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TraySure | Ingredient Tracking</title>
    <link rel="stylesheet" href="styles.css"> <!-- Optional custom styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">TraySure</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.html">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="add_ingredient.php">Add Ingredient</a></li>
                <li class="nav-item"><a class="nav-link" href="usage.php">Daily Usage</a></li>
                <li class="nav-item"><a class="nav-link" href="donate.php">Donate</a></li>
                <li class="nav-item"><a class="nav-link" href="summary.php">Monthly Summary</a></li>
                <li class="nav-item"><a class="nav-link" href="backend/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container mt-5">
    <h2 class="mb-4">Ingredient Tracking</h2>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Ingredient</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Cost / Unit (₹)</th>
                <th>Expiry Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $today = date('Y-m-d');
        $threeDaysLater = date('Y-m-d', strtotime('+3 days'));

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ingredient_id = $row["ingredient_id"];
                $initial = $row["initial_quantity"];

                // Fetch total used and wasted for this ingredient
                $usage_sql = "SELECT 
                                (SELECT IFNULL(SUM(quantity_used), 0) FROM daily_usage WHERE ingredient_id = ?) AS used,
                                (SELECT IFNULL(SUM(quantity_wasted), 0) FROM food_waste WHERE ingredient_id = ?) AS wasted";
                $usage_stmt = $conn->prepare($usage_sql);
                $usage_stmt->bind_param("ii", $ingredient_id, $ingredient_id);
                $usage_stmt->execute();
                $usage_result = $usage_stmt->get_result();
                $usage = $usage_result->fetch_assoc();

                $used = $usage['used'];
                $wasted = $usage['wasted'];
                $remaining = $initial - $used - $wasted;

                if ($remaining > 0) {
                    $expiry = $row["expiry_date"];
                    if ($expiry < $today) {
                        $status = "<span class='badge bg-danger'>Expired</span>";
                    } elseif ($expiry <= $threeDaysLater) {
                        $status = "<span class='badge bg-warning text-dark'>Expiring Soon</span>";
                    } else {
                        $status = "<span class='badge bg-success'>Fresh</span>";
                    }

                    echo "<tr>
                        <td>{$row["name"]}</td>
                        <td>{$row["category"]}</td>
                        <td>$remaining</td>
                        <td>{$row["unit"]}</td>
                        <td>{$row["cost_per_unit"]}</td>
                        <td>{$row["expiry_date"]}</td>
                        <td>$status</td>
                    </tr>";
                }

                $usage_stmt->close();
            }
        } else {
            echo "<tr><td colspan='7' class='text-center'>No ingredients available.</td></tr>";
        }

        $stmt->close();
        $conn->close();
        ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
