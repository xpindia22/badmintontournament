<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $match_id = $_POST['match_id'];
    $player1_set1 = $_POST['player1_set1'];
    $player1_set2 = $_POST['player1_set2'];
    $player1_set3 = $_POST['player1_set3'];
    $player2_set1 = $_POST['player2_set1'];
    $player2_set2 = $_POST['player2_set2'];
    $player2_set3 = $_POST['player2_set3'];

    $sql = "UPDATE matches SET player1_score1 = ?, player1_score2 = ?, player1_score3 = ?, player2_score1 = ?, player2_score2 = ?, player2_score3 = ? WHERE match_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiiii", $player1_set1, $player1_set2, $player1_set3, $player2_set1, $player2_set2, $player2_set3, $match_id);
    $stmt->execute();

    echo "Scores updated successfully!";
    header("Location: enter_scores.php"); // Redirect to the same page or wherever appropriate
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Scores</title>
</head>
<body>
    <header>
        <h1>Enter Scores</h1>
    </header>
    <section>
        <form method="POST" action="enter_scores.php">
            <label for="match_id">Select Match:</label>
            <select id="match_id" name="match_id">
                <?php
                require_once 'config.php';
                $result = $conn->query("SELECT match_id, p1.player_name AS player1, p2.player_name AS player2 FROM matches LEFT JOIN players p1 ON matches.player1_id = p1.player_id LEFT JOIN players p2 ON matches.player2_id = p2.player_id");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['match_id']}'>{$row['player1']} vs {$row['player2']}</option>";
                }
                ?>
            </select><br><br>
            <label for="player1_set1">Player 1 Set 1:</label>
            <input type="number" id="player1_set1" name="player1_set1" required><br>
            <label for="player1_set2">Player 1 Set 2:</label>
            <input type="number" id="player1_set2" name="player1_set2" required><br>
            <label for="player1_set3">Player 1 Set 3:</label>
            <input type="number" id="player1_set3" name="player1_set3" required><br><br>
            <label for="player2_set1">Player 2 Set 1:</label>
            <input type="number" id="player2_set1" name="player2_set1" required><br>
            <label for="player2_set2">Player 2 Set 2:</label>
            <input type="number" id="player2_set2" name="player2_set2" required><br>
            <label for="player2_set3">Player 2 Set 3:</label>
            <input type="number" id="player2_set3" name="player2_set3" required><br><br>
            <button type="submit">Submit Scores</button>
        </form>
    </section>
</body>
</html>
