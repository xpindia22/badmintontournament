<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tournament Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        a {
            display: block;
            padding: 10px;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            margin: 5px 0;
            text-decoration: none;
            color: #333;
            border-radius: 5px;
        }
        a:hover {
            background-color: #ddd;
        }
        .tournament-details {
            margin-top: 20px;
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
        function showPlayers(tournamentId) {
            $.ajax({
                url: 'tournament_players.php',
                type: 'GET',
                data: {tournament_id: tournamentId},
                success: function(data) {
                    $('#tournament-details').html(data);
                }
            });
        }
    </script>
</head>
<body>
    <h1>Tournament Dashboard</h1>
    <div id="tournaments">
        <?php
        require_once 'config.php';
        $result = mysqli_query($conn, "SELECT id, name FROM tournaments");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<a href='#' onclick='showPlayers(" . $row['id'] . ")'>" . $row['name'] . "</a>";
        }
        ?>
    </div>
    <div id="tournament-details" class="tournament-details">
        <!-- Player details will be loaded here via AJAX -->
    </div>
</body>
</html>
