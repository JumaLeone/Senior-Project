<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/homepage-style.css">
    <title>KeyNest: Allocation System</title>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="homepage.php" class="logo">KeyNest</a>

        <!-- Desktop Menu -->
        <div class="menu">
            <ul>
                <li><a href="#" class="active">HOME</a></li>
                <li><a href="profile.php">MY PROFILE</a></li>
                <li><a href="searching.php">HOUSING OFFERS</a></li>
                <li><a href="notifications.php">NOTIFICATIONS</a></li>
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
                <li><a href="#" class="active">HOME</a></li>
                <li><a href="profile.php">MY PROFILE</a></li>
                <li><a href="searching.php">HOUSING OFFERS</a></li>
                <li><a href="notifications.php">NOTIFICATIONS</a></li>
                <li><a href="homepage.php#about">ABOUT</a></li>
            </ul>
        </div>
    </nav>

    <!-- Your page content goes here -->
    <div class="content">
        <!-- Add your profile content here -->
    </div>

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

    <section class="slideshow">
        <div class="slide active">
            <div class="slide-image-container">
                <img src="img/1.png" alt="Modern Home" class="slide-image">
                <div class="overlay"></div>
            </div>
            <div class="slide-content">
                <h2>Quality Living for Every Budget</h2>
                <p>Affordable, secure, and modern homes—designed to make homeownership accessible for every family.</p>
                <a href="searching.php" class="btn apply-now">Apply Now</a>
            </div>
        </div>
        <div class="slide">
            <div class="slide-image-container">
                <img src="img/2.png" alt="Sustainable Community" class="slide-image">
                <div class="overlay"></div>
            </div>
            <div class="slide-content">
                <h2>Sustainable Communities</h2>
                <p>Join vibrant, eco-friendly communities offering affordable, sustainable living for all.</p>
                <a href="searching.php" class="btn apply-now">Apply Now</a>
            </div>
        </div>
        <div class="slide">
            <div class="slide-image-container">
                <img src="img/3.png" alt="Affordable Housing" class="slide-image">
                <div class="overlay"></div>
            </div>
            <div class="slide-content">
                <h2>Affordable Housing</h2>
                <p>Own the home you live in through the Tenant Purchase System — offering quality housing at affordable prices and making homeownership a reality for long-term tenants.</p>
                <a href="searching.php" class="btn apply-now">Apply Now</a>
            </div>
        </div>
        <div class="slide-arrows">
            <button class="arrow prev" onclick="previousSlide()">&#8249;</button>
            <button class="arrow next" onclick="nextSlide()">&#8250;</button>
        </div>
    </section>

    <section id="about" class="about">
        <div class="about-content">
            <div class="about-card">
                <h3>Our Mission</h3>
                <p>To make homeownership accessible for tenants through affordable purchase options while fostering sustainable communities.</p>
            </div>
            <div class="about-card">
                <h3>Our Vision</h3>
                <p>Creating inclusive neighborhoods where everyone has access to quality housing and community resources.</p>
            </div>
            <div class="about-card">
                <h3>Our Values</h3>
                <p>Commitment to sustainability, community engagement, and affordable housing solutions.</p>
            </div>
        </div>
    </section>

    <section class="contact">
        <div class="contact-content">
            <h2>Get in Touch</h2>
            <p>We're here to help you find your perfect home</p>
            <div class="contact-info">
                <div>
                    <h3>Email</h3>
                    <p>okothleone@gmail.com</p>
                </div>
                <div>
                    <h3>Phone</h3>
                    <p>(+254) 708 941 090</p>
                </div>
                <div>
                    <h3>Address</h3>
                    <p>849 Kisumu City<br>Kisumu, Kenya</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; 2025 KeyNest. All rights reserved.</p>
        </div>
    </footer>


    <script>
        // Slideshow functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            currentSlide = (index + slides.length) % slides.length;
            slides[currentSlide].classList.add('active');
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function previousSlide() {
            showSlide(currentSlide - 1);
        }

        setInterval(nextSlide, 5000);
    </script>


</body>

</html>