<?php
require_once 'config.php';

if (isset($_GET['age']) && isset($_GET['sex'])) {
    $age = (int)$_GET['age'];
    $sex = mysqli_real_escape_string($conn, $_GET['sex']);

    $sql = "SELECT id, name FROM tournaments WHERE age_min <= $age AND age_max >= $age AND sex = '$sex'";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        echo "<input type='checkbox' name='tournaments[]' value='" . $row['id'] . "'>" . $row['name'] . "<br>";
    }

    mysqli_close($conn);
}
?>
