<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Database connection - using directly from config.php, not relying on $db_config
// The PDO connection should already be established in config.php

// Function to get user's bookings
function getUserBookings($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   cs.name AS station_name, 
                   cs.address AS station_address, 
                   cs.image AS image_url 
            FROM bookings b
            JOIN charging_stations cs ON b.station_id = cs.station_id
            WHERE b.user_id = ?
            GROUP BY b.station_id
            ORDER BY MAX(b.booking_date) DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching bookings: " . $e->getMessage());
        return [];
    }
}

// Function to get user's existing reviews
function getUserReviews($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM station_reviews 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching reviews: " . $e->getMessage());
        return [];
    }
}

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $stationId = $_POST['station_id'];
    $rating = $_POST['rating'];
    $reviewText = $_POST['review_text'];
    $userId = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($rating) || empty($reviewText)) {
        $errorMessage = "Please provide both a rating and review text.";
    } else {
        try {
            // Check if user already submitted a review for this station
            $stmt = $pdo->prepare("SELECT id FROM station_reviews WHERE user_id = ? AND station_id = ?");
            $stmt->execute([$userId, $stationId]);
            $existingReview = $stmt->fetch();
            
            if ($existingReview) {
                // Update existing review
                $stmt = $pdo->prepare("
                    UPDATE station_reviews 
                    SET rating = ?, review_text = ?, updated_at = NOW() 
                    WHERE user_id = ? AND station_id = ?
                ");
                $stmt->execute([$rating, $reviewText, $userId, $stationId]);
                $successMessage = "Your review has been updated successfully!";
            } else {
                // Insert new review
                $stmt = $pdo->prepare("
                    INSERT INTO station_reviews (user_id, station_id, rating, review_text, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$userId, $stationId, $rating, $reviewText]);
                $successMessage = "Your review has been submitted successfully!";
            }
        } catch (PDOException $e) {
            error_log("Error submitting review: " . $e->getMessage());
            $errorMessage = "Failed to submit review. Please try again.";
        }
    }
}

// Get user bookings and reviews
$userBookings = getUserBookings($_SESSION['user_id']);
$userReviews = getUserReviews($_SESSION['user_id']);

// Create a lookup for easy access to user's reviews
$reviewsByStation = [];
foreach ($userReviews as $review) {
    $reviewsByStation[$review['station_id']] = $review;
}

