<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rusumo High School | Official Portal</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- =========================
         HEADER
    ========================== -->
    <header class="header">

        <div class="logo-area">
            <img src="assets/images/logo.png" alt="Rusumo High School Logo">

            <div>
                <h1>Rusumo High School</h1>
                <p>Knowledge • Discipline • Success</p>
            </div>
        </div>

        <nav class="navbar">
            <button id="menu-btn">Menu</button>

            <ul id="nav-links">
                <li><a href="#hero">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#academics">Academics</a></li>
                <li><a href="#student-life">Student Life</a></li>
                <li><a href="#portal">Portal</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="auth/login.php">Admin</a></li>
            </ul>
        </nav>

    </header>

    <!-- =========================
         HERO SECTION
    ========================== -->
    <section id="hero" class="hero">

        <div class="hero-text">

            <span class="hero-tag">
                Rwanda's Future Starts Here
            </span>

            <h2>
                Empowering Students Through Modern Education
            </h2>

            <p>
                Rusumo High School combines academic excellence,
                discipline, innovation, and leadership to prepare
                students for Rwanda's future and the global stage.
            </p>

            <div class="hero-buttons">
                <a href="#portal" class="primary-btn">
                    Student Portal
                </a>

                <a href="#academics" class="secondary-btn">
                    Explore Academics
                </a>
            </div>

        </div>

        <div class="hero-image">
            <img src="assets/images/headmaster.jpg" alt="Rusumo High School Campus">
        </div>

    </section>

    <!-- =========================
         ABOUT
    ========================== -->
    <section id="about" class="content-section">

        <h2>About Rusumo High School</h2>

        <p>
            Located in Kirehe District, Eastern Province,
            Rusumo High School is committed to providing
            quality education, discipline, and innovation-driven
            learning experiences for students across Rwanda.
        </p>

    </section>

    <!-- =========================
         ACADEMICS
    ========================== -->
    <section id="academics" class="content-section">

        <h2>Academic Combinations</h2>

        <input
            type="text"
            id="academic-search"
            placeholder="Search combinations..."
        >

        <div class="card-grid">

            <div class="card academic-card">
                <h3>PCM</h3>
                <p>Physics • Chemistry • Mathematics</p>
            </div>

            <div class="card academic-card">
                <h3>MCB</h3>
                <p>Mathematics • Chemistry • Biology</p>
            </div>

            <div class="card academic-card">
                <h3>MEG</h3>
                <p>Mathematics • Economics • Geography</p>
            </div>

            <div class="card academic-card">
                <h3>HEG</h3>
                <p>History • Economics • Geography</p>
            </div>

        </div>

    </section>

    <!-- =========================
         STUDENT LIFE
    ========================== -->
    <section id="student-life" class="content-section">

        <h2>Student Life</h2>

        <div class="card-grid">

            <div class="card">
                <h3>Sports</h3>
                <p>
                    Football, basketball, volleyball,
                    athletics, and interschool competitions.
                </p>
            </div>

            <div class="card">
                <h3>Clubs</h3>
                <p>
                    Debate club, anti-crime club,
                    peace club, and environmental initiatives.
                </p>
            </div>

            <div class="card">
                <h3>Innovation</h3>
                <p>
                    Students participate in STEM projects,
                    robotics, and science competitions.
                </p>
            </div>

        </div>

    </section>

    <!-- =========================
         PARENT PORTAL
    ========================== -->
    <section id="portal" class="content-section">

        <h2>Parent Portal</h2>

        <form id="portal-form">

            <input
                type="text"
                id="student-id"
                placeholder="Enter Student ID"
                required
            >

            <input
                type="password"
                id="student-pin"
                placeholder="Enter PIN"
                required
            >

            <button type="submit">
                Access Student Report
            </button>

        </form>

        <div id="result-box"></div>

    </section>

    <!-- =========================
         CONTACT
    ========================== -->
    <section id="contact" class="content-section">

        <h2>Contact Us</h2>

        <p>Email: info@rusumohighschool.rw</p>
        <p>Location: Kirehe District, Rwanda</p>

    </section>

    <!-- =========================
         FOOTER
    ========================== -->
    <footer class="footer">

        <p>
            &copy; 2026 Rusumo High School &mdash;
            All Rights Reserved
        </p>

    </footer>

    <script src="script.js"></script>

</body>
</html>
