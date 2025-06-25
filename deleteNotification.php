<?php
session_start();
include('connect.php');

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    $sql = "DELETE FROM notifications WHERE id = ?";
    $params = array($id);

    $stmt = sqlsrv_prepare($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    if (sqlsrv_execute($stmt)) {
        header("Location: notifications.php");
        exit();
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
