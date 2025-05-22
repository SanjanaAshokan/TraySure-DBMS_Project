<?php
require 'db_connection.php';

// Check if the user is logged in and has a valid restaurant ID
session_start();  // Make sure the session is started
if (!isset($_SESSION['restaurant_id'])) {
    header("Location: ../index.html");  // Redirect to login page if not logged in
    exit();
}

$restaurant_id = $_SESSION['restaurant_id']; // Get the restaurant_id from the session

// Form data - assuming you're receiving them via POST method
$name = $_POST['name'];
$category = $_POST['category'];
$unit = $_POST['unit'];
$initial_qty = $_POST['initial_quantity'];
$expiry_date = $_POST['expiry_date'];
$cost_per_unit = $_POST['cost_per_unit'];
$date_added = date('Y-m-d'); // current date

// Step 1: Check if the ingredient already exists for this restaurant (same name, category, unit, expiry_date)
$sql_check = "SELECT ingredient_id, ingredient_group_id FROM ingredients
              WHERE name = '$name' AND category = '$category' AND unit = '$unit' 
              AND expiry_date = '$expiry_date' AND restaurant_id = '$restaurant_id'"; // Added restaurant_id to check

$result_check = $conn->query($sql_check);

if ($result_check->num_rows > 0) {
    // Ingredient exists, fetch the ingredient_group_id
    $row = $result_check->fetch_assoc();
    $ingredient_group_id = $row['ingredient_group_id'];

    // Step 2: Update the existing entry for this ingredient group, only for the current restaurant
    $sql_update = "UPDATE ingredients 
                   SET initial_quantity = initial_quantity + $initial_qty, 
                       cost_per_unit = '$cost_per_unit', 
                       expiry_date = '$expiry_date'
                   WHERE ingredient_group_id = $ingredient_group_id AND restaurant_id = '$restaurant_id'"; // Added restaurant_id to the WHERE clause

    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Ingredient updated successfully.');
              window.location.href = '../add_ingredient.php';</script>";
    } else {
        echo "Error updating ingredient: " . $conn->error;
    }

} else {
    // Step 3: Insert new ingredient with a new ingredient_group_id, associating it with the current restaurant
    $sql_insert = "INSERT INTO ingredients (name, category, unit, initial_quantity, expiry_date, cost_per_unit, date_added, restaurant_id)
                   VALUES ('$name', '$category', '$unit', $initial_qty, '$expiry_date', '$cost_per_unit', '$date_added', '$restaurant_id')"; // Added restaurant_id to the INSERT query

    if ($conn->query($sql_insert) === TRUE) {
        // Fetch the newly created ingredient_group_id
        $ingredient_group_id = $conn->insert_id;

        // Step 4: Update the ingredient_group_id in the same row to establish the group, still ensuring the restaurant_id matches
        $conn->query("UPDATE ingredients SET ingredient_group_id = $ingredient_group_id WHERE ingredient_id = $ingredient_group_id AND restaurant_id = '$restaurant_id'");

        echo "<script>alert('New ingredient added successfully.');
              window.location.href = '../add_ingredient.php';</script>";
    } else {
        echo "Error adding ingredient: " . $conn->error;
    }
}

$conn->close();
?>
