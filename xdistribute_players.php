<?php
require_once 'config.php';

// Fetch all players who have not been assigned to a pool yet
$result = mysqli_query($conn, "SELECT id, player_name, age, sex FROM players WHERE pool IS NULL");

$players = [];
while ($row = mysqli_fetch_assoc($result)) {
    $players[] = $row;
}

shuffle($players);  // Shuffle players to distribute them randomly

$poolA = [];
$poolB = [];
$byePlayer = null;

foreach ($players as $index => $player) {
    if ($index % 2 == 0) {
        $poolA[] = $player;
    } else {
        $poolB[] = $player;
    }
}

// If there's an odd number of players, assign the last player a bye
if (count($players) % 2 != 0) {
    $byePlayer = array_pop($poolB);
}

// Assign players to pools in the database
foreach ($poolA as $player) {
    mysqli_query($conn, "UPDATE players SET pool = 'A' WHERE id = {$player['id']}");
}

foreach ($poolB as $player) {
    mysqli_query($conn, "UPDATE players SET pool = 'B' WHERE id = {$player['id']}");
}

// Move the player with a bye to the next level (if any)
if ($byePlayer) {
    // Insert code here to move the player to the next level if needed
    echo "Player with ID {$byePlayer['id']} has a bye and is moved to the next level.<br>";
}

echo "Players have been distributed into Pool A and Pool B.";

mysqli_close($conn);
?>
