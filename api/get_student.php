<?php

header("Content-Type: application/json");

include "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

$student_id = trim($_POST["student_id"] ?? "");
$pin = trim($_POST["pin"] ?? "");

if (empty($student_id) || empty($pin)) {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required."
    ]);
    exit;
}

$sql = "SELECT * FROM students 
        WHERE student_id = ? 
        AND pin = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ss", $student_id, $pin);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {

    $student = mysqli_fetch_assoc($result);

    echo json_encode([
        "success" => true,
        "student" => [
            "name" => $student["full_name"],
            "class" => $student["class_name"],
            "division" => $student["division_name"],
            "score" => $student["score"],
            "conduct" => $student["conduct"],
            "balance" => $student["balance"]
        ]
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Invalid student credentials."
    ]);
}

mysqli_close($conn);

?>