<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_id = (int)$_POST['id'];

    $sql = "DELETE FROM tournaments WHERE id = $tournament_id";

    if (mysqli_query($conn, $sql)) {
        echo "Tournament deleted successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