// Create the station_reviews table if it doesn't exist yet
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS station_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            station_id INT NOT NULL,
            rating INT NOT NULL,
            review_text TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY user_station (user_id, station_id),
            FOREIGN KEY (user_id) REFERENCES tbl_users(user_id),
            FOREIGN KEY (station_id) REFERENCES charging_stations(station_id)
        )
    ");
} catch (PDOException $e) {
    error_log("Error creating reviews table: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVolve - Write Reviews</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #4CAF50;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --gray-color: #64748b;
            --danger-color: #dc3545;
            --success-color: #28a745;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #4a6cfa, #2d95bd);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
        }
        
        .logo-text {
            letter-spacing: 1px;
        }
        
        .highlight {
            color: #4CAF50;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 24px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 40px;
            color: var(--dark-color);
        }
        
        .page-description {
            text-align: center;
            margin-bottom: 40px;
            color: var(--gray-color);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .reviews-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
        }
        
        .station-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .station-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .station-image {
            height: 200px;
            background-color: #f0f0f0;
            overflow: hidden;
        }
        
        .station-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .station-card:hover .station-image img {
            transform: scale(1.05);
        }
        
        .station-content {
            padding: 20px;
        }
        
        .station-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .station-address {
            color: var(--gray-color);
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        
        .station-address i {
            color: var(--primary-color);
            margin-top: 4px;
        }
        
        .booking-date {
            font-size: 14px;
            color: var(--gray-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .booking-date i {
            color: var(--primary-color);
        }
        
        .review-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .review-status.reviewed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .review-status.not-reviewed {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: #f0f7ff;
        }
        
        .btn-full {
            width: 100%;
        }
        
        .review-form {
            margin-top: 20px;
            display: none;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            cursor: pointer;
            font-size: 25px;
            color: #ddd;
            transition: color 0.3s ease;
        }
        
        .star-rating input:checked ~ label {
            color: #ffc107;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffdb70;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .existing-review {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .existing-review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .existing-rating {
            color: #ffc107;
            font-size: 18px;
        }
        
        .review-date {
            font-size: 12px;
            color: var(--gray-color);
        }
        
        .review-text {
            color: var(--dark-color);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .edit-review-btn {
            color: var(--primary-color);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-review-btn:hover {
            text-decoration: underline;
        }
        
        .no-bookings {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .no-bookings i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-bookings h3 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .no-bookings p {
            color: var(--gray-color);
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .reviews-container {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 0 15px;
            }
            
            .station-image {
                height: 180px;
            }
        }
        
        .booking-details {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .booking-details div {
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .booking-details i {
            color: var(--primary-color);
            width: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .status-confirmed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-in_progress {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <i class="fas fa-charging-station"></i>
                <span class="logo-text">E<span class="highlight">V</span>olve</span>
            </div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Home
                </a>
                <a href="user_stations.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    Find Stations
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    My Bookings
                </a>
                <a href="reviews.php" class="nav-link active">
                    <i class="fas fa-star"></i>
                    Reviews
                </a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1 class="page-title">Review Your EV Charging Experience</h1>
        <p class="page-description">Share your feedback about the stations you've visited. Your reviews help other EV drivers find the best charging spots.</p>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($userBookings)): ?>
            <div class="no-bookings">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Found</h3>
                <p>You haven't booked any charging stations yet. Once you make a booking, you'll be able to leave reviews.</p>
                <a href="user_stations.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Find Stations
                </a>
            </div>
        <?php else: ?>
            <div class="reviews-container">
                <?php foreach ($userBookings as $booking): ?>
                    <div class="station-card">
                        <div class="station-image">
                            <img src="<?php echo !empty($booking['image_url']) ? htmlspecialchars($booking['image_url']) : 'assets/img/station-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($booking['station_name'] ?? $booking['name'] ?? 'Unknown Station'); ?>">
                        </div>
                        <div class="station-content">
                            <h3 class="station-name"><?php echo htmlspecialchars($booking['station_name'] ?? $booking['name'] ?? 'Unknown Station'); ?></h3>
                            <div class="station-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($booking['station_address'] ?? $booking['address'] ?? 'No Address'); ?></span>
                            </div>
                            <div class="booking-date">
                                <i class="fas fa-calendar-check"></i>
                                <span>Last visited: <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></span>
                            </div>
                            <div class="booking-details">
                                <div><i class="fas fa-clock"></i> Time: <?php echo date('h:i A', strtotime($booking['booking_time'])); ?></div>
                                <div><i class="fas fa-hourglass-half"></i> Duration: <?php echo $booking['duration']; ?> mins</div>
                                <div><i class="fas fa-money-bill-wave"></i> Amount: ₹<?php echo number_format($booking['amount'], 2); ?></div>
                                <div><i class="fas fa-info-circle"></i> Status: 
                                    <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if (isset($reviewsByStation[$booking['station_id']])): ?>
                                <?php $review = $reviewsByStation[$booking['station_id']]; ?>
                                <span class="review-status reviewed">
                                    <i class="fas fa-check-circle"></i> Reviewed
                                </span>
                                
                                <div class="existing-review">
                                    <div class="existing-review-header">
                                        <div class="existing-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="review-date">
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </div>
                                    </div>
                                    <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                    <button type="button" class="edit-review-btn" onclick="showReviewForm('form-<?php echo $booking['station_id']; ?>')">
                                        <i class="fas fa-edit"></i> Edit Review
                                    </button>
                                </div>
                                
                                <div id="form-<?php echo $booking['station_id']; ?>" class="review-form">
                                    <form action="reviews.php" method="post">
                                        <input type="hidden" name="station_id" value="<?php echo $booking['station_id']; ?>">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Your Rating</label>
                                            <div class="star-rating">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <input type="radio" id="star<?php echo $i; ?>-<?php echo $booking['station_id']; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($review['rating'] == $i) ? 'checked' : ''; ?>>
                                                    <label for="star<?php echo $i; ?>-<?php echo $booking['station_id']; ?>" title="<?php echo $i; ?> stars">
                                                        <i class="fas fa-star"></i>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label" for="review-<?php echo $booking['station_id']; ?>">Your Review</label>
                                            <textarea class="form-control" id="review-<?php echo $booking['station_id']; ?>" name="review_text" rows="4" placeholder="Share your experience at this station..."><?php echo htmlspecialchars($review['review_text']); ?></textarea>
                                        </div>
                                        
                                        <button type="submit" name="submit_review" class="btn btn-primary btn-full">
                                            <i class="fas fa-save"></i> Update Review
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="review-status not-reviewed">
                                    <i class="fas fa-pen"></i> Not Reviewed Yet
                                </span>
                                
                                <button type="button" class="btn btn-primary btn-full" onclick="showReviewForm('form-<?php echo $booking['station_id']; ?>')">
                                    <i class="fas fa-star"></i> Write a Review
                                </button>
                                
                                <div id="form-<?php echo $booking['station_id']; ?>" class="review-form">
                                    <form action="reviews.php" method="post">
                                        <input type="hidden" name="station_id" value="<?php echo $booking['station_id']; ?>">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Your Rating</label>
                                            <div class="star-rating">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <input type="radio" id="star<?php echo $i; ?>-<?php echo $booking['station_id']; ?>" name="rating" value="<?php echo $i; ?>">
                                                    <label for="star<?php echo $i; ?>-<?php echo $booking['station_id']; ?>" title="<?php echo $i; ?> stars">
                                                        <i class="fas fa-star"></i>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label" for="review-<?php echo $booking['station_id']; ?>">Your Review</label>
                                            <textarea class="form-control" id="review-<?php echo $booking['station_id']; ?>" name="review_text" rows="4" placeholder="Share your experience at this station..."></textarea>
                                        </div>
                                        
                                        <button type="submit" name="submit_review" class="btn btn-primary btn-full">
                                            <i class="fas fa-paper-plane"></i> Submit Review
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showReviewForm(formId) {
            const form = document.getElementById(formId);
            if (form.style.display === 'block') {
                form.style.display = 'none';
            } else {
                // Hide all other forms
                const allForms = document.querySelectorAll('.review-form');
                allForms.forEach(form => {
                    form.style.display = 'none';
                });
                
                // Show the selected form
                form.style.display = 'block';
            }
        }
        
        // Initialize star rating system
        document.addEventListener('DOMContentLoaded', function() {
            const starLabels = document.querySelectorAll('.star-rating label');
            
            starLabels.forEach(label => {
                label.addEventListener('mouseover', function() {
                    // Get the rating form this star belongs to
                    const ratingContainer = this.closest('.star-rating');
                    const stars = ratingContainer.querySelectorAll('label');
                    const starIndex = Array.from(stars).indexOf(this);
                    
                    // Apply hover style to this star and all stars before it
                    for (let i = stars.length - 1; i >= starIndex; i--) {
                        stars[i].classList.add('hover');
                    }
                });
                
                label.addEventListener('mouseout', function() {
                    // Remove hover class from all stars
                    const ratingContainer = this.closest('.star-rating');
                    const stars = ratingContainer.querySelectorAll('label');
                    
                    stars.forEach(star => {
                        star.classList.remove('hover');
                    });
                });
            });
        });
    </script>
</body>
</html> 