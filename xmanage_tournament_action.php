<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_name = mysqli_real_escape_string($conn, $_POST['tournament_name']);
    $age_min = (int)$_POST['age_min'];
    $age_max = (int)$_POST['age_max'];
    $sex = mysqli_real_escape_string($conn, $_POST['sex']);
    $championship_id = (int)$_POST['championship_id'];
    $edit_mode = $_POST['edit_mode'] === 'true';

    if ($edit_mode) {
        $tournament_id = (int)$_POST['tournament_id'];
        $sql = "UPDATE tournaments SET name = '$tournament_name', age_min = $age_min, age_max = $age_max, sex = '$sex', championship_id = $championship_id WHERE id = $tournament_id";
    } else {
        $sql = "INSERT INTO tournaments (name, age_min, age_max, sex, championship_id) VALUES ('$tournament_name', $age_min, $age_max, '$sex', $championship_id)";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_tournament.php?status=success");
    } else {
        header("Location: manage_tournament.php?status=error");
    }

    mysqli_close($conn);
}
?>
