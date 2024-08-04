<?php
require_once 'config.php';

// Handle adding a new player and assigning to tournament
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_player_name'], $_POST['new_player_dob'], $_POST['new_player_sex'], $_POST['new_tournament_id'])) {
    $player_name = $_POST['new_player_name'];
    $dob = $_POST['new_player_dob'];
    $sex = $_POST['new_player_sex'];
    $tournament_id = $_POST['new_tournament_id'];
    $age = date_diff(date_create($dob), date_create('today'))->y;

    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO players (player_name, dob, age, sex) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $player_name, $dob, $age, $sex);
        $stmt->execute();
        $player_id = $stmt->insert_id;

        $sql = "INSERT INTO tournament_players (tournament_id, player_id, pool) VALUES (?, ?, 'A')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $tournament_id, $player_id);
        $stmt->execute();

        $conn->commit();
        echo "Player added and assigned to tournament successfully!";
        header("Location: add_player.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Failed to add player: " . $e->getMessage();
    }
}

// Handle updating player details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_player_id'])) {
    $player_id = $_POST['update_player_id'];
    $player_name = $_POST['update_player_name'];
    $dob = $_POST['update_player_dob'];
    $sex = $_POST['update_player_sex'];
    $age = date_diff(date_create($dob), date_create('today'))->y;

    $sql = "UPDATE players SET player_name = ?, dob = ?, age = ?, sex = ? WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisi", $player_name, $dob, $age, $sex, $player_id);
    $stmt->execute();
    echo "Player updated successfully!";
    header("Location: add_player.php");
    exit();
}

// Handle assigning existing player to tournament
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['player_id'], $_POST['tournament_id'])) {
    $player_id = $_POST['player_id'];
    $tournament_id = $_POST['tournament_id'];

    $sql = "SELECT * FROM tournament_players WHERE player_id = ? AND tournament_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $player_id, $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $sql = "INSERT INTO tournament_players (tournament_id, player_id, pool) VALUES (?, ?, 'A')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $tournament_id, $player_id);
        $stmt->execute();
        echo "Player assigned to tournament successfully!";
    } else {
        echo "Player is already assigned to this tournament!";
    }

    header("Location: add_player.php");
    exit();
}

// Handle deleting player
if (isset($_GET['delete'])) {
    $player_id = $_GET['delete'];
    $sql = "DELETE FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    header("Location: add_player.php");
    exit();
}

// Handle deleting player assignment
if (isset($_GET['delete_assignment'])) {
    $player_id = $_GET['delete_assignment'];
    $sql = "DELETE FROM tournament_players WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    header("Location: add_player.php");
    exit();
}

// Handle tournament creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tournament_name'])) {
    $tournament_name = $_POST['tournament_name'];
    $sql = "INSERT INTO tournaments (tournament_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tournament_name);
    $stmt->execute();
    echo "Tournament created successfully!";
    header("Location: add_player.php");
    exit();
}

// Fetch players from the database
$players = [];
$sql = "SELECT * FROM players";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
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

