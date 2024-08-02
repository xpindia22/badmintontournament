<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $player_name = $_POST['player_name'];
    $sql = "INSERT INTO players (player_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $player_name);
    $stmt->execute();
    echo "Player added successfully!";
    header("Location: add_player.php"); // Redirect to the same page or wherever appropriate
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Player</title>
</head>
<body>
    <header>
        <h1>Add Player</h1>
    </header>
    <section>
        <form method="POST" action="add_player.php">
            <label for="player_name">Player Name:</label>
            <input type="text" id="player_name" name="player_name" required>
            <button type="submit">Add Player</button>
        </form>
    </section>
</body>
</html>
