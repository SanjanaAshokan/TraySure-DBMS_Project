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

// Fetch latitude and longitude of the restaurant
$coord_query = $conn->prepare("SELECT latitude, longitude FROM users WHERE restaurant_id = ?");
$coord_query->bind_param("s", $restaurant_id);
$coord_query->execute();
$coord_result = $coord_query->get_result();
$latitude = 0;
$longitude = 0;
if ($row = $coord_result->fetch_assoc()) {
    $latitude = $row['latitude'];
    $longitude = $row['longitude'];
}

// Fetch available and non-expired ingredients
$ingredient_query = $conn->prepare("
    SELECT i.name, i.initial_quantity,
           IFNULL(SUM(d.quantity_used), 0) AS used,
           IFNULL(SUM(f.quantity_wasted), 0) AS wasted
    FROM ingredients i
    LEFT JOIN daily_usage d ON i.ingredient_id = d.ingredient_id AND d.restaurant_id = ?
    LEFT JOIN food_waste f ON i.ingredient_id = f.ingredient_id AND f.restaurant_id = ?
    WHERE i.restaurant_id = ? AND i.expiry_date >= CURDATE()
    GROUP BY i.ingredient_id
    HAVING (i.initial_quantity - used - wasted) > 0
");
$ingredient_query->bind_param("iii", $restaurant_id, $restaurant_id, $restaurant_id);
$ingredient_query->execute();
$ingredient_result = $ingredient_query->get_result();

// Fetch user's latitude and longitude
$lat_query = $conn->prepare("SELECT latitude, longitude FROM users WHERE restaurant_id = ?");
$lat_query->bind_param("s", $restaurant_id);
$lat_query->execute();
$lat_result = $lat_query->get_result();

$latitude = 0;
$longitude = 0;
if ($row = $lat_result->fetch_assoc()) {
    $latitude = $row['latitude'];
    $longitude = $row['longitude'];
}

// Haversine query to fetch nearby NGOs within 10km
$ngo_query = $conn->prepare("
    SELECT name, location,
        (6371 * acos(
            cos(radians(?)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) *
            sin(radians(latitude))
        )) AS distance
    FROM ngos
    HAVING distance < 20
    ORDER BY distance ASC
");
$ngo_query->bind_param("ddd", $latitude, $longitude, $latitude);
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
            <?php
              $remaining = $row['initial_quantity'] - $row['used'] - $row['wasted'];
            ?>
            <option value="<?= htmlspecialchars($row['name']) ?>">
              <?= htmlspecialchars($row['name']) ?> (Remaining: <?= $remaining ?> units)
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
            <option value="<?= htmlspecialchars($row['name']) ?>">
              <?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['location']) ?> - <?= round($row['distance'], 2) ?> km away)
            </option>

          <?php endwhile; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-warning">Submit Donation</button>
    </form>
  </div>
</body>
</html>
