<?php
require_once 'config.php'; // Ensure your config.php file is included

function getQuarterfinalFixtures($conn) {
    $query = "
        SELECT 
            f.id, f.round, f.pool, f.winner_id,
            p1.player_name AS player1_name,
            p2.player_name AS player2_name,
            pw.player_name AS winner_name
        FROM 
            fixtures f
        LEFT JOIN 
            players p1 ON f.player1_id = p1.player_id
        LEFT JOIN 
            players p2 ON f.player2_id = p2.player_id
        LEFT JOIN 
            players pw ON f.winner_id = pw.player_id
        WHERE 
            f.round = 2 -- Assuming 2 is the round number for quarterfinals
        ORDER BY 
            f.id
    ";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt->execute();
    return $stmt->get_result();
}

$conn = new mysqli($host, $user, $pwd, $db);
if ($conn->connect_error) {
    die("Connection
