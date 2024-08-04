<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $num_players = $_POST['num_players'];
    createFixtures($conn, $num_players);
    echo "Fixtures created successfully!";
    header("Location: create_fixtures.php"); // Redirect to the same page or wherever appropriate
    exit();
}

function createFixtures($conn, $num_players) {
    $result = $conn->query("SELECT player_id FROM players LIMIT $num_players");
    $players = $result->fetch_all(MYSQLI_ASSOC);
    shuffle($players);
    
    $rounds = ['Pre-Quarter-finals', 'Quarter-finals', 'Semi-finals', 'Finals'];
    $matches = [];
    while (count($players) > 1) {
        $round = count($players) > 8 ? 'Pre-Quarter-finals' : (count($players) > 4 ? 'Quarter-finals' : (count($players) > 2 ? 'Semi-finals' : 'Finals'));
        $matches[] = [
            'round' => $round,
            'player1_id' => array_pop($players)['player_id'],
            'player2_id' => array_pop($players)['player_id'] ?? null
        ];
    }
    
    foreach ($matches as $match) {
        $stmt = $conn->prepare("INSERT INTO matches (round, player1_id, player2_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $match['round'], $match['player1_id'], $match['player2_id']);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Fixtures</title>
</head>
<body>
    <header>
        <h1>Create Fixtures</h1>
    </header>
    <section>
        <form method="POST" action="create_fixtures.php">
            <label for="num_players">Number of Players:</label>
            <input type="number" id="num_players" name="num_players" required>
            <button type="submit">Create Fixtures</button>
        </form>
    </section>
</body>
</html>
