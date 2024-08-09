<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pool Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
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
        .pool {
            margin-bottom: 30px;
        }
        h2 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <h1>Pool Dashboard</h1>

    <div class="pool">
        <h2>Pool A</h2>
        <table>
            <tr>
                <th>Player Name</th>
                <th>Age</th>
                <th>Sex</th>
            </tr>
            <?php
            require_once 'config.php';
            $result = mysqli_query($conn, "SELECT player_name, age, sex FROM players WHERE pool = 'A'");
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['player_name']}</td>
                        <td>{$row['age']}</td>
                        <td>{$row['sex']}</td>
                      </tr>";
            }
            ?>
        </table>
    </div>

    <div class="pool">
        <h2>Pool B</h2>
        <table>
            <tr>
                <th>Player Name</th>
                <th>Age</th>
                <th>Sex</th>
            </tr>
            <?php
            $result = mysqli_query($conn, "SELECT player_name, age, sex FROM players WHERE pool = 'B'");
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['player_name']}</td>
                        <td>{$row['age']}</td>
                        <td>{$row['sex']}</td>
                      </tr>";
            }
            mysqli_close($conn);
            ?>
        </table>
    </div>
</body>
</html>
