<?php
include 'db.php';

// File ka naam set karein (e.g., Transactions_2024-01-28.csv)
$filename = "Transactions_" . date('Y-m-d') . ".csv";

// Browser ko bataiye ki ye file download karni hai
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// File pointer open karein
$output = fopen('php://output', 'w');

// Table ki headings (Columns) likhein
fputcsv($output, array('Date', 'Category', 'Note', 'Amount', 'Type'));

// Database se data fetch karein
$query = "SELECT t.date, c.name, t.note, t.amount, c.type 
          FROM transactions t 
          JOIN categories c ON t.category_id = c.id 
          ORDER BY t.date DESC";
$result = $conn->query($query);

// Har row ko CSV mein likhein
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>