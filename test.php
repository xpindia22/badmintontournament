require_once 'config.php';  // Database configuration and connection

// Function to update match results and determine the winner
function updateMatchResults($conn, $matchId, $player1Id, $player2Id, $scores) {
    $winnerId = ($scores['player1'] > $scores['player2']) ? $player1Id : $player2Id;

    // Update the current match
    $updateMatch = $conn->prepare("UPDATE matches SET winner_id = ?, status = 'completed' WHERE match_id = ?");
    $updateMatch->bind_param("ii", $winnerId, $matchId);
    $updateMatch->execute();

    // Check if all matches in the current round are completed
    if (checkAllMatchesCompleted($conn, $round)) {
        createNextRoundFixtures($conn, $round);
    }
}

// Function to check if all matches in the current round are completed
function checkAllMatchesCompleted($conn, $round) {
    $check = $conn->query("SELECT count(*) as pending FROM matches WHERE round = '$round' AND status != 'completed'");
    $result = $check->fetch_assoc();
    return ($result['pending'] == 0);
}

// Function to create fixtures for the next round
function createNextRoundFixtures($conn, $currentRound) {
    $nextRound = $currentRound + 1;  // Define logic to determine the next round
    $winners = $conn->query("SELECT winner_id FROM matches WHERE round = '$currentRound'");

    $players = [];
    while ($row = $winners->fetch_assoc()) {
        $players[] = $row['winner_id'];
    }

    // Pair players for the next round
    for ($i = 0; $i < count($players); $i += 2) {
        if (isset($players[$i + 1])) {
            $insertMatch = $conn->prepare("INSERT INTO matches (round, player1_id, player2_id, status) VALUES (?, ?, ?, 'pending')");
            $insertMatch->bind_param("iii", $nextRound, $players[$i], $players[$i + 1]);
            $insertMatch->execute();
        }
    }
}

// Assuming scores are submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matchId = $_POST['match_id'];
    $player1Id = $_POST['player1_id'];
    $player2Id = $_POST['player2_id'];
    $scores = [
        'player1' => $_POST['player1_score'],
        'player2' => $_POST['player2_score']
    ];

    updateMatchResults($conn, $matchId, $player1Id, $player2Id, $scores);
}
