<?php
include 'navbar.php';
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: index.html");
    exit();
}

require 'backend/db_connection.php';

$restaurant_id = $_SESSION['restaurant_id']; // Get current user's restaurant

// Filter ingredients by restaurant_id and check remaining quantity
$dropdown_sql = "
    SELECT i.name, i.expiry_date, i.initial_quantity,
           IFNULL(SUM(d.quantity_used), 0) AS used,
           IFNULL(SUM(f.quantity_wasted), 0) AS wasted
    FROM ingredients i
    LEFT JOIN daily_usage d ON i.ingredient_id = d.ingredient_id AND d.restaurant_id = ?
    LEFT JOIN food_waste f ON i.ingredient_id = f.ingredient_id AND f.restaurant_id = ?
    WHERE i.restaurant_id = ?
    GROUP BY i.ingredient_id
    HAVING (i.initial_quantity - used - wasted) > 0
";

$stmt = $conn->prepare($dropdown_sql);
$stmt->bind_param("iii", $restaurant_id, $restaurant_id, $restaurant_id);
$stmt->execute();
$dropdown_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Record Daily Usage</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="usage-page">
  <div class="container mt-5">
    <h2 class="text-center mb-4">Record Daily Usage</h2>
    <form action="backend/record_usage.php" method="POST">
      <div class="mb-3">
        <label class="form-label">Ingredient Name</label>
        <select class="form-select" name="ingredient_name" required>
          <option value="" disabled selected>Select an ingredient</option>
          <?php
          if ($dropdown_result->num_rows > 0) {
              while ($row = $dropdown_result->fetch_assoc()) {
                  $name = $row['name'];
                  $expiry = $row['expiry_date'];
                  $remaining = $row['initial_quantity'] - $row['used'] - $row['wasted'];
                  echo "<option value=\"$name\">$name — $remaining units left (Expires: $expiry)</option>";
              }
          } else {
              echo "<option value=\"\">No available ingredients</option>";
          }
          ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Quantity Used Today</label>
        <input type="number" class="form-control" name="used_quantity" step="0.01" min="0.01" required>
      </div>
      <button type="submit" class="btn btn-success">Submit Usage</button>
    </form>
  </div>
</body>
</html>
