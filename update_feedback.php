<?php
session_start();
include('connect.php');

function sanitize($value)
{
    return intval($value);
}

function tryQueries($conn, $queries, $feedback_id)
{
    foreach ($queries as $query) {
        $params = array($feedback_id);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt) return true;
    }
    return false;
}

// Mark as Read
if (isset($_POST['mark_read'])) {
    $feedback_id = sanitize($_POST['feedback_id']);

    $queries = [
        "UPDATE feedback SET status = 'read' WHERE id = ?",
        "UPDATE dbo.feedback SET status = 'read' WHERE id = ?",
        "UPDATE [feedback] SET [status] = 'read' WHERE [id] = ?",
        "UPDATE [dbo].[feedback] SET [status] = 'read' WHERE [id] = ?"
    ];

    $_SESSION['notification'] = tryQueries($conn, $queries, $feedback_id)
        ? "Feedback marked as read."
        : "Error marking feedback as read.";

    header("Location: adminhome.php");
    exit();
}

// Delete
if (isset($_POST['delete_feedback'])) {
    $feedback_id = sanitize($_POST['feedback_id']);

    $queries = [
        "DELETE FROM feedback WHERE id = ?",
        "DELETE FROM dbo.feedback WHERE id = ?",
        "DELETE FROM [feedback] WHERE [id] = ?",
        "DELETE FROM [dbo].[feedback] WHERE [id] = ?"
    ];

    $_SESSION['notification'] = tryQueries($conn, $queries, $feedback_id)
        ? "Feedback deleted successfully."
        : "Error deleting feedback.";

    header("Location: adminhome.php");
    exit();
}

header("Location: adminhome.php");
exit();
