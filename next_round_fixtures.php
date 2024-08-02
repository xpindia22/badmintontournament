<?php
require_once 'config.php';
require_once 'functions.php';

// Assuming the winners' IDs from the previous round are 1 and 2
$winner1_id = 1;
$winner2_id = 2;
$pool = 'A'; // Pool A
$round = 2; // Quarter-final round (assuming 1 was the previous round)

// Function to insert the next round fixture
function insertQuarterFinalFixture($conn, $round, $winner1_id, $winner2_id, $pool) {
    $stmt = $conn->prepare("INSERT INTO fixtures (round, player1_id, player2_id, pool) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("iiis", $round, $winner1_id, $winner2_id, $pool);
    if (!$stmt->execute()) {
        die("Execute statement failed: " . $stmt->error);
    }
    $stmt->close();
    echo "Quarter-final fixture inserted successfully.";
}

// Insert the quarter-final fixture
insertQuarterFinalFixture($conn, $round, $winner1_id, $winner2_id, $pool);

$conn->close();
?>
