<?php
require_once 'config.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form_action'] === 'add_player') {
        $player_name = $_POST['player_name'];
        $dob = $_POST['dob'];
        $age = $_POST['age'];
        $sex = $_POST['sex'];

        // Insert player data into the database
        $stmt = $conn->prepare("INSERT INTO players (player_name, dob, age, sex) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $player_name, $dob, $age, $sex);

        if ($stmt->execute()) {
            $message = "Player added successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    } elseif ($_POST['form_action'] === 'edit_player') {
        $player_id = $_POST['player_id'];
        $player_name = $_POST['player_name'];
        $dob = $_POST['dob'];
        $age = $_POST['age'];
        $sex = $_POST['sex'];

        // Update player data in the database
        $stmt = $conn->prepare("UPDATE players SET player_name = ?, dob = ?, age = ?, sex = ? WHERE player_id = ?");
        $stmt->bind_param("ssisi", $player_name, $dob, $age, $sex, $player_id);

        if ($stmt->execute()) {
            $message = "Player updated successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_player'])) {
    $player_id = $_GET['delete_player'];

    // Delete player from the database
    $stmt = $conn->prepare("DELETE FROM players WHERE player_id = ?");
    $stmt->bind_param("i", $player_id);

    if ($stmt->execute()) {
        $message = "Player deleted successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch players from the database
$players = [];
$result = $conn->query("SELECT * FROM players");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Player</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin: auto;
            overflow: hidden;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin-top: 10px;
            color: #666;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background-color: #5cb85c;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #4cae4c;
        }
        .message {
            text-align: center;
            margin-top: 20px;
            font-size: 1.2em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .edit, .delete {
            background-color: #5bc0de;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        .delete {
            background-color: #d9534f;
        }
    </style>
    <script>
        function calculateAge() {
            var dob = document.getElementById('dob').value;
            var dobDate = new Date(dob);
            var today = new Date();
            var age = today.getFullYear() - dobDate.getFullYear();
            var monthDiff = today.getMonth() - dobDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dobDate.getDate())) {
                age--;
            }
            document.getElementById('age').value = age;
        }

        function populateEditForm(player) {
            document.getElementById('player_id').value = player.player_id;
            document.getElementById('player_name').value = player.player_name;
            document.getElementById('dob').value = player.dob;
            document.getElementById('age').value = player.age;
            document.getElementById('sex').value = player.sex;
            document.getElementById('submit_button').innerText = 'Update Player';
            document.getElementById('form_action').value = 'edit_player';
        }

        function resetForm() {
            document.getElementById('player_id').value = '';
            document.getElementById('player_name').value = '';
            document.getElementById('dob').value = '';
            document.getElementById('age').value = '';
            document.getElementById('sex').value = '';
            document.getElementById('submit_button').innerText = 'Add Player';
            document.getElementById('form_action').value = 'add_player';
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Add New Player</h1>
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" id="player_id" name="player_id">
            <input type="hidden" id="form_action" name="form_action" value="add_player">
            <label for="player_name">Player Name:</label>
            <input type="text" id="player_name" name="player_name" required>
            <br>
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" required onchange="calculateAge()">
            <br>
            <label for="age">Age:</label>
            <input type="number" id="age" name="age" readonly required>
            <br>
            <label for="sex">Sex:</label>
            <select id="sex" name="sex" required>
                <option value="">Select Sex</option>
                <option value="M">Male</option>
                <option value="F">Female</option>
                <option value="U">Unspecified</option>
            </select>
            <br>
            <button type="submit" id="submit_button" name="add_player">Add Player</button>
        </form>

        <h2>List of Players</h2>
        <?php if (count($players) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Player ID</th>
                        <th>Player Name</th>
                        <th>Date of Birth</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $player): ?>
                        <tr>
                            <td><?php echo $player['player_id']; ?></td>
                            <td><?php echo $player['player_name']; ?></td>
                            <td><?php echo $player['dob']; ?></td>
                            <td><?php echo $player['age']; ?></td>
                            <td><?php echo $player['sex']; ?></td>
                            <td class="action-buttons">
                                <button class="edit" onclick='populateEditForm(<?php echo json_encode([
                                    'player_id' => $player['player_id'],
                                    'player_name' => $player['player_name'],
                                    'dob' => date('Y-m-d', strtotime($player['dob'])),
                                    'age' => $player['age'],
                                    'sex' => $player['sex']
                                ]); ?>)'>Edit</button>
                                <a class="delete" href="?delete_player=<?php echo $player['player_id']; ?>" onclick="return confirm('Are you sure you want to delete this player?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No players found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
