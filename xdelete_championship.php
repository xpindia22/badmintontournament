<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $championship_id = (int)$_POST['id'];

    $sql = "DELETE FROM championships WHERE id = $championship_id";

    if (mysqli_query($conn, $sql)) {
        echo "Championship deleted successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
