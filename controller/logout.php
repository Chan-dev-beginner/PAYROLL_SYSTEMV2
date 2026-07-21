<?php
// ============================================================
// LOGOUT - Destroy session and redirect to login
// ============================================================

require_once('../model/session.php');

// Check if confirmation is received
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Call logout function
    logoutUser();
} else {
    // Show confirmation dialog via JavaScript
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logout Confirmation</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f4f4f4;
            }
            .confirmation-box {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 400px;
            }
            h2 {
                margin-top: 0;
                color: #333;
            }
            p {
                margin: 20px 0;
                color: #666;
            }
            .btn {
                padding: 10px 20px;
                margin: 0 10px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                text-decoration: none;
                display: inline-block;
            }
            .btn-yes {
                background-color: #d9534f;
                color: white;
            }
            .btn-yes:hover {
                background-color: #c9302c;
            }
            .btn-no {
                background-color: #5bc0de;
                color: white;
            }
            .btn-no:hover {
                background-color: #31b0d5;
            }
        </style>
    </head>
    <body>
        <div class="confirmation-box">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            <a href="?confirm=yes" class="btn btn-yes">Yes, Logout</a>
            <a href="javascript:history.back()" class="btn btn-no">No, Cancel</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
