<?php
session_start();
require_once 'db_connection.php'; // Ensure this file sets up $conn

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get restaurant_id from session
    if (!isset($_SESSION['restaurant_id'])) {
        die("Unauthorized access.");
    }
    $restaurant_id = $_SESSION['restaurant_id'];

    // Sanitize and fetch inputs
    $ingredient_id = mysqli_real_escape_string($conn, $_POST['ingredient_id']);
    $date_donated = mysqli_real_escape_string($conn, $_POST['date_donated']);
    $quantity_donated = mysqli_real_escape_string($conn, $_POST['quantity_donated']);
    $recipient = mysqli_real_escape_string($conn, $_POST['recipient']);

    // Validation (optional but recommended)
    if (empty($ingredient_id) || empty($date_donated) || empty($quantity_donated) || empty($recipient)) {
        die("All fields are required.");
    }

    // Insert into Donations table
    $sql = "INSERT INTO Donations (ingredient_id, date_donated, quantity_donated, recipient, restaurant_id) 
            VALUES ('$ingredient_id', '$date_donated', '$quantity_donated', '$recipient', '$restaurant_id')";

    if (mysqli_query($conn, $sql)) {
        echo "Donation recorded successfully.";
        // Redirect if needed:
        // header("Location: donate.php?success=1");
        // exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }

    mysqli_close($conn);
} else {
    echo "Invalid request method.";
}
?>
