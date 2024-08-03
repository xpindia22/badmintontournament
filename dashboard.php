<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'functions.php';

$conn = new mysqli($host, $user, $pwd, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!fixturesCreated($conn)) {
        assignPlayersToPools($conn);
        $rounds = [
            'Pre-Quarter-finals' => 'Quarter-finals', 
            'Quarter-finals' => 'Semi-finals', 
            'Semi-finals' => 'Finals'
        ];
        foreach ($rounds as $currentRound => $nextRound) {
            createNextRoundFixtures($conn, $currentRound, $nextRound);
        }
    } else {
        // Handle updating match results
        $matchId = $_POST['match_id'];
        $player1Scores = [$_POST['player1_score1'], $_POST['player1_score2'], $_POST['player1_score3']];
        $player2Scores = [$_POST['player2_score1'], $_POST['player2_score2'], $_POST['player2_score3']];
        $player1Id = $_POST['player1_id'];
        $player2Id = $_POST['player2_id'];
        
        updateMatchResults($conn, $matchId, $player1Scores, $player2Scores, $player1Id, $player2Id);
        
        // Update fixtures after the match results
        $rounds = [
            'Pre-Quarter-finals' => 'Quarter-finals', 
            'Quarter-finals' => 'Semi-finals', 
            'Semi-finals' => 'Finals'
        ];
        foreach ($rounds as $currentRound => $nextRound) {
            createNextRoundFixtures($conn, $currentRound, $nextRound);
        }
    }
}

$fixtures = getFixtures($conn);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Dashboard</title>
    <style>
        .bracket {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px;
        }
        .round {
            width: 200px;
        }
        .match {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
        }
        .winner {
            background-color: green;
            color: white;
            padding: 5px;
            margin-bottom: 5px;
        }
        .loser {
            background-color: lightblue;
            padding: 5px;
            margin-bottom: 5px;
        }
        .player {
            width: 45%;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <h1>Tournament Dashboard</h1>
    </header>

    <form method="POST" action="">
        <button type="submit">Create Fixtures</button>
    </form>

    <div class="bracket">
        <?php 
        $rounds = ['Pre-Quarter-finals', 'Quarter-finals', 'Semi-finals', 'Finals'];
        foreach ($rounds as $round) : ?>
            <div class="round">
                <h2><?php echo $round; ?></h2>
                <?php foreach ($fixtures as $fixture) : 
                    if ($fixture['round'] == $round) :
                        $player1 = htmlspecialchars($fixture['player1'] ?? 'Unknown Player');
                        $player2 = htmlspecialchars($fixture['player2'] ?? 'Unknown Player');
                        $player1_class = ($fixture['winner_id'] == $fixture['player1_id']) ? 'winner' : 'loser';
                        $player2_class = ($fixture['winner_id'] == $fixture['player2_id']) ? 'winner' : 'loser';
                ?>
                    <div class="match">
                        <div class="player <?php echo $player1_class; ?>">
                            <?php echo $player1; ?>
                        </div>
                        <div class="player <?php echo $player2_class; ?>">
                            <?php echo $player2; ?>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
