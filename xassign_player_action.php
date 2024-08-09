<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $player_id = (int)$_POST['player_id'];
    $tournament_ids = $_POST['tournaments'];

    $all_success = true;

    foreach ($tournament_ids as $tournament_id) {
        $tournament_id = (int)$tournament_id;
        $sql = "INSERT INTO player_tournaments (player_id, tournament_id) VALUES ($player_id, $tournament_id)";
        if (!mysqli_query($conn, $sql)) {
            $all_success = false;
            break;
        }
    }

    if ($all_success) {
        header("Location: assign_player.php?status=success");
    } else {
        header("Location: assign_player.php?status=error");
    }

    mysqli_close($conn);
}
?>