// Fetch player assignments to tournaments
$player_assignments = [];
$sql = "
    SELECT p.player_id, p.player_name, GROUP_CONCAT(DISTINCT t.tournament_name ORDER BY t.tournament_name ASC SEPARATOR ', ') AS tournaments
    FROM players p
    LEFT JOIN tournament_players tp ON p.player_id = tp.player_id
    LEFT JOIN tournaments t ON tp.tournament_id = t.tournament_id
    GROUP BY p.player_id, p.player_name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $player_assignments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Player and Create Tournament</title>
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
        form {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('new_player_name').focus();
        });

        function keepFocus() {
            setTimeout(function () {
                document.getElementById('new_player_name').focus();
            }, 0);
        }

        function updatePlayer(playerId) {
            var row = document.querySelector('tr[data-player-id="' + playerId + '"]');
            var player_name = row.querySelector('.player_name').innerText;
            var dob = row.querySelector('.dob').innerText;
            var sex = row.querySelector('.sex').innerText;

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'add_player.php';

            var inputPlayerId = document.createElement('input');
            inputPlayerId.type = 'hidden';
            inputPlayerId.name = 'update_player_id';
            inputPlayerId.value = playerId;
            form.appendChild(inputPlayerId);

            var inputPlayerName = document.createElement('input');
            inputPlayerName.type = 'hidden';
            inputPlayerName.name = 'update_player_name';
            inputPlayerName.value = player_name;
            form.appendChild(inputPlayerName);

            var inputDob = document.createElement('input');
            inputDob.type = 'hidden';
            inputDob.name = 'update_player_dob';
            inputDob.value = dob;
            form.appendChild(inputDob);

            var inputSex = document.createElement('input');
            inputSex.type = 'hidden';
            inputSex.name = 'update_player_sex';
            inputSex.value = sex;
            form.appendChild(inputSex);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</head>
<body>
    <header>
        <h1>Add Player and Create Tournament</h1>
    </header>
    <section>
        <!-- Form to add new player and assign to tournament -->
        <form method="POST" action="add_player.php" onsubmit="keepFocus()">
            <label for="new_player_name">Player Name:</label>
            <input type="text" id="new_player_name" name="new_player_name" required>
            <label for="new_player_dob">Date of Birth:</label>
            <input type="date" id="new_player_dob" name="new_player_dob" required>
            <label for="new_player_sex">Sex:</label>
            <select id="new_player_sex" name="new_player_sex" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
            <label for="new_tournament_id">Select Tournament:</label>
            <select id="new_tournament_id" name="new_tournament_id" required>
                <option value="">Select Tournament</option>
                <?php foreach ($tournaments as $tournament): ?>
                    <option value="<?= $tournament['tournament_id'] ?>"><?= $tournament['tournament_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Add Player and Assign to Tournament</button>
        </form>

        <!-- Form to assign existing player to tournament -->
        <form method="POST" action="add_player.php" onsubmit="keepFocus()">
            <label for="player_id">Select Player:</label>
            <select id="player_id" name="player_id" required>
                <option value="">Select Player</option>
                <?php foreach ($players as $player): ?>
                    <option value="<?= $player['player_id'] ?>"><?= $player['player_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <label for="tournament_id">Select Tournament:</label>
            <select id="tournament_id" name="tournament_id" required>
                <option value="">Select Tournament</option>
                <?php foreach ($tournaments as $tournament): ?>
                    <option value="<?= $tournament['tournament_id'] ?>"><?= $tournament['tournament_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Assign Player to Tournament</button>
        </form>

        <!-- Form to create tournament -->
        <form method="POST" action="add_player.php">
            <label for="tournament_name">Tournament Name:</label>
            <input type="text" id="tournament_name" name="tournament_name" required>
            <button type="submit">Create Tournament</button>
        </form>

        <!-- Display players -->
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
                <tr data-player-id="<?php echo $player['player_id']; ?>">
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($player['player_id']); ?></td>
                    <td contenteditable="true" class="player_name"><?php echo htmlspecialchars($player['player_name']); ?></td>
                    <td contenteditable="true" class="dob"><?php echo htmlspecialchars($player['dob']); ?></td>
                    <td><?php echo htmlspecialchars($player['age']); ?></td>
                    <td contenteditable="true" class="sex"><?php echo htmlspecialchars($player['sex']); ?></td>
                    <td class="action-buttons">
                        <a href="#" onclick="updatePlayer(<?php echo $player['player_id']; ?>)">Edit</a>
                        <a href="add_player.php?delete=<?php echo $player['player_id']; ?>" onclick="return confirm('Are you sure you want to delete this player?');">Delete</a>
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
                    <td contenteditable="true"><?php echo htmlspecialchars($assignment['tournaments']); ?></td>
                    <td class="action-buttons">
                        <a href="edit_assignment.php?player_id=<?php echo $assignment['player_id']; ?>">Edit</a>
                        <a href="add_player.php?delete_assignment=<?php echo $assignment['player_id']; ?>" onclick="return confirm('Are you sure you want to delete this assignment?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
