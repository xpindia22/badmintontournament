<?php
require_once 'config.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $conn->close();
}
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
        h1 {
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
            <button type="submit">Add Player</button>
        </form>
    </div>
</body>
</html>
