<?php
require_once 'config.php';

if (isset($_GET['player_id'])) {
    $player_id = $_GET['player_id'];

    // Fetch player details
    $player = null;
    $sql = "SELECT * FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $player = $result->fetch_assoc();
    } else {
        echo "Player not found!";
        exit();
    }

    // Fetch all tournaments
    $tournaments = [];
    $sql = "SELECT * FROM tournaments";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tournaments[] = $row;
        }
    }

    // Fetch tournaments the player is assigned to
    $assigned_tournaments = [];
    $sql = "SELECT tournament_id FROM tournament_players WHERE player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $assigned_tournaments[] = $row['tournament_id'];
        }
    }

    // Handle form submission to update assignments
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tournament_ids'])) {
        $selected_tournaments = $_POST['tournament_ids'];

        // Delete existing assignments
        $sql = "DELETE FROM tournament_players WHERE player_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $player_id);
        $stmt->execute();

        // Insert new assignments
        $sql = "INSERT INTO tournament_players (tournament_id, player_id, pool) VALUES (?, ?, 'A')";
        $stmt = $conn->prepare($sql);
        foreach ($selected_tournaments as $tournament_id) {
            $stmt->bind_param("ii", $tournament_id, $player_id);
            $stmt->execute();
        }

        echo "Assignments updated successfully!";
        header("Location: add_player.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Player Assignments</title>
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
            width: 600px;
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
        <h1>Edit Player Assignments</h1>
    </header>
    <section>
        <h2>Player: <?php echo htmlspecialchars($player['player_name']); ?></h2>
        <form method="POST" action="edit_assignment.php?player_id=<?php echo $player_id; ?>">
            <h3>Select Tournaments</h3>
            <?php foreach ($tournaments as $tournament): ?>
                <div>
                    <label>
                        <input type="checkbox" name="tournament_ids[]" value="<?php echo $tournament['tournament_id']; ?>" <?php echo in_array($tournament['tournament_id'], $assigned_tournaments) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($tournament['tournament_name']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
            <button type="submit">Update Assignments</button>
        </form>
        <div class="action-buttons">
            <a href="add_player.php">Back</a>
        </div>
    </section>
</body>
</html>
<?php
} else {
    echo "No player selected!";
    exit();
}
?>
