<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kidzenia Kindergarten | Where Learning Meets Joy</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --primary: #7C3AED;
            --primary-soft: #F3E8FF;
            --secondary: #FF8A00;
            --secondary-soft: #FFF3E6;
            --accent-pink: #EC4899;
            --accent-blue: #0EA5E9;
            --dark: #1E1B4B;
            --text: #475569;
            --bg-light: #FAFAFF;
            --white: #ffffff;
            --radius-lg: 30px;
            --radius-md: 20px;
            --shadow: 0 20px 40px rgba(0,0,0,0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Quicksand', sans-serif;
        }

        html { scroll-behavior: smooth; }
        body { background-color: var(--bg-light); color: var(--text); line-height: 1.6; }
        h1, h2, h3, h4 { font-family: 'Baloo 2', cursive; color: var(--dark); font-weight: 700; }

        /* --- Navigation --- */
        nav {
            position: fixed;
            top: 0; width: 100%;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            z-index: 1000;
            transition: 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        }

        .logo { font-size: 1.8rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .logo span { color: var(--secondary); }

        .nav-links { display: flex; list-style: none; gap: 30px; }
        .nav-links a { text-decoration: none; color: var(--dark); font-weight: 700; font-size: 1rem; transition: 0.3s; }
        .nav-links a:hover { color: var(--primary); }

        .btn-main {
            background: linear-gradient(135deg, var(--primary), var(--accent-pink));
            color: white !important;
            padding: 10px 25px;
            border-radius: 50px;
            box-shadow: 0 10px 20px rgba(124, 58, 237, 0.2);
        }

        /* --- Hero Section --- */
        .hero {
            padding: 160px 5% 100px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 50px;
            min-height: 100vh;
        }

        .hero-text { flex: 1; }
        .hero-text h1 { font-size: 4.5rem; line-height: 1.1; margin-bottom: 20px; }
        .hero-text h1 span { color: var(--primary); }
        .hero-text p { font-size: 1.2rem; margin-bottom: 30px; max-width: 500px; }

        .hero-image { flex: 1; position: relative; }
        .hero-image img { width: 100%; border-radius: var(--radius-lg); box-shadow: var(--shadow); }

        /* Floating Cards */
        .float-card {
            position: absolute;
            background: white;
            padding: 15px 20px;
            border-radius: var(--radius-md);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            animation: float 4s ease-in-out infinite;
            z-index: 2;
        }
        @keyframes float { 
            0%, 100% { transform: translateY(0); } 
            50% { transform: translateY(-15px); } 
        }

        /* --- Sections --- */
        section { padding: 80px 5%; }
        .section-header { text-align: center; margin-bottom: 50px; }
        .section-header span { color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 0.9rem; }
        .section-header h2 { font-size: 3rem; margin-top: 10px; }

        /* --- Program Grid --- */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .card { 
            background: var(--white); 
            padding: 30px; 
            border-radius: var(--radius-lg); 
            transition: 0.4s; 
            text-align: center;
            border: 1px solid #f0f0f0;
        }
        .card:hover { transform: translateY(-10px); box-shadow: var(--shadow); border-color: var(--primary-soft); }
        .card .icon { 
            width: 80px; height: 80px; 
            background: var(--primary-soft); 
            color: var(--primary); 
            border-radius: 20px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 2rem; margin: 0 auto 20px;
        }

        /* --- Board (Notice & Events) --- */
        .board-container { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .list-item { 
            background: white; 
            padding: 20px; 
            border-radius: var(--radius-md); 
            margin-bottom: 15px; 
            display: flex; gap: 15px;
            border-left: 5px solid var(--primary);
            transition: 0.3s;
            cursor: pointer;
        }
        .list-item:hover { background: var(--primary-soft); }
        .item-date { font-weight: 800; color: var(--primary); text-transform: uppercase; font-size: 0.8rem; }

        /* --- Gallery --- */
        .gallery-wrap {
            columns: 3 300px;
            column-gap: 20px;
        }
        .gallery-wrap img {
            width: 100%;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .gallery-wrap img:hover { transform: scale(1.03); filter: brightness(0.9); }

        /* --- Footer --- */
        footer { 
            background: var(--dark); 
            color: rgba(255,255,255,0.7); 
            padding: 80px 5% 30px; 
        }
        .footer-grid { 
            display: grid; 
            grid-template-columns: 2fr 1fr 1fr 1.5fr; 
            gap: 40px; margin-bottom: 50px; 
        }
        footer h4 { color: white; margin-bottom: 20px; font-size: 1.5rem; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; display: block; margin-bottom: 10px; }
        footer a:hover { color: var(--secondary); }

        /* --- Responsive --- */
        @media (max-width: 992px) {
            .hero { flex-direction: column; text-align: center; padding-top: 120px; }
            .hero-text h1 { font-size: 3rem; }
            .board-container { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        .burger { display: none; cursor: pointer; font-size: 1.5rem; }
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .burger { display: block; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="#" class="logo">Kidzenia<span>.</span></a>
        <ul class="nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#programs">Programs</a></li>
            <li><a href="#updates">Notice Board</a></li>
            <li><a href="#gallery">Gallery</a></li>
            <li><a href="#contact" class="btn-main">Enroll Now</a></li>
        </ul>
        <div class="burger"><i class="fas fa-bars"></i></div>
    </nav>

    <section class="hero" id="home">
        <div class="hero-text">
            <h1>Where <span>Curiosity</span> Ignites Learning</h1>
            <p>A vibrant, nurturing environment for children aged 2-5. We focus on play-based education and social growth.</p>
            <div style="display: flex; gap: 15px;">
                <a href="#contact" class="btn-main" style="text-decoration: none;">Apply Today</a>
                <a href="#programs" style="text-decoration: none; color: var(--dark); font-weight: 700; padding: 10px;">Explore Programs →</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=800" alt="Preschool kids">
            <div class="float-card" style="top: 10%; left: -5%;">
                <i class="fas fa-paint-brush" style="color: var(--accent-pink);"></i> Creative Arts
            </div>
            <div class="float-card" style="bottom: 15%; right: -5%; animation-delay: 1s;">
                <i class="fas fa-shield-alt" style="color: var(--accent-blue);"></i> Safe Campus
            </div>
        </div>
    </section>

    <section id="programs">
        <div class="section-header">
            <span>Our Journey</span>
            <h2>Learning Paths</h2>
        </div>
        <div class="grid">
            <div class="card">
                <div class="icon" style="background: #E0F2FE; color: var(--accent-blue);"><i class="fas fa-baby"></i></div>
                <h3>Toddler Prep</h3>
                <p>Ages 2 - 3 Years. Sensory play and social-emotional foundation.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: #FFF3E6; color: var(--secondary);"><i class="fas fa-child"></i></div>
                <h3>Nursery</h3>
                <p>Ages 3 - 4 Years. Introduction to language, numbers, and team play.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: #F3E8FF; color: var(--primary);"><i class="fas fa-graduation-cap"></i></div>
                <h3>Kindergarten</h3>
                <p>Ages 4 - 5 Years. Advanced school readiness and creative projects.</p>
            </div>
        </div>
    </section>

    <section id="updates" style="background: white;">
        <div class="section-header">
            <span>Stay Updated</span>
            <h2>School Notice Board</h2>
        </div>
        <div class="board-container">
            <div>
                <h4 style="margin-bottom: 20px;"><i class="fas fa-bullhorn" style="color: var(--primary);"></i> Announcements</h4>
                <div class="list-item">
                    <div>
                        <span class="item-date">May 15, 2026</span>
                        <h3>Summer Camp Registration</h3>
                        <p>Early bird registrations are now open for the 2026 Summer Fun Camp...</p>
                    </div>
                </div>
                <div class="list-item">
                    <div>
                        <span class="item-date">May 10, 2026</span>
                        <h3>New Campus Security Features</h3>
                        <p>We have updated our live GPS tracking systems for all school buses...</p>
                    </div>
                </div>
            </div>
            <div>
                <h4 style="margin-bottom: 20px;"><i class="fas fa-calendar-alt" style="color: var(--secondary);"></i> Upcoming Events</h4>
                <div class="list-item" style="border-color: var(--secondary);">
                    <div>
                        <span class="item-date">June 02, 2026</span>
                        <h3>Annual Sports Day</h3>
                        <p>Location: School Playground. Time: 9:00 AM onwards.</p>
                    </div>
                </div>
                <div class="list-item" style="border-color: var(--secondary);">
                    <div>
                        <span class="item-date">June 10, 2026</span>
                        <h3>Parent-Teacher Meet</h3>
                        <p>A session to discuss quarterly progress and developmental milestones.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="gallery">
        <div class="section-header">
            <span>Moments</span>
            <h2>Life at Kidzenia</h2>
        </div>
        <div class="gallery-wrap">
            <img src="https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=500" alt="Gallery">
            <img src="https://images.unsplash.com/photo-1516627145497-ae6968895b74?auto=format&fit=crop&w=500" alt="Gallery">
            <img src="https://images.unsplash.com/photo-1587654780291-39c9404d746b?auto=format&fit=crop&w=500" alt="Gallery">
            <img src="https://images.unsplash.com/photo-1502086223501-7ea6ecd79368?auto=format&fit=crop&w=500" alt="Gallery">
            <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=500" alt="Gallery">
            <img src="https://images.unsplash.com/photo-1517164850305-99a3e65bb47e?auto=format&fit=crop&w=500" alt="Gallery">
        </div>
    </section>

    <footer id="contact">
        <div class="footer-grid">
            <div>
                <a href="#" class="logo" style="color: white;">Kidzenia<span>.</span></a>
                <p style="margin-top: 15px;">Creating joyful learning experiences through creativity, care, and safety since 2014.</p>
                <div style="margin-top: 20px; font-size: 1.5rem; display: flex; gap: 15px;">
                    <i class="fab fa-facebook"></i><i class="fab fa-instagram"></i><i class="fab fa-youtube"></i>
                </div>
            </div>
            <div>
                <h4>Explore</h4>
                <a href="#home">Home</a>
                <a href="#programs">Programs</a>
                <a href="#gallery">Gallery</a>
            </div>
            <div>
                <h4>Contact</h4>
                <p>123 Learning St, Education City</p>
                <p>+91 98765 43210</p>
                <p>hello@kidzenia.com</p>
            </div>
            <div>
                <h4>Newsletter</h4>
                <p>Get school updates in your inbox.</p>
                <input type="email" placeholder="Your Email" style="width: 100%; padding: 12px; border-radius: 10px; border: none; margin-top: 10px;">
            </div>
        </div>
        <div style="text-align: center; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1);">
            <p>&copy; 2026 Kidzenia Kindergarten. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.style.padding = '10px 5%';
                nav.style.background = '#ffffff';
            } else {
                nav.style.padding = '15px 5%';
                nav.style.background = 'rgba(255, 255, 255, 0.9)';
            }
        });

        // Simple Smooth Scroll for Anchor Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Placeholder for Notice Clicks
        document.querySelectorAll('.list-item').forEach(item => {
            item.addEventListener('click', () => {
                const title = item.querySelector('h3').innerText;
                alert(`Details for: ${title}\n(This would open the Modal in the full version)`);
            });
        });
    </script>
</body>
</html>