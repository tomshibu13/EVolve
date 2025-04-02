<?php
session_start();
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

// Initialize variables
$station = null;
$error_message = '';
$success_message = '';

// Check if station ID is provided
if (!isset($_POST['id']) && !isset($_GET['id'])) {
    header('Location: user_stations.php');
    exit();
}

$station_id = $_POST['id'] ?? $_GET['id'];

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Fetch station details
    $query = "
        SELECT 
            cs.*,
            u.name as operator_name,
            ST_X(cs.location) as longitude,
            ST_Y(cs.location) as latitude
        FROM charging_stations cs 
        LEFT JOIN tbl_users u ON cs.operator_id = u.user_id 
        WHERE cs.station_id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $station_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $station = mysqli_fetch_assoc($result);

    // Handle booking submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add your booking logic here
        $user_id = $_SESSION['user_id'] ?? null; // Make sure user is logged in
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        $duration = $_POST['duration'];
        
        if (!$user_id) {
            throw new Exception("Please log in to book a station");
        }
        
        // Validate time is between 8 AM and 9 PM
        $booking_hour = (int)substr($booking_time, 0, 2);
        if ($booking_hour < 8 || $booking_hour > 21 || ($booking_hour == 21 && substr($booking_time, 3, 2) > '00')) {
            throw new Exception("Booking time must be between 8:00 AM and 9:00 PM");
        }
        
        // Check if the slot is already booked
        $end_time = date('H:i', strtotime($booking_time . ' + ' . $duration . ' minutes'));
        $check_query = "
            SELECT * FROM bookings 
            WHERE station_id = ? 
            AND booking_date = ? 
            AND (
                (booking_time <= ? AND DATE_ADD(booking_time, INTERVAL duration MINUTE) > ?) OR
                (booking_time < ? AND DATE_ADD(booking_time, INTERVAL duration MINUTE) >= ?)
            )
        ";
        
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "isssss", $station_id, $booking_date, $end_time, $booking_time, $booking_time, $booking_time);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            throw new Exception("This time slot is already booked. Please select another time.");
        }

        // Insert booking into database
        $booking_query = "
            INSERT INTO bookings (user_id, station_id, booking_date, booking_time, duration, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ";
        
        $booking_stmt = mysqli_prepare($conn, $booking_query);
        mysqli_stmt_bind_param($booking_stmt, "iissi", $user_id, $station_id, $booking_date, $booking_time, $duration);
        
        if (mysqli_stmt_execute($booking_stmt)) {
            $success_message = "Booking successful! We'll notify you once it's confirmed.";
            header('Location: my-bookings.php');
            exit();
        } else {
            throw new Exception("Failed to create booking");
        }
    }

} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Station - EVolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="booking-styles.css">
    <style>
        /* Reuse your existing CSS variables and basic styles */
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --secondary-color: #2C3E50;
            --background-color: #f5f6fa;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --border-radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .booking-form {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .station-details {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .time-slots-container {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            border: 1px solid #e2e8f0;
        }
        
        .time-slot {
            display: inline-block;
            padding: 6px 12px;
            margin: 5px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .time-slot.available {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .time-slot.available:hover {
            background-color: #bbf7d0;
            transform: scale(1.05);
        }
        
        .time-slot.selected {
            background-color: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-hover);
            font-weight: bold;
        }
        
        .time-slot.booked {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            text-decoration: line-through;
            cursor: not-allowed;
        }
        
        .time-slots-legend {
            display: flex;
            justify-content: flex-start;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            border-radius: 4px;
        }

        .booking-error {
            color: #dc2626;
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 14px;
            display: none;
        }
    </style>
</head>
<body>
    <header style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: var(--card-bg); box-shadow: var(--shadow);">
        <?php include 'header.php'; ?>  
    </header>

    <div class="container" style="margin-top: 80px;">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
            <a href="javascript:history.back()" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Book Charging Station</h1>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($station): ?>


            <form class="booking-form" method="POST" action="create_order.php">
                <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($station_id); ?>">
                
                <div id="live-error" class="alert alert-error" style="display: none;"></div>
                
                <div class="form-group">
                    <label for="booking_date">Date</label>
                    <input type="date" id="booking_date" name="booking_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="booking_time">Time (8:00 AM - 9:00 PM)</label>
                    <input type="time" id="booking_time" name="booking_time" min="08:00" max="21:00" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="duration">Duration (minutes)</label>
                    <select id="duration" name="duration" class="form-control" required>
                        <option value="30">30 minutes </option>
                        <option value="60">1 hour </option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Availability for Selected Date</label>
                    <div id="time-slots-container" class="time-slots-container" style="max-height: 250px; overflow-y: auto;">
                        <p id="select-date-message">Please select a date to view availability</p>
                        <div id="time-slots" style="display:none;"></div>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Proceed to Payment</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookingForm = document.querySelector('.booking-form');
            const timeInput = document.getElementById('booking_time');
            const dateInput = document.getElementById('booking_date');
            const durationSelect = document.getElementById('duration');
            const liveError = document.getElementById('live-error');
            const timeSlots = document.getElementById('time-slots');
            const selectDateMessage = document.getElementById('select-date-message');
            const stationId = <?php echo json_encode($station_id); ?>;
            
            // Set min date to today
            const today = new Date().toISOString().split('T')[0];
            dateInput.min = today;
            
            // Function to check availability in real-time
            function checkAvailability() {
                if (!dateInput.value || !timeInput.value || !durationSelect.value) {
                    return; // Don't check if fields are empty
                }
                
                // Validate time range first
                const selectedTime = timeInput.value;
                const hour = parseInt(selectedTime.split(':')[0]);
                const minute = parseInt(selectedTime.split(':')[1]);
                
                if (hour < 8 || (hour === 21 && minute > 0) || hour > 21) {
                    liveError.textContent = 'Please select a time between 8:00 AM and 9:00 PM';
                    liveError.style.display = 'block';
                    return;
                }
                
                // Check for conflicting bookings via AJAX
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'check_availability.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        if (!response.available) {
                            liveError.textContent = 'This time slot is already booked. Please select another time.';
                            liveError.style.display = 'block';
                        } else {
                            liveError.style.display = 'none';
                        }
                    }
                };
                
                xhr.send(`station_id=${stationId}&booking_date=${dateInput.value}&booking_time=${timeInput.value}&duration=${durationSelect.value}`);
            }
            
            // Function to fetch and display booked time slots
            function fetchTimeSlots() {
                if (!dateInput.value) {
                    return;
                }
                
                const xhr = new XMLHttpRequest();
                xhr.open('GET', `get_booked_slots.php?station_id=${stationId}&date=${dateInput.value}`, true);
                
                xhr.onload = function() {
                    if (this.status === 200) {
                        const bookedSlots = JSON.parse(this.responseText);
                        displayTimeSlots(bookedSlots);
                    }
                };
                
                xhr.send();
            }
            
            // Function to display time slots
            function displayTimeSlots(bookedSlots) {
                timeSlots.innerHTML = '';
                
                // Create legend
                const legend = document.createElement('div');
                legend.className = 'time-slots-legend';
                
                const availableLegend = document.createElement('div');
                availableLegend.className = 'legend-item';
                const availableColor = document.createElement('div');
                availableColor.className = 'legend-color';
                availableColor.style.backgroundColor = '#dcfce7';
                availableColor.style.border = '1px solid #bbf7d0';
                availableLegend.appendChild(availableColor);
                availableLegend.appendChild(document.createTextNode('Available'));
                
                const bookedLegend = document.createElement('div');
                bookedLegend.className = 'legend-item';
                const bookedColor = document.createElement('div');
                bookedColor.className = 'legend-color';
                bookedColor.style.backgroundColor = '#fee2e2';
                bookedColor.style.border = '1px solid #fecaca';
                bookedLegend.appendChild(bookedColor);
                bookedLegend.appendChild(document.createTextNode('Booked'));
                
                const selectedLegend = document.createElement('div');
                selectedLegend.className = 'legend-item';
                const selectedColor = document.createElement('div');
                selectedColor.className = 'legend-color';
                selectedColor.style.backgroundColor = 'var(--primary-color)';
                selectedColor.style.border = '1px solid var(--primary-hover)';
                selectedLegend.appendChild(selectedColor);
                selectedLegend.appendChild(document.createTextNode('Selected'));
                
                legend.appendChild(availableLegend);
                legend.appendChild(bookedLegend);
                legend.appendChild(selectedLegend);
                timeSlots.appendChild(legend);
                
                // Generate time slots from 8 AM to 9 PM in 30-minute intervals
                for (let hour = 8; hour <= 21; hour++) {
                    for (let minute of [0, 30]) {
                        // Skip 9:30 PM as it's outside our range
                        if (hour === 21 && minute === 30) continue;
                        
                        const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                        const timeSlot = document.createElement('div');
                        timeSlot.className = 'time-slot';
                        timeSlot.dataset.time = timeStr;
                        timeSlot.textContent = formatTimeDisplay(timeStr);
                        
                        // Check if this slot is booked
                        const isBooked = isTimeSlotBooked(timeStr, bookedSlots);
                        
                        if (isBooked) {
                            timeSlot.classList.add('booked');
                            timeSlot.title = "This slot is already booked";
                        } else {
                            timeSlot.classList.add('available');
                            timeSlot.title = "Click to select this time slot";
                            
                            // Click to select the time
                            timeSlot.addEventListener('click', function() {
                                // Remove selected class from all time slots
                                document.querySelectorAll('.time-slot.selected').forEach(slot => {
                                    slot.classList.remove('selected');
                                });
                                
                                // Set the time input value
                                timeInput.value = timeStr;
                                
                                // Highlight all slots covered by the selected duration
                                highlightSelectedTimeSlots(timeStr, durationSelect.value);
                                
                                // Check availability
                                checkAvailability();
                            });
                        }
                        
                        timeSlots.appendChild(timeSlot);
                    }
                }
                
                // If there's a currently selected time, highlight all slots for its duration
                if (timeInput.value) {
                    highlightSelectedTimeSlots(timeInput.value, durationSelect.value);
                }
                
                // Show the time slots and hide the message
                selectDateMessage.style.display = 'none';
                timeSlots.style.display = 'block';
            }
            
            // Function to check if a time slot is booked
            function isTimeSlotBooked(timeStr, bookedSlots) {
                const timeMinutes = timeToMinutes(timeStr);
                
                for (const slot of bookedSlots) {
                    // Skip completed bookings - these slots should appear as available
                    if (slot.status === 'completed') {
                        continue;
                    }
                    
                    const startMinutes = timeToMinutes(slot.booking_time);
                    const endMinutes = startMinutes + parseInt(slot.duration);
                    
                    // Check if this slot overlaps with any booking
                    if (timeMinutes >= startMinutes && timeMinutes < endMinutes) {
                        return true;
                    }
                }
                
                return false;
            }
            
            // Helper function to convert time to minutes since midnight
            function timeToMinutes(timeStr) {
                const [hours, minutes] = timeStr.split(':').map(Number);
                return hours * 60 + minutes;
            }
            
            // Helper function to format time for display
            function formatTimeDisplay(timeStr) {
                const [hours, minutes] = timeStr.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const hour12 = hour % 12 || 12;
                return `${hour12}:${minutes} ${ampm}`;
            }
            
            // Function to highlight all time slots that would be occupied by the selected time and duration
            function highlightSelectedTimeSlots(startTime, durationMinutes) {
                // Clear all previous selections
                document.querySelectorAll('.time-slot.selected').forEach(slot => {
                    slot.classList.remove('selected');
                });
                
                const startMinutes = timeToMinutes(startTime);
                const endMinutes = startMinutes + parseInt(durationMinutes);
                
                // First check if any of these slots are booked
                let hasBookedSlotInRange = false;
                
                // Add selected class to all time slots in the range
                document.querySelectorAll('.time-slot').forEach(slot => {
                    const slotMinutes = timeToMinutes(slot.dataset.time);
                    
                    // If this slot falls within the selected time range
                    if (slotMinutes >= startMinutes && slotMinutes < endMinutes) {
                        slot.classList.add('selected');
                        
                        // Check if this slot is booked
                        if (slot.classList.contains('booked')) {
                            hasBookedSlotInRange = true;
                        }
                        
                        // Ensure the first selected slot is visible
                        if (slotMinutes === startMinutes) {
                            slot.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                });
                
                // Show error if there's a booked slot in the selected range
                if (hasBookedSlotInRange) {
                    liveError.textContent = 'Your selected time range overlaps with a booked slot. Please select a different time or duration.';
                    liveError.style.display = 'block';
                } else {
                    liveError.style.display = 'none';
                }
                
                return hasBookedSlotInRange;
            }
            
            // Add event listeners to form inputs
            dateInput.addEventListener('change', function() {
                fetchTimeSlots();
                if (timeInput.value) {
                    checkAvailability();
                }
            });
            
            timeInput.addEventListener('change', function() {
                if (this.value) {
                    highlightSelectedTimeSlots(this.value, durationSelect.value);
                }
                checkAvailability();
            });
            
            durationSelect.addEventListener('change', function() {
                if (timeInput.value) {
                    highlightSelectedTimeSlots(timeInput.value, this.value);
                }
                checkAvailability();
            });
            
            bookingForm.addEventListener('submit', function(e) {
                // Validate time range
                const selectedTime = timeInput.value;
                const hour = parseInt(selectedTime.split(':')[0]);
                const minute = parseInt(selectedTime.split(':')[1]);
                
                if (hour < 8 || (hour === 21 && minute > 0) || hour > 21) {
                    e.preventDefault();
                    liveError.textContent = 'Please select a time between 8:00 AM and 9:00 PM';
                    liveError.style.display = 'block';
                    return false;
                }
                
                // Check if there's an error displayed
                if (liveError.style.display !== 'none') {
                    e.preventDefault();
                    return false;
                }
                
                if (!confirm('Confirm booking?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html> 