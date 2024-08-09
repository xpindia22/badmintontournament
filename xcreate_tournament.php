<?php
require_once 'config.php';

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_name = $_POST['tournament_name'];
    $gender = $_POST['gender'];
    $age_group = $_POST['age_group'];

    // Insert tournament into the database
    $stmt = $conn->prepare("INSERT INTO tournaments (tournament_name, gender, age_group) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $tournament_name, $gender, $age_group);

    if ($stmt->execute()) {
        $message = "Tournament created successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch tournaments from the database
$query = "SELECT tournament_id, tournament_name, gender, age_group FROM tournaments";
$result = mysqli_query($conn, $query);

$tournaments = [];
if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tournaments[] = $row;
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Tournaments</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Tournament</h1>
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="tournament_name">Tournament Name:</label>
            <input type="text" id="tournament_name" name="tournament_name" required>
            <br>
            <label for="gender">Gender:</label>
            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="M">Male</option>
                <option value="F">Female</option>
            </select>
            <br>
            <label for="age_group">Age Group:</label>
            <input type="text" id="age_group" name="age_group" required>
            <br>
            <button type="submit">Create Tournament</button>
        </form>

        <h2>Existing Tournaments</h2>
        <?php if (!empty($tournaments)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Tournament Name</th>
                        <th>Gender</th>
                        <th>Age Group</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tournaments as $tournament): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tournament['tournament_name']); ?></td>
                            <td><?php echo htmlspecialchars($tournament['gender']); ?></td>
                            <td><?php echo htmlspecialchars($tournament['age_group']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tournaments found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
