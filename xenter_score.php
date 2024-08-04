<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchId = $_POST['match_id'];
    $player1Scores = [$_POST['player1_set1'], $_POST['player1_set2'], $_POST['player1_set3']];
    $player2Scores = [$_POST['player2_set1'], $_POST['player2_set2'], $_POST['player2_set3']];
    $player1Id = $_POST['player1_id'];
    $player2Id = $_POST['player2_id'];
    updateMatchResults($conn, $matchId, $player1Scores, $player2Scores, $player1Id, $player2Id);
    echo "Scores updated successfully!";
}

$matches = getMatches($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enter Match Scores</title>
</head>
<body>
<header>
<h1>Enter Match Scores</h1>
</header>

<form method="POST" action="enter_score.php">
<select name="match_id" required>
    <?php if (count($matches) > 0): ?>
        <?php foreach ($matches as $match): 
            $player1 = htmlspecialchars($match['player1'] ?? 'Unknown Player');
            $player2 = htmlspecialchars($match['player2'] ?? 'Unknown Player');
        ?>
            <option value="<?php echo htmlspecialchars($match['match_id']); ?>" data-player1-id="<?php echo htmlspecialchars($match['player1_id']); ?>" data-player2-id="<?php echo htmlspecialchars($match['player2_id']); ?>">
                <?php echo $player1 . ' vs. ' . $player2; ?>
            </option>
        <?php endforeach; ?>
    <?php else: ?>
        <option value="">No matches available</option>
    <?php endif; ?>
</select>

<input type="hidden" name="player1_id" id="player1_id" value="">
<input type="hidden" name="player2_id" id="player2_id" value="">

<h3>Player 1 Scores</h3>
<input type="number" name="player1_set1" placeholder="Set 1" required>
<input type="number" name="player1_set2" placeholder="Set 2" required>
<input type="number" name="player1_set3" placeholder="Set 3">

<h3>Player 2 Scores</h3>
<input type="number" name="player2_set1" placeholder="Set 1" required>
<input type="number" name="player2_set2" placeholder="Set 2" required>
<input type="number" name="player2_set3" placeholder="Set 3">

<button type="submit">Submit Scores</button>
</form>

<script>
document.querySelector('select[name="match_id"]').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    document.getElementById('player1_id').value = selectedOption.getAttribute('data-player1-id');
    document.getElementById('player2_id').value = selectedOption.getAttribute('data-player2-id');
});
</script>
</body>
</html>
