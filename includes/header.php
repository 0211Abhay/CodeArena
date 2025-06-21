<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    header {
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 100;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif !important;
    }

    .nav-container {
        max-width: 95%;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        flex-wrap: wrap; /* Allows wrapping of items for smaller screens */
    }

    .logo {
        font-size: 24px;
        font-weight: 700;
        color: #02959F;
        text-decoration: none;
        display: flex;
        align-items: center;
        flex: 1;
    }

    .logo-icon {
        margin-right: 10px;
        font-size: 28px;
    }

    .nav-links {
        display: flex;
        list-style: none;
        margin-left: auto;
        padding: 0;
        display: flex;
        flex-wrap: wrap; /* Wrap links for small screens */
        justify-content: flex-end; /* Align links to the right */
    }

    .nav-links li {
        margin-left: 30px;
    }

    .nav-links a {
        text-decoration: none;
        color: #2B2D42;
        font-weight: 500;
        transition: color 0.3s ease;
        letter-spacing: 0.2px;
    }

    .nav-links a:hover,
    .nav-links a.active {
        color: #02959F;
        font-weight: 600;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #02959F;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        margin-left: 15px;
        cursor: pointer;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .nav-container {
            justify-content: space-between;
            padding: 10px;
        }

        .nav-links {
            display: none; /* Hide nav links initially on mobile */
            width: 100%;
            flex-direction: column;
            margin-top: 10px;
            text-align: center;
        }

        .nav-links li {
            margin-left: 0;
            margin-bottom: 15px;
        }

        .nav-links.active {
            display: flex;
        }

        .hamburger {
            display: block;
            cursor: pointer;
            font-size: 30px;
            color: #2B2D42;
        }

        .hamburger.active {
            color: #02959F;
        }

        /* Adjust user-avatar position */
        .user-avatar {
            margin-left: 0;
        }
    }

    /* Menu toggle button (hamburger) */
    .hamburger {
        display: none;
    }
</style>

<header>
    <div class="nav-container">
        <a href="#" class="logo">
            <span class="logo-icon"><img src="../../assets/images/Logo_Black_Text-removebg_resized.png" alt="" srcset=""></span>
            <!-- Code<span>Arena</span> -->
        </a>

        <!-- Hamburger Button for Mobile -->
        <div class="hamburger" onclick="toggleMenu()">&#9776;</div>

        <ul class="nav-links">
            <li><a href="#" class="active">Problems</a></li>
            <li><a href="#">Exam</a></li>
            <li><a href="#">Discuss</a></li>
            <li><a href="#">Leaderboard</a></li>
            <li><a href="#">Preparation Resources</a></li>
        </ul>

        <div class="user-actions">
            <div class="user-avatar">JS</div>
        </div>
    </div>
</header>

<!-- Add JavaScript for toggling the menu on mobile -->
<script>
    function toggleMenu() {
        const navLinks = document.querySelector('.nav-links');
        navLinks.classList.toggle('active');
        const hamburger = document.querySelector('.hamburger');
        hamburger.classList.toggle('active');
    }
</script>
