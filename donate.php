<?php
include 'navbar.php';
include 'backend/db_connection.php';
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: index.html");
    exit();
}

// Get restaurant ID from session
$restaurant_id = $_SESSION['restaurant_id'];

// Fetch restaurant location from users table
$loc_query = $conn->prepare("SELECT location FROM users WHERE restaurant_id = ?");
$loc_query->bind_param("s", $restaurant_id);
$loc_query->execute();
$loc_result = $loc_query->get_result();
$location = '';
if ($row = $loc_result->fetch_assoc()) {
    $location = $row['location'];
}

// Fetch available and non-expired ingredients
$today = date('Y-m-d');
$ingredient_query = $conn->prepare("SELECT ingredient_name, quantity FROM ingredients WHERE restaurant_id = ? AND expiry_date >= ? AND quantity > 0");
$ingredient_query->bind_param("ss", $restaurant_id, $today);
$ingredient_query->execute();
$ingredient_result = $ingredient_query->get_result();

// Fetch nearby NGOs based on location
$ngo_query = $conn->prepare("SELECT ngo_name FROM ngos WHERE location = ?");
$ngo_query->bind_param("s", $location);
$ngo_query->execute();
$ngo_result = $ngo_query->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Donate Surplus Food</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="donation-page">
  <div class="container mt-5">
    <h2 class="text-center mb-4">Donate Surplus Edible Food</h2>
    <form action="backend/record_donation.php" method="POST">

      <!-- Ingredient Dropdown -->
      <div class="mb-3">
        <label class="form-label">Select Ingredient</label>
        <select class="form-select" name="ingredient_name" required>
          <option value="">-- Choose Ingredient --</option>
          <?php while ($row = $ingredient_result->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($row['ingredient_name']) ?>">
              <?= htmlspecialchars($row['ingredient_name']) ?> (Remaining: <?= $row['quantity'] ?> units)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Quantity Input -->
      <div class="mb-3">
        <label class="form-label">Quantity to Donate</label>
        <input type="number" class="form-control" name="donate_quantity" min="1" required>
      </div>

      <!-- NGO Dropdown -->
      <div class="mb-3">
        <label class="form-label">Select Nearby NGO / Food Bank</label>
        <select class="form-select" name="ngo_name" required>
          <option value="">-- Choose NGO --</option>
          <?php while ($row = $ngo_result->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($row['ngo_name']) ?>">
              <?= htmlspecialchars($row['ngo_name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-warning">Submit Donation</button>
    </form>
  </div>
</body>
</html>
