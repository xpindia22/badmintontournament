<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fixtures Dashboard</title>
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
    <h1>Fixtures Dashboard</h1>

    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once 'config.php';

    // Check database connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    function displayFixtures($pool, $conn) {
        $query = "SELECT f.round, p1.player_name AS player1, p2.player_name AS player2, w.player_name AS winner
                  FROM fixtures f
                  LEFT JOIN players p1 ON f.player1_id = p1.id
                  LEFT JOIN players p2 ON f.player2_id = p2.id
                  LEFT JOIN players w ON f.winner_id = w.id
                  WHERE f.pool = '$pool'";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            echo "Error: " . mysqli_error($conn);
            return;
        }

        echo "<div class='pool'>
                <h2>Pool $pool Fixtures</h2>
                <table>
                    <tr>
                        <th>Player 1</th>
                        <th>Player 2</th>
                        <th>Round</th>
                        <th>Winner</th>
                    </tr>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>" . ($row['player1'] ? $row['player1'] : 'Bye') . "</td>
                    <td>" . ($row['player2'] ? $row['player2'] : 'Bye') . "</td>
                    <td>{$row['round']}</td>
                    <td>" . ($row['winner'] ? $row['winner'] : 'TBD') . "</td>
                  </tr>";
        }

        echo "</table></div>";
    }

    displayFixtures('A', $conn);
    displayFixtures('B', $conn);

    mysqli_close($conn);
    ?>
</body>
</html>
