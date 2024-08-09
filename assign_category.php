<?php
require_once 'config.php';

$message = "";

// Fetch players from the database
$query = "SELECT player_id, player_name, dob, sex FROM players";
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

// Fetch categories from the database
$query = "SELECT * FROM categories";
$result = mysqli_query($conn, $query);

$categories = [];
if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}

// Function to calculate age
function calculateAge($dob) {
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y;
}

// Assign category to player
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['player_id']) && isset($_POST['championship_id']) && isset($_POST['category'])) {
    $player_id = $_POST['player_id'];
    $championship_id = $_POST['championship_id'];
    $category = $_POST['category'];

    // Insert into player_categories table
    $sql = "INSERT INTO player_categories (player_id, championship_id, category) VALUES ($player_id, $championship_id, '$category')";
    if (mysqli_query($conn, $sql)) {
        $message = "Category assigned successfully!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Category to Player</title>
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
            color: green;
        }
    </style>
    <script>
        function fetchEligibleCategories() {
            const playerSelect = document.getElementById('player_id');
            const categorySelect = document.getElementById('category');
            const playerDob = playerSelect.options[playerSelect.selectedIndex].getAttribute('data-dob');
            const playerSex = playerSelect.options[playerSelect.selectedIndex].getAttribute('data-sex');

            const playerAge = calculateAge(playerDob);

            const categories = JSON.parse('<?php echo json_encode($categories); ?>');

            categorySelect.innerHTML = ''; // Clear existing options

            categories.forEach(function(category) {
                if ((playerAge >= category.min_age && playerAge <= category.max_age) && 
                    (category.sex === playerSex || category.sex === 'Mixed')) {
                    const option = document.createElement('option');
                    option.value = category.name;
                    option.text = category.name;
                    categorySelect.appendChild(option);
                }
            });

            if (categorySelect.options.length === 0) {
                const option = document.createElement('option');
                option.text = "No eligible categories available";
                option.disabled = true;
                categorySelect.appendChild(option);
            }
        }

        function calculateAge(dob) {
            const dobDate = new Date(dob);
            const today = new Date();
            let age = today.getFullYear() - dobDate.getFullYear();
            const monthDiff = today.getMonth() - dobDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dobDate.getDate())) {
                age--;
            }
            return age;
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Assign Category to Player</h1>
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="player_id">Select Player:</label>
            <select id="player_id" name="player_id" required onchange="fetchEligibleCategories()">
                <option value="">Select Player</option>
                <?php foreach ($players as $player): ?>
                    <option value="<?php echo $player['player_id']; ?>" data-dob="<?php echo $player['dob']; ?>" data-sex="<?php echo $player['sex']; ?>">
                        <?php echo $player['player_name']; ?>
                    </option>
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
            <label for="category">Select Category:</label>
            <select id="category" name="category" required>
                <option value="">Select Category</option>
                <!-- Categories will be dynamically populated based on player selection -->
            </select>
            <br>
            <button type="submit">Assign Category</button>
        </form>
    </div>
</body>
</html>
