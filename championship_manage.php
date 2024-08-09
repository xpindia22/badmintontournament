<?php
require_once 'config.php';

$message = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form_action'] === 'add_championship') {
        $championship_name = $_POST['championship_name'];

        // Insert championship into the database
        $stmt = $conn->prepare("INSERT INTO championships (championship_name) VALUES (?)");
        $stmt->bind_param("s", $championship_name);

        if ($stmt->execute()) {
            $message = "Championship added successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    } elseif ($_POST['form_action'] === 'edit_championship') {
        $championship_id = $_POST['championship_id'];
        $championship_name = $_POST['championship_name'];

        // Update championship in the database
        $stmt = $conn->prepare("UPDATE championships SET championship_name = ? WHERE championship_id = ?");
        $stmt->bind_param("si", $championship_name, $championship_id);

        if ($stmt->execute()) {
            $message = "Championship updated successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_championship'])) {
    $championship_id = $_GET['delete_championship'];

    // Delete championship from the database
    $stmt = $conn->prepare("DELETE FROM championships WHERE championship_id = ?");
    $stmt->bind_param("i", $championship_id);

    if ($stmt->execute()) {
        $message = "Championship deleted successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch championships from the database
$query = "SELECT championship_id, championship_name FROM championships";
$result = $conn->query($query);

$championships = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $championships[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Championships</title>
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
        function populateEditForm(championship) {
            document.getElementById('championship_id').value = championship.championship_id;
            document.getElementById('championship_name').value = championship.championship_name;
            document.getElementById('submit_button').innerText = 'Update Championship';
            document.getElementById('form_action').value = 'edit_championship';
        }

        function resetForm() {
            document.getElementById('championship_id').value = '';
            document.getElementById('championship_name').value = '';
            document.getElementById('submit_button').innerText = 'Add Championship';
            document.getElementById('form_action').value = 'add_championship';
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Manage Championships</h1>
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" id="championship_id" name="championship_id">
            <input type="hidden" id="form_action" name="form_action" value="add_championship">
            <label for="championship_name">Championship Name:</label>
            <input type="text" id="championship_name" name="championship_name" required>
            <br>
            <button type="submit" id="submit_button">Add Championship</button>
        </form>

        <h2>List of Championships</h2>
        <?php if (count($championships) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Championship ID</th>
                        <th>Championship Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($championships as $championship): ?>
                        <tr>
                            <td><?php echo $championship['championship_id']; ?></td>
                            <td><?php echo $championship['championship_name']; ?></td>
                            <td class="action-buttons">
                                <button class="edit" onclick='populateEditForm(<?php echo json_encode([
                                    'championship_id' => $championship['championship_id'],
                                    'championship_name' => $championship['championship_name']
                                ]); ?>)'>Edit</button>
                                <a class="delete" href="?delete_championship=<?php echo $championship['championship_id']; ?>" onclick="return confirm('Are you sure you want to delete this championship?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No championships found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
