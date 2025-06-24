<?php
session_start();
include('connect.php');

$userEmail = $_SESSION['email'];

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
  $subject = trim($_POST['subject']);
  $message = trim($_POST['message']);

  // Validate inputs
  if (empty($subject) || empty($message)) {
    $_SESSION['feedback_message'] = "Please fill in all fields";
    header("Location: notifications.php");
    exit();
  }

  // Prepare and execute query
  $sql = "INSERT INTO feedback (user_email, subject, message) VALUES (?, ?, ?)";
  $params = array($userEmail, $subject, $message);
  $stmt = sqlsrv_prepare($conn, $sql, $params);

  if ($stmt && sqlsrv_execute($stmt)) {
    $_SESSION['feedback_message'] = "Thank you! Your feedback has been submitted successfully.";
  } else {
    $_SESSION['feedback_message'] = "Error submitting feedback. Please try again.";
    error_log("Feedback error: " . print_r(sqlsrv_errors(), true));
  }
  sqlsrv_free_stmt($stmt);
  header("Location: notifications.php");
  exit();
}

// Fetch notifications
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
  <style>
    .feedback-section {
      margin-top: 40px;
      padding: 30px;
      background: rgba(255, 255, 255, 0.8);
      /* Frosted glass effect */
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-radius: 12px;
      border: 1px solid #dee2e6;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }

    .feedback-form {
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
      text-align: center;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #444;
      font-size: 15px;
      text-align: center;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
      background: #f9f9f9;
      transition: all 0.3s ease;
      box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .form-group textarea {
      min-height: 120px;
      resize: vertical;
    }

    .feedback-btn {
      background: #007bff;
      color: white;
      padding: 12px 28px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 15px;
      font-weight: 500;
      transition: background 0.3s ease, transform 0.2s ease;
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }

    .feedback-btn:hover {
      background: #0056b3;
      transform: translateY(-2px);
    }
  </style>
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
        <li><a href="profile.php">MY PROFILE</a></li>
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

    <!-- Feedback Section -->
    <div class="feedback-section">
      <h3>Send Feedback</h3>
      <?php if (isset($_SESSION['feedback_message'])): ?>
        <div class="alert alert-<?= strpos($_SESSION['feedback_message'], 'Thank') !== false ? 'success' : 'danger' ?>">
          <?= $_SESSION['feedback_message'] ?>
        </div>
        <?php unset($_SESSION['feedback_message']); ?>
      <?php endif; ?>

      <form method="POST" action="notifications.php" class="feedback-form">
        <div class="form-group">
          <label for="subject">Subject:</label>
          <input type="text" id="subject" name="subject" required>
        </div>
        <div class="form-group">
          <label for="message">Message:</label>
          <textarea id="message" name="message" required></textarea>
        </div>
        <button type="submit" name="submit_feedback" class="feedback-btn">Send Feedback</button>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      $('#notificationsTable').DataTable({
        order: [
          [1, 'desc']
        ]
      });

      // Show feedback message if exists
      <?php if (isset($_SESSION['feedback_message'])): ?>
        Swal.fire({
          title: 'Feedback',
          text: '<?= $_SESSION['feedback_message'] ?>',
          icon: '<?= strpos($_SESSION['feedback_message'], 'successfully') !== false ? 'success' : 'error' ?>',
          confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['feedback_message']); ?>
      <?php endif; ?>
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