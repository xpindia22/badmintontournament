<?php
require_once 'config.php';

$assignedPlayers = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tournament_name'])) {
    $tournamentName = $_POST['tournament_name'];
    $selectedPlayers = isset($_POST['players']) ? $_POST['players'] : [];

    // Insert the tournament into the database
    $stmt = $conn->prepare("INSERT INTO tournaments (tournament_name) VALUES (?)");
    $stmt->bind_param("s", $tournamentName);
    $stmt->execute();
    $tournamentId = $stmt->insert_id;
    $stmt->close();

    // Shuffle players to randomize pools
    shuffle($selectedPlayers);

    // Split players into pools
    $poolA = [];
    $poolB = [];
    $pool = 'A';

    foreach ($selectedPlayers as $playerId) {
        if ($pool == 'A') {
            $poolA[] = $playerId;
            $pool = 'B';
        } else {
            $poolB[] = $playerId;
            $pool = 'A';
        }
    }

    // Handle odd number of players
    if (count($selectedPlayers) % 2 != 0) {
        // Give a bye to the last player in pool B
        $byePlayer = array_pop($poolB);
        $stmt = $conn->prepare("INSERT INTO tournament_players (tournament_id, player_id, pool) VALUES (?, ?, ?)");
        $poolName = "Bye";
        $stmt->bind_param("iis", $tournamentId, $byePlayer, $poolName);
        $stmt->execute();
        $stmt->close();
    }

    // Insert players into the tournament_players table
    $stmt = $conn->prepare("INSERT INTO tournament_players (tournament_id, player_id, pool) VALUES (?, ?, ?)");
    $assignedPlayers = [];

    foreach ($poolA as $playerId) {
        $poolName = 'A';
        $stmt->bind_param("iis", $tournamentId, $playerId, $poolName);
        $stmt->execute();
        $assignedPlayers[] = ['player_id' => $playerId, 'pool' => $poolName];
    }

    foreach ($poolB as $playerId) {
        $poolName = 'B';
        $stmt->bind_param("iis", $tournamentId, $playerId, $poolName);
        $stmt->execute();
        $assignedPlayers[] = ['player_id' => $playerId, 'pool' => $poolName];
    }

    $stmt->close();

    echo "<div class='success'>Tournament and players added successfully!</div>";
}

// Fetch players from the database who are not already assigned to any tournament
$players = $conn->query("
    SELECT p.player_id, p.player_name
    FROM players p
    LEFT JOIN tournament_players tp ON p.player_id = tp.player_id
    WHERE tp.player_id IS NULL
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Tournament</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        header {
            text-align: center;
            padding: 20px;
            background-color: #007bff;
            color: white;
        }
        section {
            margin: 20px auto;
            width: 900px;
            text-align: center;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        form {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input[type="text"] {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-group input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .checkbox-group {
            text-align: left;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            margin: 5px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        .select-all-container {
            text-align: left;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .success {
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('tournament_name').focus();

            document.getElementById('select-all').addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.checkbox-group input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        });

        function keepFocus() {
            setTimeout(function () {
                document.getElementById('tournament_name').focus();
            }, 0);
        }
    </script>
</head>
<body>
    <header>
        <h1>Setup Tournament</h1>
    </header>
    <section>
        <form method="POST" action="tournament_setup.php" onsubmit="keepFocus()">
            <div class="form-group">
                <label for="tournament_name">Tournament Name:</label>
                <input type="text" id="tournament_name" name="tournament_name" required>
            </div>
            <div class="form-group">
                <h2>Select Players</h2>
                <div class="select-all-container">
                    <label><input type="checkbox" id="select-all"> Select All</label>
                </div>
                <div class="checkbox-group">
                    <?php foreach ($players as $player): ?>
                        <label>
                            <input type="checkbox" name="players[]" value="<?= $player['player_id'] ?>"> <?= $player['player_name'] ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <input type="submit" value="Create Tournament">
            </div>
        </form>
    </section>
    <?php if (!empty($assignedPlayers)): ?>
    <section>
        <h2>Assigned Players</h2>
        <table>
            <thead>
                <tr>
                    <th>Player ID</th>
                    <th>Player Name</th>
                    <th>Pool</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignedPlayers as $assignedPlayer): 
                    $playerId = $assignedPlayer['player_id'];
                    $playerName = array_search($playerId, array_column($players, 'player_id')) !== false ? $players[array_search($playerId, array_column($players, 'player_id'))]['player_name'] : '';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($playerId); ?></td>
                    <td><?php echo htmlspecialchars($playerName); ?></td>
                    <td><?php echo htmlspecialchars($assignedPlayer['pool']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>
</body>
</html>
