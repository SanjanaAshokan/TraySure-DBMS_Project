<?php
session_start();
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $restaurant_id = $_POST['restaurant_id'];
    $restaurant_name = $_POST['restaurant_name'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE restaurant_id = ? AND restaurant_name = ?");
    $stmt->bind_param("ss", $restaurant_id, $restaurant_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['logged_in'] = true;
            $_SESSION['restaurant_id'] = $restaurant_id;
            header("Location: ../dashboard.html");
            exit();
        } else {
            echo "<script>alert('Incorrect password. Please try again.'); window.location.href='../index.html';</script>";
        }
    } else {
        echo "<script>alert('Invalid login. Please check Restaurant ID and Name.'); window.location.href='../index.html';</script>";
    }
}
?>
