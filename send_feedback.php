<?php
session_start();
include('connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $email = $_SESSION['email'];
    $message = trim($_POST['message']);

    $query = "INSERT INTO feedback (user_email, message, date_sent) VALUES (?, ?, GETDATE())";
    $params = [$email, $message];

    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt) {
        $_SESSION['feedback_success'] = "Feedback submitted successfully.";
    } else {
        $_SESSION['feedback_success'] = "Failed to send feedback.";
    }

    header('Location: notifications.php');
    exit();
}
