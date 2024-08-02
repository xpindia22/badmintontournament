<?php
require_once 'config.php';

// Fetch fixtures from the database
function getFixtures($conn) {
    $query = 'SELECT m.match_id, m.round, m.pool, p1.player_name AS player1, p2.player_name AS player2, 
              m.player1_score1, m.player1_score2, m.player1_score3, m.player2_score1, m.player2_score2, m.player2_score3, 
              m.winner_id, m.match_date, m.player1_id, m.player2_id
              FROM matches m 
              LEFT JOIN players p1 ON m.player1_id = p1.player_id 
              LEFT JOIN players p2 ON m.player2_id = p2.player_id 
              ORDER BY FIELD(m.round, "Pre-Quarter-finals", "Quarter-finals", "Semi-finals", "Finals"), m.match_date';
    $result = $conn->query($query);
    if (!$result) {
        die('Query failed: ' . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

$fixtures = getFixtures($conn);

// Create fixtures for the next round
function createNextRoundFixtures($conn, $currentRound, $nextRound) {
    $query = 'SELECT winner_id, pool FROM matches WHERE round = ? AND winner_id IS NOT NULL';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $currentRound);
    $stmt->execute();
    $result = $stmt->get_result();
    $winners = $result->fetch_all(MYSQLI_ASSOC);

    $winnersByPool = [];
    foreach ($winners as $winner) {
        $winnersByPool[$winner['pool']][] = $winner['winner_id'];
    }

    foreach ($winnersByPool as $pool => $winners) {
        for ($i = 0; $i < count($winners); $i += 2) {
            if (isset($winners[$i + 1])) {
                $query = 'INSERT INTO matches (round, pool, player1_id, player2_id) VALUES (?, ?, ?, ?)';
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssii', $nextRound, $pool, $winners[$i], $winners[$i + 1]);
                $stmt->execute();
            }
        }
    }
}

function assignPlayersToPools($conn) {
    $pools = ['A', 'B'];
    $players = [];

    // Fetch players from the database
    $query = 'SELECT player_id FROM players';
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        $players[] = $row['player_id'];
    }

    // Shuffle and split players into pools
    shuffle($players);
    $poolA = array_slice($players, 0, 8);
    $poolB = array_slice($players, 8, 8);

    // Insert fixtures for Pre-Quarter-finals
    $round = 'Pre-Quarter-finals';

    foreach ([$poolA, $poolB] as $index => $pool) {
        $poolName = $pools[$index];
        for ($i = 0; $i < count($pool); $i += 2) {
            if (isset($pool[$i + 1])) {
                $query = 'INSERT INTO matches (round, pool, player1_id, player2_id) VALUES (?, ?, ?, ?)';
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssii', $round, $poolName, $pool[$i], $pool[$i + 1]);
                $stmt->execute();
            }
        }
    }

    // Update the settings table to indicate fixtures have been created
    $query = 'UPDATE settings SET value = "yes" WHERE key_name = "fixtures_created"';
    $conn->query($query);

    echo "Players assigned to pools and fixtures created successfully!";
}

function fixturesCreated($conn) {
    $query = 'SELECT value FROM settings WHERE key_name = "fixtures_created"';
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['value'] === 'yes';
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !fixturesCreated($conn)) {
    assignPlayersToPools($conn);
    $rounds = ['Pre-Quarter-finals' => 'Quarter-finals', 'Quarter-finals' => 'Semi-finals', 'Semi-finals' => 'Finals'];
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
        $rounds = ["Pre-Quarter-finals", "Quarter-finals", "Semi-finals", "Finals"];
        foreach ($rounds as $round) : ?>
            <div class="round">
                <h2><?php echo $round; ?></h2>
                <?php foreach ($fixtures as $fixture) : 
                    if ($fixture['round'] == $round) : 
                        $player1_class = ($fixture['winner_id'] == $fixture['player1_id']) ? 'winner' : 'loser';
                        $player2_class = ($fixture['winner_id'] == $fixture['player2_id']) ? 'winner' : 'loser';
                ?>
                    <div class="match">
                        <div class="player <?php echo $player1_class; ?>">
                            <?php echo htmlspecialchars($fixture['player1']); ?>
                        </div>
                        <div class="player <?php echo $player2_class; ?>">
                            <?php echo htmlspecialchars($fixture['player2']); ?>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
