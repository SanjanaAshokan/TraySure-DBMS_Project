<?php
include 'navbar.php';
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add Ingredients</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="add-ingredient-page">

  <!-- Add Ingredient Form -->
  <div class="container mt-5">
    <h2 class="text-center mb-4">Add Monthly Ingredients</h2>
    <form action="backend/add_ingredient.php" method="POST">
      <div class="mb-3">
        <label class="form-label">Ingredient Name</label>
        <input type="text" class="form-control" name="name" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Category</label>
        <select name="category" class="form-select" required>
          <option value="">Select Category</option>
          <option value="Dairy">Dairy</option>
          <option value="Vegetables">Vegetables</option>
          <option value="Fruits">Fruits</option>
          <option value="Grains">Grains</option>
          <option value="Meat">Meat</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Quantity (kg or litres)</label>
        <input type="number" class="form-control" name="initial_quantity" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Unit of Quantity</label>
        <input type="text" class="form-control" name="unit" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Cost (₹)</label>
        <input type="number" class="form-control" name="cost_per_unit" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Expiry Date</label>
        <input type="date" class="form-control" name="expiry_date" required>
      </div>
      <button type="submit" class="btn btn-primary">Add Ingredient</button>
    </form>
  </div>
  <!-- Bootstrap JavaScript (for navbar toggle on mobile) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
