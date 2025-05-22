<?php
session_start();
include("db_connection.php");

if (!isset($_SESSION['restaurant_id'])) {
    echo "Access denied. Please log in.";
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

// Step 1: Insert monthly summary if not already present
for ($month = 1; $month <= 12; $month++) {
    // Check if monthly summary already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM monthly_summary WHERE restaurant_id = ? AND year = ? AND month = ?");
    $check_stmt->bind_param("sii", $restaurant_id, $year, $month);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) continue;

    // Total used from daily_usage
    $used_stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity_used), 0)
        FROM daily_usage
        WHERE restaurant_id = ? AND YEAR(date_used) = ? AND MONTH(date_used) = ?
    ");
    $used_stmt->bind_param("sii", $restaurant_id, $year, $month);
    $used_stmt->execute();
    $used_stmt->bind_result($total_used);
    $used_stmt->fetch();
    $used_stmt->close();

    // Total wasted and monetary loss (by joining food_waste with ingredients)
    $waste_stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(fw.quantity_wasted), 0) AS total_wasted,
            COALESCE(SUM(fw.quantity_wasted * i.cost_per_unit), 0) AS monetary_loss
        FROM food_waste fw
        JOIN ingredients i ON fw.ingredient_id = i.ingredient_id
        WHERE fw.restaurant_id = ? AND YEAR(fw.date_wasted) = ? AND MONTH(fw.date_wasted) = ?
    ");
    $waste_stmt->bind_param("sii", $restaurant_id, $year, $month);
    $waste_stmt->execute();
    $waste_stmt->bind_result($total_wasted, $monetary_loss);
    $waste_stmt->fetch();
    $waste_stmt->close();

    // Total donated
    $donate_stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity_donated), 0)
        FROM donations
        WHERE restaurant_id = ? AND YEAR(date_donated) = ? AND MONTH(date_donated) = ?
    ");
    $donate_stmt->bind_param("sii", $restaurant_id, $year, $month);
    $donate_stmt->execute();
    $donate_stmt->bind_result($total_donated);
    $donate_stmt->fetch();
    $donate_stmt->close();

    // Insert monthly summary
    $insert_stmt = $conn->prepare("
        INSERT INTO monthly_summary (month, year, total_used, total_wasted, total_donated, monetary_loss, restaurant_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert_stmt->bind_param("iiiiids", $month, $year, $total_used, $total_wasted, $total_donated, $monetary_loss, $restaurant_id);
    $insert_stmt->execute();
    $insert_stmt->close();
}

// Step 2: Display the Yearly Summary Table
echo "<h2>Yearly Summary for $year</h2>";

echo '<form method="get" style="margin-bottom: 20px;">
        <label>Select Year: </label>
        <input type="number" name="year" value="' . $year . '" min="2020" max="' . date('Y') . '">
        <input type="submit" value="Show Summary">
      </form>';

echo "<table border='1' cellpadding='8' cellspacing='0'>
<tr>
    <th>Month</th>
    <th>Total Used</th>
    <th>Total Wasted</th>
    <th>Total Donated</th>
    <th>Monetary Loss (₹)</th>
</tr>";

$months = [1=>"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

$summary_stmt = $conn->prepare("
    SELECT month, total_used, total_wasted, total_donated, monetary_loss
    FROM monthly_summary
    WHERE restaurant_id = ? AND year = ?
    ORDER BY month ASC
");
$summary_stmt->bind_param("si", $restaurant_id, $year);
$summary_stmt->execute();
$summary_stmt->bind_result($month, $used, $wasted, $donated, $loss);

while ($summary_stmt->fetch()) {
    $month_name = $months[$month];
    echo "<tr>
            <td>$month_name</td>
            <td>$used</td>
            <td>$wasted</td>
            <td>$donated</td>
            <td>₹" . number_format($loss, 2) . "</td>
          </tr>";
}

echo "</table>";

$summary_stmt->close();
$conn->close();
?>

