<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $player_id = $_GET['id'];
    $sql = "SELECT player_name FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $stmt->bind_result($player_name);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $player_id = $_POST['player_id'];
    $player_name = $_POST['player_name'];
    $sql = "UPDATE players SET player_name = ? WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $player_name, $player_id);
    $stmt->execute();
    header("Location: add_player.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Player</title>
</head>
<body>
    <header>
        <h1>Edit Player</h1>
    </header>
    <section>
        <form method="POST" action="edit_player.php">
            <input type="hidden" name="player_id" value="<?php echo $player_id; ?>">
            <label for="player_name">Player Name:</label>
            <input type="text" id="player_name" name="player_name" value="<?php echo htmlspecialchars($player_name); ?>" required>
            <button type="submit">Update Player</button>
        </form>
    </section>
</body>
</html>
