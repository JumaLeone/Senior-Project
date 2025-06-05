<?php
session_start();
include('connect.php');

$userEmail = $_SESSION['email'];
$sql = "SELECT * FROM notifications WHERE user_email = ? ORDER BY date_created DESC";
$params = array($userEmail);

$notifications = sqlsrv_query($conn, $sql, $params);

// Check if the query failed
if ($notifications === false) {
    echo "Query failed:<br>";
    die(print_r(sqlsrv_errors(), true)); // This shows detailed SQL Server errors
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - KeyNest</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="./css/notifications.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="homepage.php" class="logo">KeyNest</a>
        <div class="menu">
            <ul>
                <li><a href="homepage.php">HOME</a></li>
                <li><a href="profile.php">MY PROFILE</a></li>
                <li><a href="searching.php">HOUSING OFFERS</a></li>
                <li><a href="#" class="active">NOTIFICATIONS</a></li>
                <li><a href="homepage.php#about">ABOUT</a></li>
            </ul>
        </div>
        <a href="logout.php" class="btn">LOGOUT</a>
    </nav>

    <div class="container">
        <h2>My Notifications</h2>
        <table id="notificationsTable" class="display">
            <thead>
                <tr>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($notif = sqlsrv_fetch_array($notifications, SQLSRV_FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($notif['message']) ?></td>
                        <td><?= $notif['date_created'] ? $notif['date_created']->format('Y-m-d H:i:s') : 'N/A' ?></td>
                        <td>
                            <button onclick="deleteNotification(<?= $notif['id'] ?>)" class="delete-btn">Delete</button>
                        </td>
                    </tr>
                <?php } ?>


            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function() {
            $('#notificationsTable').DataTable({
                order: [
                    [1, 'desc']
                ]
            });
        });

        function deleteNotification(id) {
            Swal.fire({
                title: 'Delete Notification?',
                text: "This action cannot be undone",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'deleteNotification.php';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'id';
                    input.value = id;

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>