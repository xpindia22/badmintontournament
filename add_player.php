<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $player_name = $_POST['player_name'];
    $sql = "INSERT INTO players (player_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $player_name);
    $stmt->execute();
    echo "Player added successfully!";
    header("Location: add_player.php"); // Redirect to the same page or wherever appropriate
    exit();
}

if (isset($_GET['delete'])) {
    $player_id = $_GET['delete'];
    $sql = "DELETE FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    header("Location: add_player.php"); // Redirect to the same page or wherever appropriate
    exit();
}

$players = [];
$sql = "SELECT * FROM players";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Player</title>
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
</head>
<body>
    <header>
        <h1>Add Player</h1>
    </header>
    <section>
        <form method="POST" action="add_player.php">
            <label for="player_name">Player Name:</label>
            <input type="text" id="player_name" name="player_name" required>
            <button type="submit">Add Player</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Serial No</th>
                    <th>Player ID</th>
                    <th>Player Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $index => $player): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($player['player_id']); ?></td>
                    <td><?php echo htmlspecialchars($player['player_name']); ?></td>
                    <td class="action-buttons">
                        <a href="edit_player.php?id=<?php echo $player['player_id']; ?>">Edit</a>
                        <a href="add_player.php?delete=<?php echo $player['player_id']; ?>" onclick="return confirm('Are you sure you want to delete this player?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
