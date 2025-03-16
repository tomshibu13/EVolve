<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code - EVolve</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        #reader {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        .status-message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Scan QR Code</h1>
        <div id="reader"></div>
        <div id="status-message"></div>
    </div>

    <script>
        function onScanSuccess(decodedText) {
            try {
                const data = JSON.parse(decodedText);
                
                // Send scan data to server
                fetch('process_scan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    const messageDiv = document.getElementById('status-message');
                    messageDiv.className = `status-message ${result.success ? 'success' : 'error'}`;
                    messageDiv.textContent = result.message;
                    
                    if (result.success) {
                        // Stop scanning after successful scan
                        html5QrcodeScanner.clear();
                        
                        // Redirect after 3 seconds if specified
                        if (result.redirect) {
                            setTimeout(() => {
                                window.location.href = result.redirect;
                            }, 3000);
                        }
                    }
                });
            } catch (error) {
                console.error('Invalid QR code data:', error);
            }
        }

        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", { fps: 10, qrbox: 250 }
        );
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html> 