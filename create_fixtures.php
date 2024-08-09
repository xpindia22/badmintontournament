<?php
require_once 'config.php';

// Fetch championships from the database
$query = "SELECT championship_id, championship_name FROM championships";
$result = mysqli_query($conn, $query);

$championships = [];
if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $championships[] = $row;
    }
}

// Function to create fixtures for a given championship and gender
function createFixtures($championship_id, $gender, $conn) {
    $result = mysqli_query($conn, "SELECT p.player_id, p.player_name, p.pool 
                                   FROM players p
                                   JOIN player_championship pc ON p.player_id = pc.player_id
                                   WHERE pc.championship_id = $championship_id AND p.sex = '$gender'");
    $players = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $players[$row['pool']][] = $row;
    }

    $displayFixtures = [];

    foreach ($players as $pool => $poolPlayers) {
        shuffle($poolPlayers);  // Shuffle players to randomize the fixtures

        $round = 1;
        $fixtures = [];

        while (count($poolPlayers) > 1) {
            $player1 = array_shift($poolPlayers);
            $player2 = array_shift($poolPlayers);
            $fixtures[] = [$player1, $player2];
        }

        // If there is an odd number of players, the last player gets a bye
        if (count($poolPlayers) == 1) {
            $fixtures[] = [$poolPlayers[0], null];
        }

        // Insert fixtures into the database and prepare display data
        foreach ($fixtures as $fixture) {
            $player1_id = $fixture[0]['player_id'];
            $player2_id = $fixture[1]['player_id'] ?? 'NULL'; // If no player2, set to NULL
            $player1_name = $fixture[0]['player_name'];
            $player2_name = $fixture[1]['player_name'] ?? 'BYE';

            $sql = "INSERT INTO fixtures (championship_id, pool, round, player1_id, player2_id) VALUES ($championship_id, '$pool', $round, $player1_id, " . ($player2_id ? $player2_id : 'NULL') . ")";
            mysqli_query($conn, $sql);

            $displayFixtures[$pool][] = [$player1_name, $player2_name];
        }
    }

    return $displayFixtures;
}

$displayFixtures = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['championship_id']) && isset($_POST['gender'])) {
    $championship_id = $_POST['championship_id'];
    $gender = $_POST['gender'];
    $displayFixtures = createFixtures($championship_id, $gender, $conn);
    $message = "Fixtures have been generated.";
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Fixtures</title>
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
        select, button {
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
    <script>
        function showGenderSelection() {
            document.getElementById('gender-selection').style.display = 'block';
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Create Fixtures</h1>
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="championship_id">Select Championship:</label>
            <select id="championship_id" name="championship_id" required onchange="showGenderSelection()">
                <option value="">Select Championship</option>
                <?php foreach ($championships as $championship): ?>
                    <option value="<?php echo $championship['championship_id']; ?>"><?php echo $championship['championship_name']; ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <div id="gender-selection" style="display: none;">
                <label for="gender">Select Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select>
                <br>
                <button type="submit">Create Fixtures</button>
            </div>
        </form>

        <?php if (!empty($displayFixtures)): ?>
            <?php foreach ($displayFixtures as $pool => $fixtures): ?>
                <h2>Fixtures for Pool <?php echo htmlspecialchars($pool); ?></h2>
                <?php if (!empty($fixtures)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Player 1</th>
                                <th>Player 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fixtures as $fixture): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fixture[0]); ?></td>
                                    <td><?php echo htmlspecialchars($fixture[1]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No fixtures found for Pool <?php echo htmlspecialchars($pool); ?>.</p>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
