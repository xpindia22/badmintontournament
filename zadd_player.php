

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Player</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 2px 2px 12px #aaa;
        }
        div {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function calculateAge(dob) {
            const today = new Date();
            const birthDate = new Date(dob);
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age;
        }

        function fetchTournaments(age, sex) {
            $.ajax({
                url: 'fetch_tournaments.php',
                type: 'GET',
                data: {age: age, sex: sex},
                success: function(data) {
                    $('#tournaments').html(data);
                }
            });
        }

        $(document).ready(function() {
            $('#dob, #sex').change(function() {
                const dob = $('#dob').val();
                const sex = $('#sex').val();
                if (dob && sex) {
                    const age = calculateAge(dob);
                    $('#age').val(age);
                    fetchTournaments(age, sex);
                }
            });
        });
    </script>
</head>
<body>
    <h1>Add Player</h1>
    <form action="add_player_action.php" method="post">
        <div>
            <label for="player_name">Player Name:</label>
            <input type="text" id="player_name" name="player_name" required>
        </div>
        <div>
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" required>
        </div>
        <div>
            <label for="age">Age:</label>
            <input type="number" id="age" name="age" readonly>
        </div>
        <div>
            <label for="sex">Sex:</label>
            <select id="sex" name="sex" required>
                <option value="B">Boy</option>
                <option value="G">Girl</option>
                <option value="M">Man</option>
                <option value="W">Woman</option>
            </select>
        </div>
        <div>
            <label>Tournaments:</label>
            <div id="tournaments">
                <!-- Tournaments will be loaded here via AJAX -->
            </div>
        </div>
        <div>
            <input type="submit" value="Add Player">
        </div>
    </form>
</body>
</html>
