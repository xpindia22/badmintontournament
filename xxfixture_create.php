<?php
require_once 'config.php';


function createFixtures($conn, $players, $round) {
    // Shuffle players array to randomize matchups
    shuffle($players);

    // Pair players and create fixtures
    for ($i = 0; $i < count($players); $i += 2) {
        // Check if there's a pair to match
        if (isset($players[$i + 1])) {
            $player1_id = $players[$i];
            $player2_id = $players[$i + 1];

            $sql = "INSERT INTO matches (round, player1_id, player2_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                return;
            }

            $stmt->bind_param('sii', $round, $player1_id, $player2_id);
            $stmt->execute();
            if ($stmt->error) {
                echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            } else {
                echo "Match between Player ID $player1_id and Player ID $player2_id added for $round.<br/>";
            }
        }
    }
}

// Example usage:
$players = [1, 2, 3, 4, 5, 6, 7, 8]; // Array of player IDs
$round = 'Quarter-finals';
createFixtures($conn, $players, $round);

?>