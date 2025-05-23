<?php
require 'db_connection.php';
session_start();

if (!isset($_SESSION['restaurant_id'])) {
    header("Location: ../index.html");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];

if (!isset($_POST['ingredient_name'], $_POST['used_quantity'])) {
    echo "<script>alert('Invalid form submission.');
          window.location.href = '../usage.php';</script>";
    exit();
}

$name = trim($_POST['ingredient_name']);
$used = floatval($_POST['used_quantity']);
if ($used <= 0) {
    echo "<script>alert('Used quantity must be a positive number.');
          window.location.href = '../usage.php';</script>";
    exit();
}

$sql = "SELECT ingredient_id, initial_quantity, expiry_date FROM ingredients 
        WHERE name = ? AND restaurant_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $name, $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Ingredient not found for your restaurant.');
          window.location.href = '../usage.php';</script>";
    exit();
}

$row = $result->fetch_assoc();
$ingredient_id = $row['ingredient_id'];
$initial_quantity = floatval($row['initial_quantity']);
$expiry_date = $row['expiry_date'];

if (strtotime($expiry_date) < time()) {
    $delete_sql = "DELETE FROM ingredients WHERE ingredient_id = ? AND restaurant_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $ingredient_id, $restaurant_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    echo "<script>alert('Error: Ingredient has expired and has been deleted.');
          window.location.href = '../usage.php';</script>";
    exit();
}

$usage_sql = "
    SELECT
      (SELECT IFNULL(SUM(quantity_used), 0) FROM daily_usage WHERE ingredient_id = ? AND restaurant_id = ?) AS total_used,
      (SELECT IFNULL(SUM(quantity_wasted), 0) FROM food_waste WHERE ingredient_id = ? AND restaurant_id = ?) AS total_wasted
";
$usage_stmt = $conn->prepare($usage_sql);
$usage_stmt->bind_param("iiii", $ingredient_id, $restaurant_id, $ingredient_id, $restaurant_id);
$usage_stmt->execute();
$usage_result = $usage_stmt->get_result();
$usage_data = $usage_result->fetch_assoc();

$total_used = floatval($usage_data['total_used']);
$total_wasted = floatval($usage_data['total_wasted']);
$usage_stmt->close();

$remaining = $initial_quantity - $total_used - $total_wasted;

if ($used > $remaining) {
    echo "<script>alert('Error: Used quantity exceeds available quantity.');
          window.location.href = '../usage.php';</script>";
    exit();
}

$insert_sql = "INSERT INTO daily_usage (ingredient_id, date_used, quantity_used, restaurant_id) VALUES (?, CURDATE(), ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("idi", $ingredient_id, $used, $restaurant_id);

if (!$insert_stmt->execute()) {
    echo "<script>alert('Error recording usage: " . $insert_stmt->error . "');
          window.location.href = '../usage.php';</script>";
    exit();
}
$insert_stmt->close();

$final_remaining = $remaining - $used;
if ($final_remaining <= 0) {
    $delete_sql = "DELETE FROM ingredients WHERE ingredient_id = ? AND restaurant_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $ingredient_id, $restaurant_id);
    $delete_stmt->execute();
    $delete_stmt->close();
}

echo "<script>alert('Usage recorded successfully.');
      window.location.href = '../usage.php';</script>";

$conn->close();
exit();
?>
