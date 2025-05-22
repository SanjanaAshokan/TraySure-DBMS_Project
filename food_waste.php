<?php
session_start();
require_once 'backend/db_connection.php';
date_default_timezone_set('Asia/Kolkata');

// Redirect if not logged in
if (!isset($_SESSION['restaurant_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$today = date('Y-m-d');
$currentMonth = date('m');
$currentYear = date('Y');

// --- 1. Insert expired ingredients ---
$expiredQuery = "
    SELECT ingredient_id, expiry_date, initial_quantity 
    FROM ingredients 
    WHERE expiry_date < '$today' AND restaurant_id = $restaurant_id
";
$expiredResult = $conn->query($expiredQuery);
while ($row = $expiredResult->fetch_assoc()) {
    $ingredientId = $row['ingredient_id'];
    $quantity = $row['initial_quantity'];

    // Prevent duplicate insertions
    $check = $conn->query("SELECT * FROM food_waste WHERE ingredient_id = $ingredientId AND date_wasted = '$today' AND reason = 'expired'");
    if ($check->num_rows == 0) {
        $conn->query("
            INSERT INTO food_waste (ingredient_id, date_wasted, quantity_wasted, reason)
            VALUES ($ingredientId, '$today', $quantity, 'expired')
        ");
    }
}

// --- 2. Insert end-of-month waste ---
$lastDayOfMonth = date('t');
if (date('d') == $lastDayOfMonth) {
    $ingredientQuery = "SELECT ingredient_id, initial_quantity FROM ingredients WHERE restaurant_id = $restaurant_id";
    $ingredients = $conn->query($ingredientQuery);

    while ($row = $ingredients->fetch_assoc()) {
        $ingredientId = $row['ingredient_id'];
        $initialQuantity = $row['initial_quantity'];

        $usedRes = $conn->query("
            SELECT SUM(quantity_used) as used 
            FROM daily_usage 
            WHERE ingredient_id = $ingredientId 
              AND MONTH(date_used) = $currentMonth 
              AND YEAR(date_used) = $currentYear
        ");
        $used = $usedRes->fetch_assoc()['used'] ?? 0;

        $wastedRes = $conn->query("
            SELECT SUM(quantity_wasted) as wasted 
            FROM food_waste 
            WHERE ingredient_id = $ingredientId 
              AND MONTH(date_wasted) = $currentMonth 
              AND YEAR(date_wasted) = $currentYear
        ");
        $wasted = $wastedRes->fetch_assoc()['wasted'] ?? 0;

        $remaining = $initialQuantity - $used - $wasted;

        if ($remaining > 0) {
            $check = $conn->query("SELECT * FROM food_waste WHERE ingredient_id = $ingredientId AND date_wasted = '$today' AND reason = 'end_of_month'");
            if ($check->num_rows == 0) {
                $conn->query("
                    INSERT INTO food_waste (ingredient_id, date_wasted, quantity_wasted, reason)
                    VALUES ($ingredientId, '$today', $remaining, 'end_of_month')
                ");
            }
        }
    }
}

// --- 3. Fetch food waste data for the current restaurant only ---
$wasteQuery = "
    SELECT fw.ingredient_id, i.name, fw.date_wasted, fw.quantity_wasted, fw.reason
    FROM food_waste fw
    JOIN ingredients i ON fw.ingredient_id = i.ingredient_id
    WHERE i.restaurant_id = $restaurant_id
    ORDER BY fw.date_wasted DESC
";
$wasteResult = $conn->query($wasteQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Food Waste Overview</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS -->
</head>
<body>
    <h1>Food Waste Tracking</h1>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Ingredient</th>
                <th>Date Wasted</th>
                <th>Quantity Wasted</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($wasteResult->num_rows > 0): ?>
                <?php while ($row = $wasteResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['date_wasted']; ?></td>
                        <td><?php echo $row['quantity_wasted']; ?></td>
                        <td><?php echo ucfirst($row['reason']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No food waste records available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br>
    <a href="dashboard.html">← Back to Dashboard</a>
</body>
</html>
