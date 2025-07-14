<?php
session_start();
include('connect.php');

$userEmail = $_SESSION['email'];

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
  $subject = trim($_POST['subject']);
  $message = trim($_POST['message']);

  if (empty($subject) || empty($message)) {
    $_SESSION['feedback_message'] = "Please fill in all fields";
    header("Location: notifications.php");
    exit();
  }

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

$sql = "SELECT * FROM notifications WHERE user_email = ? ORDER BY date_created DESC";
$params = array($userEmail);
$notifications = sqlsrv_query($conn, $sql, $params);

if ($notifications === false) {
  echo "Query failed:<br>";
  die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notifications - KeyNest</title>

  <!-- External Styles -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
  <link rel="stylesheet" href="./css/notifications.css" />

  <style>
    .feedback-section {
      margin-top: 40px;
      padding: 30px;
      background: rgba(255, 255, 255, 0.8);
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

    @media screen and (max-width: 768px) {

      .container h2,
      .container h3 {
        font-size: 1.4rem;
        color: #111 !important;
        text-align: center;
        display: block;
        margin: 1.5rem auto 1rem;
      }
    }
  </style>
</head>

<body>
  <!-- Navbar -->
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
    <div class="menu-toggle" id="menuToggle"><span></span><span></span><span></span></div>
    <a href="logout.php" class="btn">LOGOUT</a>
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
      <h3>Applicant Feedback</h3>
      <form method="POST" action="notifications.php" class="feedback-form">
        <div class="form-group">
          <label for="subject">Subject:</label>
          <input type="text" id="subject" name="subject" required />
        </div>
        <div class="form-group">
          <label for="message">Message:</label>
          <textarea id="message" name="message" required></textarea>
        </div>
        <button type="submit" name="submit_feedback" class="feedback-btn">Send Feedback</button>
      </form>
    </div>
  </div>

  <footer class="footer">
    <div class="footer-bottom">
      <p>&copy; 2025 KeyNest. All rights reserved.</p>
    </div>
  </footer>

  <!-- Scripts loaded at the bottom -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
  <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      $('#notificationsTable').DataTable({
        order: [
          [1, 'desc']
        ]
      });

      <?php if (isset($_SESSION['feedback_message'])): ?>
        Swal.fire({
          title: 'Feedback',
          text: '<?= $_SESSION['feedback_message'] ?>',
          icon: '<?= strpos($_SESSION['feedback_message'], 'successfully') !== false ? 'success' : 'error' ?>',
          confirmButtonText: 'OK'
        });
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

    document.addEventListener('DOMContentLoaded', () => {
      const menuToggle = document.getElementById('menuToggle');
      const mobileDropdown = document.getElementById('mobileDropdown');
      let isMenuOpen = false;

      menuToggle.addEventListener('click', () => {
        isMenuOpen = !isMenuOpen;
        menuToggle.classList.toggle('active');
        if (isMenuOpen) {
          mobileDropdown.style.display = 'block';
          setTimeout(() => mobileDropdown.classList.add('show'), 10);
        } else {
          mobileDropdown.classList.remove('show');
          setTimeout(() => mobileDropdown.style.display = 'none', 300);
        }
      });

      document.addEventListener('click', (e) => {
        if (!menuToggle.contains(e.target) && !mobileDropdown.contains(e.target) && isMenuOpen) {
          isMenuOpen = false;
          menuToggle.classList.remove('active');
          mobileDropdown.classList.remove('show');
          setTimeout(() => mobileDropdown.style.display = 'none', 300);
        }
      });

      mobileDropdown.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
          isMenuOpen = false;
          menuToggle.classList.remove('active');
          mobileDropdown.classList.remove('show');
          setTimeout(() => mobileDropdown.style.display = 'none', 300);
        });
      });

      window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && isMenuOpen) {
          isMenuOpen = false;
          menuToggle.classList.remove('active');
          mobileDropdown.classList.remove('show');
          mobileDropdown.style.display = 'none';
        }
      });
    });
  </script>
</body>

</html>

<?php
// Clear feedback message session after page load
if (isset($_SESSION['feedback_message'])) {
  unset($_SESSION['feedback_message']);
}
?>
