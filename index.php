<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lewis Car Wash - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><i class="fas fa-car"></i> Lewis Car Wash</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2" href="login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="py-5 text-center bg-primary text-white">
  <div class="container">
    <h1 class="display-4 fw-bold">Welcome to Lewis Car Wash</h1>
    <p class="lead">Premium car care, fast and affordable. Experience the shine!</p>
    <a href="login.php" class="btn btn-light btn-lg mt-3"><i class="fas fa-sign-in-alt"></i> Get Started</a>
  </div>
</section>

<!-- About Us Section -->
<section id="about" class="py-5 bg-light">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 mb-4 mb-md-0">
        <img src="assets/logo.png" alt="Lewis Car Wash" class="img-fluid rounded shadow">
      </div>
      <div class="col-md-6">
        <h2>About Us</h2>
        <p>Lewis Car Wash is dedicated to providing top-notch car cleaning and detailing services. With years of experience, our team ensures your vehicle gets the best care using eco-friendly products and modern techniques. We value customer satisfaction and strive to make every visit a pleasant experience.</p>
        <ul class="list-unstyled">
          <li><i class="fas fa-check-circle text-success"></i> Professional Staff</li>
          <li><i class="fas fa-check-circle text-success"></i> Eco-Friendly Products</li>
          <li><i class="fas fa-check-circle text-success"></i> Fast & Reliable Service</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Services Section -->
<section id="services" class="py-5">
  <div class="container">
    <h2 class="text-center mb-5">Our Services</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100 text-center shadow">
          <div class="card-body">
            <i class="fas fa-broom fa-3x text-primary mb-3"></i>
            <h5 class="card-title">Exterior Wash</h5>
            <p class="card-text">Thorough cleaning of your car's exterior for a sparkling finish.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 text-center shadow">
          <div class="card-body">
            <i class="fas fa-vacuum fa-3x text-primary mb-3"></i>
            <h5 class="card-title">Interior Vacuum</h5>
            <p class="card-text">Deep vacuuming and cleaning of your car's interior for a fresh feel.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 text-center shadow">
          <div class="card-body">
            <i class="fas fa-spray-can-sparkles fa-3x text-primary mb-3"></i>
            <h5 class="card-title">Detailing</h5>
            <p class="card-text">Comprehensive detailing to restore your car's shine and protect its value.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-4">Contact Us</h2>
    <div class="row justify-content-center">
      <div class="col-md-8">
        <form>
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" placeholder="Your Name">
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" placeholder="Your Email">
          </div>
          <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" id="message" rows="4" placeholder="Your Message"></textarea>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Message</button>
        </form>
      </div>
    </div>
  </div>
</section>

<footer class="bg-dark text-white text-center py-3 mt-5">
  <div class="container">
    <small>&copy; <?php echo date('Y'); ?> Lewis Car Wash. All rights reserved.</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
