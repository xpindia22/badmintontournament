<?php
require_once 'config.php';

$message = "";

// Fetch championships from the database
$query = "SELECT championship_id, championship_name FROM championships";
$result = $conn->query($query);

$championships = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $championships[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form_action'] === 'assign_championship') {
        $player_id = $_POST['player_id'];
        $championship_id = $_POST['championship_id'];

        // Assign player to championship
        $stmt = $conn->prepare("INSERT INTO player_championship (player_id, championship_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $player_id, $championship_id);

        if ($stmt->execute()) {
            $message = "Player assigned to championship successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    } elseif ($_POST['form_action'] === 'edit_assignment') {
        $assignment_id = $_POST['assignment_id'];
        $player_id = $_POST['player_id'];
        $championship_id = $_POST['championship_id'];

        // Update assignment
        $stmt = $conn->prepare("UPDATE player_championship SET player_id = ?, championship_id = ? WHERE id = ?");
        $stmt->bind_param("iii", $player_id, $championship_id, $assignment_id);

        if ($stmt->execute()) {
            $message = "Assignment updated successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_assignment'])) {
    $assignment_id = $_GET['delete_assignment'];

    // Delete assignment
    $stmt = $conn->prepare("DELETE FROM player_championship WHERE id = ?");
    $stmt->bind_param("i", $assignment_id);

    if ($stmt->execute()) {
        $message = "Assignment deleted successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch players from the database
$query = "SELECT player_id, player_name FROM players";
$result = $conn->query($query);

$players = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
}

// Fetch assigned championships
$query = "SELECT pc.id, p.player_name, c.championship_name 
          FROM player_championship pc
          JOIN players p ON pc.player_id = p.player_id
          JOIN championships c ON pc.championship_id = c.championship_id";
$result = $conn->query($query);

$assignments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Championship</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 70%;
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
        function populateEditForm(assignment) {
            document.getElementById('assignment_id').value = assignment.id;
            document.getElementById('player_id').value = assignment.player_id;
            document.getElementById('championship_id').value = assignment.championship_id;
            document.getElementById('submit_button').innerText = 'Update Assignment';
            document.getElementById('form_action').value = 'edit_assignment';
        }

        function resetForm() {
            document.getElementById('assignment_id').value = '';
            document.getElementById('player_id').value = '';
            document.getElementById('championship_id').value = '';
            document.getElementById('submit_button').innerText = 'Assign Championship';
            document.getElementById('form_action').value = 'assign_championship';
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Assign Championship</h1>
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" id="assignment_id" name="assignment_id">
            <input type="hidden" id="form_action" name="form_action" value="assign_championship">
            <label for="player_id">Select Player:</label>
            <select id="player_id" name="player_id" required>
                <option value="">Select Player</option>
                <?php foreach ($players as $player): ?>
                    <option value="<?php echo $player['player_id']; ?>"><?php echo $player['player_name']; ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <label for="championship_id">Select Championship:</label>
            <select id="championship_id" name="championship_id" required>
                <option value="">Select Championship</option>
                <?php foreach ($championships as $championship): ?>
                    <option value="<?php echo $championship['championship_id']; ?>"><?php echo $championship['championship_name']; ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <button type="submit" id="submit_button">Assign Championship</button>
        </form>

        <h2>Assigned Championships</h2>
        <?php if (count($assignments) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Player Name</th>
                        <th>Championship Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?php echo $assignment['player_name']; ?></td>
                            <td><?php echo $assignment['championship_name']; ?></td>
                            <td class="action-buttons">
                                <button class="edit" onclick='populateEditForm(<?php echo json_encode([
                                    'id' => $assignment['id'],
                                    'player_id' => array_search($assignment['player_name'], array_column($players, 'player_name')),
                                    'championship_id' => array_search($assignment['championship_name'], array_column($championships, 'championship_name'))
                                ]); ?>)'>Edit</button>
                                <a class="delete" href="?delete_assignment=<?php echo $assignment['id']; ?>" onclick="return confirm('Are you sure you want to delete this assignment?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assignments found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
