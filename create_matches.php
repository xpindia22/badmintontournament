<?php
require_once 'config.php';

function fetchPlayers($tournament_id, $pool) {
    global $conn;
    $players = [];
    $sql = "SELECT player_id, player_name FROM pools WHERE tournament_id = ? AND pool = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tournament_id, $pool);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $players[] = $row;
        }
    }
    return $players;
}

// Fetch matches from the database
function fetchMatches($tournament_id) {
    global $conn;
    $matches = [];
    $sql = "SELECT * FROM matches WHERE tournament_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }
    }
    return $matches;
}

if (isset($_GET['tournament_id'])) {
    $tournament_id = $_GET['tournament_id'];

    // Fetch existing matches
    $matches = fetchMatches($tournament_id);

    // Fetch players from Pool A and Pool B
    $pools = ['A', 'B'];
    $newMatches = [];

    foreach ($pools as $pool) {
        $players = fetchPlayers($tournament_id, $pool);

        // Create new matches for newly added players
        $existingMatchPlayers = array_column($matches, 'player1_id');
        $existingMatchPlayers = array_merge($existingMatchPlayers, array_column($matches, 'player2_id'));

        $newPlayers = array_filter($players, function($player) use ($existingMatchPlayers) {
            return !in_array($player['player_id'], $existingMatchPlayers);
        });

        $newPlayers = array_values($newPlayers); // Reindex array

        for ($i = 0; $i < count($newPlayers); $i += 2) {
            if (isset($newPlayers[$i + 1])) {
                $newMatches[] = [
                    'tournament_id' => $tournament_id,
                    'player1_id' => $newPlayers[$i]['player_id'],
                    'player1_name' => $newPlayers[$i]['player_name'],
                    'player2_id' => $newPlayers[$i + 1]['player_id'],
                    'player2_name' => $newPlayers[$i + 1]['player_name'],
                    'pool' => $pool
                ];
            }
        }
    }

    // Insert new matches into the database
    if (!empty($newMatches)) {
        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO matches (tournament_id, player1_id, player1_name, player2_id, player2_name, pool) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            foreach ($newMatches as $match) {
                $stmt->bind_param("iissss", $match['tournament_id'], $match['player1_id'], $match['player1_name'], $match['player2_id'], $match['player2_name'], $match['pool']);
                $stmt->execute();
            }

            $conn->commit();
            // Fetch all matches including newly created ones
            $matches = fetchMatches($tournament_id);
        } catch (Exception $e) {
            $conn->rollback();
            echo "Failed to create matches: " . $e->getMessage();
            exit();
        }
    }
}

// Handle score submission and determine winner
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['match_id'])) {
    $match_id = $_POST['match_id'];
    $set1_player1 = $_POST['set1_player1'];
    $set1_player2 = $_POST['set1_player2'];
    $set2_player1 = $_POST['set2_player1'];
    $set2_player2 = $_POST['set2_player2'];
    $set3_player1 = $_POST['set3_player1'];
    $set3_player2 = $_POST['set3_player2'];

    $sql = "UPDATE matches SET set1_player1 = ?, set1_player2 = ?, set2_player1 = ?, set2_player2 = ?, set3_player1 = ?, set3_player2 = ? WHERE match_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiiii", $set1_player1, $set1_player2, $set2_player1, $set2_player2, $set3_player1, $set3_player2, $match_id);
    $stmt->execute();

    // Determine the winner
    $player1_sets_won = 0;
    $player2_sets_won = 0;

    if ($set1_player1 > $set1_player2) $player1_sets_won++;
    if ($set1_player2 > $set1_player1) $player2_sets_won++;
    if ($set2_player1 > $set2_player2) $player1_sets_won++;
    if ($set2_player2 > $set2_player1) $player2_sets_won++;
    if ($set3_player1 > $set3_player2) $player1_sets_won++;
    if ($set3_player2 > $set3_player1) $player2_sets_won++;

    $winner_id = ($player1_sets_won > $player2_sets_won) ? $_POST['player1_id'] : $_POST['player2_id'];

    // Update match with the winner
    $sql = "UPDATE matches SET winner_id = ? WHERE match_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $winner_id, $match_id);
    $stmt->execute();

    echo "Scores and winner updated successfully.";
}

// Fetch matches with scores and winners
$matches = fetchMatches($tournament_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Matches</title>
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
            width: 900px;
            text-align: center;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
        form {
            margin-bottom: 20px;
        }
        input[type="number"] {
            width: 50px;
            padding: 5px;
            font-size: 16px;
            text-align: center;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .winner {
            background-color: green;
            color: white;
        }
        .loser {
            background-color: lightblue;
        }
    </style>
</head>
<body>
    <header>
        <h1>Create Matches and Enter Scores</h1>
    </header>
    <section>
        <h2>Matches for Tournament ID: <?php echo htmlspecialchars($tournament_id); ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Match ID</th>
                    <th>Player 1</th>
                    <th>Player 2</th>
                    <th>Pool</th>
                    <th>Set 1</th>
                    <th>Set 2</th>
                    <th>Set 3</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $match): ?>
                <?php
                    $winner_class = '';
                    if (isset($match['winner_id'])) {
                        $winner_class = ($match['winner_id'] == $match['player1_id']) ? 'winner' : 'loser';
                    }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($match['match_id']); ?></td>
                    <td class="<?php echo $winner_class; ?>"><?php echo htmlspecialchars($match['player1_name']); ?></td>
                    <td class="<?php echo $winner_class == 'winner' ? 'loser' : 'winner'; ?>"><?php echo htmlspecialchars($match['player2_name']); ?></td>
                    <td><?php echo htmlspecialchars($match['pool']); ?></td>
                    <td>
                        <form method="POST" action="create_matches.php?tournament_id=<?php echo $tournament_id; ?>">
                            <input type="hidden" name="match_id" value="<?php echo $match['match_id']; ?>">
                            <input type="hidden" name="player1_id" value="<?php echo $match['player1_id']; ?>">
                            <input type="hidden" name="player2_id" value="<?php echo $match['player2_id']; ?>">
                            <input type="number" name="set1_player1" value="<?php echo $match['set1_player1']; ?>" required>
                            <input type="number" name="set1_player2" value="<?php echo $match['set1_player2']; ?>" required>
                    </td>
                    <td>
                            <input type="number" name="set2_player1" value="<?php echo $match['set2_player1']; ?>" required>
                            <input type="number" name="set2_player2" value="<?php echo $match['set2_player2']; ?>" required>
                    </td>
                    <td>
                            <input type="number" name="set3_player1" value="<?php echo $match['set3_player1']; ?>" required>
                            <input type="number" name="set3_player2" value="<?php echo $match['set3_player2']; ?>" required>
                    </td>
                    <td>
                            <button type="submit">Submit Scores</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
