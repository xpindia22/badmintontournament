<?php
require_once 'config.php';
$result = $conn->query("SELECT match_id, round, p1.player_name AS player1, p2.player_name AS player2, player1_score1, player1_score2, player1_score3, player2_score1, player2_score2, player2_score3, match_date FROM matches LEFT JOIN players p1 ON matches.player1_id = p1.player_id LEFT JOIN players p2 ON matches.player2_id = p2.player_id ORDER BY FIELD(round, 'Pre-Quarter-finals', 'Quarter-finals', 'Semi-finals', 'Finals')");

function getPlayers($conn) {
    $result = $conn->query("SELECT * FROM players");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getMatches($conn) {
    $result = $conn->query("SELECT * FROM matches");
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Tournament</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <h1>Badminton Tournament</h1>
    </header>

    <!-- Players section -->
    <section class="players">
        <h2>Players</h2>
        <?php
        $players = getPlayers($conn);
        foreach ($players as $player) {
            echo "<p>{$player['player_name']}</p>";
        }
        ?>
    </section>

    <!-- Matches section -->
    <section class="matches">
        <h2>Matches</h2>
        <table>
            <tr>
                <th>Round</th>
                <th>Player 1</th>
                <th>Player 2</th>
                <th>Scores</th>
                <th>Match Date</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['round']; ?></td>
                    <td><?php echo $row['player1']; ?></td>
                    <td><?php echo $row['player2']; ?></td>
                    <td><?php echo "{$row['player1_score1']}-{$row['player2_score1']}, {$row['player1_score2']}-{$row['player2_score2']}, {$row['player1_score3']}-{$row['player2_score3']}"; ?></td>
                    <td><?php echo $row['match_date']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </section>

    <footer>
        <p>© 2024 Badminton Tournament</p>
    </footer>
</body>
</html>
