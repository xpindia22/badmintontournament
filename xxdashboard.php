<?php
require_once 'config.php';

// Fetch players from the database
$players = [];
$sql = "SELECT * FROM players";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
}

// Fetch player assignments to tournaments
$player_assignments = [];
$sql = "
    SELECT p.player_id, p.player_name, GROUP_CONCAT(DISTINCT t.tournament_name ORDER BY t.tournament_name ASC SEPARATOR ', ') AS tournaments
    FROM players p
    LEFT JOIN category_players cp ON p.player_id = cp.player_id
    LEFT JOIN tournaments t ON t.tournament_id = cp.tournament_id
    GROUP BY p.player_id, p.player_name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $player_assignments[] = $row;
    }
}

// Fetch tournaments from the database
$tournaments = [];
$sql = "SELECT * FROM tournaments";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tournaments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        header {
            text-align: center;
            padding: 20px;
        }
        section {
            margin: 20px auto;
            width: 900px;
            text-align: center;
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
        .action-buttons {
            display: flex;
            justify-content: space-around;
        }
        .action-buttons a {
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #007bff;
            color: #007bff;
            border-radius: 5px;
        }
        .action-buttons a:hover {
            background-color: #007bff;
            color: white;
        }
    </style>
    <script>
        function deletePlayer(playerId) {
            if (confirm('Are you sure you want to delete this player?')) {
                window.location.href = 'dashboard.php?delete=' + playerId;
            }
        }

        function deleteAssignment(playerId, tournamentId) {
            if (confirm('Are you sure you want to delete this assignment?')) {
                window.location.href = 'dashboard.php?delete_assignment=' + playerId + '&tournament_id=' + tournamentId;
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Dashboard</h1>
    </header>
    <section>
        <!-- Display players -->
        <h2>Players</h2>
        <table>
            <thead>
                <tr>
                    <th>Serial No</th>
                    <th>Player ID</th>
                    <th>Player Name</th>
                    <th>Date of Birth</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $index => $player): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($player['player_id']); ?></td>
                    <td><?php echo htmlspecialchars($player['player_name']); ?></td>
                    <td><?php echo htmlspecialchars($player['dob']); ?></td>
                    <td><?php echo htmlspecialchars($player['age']); ?></td>
                    <td><?php echo htmlspecialchars($player['sex']); ?></td>
                    <td class="action-buttons">
                        <a href="edit_player.php?player_id=<?php echo $player['player_id']; ?>">Edit</a>
                        <a href="javascript:void(0);" onclick="deletePlayer(<?php echo $player['player_id']; ?>)">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Display player assignments -->
        <h2>Player Assignments to Tournaments</h2>
        <table>
            <thead>
                <tr>
                    <th>Player ID</th>
                    <th>Player Name</th>
                    <th>Tournaments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($player_assignments as $assignment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($assignment['player_id']); ?></td>
                    <td><?php echo htmlspecialchars($assignment['player_name']); ?></td>
                    <td><?php echo htmlspecialchars($assignment['tournaments']); ?></td>
                    <td class="action-buttons">
                        <a href="edit_assignment.php?player_id=<?php echo $assignment['player_id']; ?>">Edit</a>
                        <a href="javascript:void(0);" onclick="deleteAssignment(<?php echo $assignment['player_id']; ?>, '<?php echo htmlspecialchars($assignment['tournament_id']); ?>')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Display tournaments -->
        <h2>Tournaments</h2>
        <table>
            <thead>
                <tr>
                    <th>Tournament ID</th>
                    <th>Tournament Name</th>
                    <th>Age Criteria</th>
                    <th>Sex Criteria</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tournaments as $tournament): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tournament['tournament_id']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['tournament_name']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['age_criteria']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['sex_criteria']); ?></td>
                    <td class="action-buttons">
                        <a href="edit_tournament.php?tournament_id=<?php echo $tournament['tournament_id']; ?>">Edit</a>
                        <a href="delete_tournament.php?tournament_id=<?php echo $tournament['tournament_id']; ?>" onclick="return confirm('Are you sure you want to delete this tournament?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
