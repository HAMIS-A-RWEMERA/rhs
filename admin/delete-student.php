<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/db.php");

/* CHECK IF ID EXISTS */
if(isset($_GET['id'])){

    $id = $_GET['id'];

    /* DELETE QUERY */
    $deleteQuery = mysqli_query(
        $conn,
        "DELETE FROM students WHERE id = '$id'"
    );

}

/* REDIRECT BACK */
header("Location: students.php");
exit();
?>