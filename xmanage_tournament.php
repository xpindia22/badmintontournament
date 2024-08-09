<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tournament</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 2px 2px 12px #aaa;
        }
        div {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function editTournament(id, name, age_min, age_max, sex) {
            $('#tournament_id').val(id);
            $('#tournament_name').val(name);
            $('#age_min').val(age_min);
            $('#age_max').val(age_max);
            $('#sex').val(sex);
            $('#edit_mode').val('true');
        }

        function deleteTournament(id) {
            if (confirm('Are you sure you want to delete this tournament?')) {
                $.ajax({
                    url: 'delete_tournament.php',
                    type: 'POST',
                    data: {id: id},
                    success: function(data) {
                        location.reload();
                    }
                });
            }
        }
    </script>
</head>
<body>
    <h1>Manage Tournament</h1>
    <form action="manage_tournament_action.php" method="post">
        <input type="hidden" id="tournament_id" name="tournament_id">
        <input type="hidden" id="edit_mode" name="edit_mode" value="false">
        <div>
            <label for="championship">Championship:</label>
            <select id="championship" name="championship_id" required>
                <?php
                require_once 'config.php';
                $result = mysqli_query($conn, "SELECT id, name FROM championships");
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label for="tournament_name">Tournament Name:</label>
            <input type="text" id="tournament_name" name="tournament_name" required>
        </div>
        <div>
            <label for="age_min">Minimum Age:</label>
            <input type="number" id="age_min" name="age_min" required>
        </div>
        <div>
            <label for="age_max">Maximum Age:</label>
            <input type="number" id="age_max" name="age_max" required>
        </div>
        <div>
            <label for="sex">Sex:</label>
            <select id="sex" name="sex" required>
                <option value="B">Boy</option>
                <option value="G">Girl</option>
                <option value="M">Man</option>
                <option value="W">Woman</option>
            </select>
        </div>
        <div>
            <input type="submit" value="Save Tournament">
        </div>
    </form>
    <h2>Existing Tournaments</h2>
    <table>
        <tr>
            <th>Championship</th>
            <th>Name</th>
            <th>Age Range</th>
            <th>Sex</th>
            <th>Actions</th>
        </tr>
        <?php
        $result = mysqli_query($conn, "SELECT t.id, c.name AS championship_name, t.name, t.age_min, t.age_max, t.sex 
                                       FROM tournaments t 
                                       JOIN championships c ON t.championship_id = c.id");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['championship_name'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['age_min'] . " - " . $row['age_max'] . "</td>";
            echo "<td>" . $row['sex'] . "</td>";
            echo "<td>
                    <button type='button' onclick='editTournament(" . $row['id'] . ", \"" . $row['name'] . "\", " . $row['age_min'] . ", " . $row['age_max'] . ", \"" . $row['sex'] . "\")'>Edit</button>
                    <button type='button' onclick='deleteTournament(" . $row['id'] . ")'>Delete</button>
                  </td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>
