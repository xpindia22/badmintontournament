<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'functions.php';

$fixtures = getFixtures($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !fixturesCreated($conn)) {
    assignPlayersToPools($conn);
    $rounds = ['Pre-Quarter-finals', 'Quarter-finals', 'Semi-finals', 'Finals'];
    foreach ($rounds as $currentRound => $nextRound) {
        createNextRoundFixtures($conn, $currentRound, $nextRound);
    }
}
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
