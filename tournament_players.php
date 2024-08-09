<?php
require_once 'config.php';

if (isset($_GET['tournament_id'])) {
    $tournament_id = (int)$_GET['tournament_id'];

    $sql = "SELECT p.player_name, p.age, p.sex
            FROM players p
            JOIN player_tournaments pt ON p.id = pt.player_id
            WHERE pt.tournament_id = $tournament_id";
    $result = mysqli_query($conn, $sql);

    echo "<h2>Players in Tournament</h2>";
    if (mysqli_num_rows($result) > 0) {
        echo "<table>
                <tr>
                    <th>Player Name</th>
                    <th>Age</th>
                    <th>Sex</th>
                </tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>" . $row['player_name'] . "</td>
                    <td>" . $row['age'] . "</td>
                    <td>" . $row['sex'] . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No players are assigned to this tournament.</p>";
    }

    mysqli_close($conn);
}
?>
