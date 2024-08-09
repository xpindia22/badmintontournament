<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $championship_name = mysqli_real_escape_string($conn, $_POST['championship_name']);
    $edit_mode = $_POST['edit_mode'] === 'true';

    if ($edit_mode) {
        $championship_id = (int)$_POST['championship_id'];
        $sql = "UPDATE championships SET name = '$championship_name' WHERE id = $championship_id";
    } else {
        $sql = "INSERT INTO championships (name) VALUES ('$championship_name')";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_championship.php?status=success");
    } else {
        header("Location: manage_championship.php?status=error");
    }

    mysqli_close($conn);
}
?>
