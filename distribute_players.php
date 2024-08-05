<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tournament_id'])) {
    $tournament_id = $_POST['tournament_id'];

    // Fetch players assigned to the tournament
    $players = [];
    $sql = "SELECT p.player_id, p.player_name FROM players p
            JOIN category_players cp ON p.player_id = cp.player_id
            WHERE cp.tournament_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $players[] = $row;
        }
    }

    // Distribute players into Pool A and Pool B
    shuffle($players);
    $poolA = [];
    $poolB = [];
    foreach ($players as $index => $player) {
        if ($index % 2 == 0) {
            $poolA[] = $player;
        } else {
            $poolB[] = $player;
        }
    }

    // Handle odd number of players by giving a bye
    $byePlayer = null;
    if (count($poolA) > count($poolB)) {
        $byePlayer = array_pop($poolA);
    } elseif (count($poolB) > count($poolA)) {
        $byePlayer = array_pop($poolB);
    }

    // Insert pools and bye player into the database
    $conn->begin_transaction();
    try {
        $sql = "DELETE FROM pools WHERE tournament_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();

        $sql = "INSERT INTO pools (tournament_id, pool, player_id, player_name) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        foreach ($poolA as $player) {
            $pool = 'A';
            $stmt->bind_param("isis", $tournament_id, $pool, $player['player_id'], $player['player_name']);
            $stmt->execute();
        }

        foreach ($poolB as $player) {
            $pool = 'B';
            $stmt->bind_param("isis", $tournament_id, $pool, $player['player_id'], $player['player_name']);
            $stmt->execute();
        }

        if ($byePlayer) {
            $pool = 'Bye';
            $stmt->bind_param("isis", $tournament_id, $pool, $byePlayer['player_id'], $byePlayer['player_name']);
            $stmt->execute();
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Failed to distribute players: " . $e->getMessage();
        exit();
    }

    header("Location: create_matches.php?tournament_id=$tournament_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribute Players</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 10px 0;
            text-align: center;
        }
        section {
            margin: 20px auto;
            width: 600px;
            text-align: center;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2, h3 {
            margin: 0 0 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <header>
        <h1>Distribute Players</h1>
    </header>
    <section>
        <h2>Players have been distributed into pools.</h2>
        <h3>Proceed to create matches</h3>
        <a href="create_matches.php?tournament_id=<?= htmlspecialchars($tournament_id) ?>">Create Matches</a>
    </section>
</body>
</html>
