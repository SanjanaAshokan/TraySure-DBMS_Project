<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['restaurant_id'])) {
        die("Unauthorized access.");
    }

    $restaurant_id = $_SESSION['restaurant_id'];
    $ingredient_name = trim($_POST['ingredient_name'] ?? '');
    $donation_quantity = floatval($_POST['donate_quantity'] ?? 0);
    $recipient = trim($_POST['ngo_name'] ?? '');

    if (empty($ingredient_name) || $donation_quantity <= 0 || empty($recipient)) {
        die("All fields are required and quantity must be greater than zero.");
    }

    // Fetch ingredient ID and remaining quantity
    $stmt = $conn->prepare("
        SELECT i.ingredient_id, i.initial_quantity,
               IFNULL(SUM(d.quantity_used), 0) AS used,
               IFNULL(SUM(f.quantity_wasted), 0) AS wasted
        FROM ingredients i
        LEFT JOIN daily_usage d ON i.ingredient_id = d.ingredient_id AND d.restaurant_id = ?
        LEFT JOIN food_waste f ON i.ingredient_id = f.ingredient_id AND f.restaurant_id = ?
        WHERE i.name = ? AND i.restaurant_id = ?
        GROUP BY i.ingredient_id
    ");
    $stmt->bind_param("iisi", $restaurant_id, $restaurant_id, $ingredient_name, $restaurant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$row = $result->fetch_assoc()) {
        die("Ingredient not found.");
    }

    $ingredient_id = $row['ingredient_id'];
    $initial_quantity = $row['initial_quantity'];
    $used = $row['used'];
    $wasted = $row['wasted'];

    $remaining = $initial_quantity - $used - $wasted;

    if ($donation_quantity > $remaining) {
        die("Cannot donate more than remaining quantity ($remaining units).");
    }

    // Step 1: Insert donation record
    $insert = $conn->prepare("
        INSERT INTO Donations (ingredient_id, date_donated, quantity_donated, recipient, restaurant_id)
        VALUES (?, CURDATE(), ?, ?, ?)
    ");
    $insert->bind_param("idsi", $ingredient_id, $donation_quantity, $recipient, $restaurant_id);

    if (!$insert->execute()) {
        die("Error saving donation: " . $conn->error);
    }

    // Step 2: Optionally record as daily usage (for quantity tracking)
    $usage = $conn->prepare("
        INSERT INTO daily_usage (restaurant_id, ingredient_id, quantity_used, date_used)
        VALUES (?, ?, ?, CURDATE())
    ");
    $usage->bind_param("iid", $restaurant_id, $ingredient_id, $donation_quantity);
    $usage->execute();

    // Step 3: Recalculate new remaining quantity
    $new_remaining = $remaining - $donation_quantity;

    // Step 4: If all quantity is donated, delete the ingredient
    if ($new_remaining <= 0) {
        $delete = $conn->prepare("DELETE FROM ingredients WHERE ingredient_id = ? AND restaurant_id = ?");
        $delete->bind_param("ii", $ingredient_id, $restaurant_id);
        $delete->execute();
        $delete->close();
    }

    echo "<script>alert('Donation recorded successfully.');
              window.location.href = '../donate.php';</script>";
    // Optional redirect:
    // header("Location: ../donate.php?success=1");
    // exit;

    $stmt->close();
    $insert->close();
    $usage->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request method.');
              window.location.href = '../donate.php';</script>";
}
?>
