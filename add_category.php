<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cat_name = $_POST['cat_name'];
    $cat_type = $_POST['cat_type'];
    $u_id = $_SESSION['user_id']; // Logged-in user ki ID

    // Query mein user_id column shamil hai
    $stmt = $conn->prepare("INSERT INTO categories (name, type, user_id) VALUES (?, ?, ?)");
    
    if ($stmt) {
        // "ssi" ka matlab: string, string, integer
        $stmt->bind_param("ssi", $cat_name, $cat_type, $u_id);

        if ($stmt->execute()) {
            header("Location: index.php?msg=CategoryAdded");
        } else {
            echo "Error executing: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing: " . $conn->error;
    }
}
$conn->close();
?>