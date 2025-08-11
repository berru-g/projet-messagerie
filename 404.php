<?php
require_once  '../includes/config.php';
require_once  '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);

?>


<link
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  rel="stylesheet"
/>
<link
  href="https://unpkg.com/aos@next/dist/aos.css"
  rel="stylesheet"
/>

<style>
  @import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap");

  /* Reset & base */
  * {
    box-sizing: border-box;
  }
  body {
    margin: 0;
    background: #f5f6fa;
    font-family: "Montserrat", sans-serif;
    color: #2c2c3a;
    overflow-x: hidden;
  }
  a {
    color: #9c8dea;
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }

  /* Canvas background container */
  #canvas-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: -1;
    background: linear-gradient(135deg, #f0f0fc, #d8d6f9);
  }

  /* Container */
  .agora-intro {
    max-width: 900px;
    margin: 70px auto 100px;
    padding: 0 20px;
  }

  /* Hero Section */
  .hero {
    text-align: center;
    margin-bottom: 60px;
  }
  
  .hero h1 {
    font-weight: 700;
    font-size: 3rem;
    color: #4b47a1;
    margin-bottom: 8px;
    text-shadow: 0 0 8px #9c8deaaa;
  }
  .hero h1 strong {
    color: #9c8dea;
  }
  .hero .count {
    font-size: 1.2rem;
    max-width: 580px;
    margin: 0 auto 30px;
    color: #5a5780;
  }
  .hero .btn-primary {
    background: #9c8dea;
    border: none;
    padding: 15px 40px;
    color: white;
    font-weight: 600;
    border-radius: 30px;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(156, 141, 234, 0.6);
    transition: all 0.3s ease;
    font-size: 1.1rem;
  }
  .hero .btn-primary:hover {
    background: #7f6ed7;
    box-shadow: 0 9px 25px rgba(127, 110, 215, 0.8);
    transform: translateY(-3px);
  }

  /* Responsive */
  @media (max-width: 480px) {
    .hero h1 {
      font-size: 2.2rem;
    }
    .hero p {
      font-size: 1rem;
    }
  }
</style>

<section class="agora-intro">
  <canvas id="canvas-bg"></canvas>

  <div class="hero" data-aos="fade-up" data-aos-duration="1200">

    <h1>
      <strong>404</strong>
    </h1>
    <div id="countdown" class="count">Redirection dans 3 secondes...</div>
    <a href="/pages/home.php"><button class="btn-primary" aria-label="Essayer Agora Social Feed maintenant">
      Revenir à la maison
    </button></a>
  </div>
</section>
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>
  AOS.init({
    once: true,
    easing: "ease-in-out-cubic",
    duration: 900,
  });
  
  // Compte à rebours
        let time = 3;
        const countdown = document.getElementById('countdown');
        const timer = setInterval(() => {
            time--;
            countdown.textContent = `Redirection dans ${time} seconde${time > 1 ? 's' : ''}...`;
            if (time <= 0) {
                clearInterval(timer);
                window.location.href = '/pages/home.php';
            }
        }, 1000);

  // Canvas background - points network (simple, performant)

  const canvas = document.getElementById("canvas-bg");
  const ctx = canvas.getContext("2d");
  let width, height;
  let points = [];

  function resize() {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width * devicePixelRatio;
    canvas.height = height * devicePixelRatio;
    canvas.style.width = width + "px";
    canvas.style.height = height + "px";
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(devicePixelRatio, devicePixelRatio);
  }

  class Point {
    constructor(x, y, vx, vy) {
      this.x = x;
      this.y = y;
      this.vx = vx;
      this.vy = vy;
      this.radius = 2;
    }
    update() {
      this.x += this.vx;
      this.y += this.vy;
      if (this.x < 0 || this.x > width) this.vx = -this.vx;
      if (this.y < 0 || this.y > height) this.vy = -this.vy;
    }
    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = "rgba(156, 141, 234, 0.7)";
      ctx.fill();
    }
  }

  function connectPoints() {
    let maxDist = 130;
    for (let i = 0; i < points.length; i++) {
      for (let j = i + 1; j < points.length; j++) {
        let dx = points[i].x - points[j].x;
        let dy = points[i].y - points[j].y;
        let dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < maxDist) {
          ctx.beginPath();
          ctx.strokeStyle = `rgba(156, 141, 234, ${1 - dist / maxDist})`;
          ctx.lineWidth = 1;
          ctx.moveTo(points[i].x, points[i].y);
          ctx.lineTo(points[j].x, points[j].y);
          ctx.stroke();
        }
      }
    }
  }

  function animate() {
    ctx.clearRect(0, 0, width, height);
    points.forEach((p) => {
      p.update();
      p.draw();
    });
    connectPoints();
    requestAnimationFrame(animate);
  }

  function init() {
    points = [];
    for (let i = 0; i < 140; i++) {
      let x = Math.random() * width;
      let y = Math.random() * height;
      let vx = (Math.random() - 0.5) * 0.3;
      let vy = (Math.random() - 0.5) * 0.3;
      points.push(new Point(x, y, vx, vy));
    }
    animate();
  }

  window.addEventListener("resize", () => {
    resize();
    init();
  });

  resize();
  init();
</script>
