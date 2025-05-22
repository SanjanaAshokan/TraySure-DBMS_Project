<?php
require 'db_connection.php';
session_start();

if (!isset($_SESSION['restaurant_id'])) {
    header("Location: ../index.html");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$month = isset($_GET['month']) ? (int)$_GET['month'] : null;
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;

if (!$month || !$year) {
    echo "<script>alert('Please select a valid month and year.');
          window.location.href = 'summary.php';</script>";
    exit();
}

$summary_sql = "SELECT ingredient_id, name, category, unit, expiry_date, SUM(initial_quantity) AS total_initial, cost_per_unit
                FROM ingredients
                WHERE MONTH(date_added) = ? AND YEAR(date_added) = ? AND restaurant_id = ?
                GROUP BY ingredient_id, name, category, unit, expiry_date, cost_per_unit";
$stmt = $conn->prepare($summary_sql);
$stmt->bind_param("iii", $month, $year, $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<div class='container mt-4'>";
echo "<h3 class='text-center mb-4'>Monthly Ingredient Summary for $month/$year</h3>";
echo "<div class='table-responsive'>";
echo "<table class='table table-hover table-bordered align-middle text-center shadow'>";
echo "<thead class='table-primary'>
        <tr class='fw-bold'>
          <th>#</th>
          <th>Ingredient Name</th>
          <th>Category</th>
          <th>Initial Quantity</th>
          <th>Used Quantity</th>
          <th>Wasted Quantity</th>
          <th>Remaining Quantity</th>
          <th>Loss (₹)</th>
        </tr>
      </thead>
      <tbody>";

$index = 1;
$total_monthly_waste = 0;
$total_monthly_loss = 0;
$unused_count = 0;
$total_unused_quantity = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ingredient_id = $row['ingredient_id'];
        $name = $row['name'];
        $category = $row['category'];
        $initial = $row['total_initial'];
        $cost_per_unit = $row['cost_per_unit'];

        $used_sql = "SELECT SUM(quantity_used) AS total_used 
                     FROM daily_usage 
                     WHERE ingredient_id = ? AND MONTH(date_used) = ? AND YEAR(date_used) = ?";
        $used_stmt = $conn->prepare($used_sql);
        $used_stmt->bind_param("iii", $ingredient_id, $month, $year);
        $used_stmt->execute();
        $used_result = $used_stmt->get_result();
        $used = $used_result->fetch_assoc()['total_used'] ?? 0;
        $used_stmt->close();

        $waste_sql = "SELECT SUM(quantity_wasted) AS total_wasted 
                      FROM food_waste 
                      WHERE ingredient_id = ? AND MONTH(date_wasted) = ? AND YEAR(date_wasted) = ?";
        $waste_stmt = $conn->prepare($waste_sql);
        $waste_stmt->bind_param("iii", $ingredient_id, $month, $year);
        $waste_stmt->execute();
        $waste_result = $waste_stmt->get_result();
        $wasted = $waste_result->fetch_assoc()['total_wasted'] ?? 0;
        $waste_stmt->close();

        $total_outflow = $used + $wasted;
        if ($total_outflow > $initial) {
            $remaining = 0;
            $loss = round($wasted * $cost_per_unit, 2);
            $note = " (Overused)";
        } else {
            $remaining = $initial - $total_outflow;
            $loss = round(($wasted + $remaining) * $cost_per_unit, 2);
            $note = "";
        }

        $total_monthly_waste += $wasted;
        $total_monthly_loss += $loss;

        if ($remaining > 0) {
            $unused_count++;
            $total_unused_quantity += $remaining;
        }

        echo "<tr>
                <td>$index</td>
                <td>$name</td>
                <td>$category</td>
                <td>$initial</td>
                <td>$used</td>
                <td>$wasted</td>
                <td>$remaining$note</td>
                <td>₹$loss</td>
              </tr>";
        $index++;
    }

    echo "</tbody></table></div>";

    echo "<div class='text-end fw-bold mt-3 me-2'>
            Total Quantity Wasted in $month/$year: <span class='text-danger'>$total_monthly_waste units</span><br>
            Total Monetary Loss from Expired & Unused Stock: <span class='text-danger'>₹" . number_format($total_monthly_loss, 2) . "</span><br>
            Total Unused Quantity: <span class='text-warning'>$total_unused_quantity units</span>
          </div>";
    echo "</div>";
} else {
    echo "<script>alert('No ingredients found for the selected month and year.');
          window.location.href = '../summary.php';</script>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Ingredient Summary</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background-image: url('https://1drv.ms/i/c/26d78ae144a472ba/ERSl3hevdqRNmHJ1wRlhDHwB7fCuL_Ujkz4doc63tyF07w?e=Ash5vh'); background-size: cover; background-repeat: no-repeat; background-attachment: fixed;">
    <!-- Table rendered by PHP -->
</body>

</html>
