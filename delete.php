<?php
include 'db.php';

// Check karein ki user login hai
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $u_id = $_SESSION['user_id']; // Logged-in user ki ID

    // Sirf wahi row delete karein jo is user ki ho
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("ii", $id, $u_id);

        if ($stmt->execute()) {
            // Delete hone ke baad dashboard par wapas bhejein
            header("Location: index.php?msg=deleted");
        } else {
            echo "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing query: " . $conn->error;
    }
}
$conn->close();
?>