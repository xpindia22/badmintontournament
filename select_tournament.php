<?php
require_once 'config.php';

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
    <title>Select Tournament</title>
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
            width: 300px;
            text-align: center;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        form {
            margin-bottom: 20px;
        }
        label, select, button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
        select, button {
            padding: 10px;
            font-size: 16px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <h1>Select Tournament</h1>
    </header>
    <section>
        <h2>Select Tournament</h2>
        <form method="POST" action="distribute_players.php">
            <label for="tournament_id">Select Tournament:</label>
            <select id="tournament_id" name="tournament_id" required>
                <option value="">Select Tournament</option>
                <?php foreach ($tournaments as $tournament): ?>
                    <option value="<?= $tournament['tournament_id'] ?>"><?= $tournament['tournament_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Select Tournament</button>
        </form>
    </section>
</body>
</html>
