<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $restaurant_id = $_POST['restaurant_id'];
    $restaurant_name = $_POST['restaurant_name'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $location = isset($_POST['location']) ? $_POST['location'] : 'Unknown';

    // Check for duplicate restaurant_id
    $checkStmt = $conn->prepare("SELECT * FROM users WHERE restaurant_id = ?");
    $checkStmt->bind_param("s", $restaurant_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Account with this Restaurant ID already exists. Please login.');
              window.location.href = '../signup.html';</script>";
        exit();
    }

    // Insert new account with location
    $stmt = $conn->prepare("INSERT INTO users (restaurant_id, restaurant_name, password, location) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $restaurant_id, $restaurant_name, $password, $location);

    if ($stmt->execute()) {
        echo "<script>alert('Signed up successfully! You can now login.');
              window.location.href = '../index.html';</script>";
        exit();
    } else {
        echo "<script>alert('Signup failed. Please try again.');
              window.location.href = '../signup.html';</script>";
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
