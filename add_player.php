<?php
require_once 'config.php';

function normalizeSex($sex) {
    $maleRepresentations = ['m', 'male', 'M', 'B', 'Boys', 'Men', 'men', 'boys'];
    $femaleRepresentations = ['f', 'female', 'F', 'G', 'g', 'girls', 'Girls'];

    if (in_array(strtolower($sex), array_map('strtolower', $maleRepresentations))) {
        return 'Male';
    }
    if (in_array(strtolower($sex), array_map('strtolower', $femaleRepresentations))) {
        return 'Female';
    }
    return null;
}

// Function to get eligible categories based on age and sex
function getEligibleCategories($age, $sex) {
    $categories = [];
    if ($age < 11) {
        $categories[] = 'Under 11';
    } else if ($age < 13) {
        $categories[] = 'Under 13';
    } else if ($age < 15) {
        $categories[] = 'Under 15';
    } else if ($age < 17) {
        $categories[] = 'Under 17';
    } else if ($age < 19) {
        $categories[] = 'Under 19';
    } else {
        $categories[] = 'Open';
    }

    if ($sex == 'Male') {
        $categories = array_map(function($category) {
            return $category . ' Boys';
        }, $categories);
    } else if ($sex == 'Female') {
        $categories = array_map(function($category) {
            return $category . ' Girls';
        }, $categories);
    }
    return $categories;
}

// Fetch tournaments from the database
function getTournaments() {
    global $conn;
    $tournaments = [];
    $sql = "SELECT tournament_id, tournament_name FROM tournaments";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tournaments[] = $row;
        }
    }
    return $tournaments;
}

$tournaments = getTournaments();

// Handle adding a new player.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_player_name'], $_POST['new_player_dob'], $_POST['new_player_sex'], $_POST['tournament_id'])) {
    $player_name = $_POST['new_player_name'];
    $dob = $_POST['new_player_dob'];
    $sex = normalizeSex($_POST['new_player_sex']);
    $age = date_diff(date_create($dob), date_create('today'))->y;
    $tournament_id = $_POST['tournament_id'];

    if ($sex === null) {
        echo "<script>alert('Invalid sex value!'); window.location.href = 'add_player.php';</script>";
        exit();
    }

    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO players (player_name, dob, age, sex) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $player_name, $dob, $age, $sex);
        $stmt->execute();
        $player_id = $stmt->insert_id;

        // Assign player to the selected tournament
        $sql = "INSERT INTO category_players (tournament_id, player_id, player_name, timestamp) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $tournament_id, $player_id, $player_name);
        $stmt->execute();

        $conn->commit();
        echo "<script>alert('Player added and assigned to tournament successfully!'); window.location.href = 'add_player.php';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Failed to add player: " . $e->getMessage() . "'); window.location.href = 'add_player.php';</script>";
    }
}

// Handle updating player details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_player_id'])) {
    $player_id = $_POST['update_player_id'];
    $player_name = $_POST['update_player_name'];
    $dob = $_POST['update_player_dob'];
    $sex = normalizeSex($_POST['update_player_sex']);
    $age = date_diff(date_create($dob), date_create('today'))->y;

    if ($sex === null) {
        echo "<script>alert('Invalid sex value!'); window.location.href = 'add_player.php';</script>";
        exit();
    }

    $sql = "UPDATE players SET player_name = ?, dob = ?, age = ?, sex = ? WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisi", $player_name, $dob, $age, $sex, $player_id);
    $stmt->execute();
    echo "<script>alert('Player updated successfully!'); window.location.href = 'add_player.php';</script>";
    exit();
}

// Handle assigning existing player to a tournament
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['player_id'], $_POST['tournament_id'])) {
    $player_id = $_POST['player_id'];
    $tournament_id = $_POST['tournament_id'];

    // Check if player already assigned to the tournament
    $sql = "SELECT * FROM category_players WHERE player_id = ? AND tournament_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $player_id, $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Fetch player details
        $sql = "SELECT player_name FROM players WHERE player_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $player = $result->fetch_assoc();
        $player_name = $player['player_name'];

        $sql = "INSERT INTO category_players (tournament_id, player_id, player_name, timestamp) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $tournament_id, $player_id, $player_name);
        $stmt->execute();
        echo "<script>alert('Player assigned to tournament successfully!'); window.location.href = 'add_player.php';</script>";
    } else {
        echo "<script>alert('Player is already assigned to this tournament!'); window.location.href = 'add_player.php';</script>";
    }
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
    $tournament_id = $_GET['tournament_id'];
    $sql = "DELETE FROM category_players WHERE player_id = ? AND tournament_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $player_id, $tournament_id);
    $stmt->execute();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Player and Assign to Tournament</title>
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
        <h1>Manage Player's and Tournament's</h1>
    </header>
    <section>
        <!-- Form to add new player -->
        <form method="POST" action="add_player.php" onsubmit="keepFocus()">
            <label for="new_player_name">Register New Player:</label>
            <input type="text" id="new_player_name" name="new_player_name" required>
            <label for="new_player_dob">Date of Birth:</label>
            <input type="date" id="new_player_dob" name="new_player_dob" required>
            <label for="new_player_sex">Sex:</label>
            <select id="new_player_sex" name="new_player_sex" required>
                <option value="M">Male</option>
                <option value="F">Female</option>
                <option value="Other">Other</option>
            </select>
            <label for="tournament_id">Select Tournament:</label>
            <select id="tournament_id" name="tournament_id" required>
                <option value="">Select Tournament</option>
                <?php foreach ($tournaments as $tournament): ?>
                    <option value="<?= $tournament['tournament_id'] ?>"><?= $tournament['tournament_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Register New Player and Assign to Tournament</button>
        </form>

        <!-- Form to assign existing player to a tournament -->
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
                    <td><?php echo htmlspecialchars($assignment['tournaments']); ?></td>
                    <td class="action-buttons">
                        <a href="edit_assignment.php?player_id=<?php echo $assignment['player_id']; ?>">Edit</a>
                        <a href="add_player.php?delete_assignment=<?php echo $assignment['player_id']; ?>&tournament_id=<?php echo $assignment['tournament_id']; ?>" onclick="return confirm('Are you sure you want to delete this assignment?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
