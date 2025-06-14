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
  die(print_r(sqlsrv_errors(), true));
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
  <!-- Navbar -->
  <nav class="navbar">
    <a href="homepage.php" class="logo">KeyNest</a>

    <!-- Desktop Menu -->
    <div class="menu">
      <ul>
        <li><a href="homepage.php">HOME</a></li>
        <li><a href="profile.php">MY PROFILE</a></li>
        <li><a href="searching.php">HOUSING OFFERS</a></li>
        <li><a href="#" class="active">NOTIFICATIONS</a></li>
        <li><a href="homepage.php#about">ABOUT</a></li>
      </ul>
    </div>

    <!-- Mobile Menu Toggle -->
    <div class="menu-toggle" id="menuToggle">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <a href="logout.php" class="btn">LOGOUT</a>

    <!-- Mobile Dropdown Menu -->
    <div class="mobile-dropdown" id="mobileDropdown">
      <ul>
        <li><a href="homepage.php">HOME</a></li>
        <li><a href="profile.php">MY PROFILE</a></li> <!-- Fixed: removed # -->
        <li><a href="searching.php">HOUSING OFFERS</a></li>
        <li><a href="#" class="active">NOTIFICATIONS</a></li>
        <li><a href="homepage.php#about">ABOUT</a></li>
      </ul>
    </div>
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

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const menuToggle = document.getElementById('menuToggle');
      const mobileDropdown = document.getElementById('mobileDropdown');
      let isMenuOpen = false;

      menuToggle.addEventListener('click', function() {
        isMenuOpen = !isMenuOpen;

        // Toggle hamburger animation
        menuToggle.classList.toggle('active');

        // Toggle dropdown menu
        if (isMenuOpen) {
          mobileDropdown.style.display = 'block';
          // Small delay to ensure display:block is applied before animation
          setTimeout(() => {
            mobileDropdown.classList.add('show');
          }, 10);
        } else {
          mobileDropdown.classList.remove('show');
          // Hide after animation completes
          setTimeout(() => {
            mobileDropdown.style.display = 'none';
          }, 300);
        }
      });

      // Close menu when clicking outside
      document.addEventListener('click', function(e) {
        if (!menuToggle.contains(e.target) && !mobileDropdown.contains(e.target)) {
          if (isMenuOpen) {
            isMenuOpen = false;
            menuToggle.classList.remove('active');
            mobileDropdown.classList.remove('show');
            setTimeout(() => {
              mobileDropdown.style.display = 'none';
            }, 300);
          }
        }
      });

      // Close menu when clicking on a link
      const mobileLinks = mobileDropdown.querySelectorAll('a');
      mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
          isMenuOpen = false;
          menuToggle.classList.remove('active');
          mobileDropdown.classList.remove('show');
          setTimeout(() => {
            mobileDropdown.style.display = 'none';
          }, 300);
        });
      });

      // Handle window resize
      window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && isMenuOpen) {
          isMenuOpen = false;
          menuToggle.classList.remove('active');
          mobileDropdown.classList.remove('show');
          mobileDropdown.style.display = 'none';
        }
      });
    });
  </script>


  <footer class="footer">
    <div class="footer-bottom">
      <p>&copy; 2025 KeyNest. All rights reserved.</p>
    </div>
  </footer>


</body>

</html>