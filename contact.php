<?php
session_start();

// No email processing needed - form will redirect to Gmail
$message_sent = false;
$error_message = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Lewis Car Wash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #ffffff;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-left: 0.5rem;
            color: #ffd700;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #ffd700;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        /* Page Title */
        .page-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
        }

        .page-title .highlight {
            color: #ffd700;
        }

        .page-title p {
            font-size: 1.2rem;
            color: #cccccc;
            font-weight: 300;
        }

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        /* Contact Form */
        .contact-form {
            background: rgba(255, 255, 255, 0.05);
            padding: 2.5rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-form h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #ffffff;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #ffffff;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            background-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control::placeholder {
            color: #aaaaaa;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
            background: linear-gradient(135deg, #ffed4a 0%, #ffd700 100%);
        }

        /* Contact Info */
        .contact-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 2.5rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-info h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #ffffff;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .info-icon i {
            font-size: 1.2rem;
            color: #1a1a1a;
        }

        .info-content h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #ffd700;
            font-weight: 600;
        }

        .info-content p {
            color: #cccccc;
            line-height: 1.5;
        }

        .info-content a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .info-content a:hover {
            color: #ffd700;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .alert-success {
            background-color: rgba(72, 187, 120, 0.1);
            color: #68d391;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }

        .alert-danger {
            background-color: rgba(245, 101, 101, 0.1);
            color: #fc8181;
            border: 1px solid rgba(245, 101, 101, 0.3);
        }

        /* Map Section */
        .map-section {
            margin-top: 3rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 2.5rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .map-section h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #ffffff;
            font-weight: 600;
            text-align: center;
        }

        .map-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cccccc;
            font-size: 1.1rem;
            border: 2px dashed rgba(255, 255, 255, 0.1);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            .container {
                padding: 2rem 1rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .page-title h1 {
                font-size: 2.5rem;
            }

            .contact-form,
            .contact-info,
            .map-section {
                padding: 2rem;
            }

            .info-item {
                flex-direction: column;
                text-align: center;
            }

            .info-icon {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 480px) {
            .page-title h1 {
                font-size: 2rem;
            }

            .contact-form,
            .contact-info,
            .map-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="customer_dashboard.php" class="logo">
                <i class="fas fa-arrow-left"></i>
                <h1>Back to Dashboard</h1>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>Get In <span class="highlight">Touch</span></h1>
            <p>We're here to help and answer any questions you might have</p>
        </div>

        <!-- Contact Grid -->
        <div class="contact-grid">
            <!-- Contact Form -->
            <div class="contact-form">
                <h2><i class="fas fa-envelope"></i> Send us a Message</h2>
                
                <?php if ($message_sent): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Thank you! Your message has been sent successfully. We'll get back to you soon.
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form id="contactForm">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required class="form-control" placeholder="Your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required class="form-control" placeholder="your.email@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="+254XXXXXXXXX">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" required class="form-control" placeholder="What is this about?">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required class="form-control" placeholder="Tell us how we can help you..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Information -->
            <div class="contact-info">
                <h2><i class="fas fa-info-circle"></i> Contact Information</h2>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email Us</h3>
                        <p><a href="mailto:lewiscarwash@gmail.com">lewiscarwash@gmail.com</a></p>
                        <p>We typically respond within 24 hours</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Call Us</h3>
                        <p><a href="tel:+254742512195">+254 742 512 195</a></p>
                        <p>Monday - Saturday: 8:00 AM - 6:00 PM</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Visit Us</h3>
                        <p>China Square K.U<br>
                        Nairobi, Kenya</p>
                        <p>Near Kenyatta University</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 8:00 AM - 7:00 PM<br>
                        Saturday: 8:00 AM - 6:00 PM<br>
                        Sunday: 9:00 AM - 5:00 PM</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h2><i class="fas fa-map"></i> Find Us</h2>
            <div style="width: 100%; height: 400px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.8193458234147!2d36.9316864!3d-1.1762583!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f1a6bf7445dc1%3A0x940b062f5e5e5c1a!2sChina%20Square%20K.U!5e0!3m2!1sen!2ske!4v1635789123456!5m2!1sen!2ske" 
                    width="100%" 
                    height="400" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <div style="margin-top: 1rem; text-align: center;">
                <p style="color: #cccccc; font-size: 0.95rem;">
                    <i class="fas fa-map-marker-alt" style="color: #ffd700;"></i>
                    China Square K.U, Nairobi, Kenya
                </p>
                <div style="margin-top: 1rem;">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=-1.1762583,36.9338751" 
                       target="_blank" 
                       style="display: inline-block; background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%); color: #1a1a1a; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                        <i class="fas fa-directions"></i> Get Directions
                    </a>
                    <a href="https://www.google.com/maps/place/-1.1762583,36.9338751/@-1.1762583,36.9338751,17z" 
                       target="_blank" 
                       style="display: inline-block; background: rgba(255, 255, 255, 0.1); color: #ffffff; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin-left: 1rem; transition: all 0.3s ease;">
                        <i class="fas fa-external-link-alt"></i> View on Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle form submission and redirect to Gmail
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            // Validate required fields
            if (!name || !email || !subject || !message) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Create email body
            const emailBody = `Hello Lewis Car Wash,

Name: ${name}
Email: ${email}
Phone: ${phone || 'Not provided'}
Subject: ${subject}

Message:
${message}

---
This message was sent from the Lewis Car Wash contact form.
Lewis Car Wash - China Square K.U, Nairobi, Kenya`;
            
            // Create Gmail compose URL
            const gmailUrl = `https://mail.google.com/mail/?view=cm&fs=1&to=lewiscarwash@gmail.com&su=${encodeURIComponent('Contact Form: ' + subject)}&body=${encodeURIComponent(emailBody)}`;
            
            // Open Gmail in new tab
            window.open(gmailUrl, '_blank');
            
            // Show success message
            alert('Gmail will open in a new tab with your message pre-filled. Please review and send the email.');
            
            // Reset form
            this.reset();
        });

        // Auto-hide success/error messages after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        });

        // Form validation
        const form = document.getElementById('contactForm');
        const inputs = form.querySelectorAll('input[required], textarea[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#fc8181';
                } else {
                    this.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                }
            });
        });
    </script>
</body>
</html>