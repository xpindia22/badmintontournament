<?php
require_once 'config.php';

$message = "";

// Function to calculate age
function calculateAge($dob) {
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['player_id']) && isset($_POST['championship_id']) && isset($_POST['category'])) {
        // Insert or update category assignment
        $player_id = $_POST['player_id'];
        $championship_id = $_POST['championship_id'];
        $category = $_POST['category'];

        // Check if the entry already exists
        $check_query = "SELECT * FROM player_categories WHERE player_id = $player_id AND championship_id = $championship_id AND category = '$category'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $message = "This category is already assigned to the player in the selected championship.";
        } else {
            $sql = "INSERT INTO player_categories (player_id, championship_id, category) VALUES ($player_id, $championship_id, '$category')";
            if (mysqli_query($conn, $sql)) {
                $message = "Category assigned successfully!";
            } else {
                $message = "Error: " . mysqli_error($conn);
            }

            // Redirect to prevent duplicate form submission on refresh
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['delete_player_id'])) {
        // Delete player
        $player_id = $_POST['delete_player_id'];
        $sql = "DELETE FROM players WHERE player_id = $player_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Player deleted successfully!";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['delete_category_id'])) {
        // Delete player category assignment
        $category_id = $_POST['delete_category_id'];
        $sql = "DELETE FROM player_categories WHERE id = $category_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Category assignment deleted successfully!";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }

        // Redirect to prevent duplicate form submission on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

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

// Fetch player category assignments
$query = "SELECT pc.id, p.player_name, c.championship_name, pc.category FROM player_categories pc 
          JOIN players p ON pc.player_id = p.player_id 
          JOIN championships c ON pc.championship_id = c.championship_id";
$result = mysqli_query($conn, $query);

$player_categories = [];
if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $player_categories[] = $row;
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
    <?php if (!empty($message)): ?>
    <script>
        alert("<?php echo $message; ?>");
    </script>
    <?php endif; ?>
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

        <h2>Players and Their Categories</h2>
        <table>
            <thead>
                <tr>
                    <th>Player Name</th>
                    <th>Championship</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($player_categories as $player_category): ?>
                    <tr>
                        <td><?php echo $player_category['player_name']; ?></td>
                        <td><?php echo $player_category['championship_name']; ?></td>
                        <td><?php echo $player_category['category']; ?></td>
                        <td class="action-buttons">
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="delete_category_id" value="<?php echo $player_category['id']; ?>">
                                <button type="submit" class="delete" onclick="return confirm('Are you sure you want to delete this category assignment?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
