<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'functions.php';

$conn = new mysqli($host, $user, $pwd, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch eligible players for the "Mens Open"
$eligiblePlayers = getEligiblePlayers($conn);

// Debugging: Check if players are being fetched correctly
echo "<pre>";
echo "Eligible Players:\n";
print_r($eligiblePlayers);
echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!fixturesCreated($conn)) {
        assignPlayersToPools($conn);
        $rounds = [
            'Round of 32' => 'Round of 16',
            'Round of 16' => 'Quarter-finals', 
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
            'Round of 32' => 'Round of 16',
            'Round of 16' => 'Quarter-finals', 
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
            justify-content: space-around;
            align-items: flex-start;
        }
        .round {
            margin: 20px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 8px;
        }
        .match {
            margin: 10px;
            padding: 10px;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .winner {
            color: green;
        }
        .loser {
            color: red;
        }
        .eligible-players {
            margin: 20px;
            padding: 10px;
            background-color: #e0f7fa;
            border-radius: 8px;
        }
        .player {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <header>
        <h1>Tournament Dashboard</h1>
    </header>

    <div class="eligible-players">
        <h2>Eligible Players for Mens Open</h2>
        <?php if (!empty($eligiblePlayers)): ?>
            <?php foreach ($eligiblePlayers as $player): ?>
                <div class="player"><?php echo htmlspecialchars($player['player_name']); ?></div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No eligible players found.</p>
        <?php endif; ?>
    </div>

    <div class="bracket">
        <?php 
        $rounds = ["Round of 32", "Round of 16", "Quarter-finals", "Semi-finals", "Finals"];
        foreach ($rounds as $round) : ?>
            <div class="round">
                <h2><?php echo $round; ?></h2>
                <?php foreach ($fixtures as $fixture) :
                    if ($fixture['round'] === $round) : 
                        $player1_class = $fixture['winner_id'] == $fixture['player1_id'] ? 'winner' : 'loser';
                        $player2_class = $fixture['winner_id'] == $fixture['player2_id'] ? 'winner' : 'loser';
                        $player1 = htmlspecialchars($fixture['player1'] ?? '');
                        $player2 = htmlspecialchars($fixture['player2'] ?? '');
                ?>
                    <div class="match">
                        <div class="<?php echo $player1_class; ?>">
                            <?php echo $player1; ?>
                        </div>
                        <div class="<?php echo $player2_class; ?>">
                            <?php echo $player2; ?>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
