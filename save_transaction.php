<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $category_id = $_POST['category_id'];
    $date = $_POST['date'];
    $note = $_POST['note'];
    $u_id = $_SESSION['user_id']; // Logged-in user ki ID

    // Query mein user_id column ko include kiya gaya hai
    $stmt = $conn->prepare("INSERT INTO transactions (amount, category_id, note, date, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("dissi", $amount, $category_id, $note, $date, $u_id);

    if ($stmt->execute()) {
        header("Location: index.php?status=success");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>