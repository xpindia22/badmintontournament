function progressWinners($conn, $pool, $gender) {
    $sql = "SELECT * FROM fixtures WHERE pool = ? AND gender = ? AND winner_id IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $pool, $gender);
    $stmt->execute();
    $result = $stmt->get_result();

    $winners = array();
    while ($row = $result->fetch_assoc()) {
        $winners[] = $row['winner_id'];
    }

    $count = count($winners);
    for ($i = 0; $i < $count; $i += 2) {
        if (isset($winners[$i + 1])) {
            createMatch($conn, $winners[$i], $winners[$i + 1], $pool);
        } else {
            createMatch($conn, $winners[$i], null, $pool);
        }
    }
}
