<?php
require_once 'config.php';

// Fetch players from the database
$query = "SELECT player_id, player_name FROM players";
$result = mysqli_query($conn, $query);

$players = [];
if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $players[] = $row;
    }
}

// Fetch championships from the database
$query = "SELECT championship_id, championship_name FROM championships";
$result = mysqli_query($conn, $query);

$championships = [];
if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $championships[] = $row;
    }
}

// Badminton categories
$categories = [
    'Under 17 Boys Singles',
    'Under 17 Girls Singles',
    'Men Singles',
    'Women Singles',
    'Under 17 Boys Doubles',
    'Under 17 Girls Doubles',
    'Men Doubles',
    'Women Doubles',
    'Mixed Doubles',
];

// Function to create fixtures for a given category, championship, and gender
function createFixtures($championship_id, $category, $gender, $conn) {
    $result = mysqli_query($conn, "SELECT p.player_id, p.player_name 
                                   FROM players p
                                   JOIN player_championship pc ON p.player_id = pc.player_id
                                   WHERE pc.championship_id = $championship_id AND p.sex = '$gender'");
    $players = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $players[] = $row;
    }

    shuffle($players);  // Shuffle players to randomize the fixtures

    $round = 1;
    $fixtures = [];
    $displayFixtures = [];

    while (count($players) > 1) {
        $player1 = array_shift($players);
        $player2 = array_shift($players);
        $fixtures[] = [$player1, $player2];
    }

    // If there is an odd number of players, the last player gets a bye
    if (count($players) == 1) {
        $fixtures[] = [$players[0], null];
    }

    // Insert fixtures into the database and prepare display data
    foreach ($fixtures as $fixture) {
        $player1_id = $fixture[0]['player_id'];
        $player2_id = $fixture[1]['player_id'] ?? 'NULL'; // If no player2, set to NULL
        $player1_name = $fixture[0]['player_name'];
        $player2_name = $fixture[1]['player_name'] ?? 'BYE';

        $sql = "INSERT INTO fixtures (championship_id, category, round, player1_id, player2_id) VALUES ($championship_id, '$category', $round, $player1_id, " . ($player2_id ? $player2_id : 'NULL') . ")";
        mysqli_query($conn, $sql);

        $displayFixtures[] = [$player1_name, $player2_name];
    }

    return $displayFixtures;
}

$displayFixtures = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['player_id']) && isset($_POST['championship_id']) && isset($_POST['category']) && isset($_POST['gender'])) {
    $championship_id = $_POST['championship_id'];
    $category = $_POST['category'];
    $gender = $_POST['gender'];
    $displayFixtures = createFixtures($championship_id, $category, $gender, $conn);
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
        function showCategorySelection() {
            document.getElementById('category-selection').style.display = 'block';
        }

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
            <label for="player_id">Select Player:</label>
            <select id="player_id" name="player_id" required onchange="showCategorySelection()">
                <option value="">Select Player</option>
                <?php foreach ($players as $player): ?>
                    <option value="<?php echo $player['player_id']; ?>"><?php echo $player['player_name']; ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <div id="category-selection" style="display: none;">
                <label for="championship_id">Select Championship:</label>
                <select id="championship_id" name="championship_id" required onchange="showGenderSelection()">
                    <option value="">Select Championship</option>
                    <?php foreach ($championships as $championship): ?>
                        <option value="<?php echo $championship['championship_id']; ?>"><?php echo $championship['championship_name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <br>
                <label for="category">Select Category:</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
            <h2>Fixtures for <?php echo htmlspecialchars($category); ?></h2>
            <?php if (!empty($displayFixtures)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Player 1</th>
                            <th>Player 2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayFixtures as $fixture): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fixture[0]); ?></td>
                                <td><?php echo htmlspecialchars($fixture[1]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No fixtures found for this category.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
