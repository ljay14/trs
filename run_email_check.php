<?php
/**
 * Thesis Routing System - Auto Email Check
 * 
 * This script runs the email checking process and then refreshes itself
 * after a set interval. Keep this page open in a browser tab to have
 * it continuously check for emails that need to be sent.
 * 
 * IMPORTANT: This is meant for development/testing environments only!
 * For production, use a proper cron job or scheduled task.
 */

// Set the refresh interval (in seconds)
$refresh_interval = 900; // 15 minutes

// Convert to minutes for display
$minutes = $refresh_interval / 60;

// Start output buffering to avoid partial content display
ob_start();

// Display header
echo '<html><head><title>TRS Email Check</title>';
echo '<meta http-equiv="refresh" content="' . $refresh_interval . '">';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
    .container { max-width: 800px; margin: 0 auto; background: #f5f7fd; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
    h1 { color: #4366b3; }
    .info { background: #e5ebf8; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    .log { background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #e0e0e0; height: 300px; overflow: auto; }
    .time { color: #777; font-size: 0.9em; }
    .success { color: green; }
    .error { color: red; }
</style>';
echo '</head><body>';
echo '<div class="container">';
echo '<h1>Thesis Routing System - Email Auto-Check</h1>';
echo '<div class="info"><strong>Info:</strong> This page automatically checks for approved adviser feedback and sends email notifications. It will refresh every ' . $minutes . ' minutes. Keep this tab open to continue the automatic checking.</div>';
echo '<div class="log" id="log">';

// Log function
function logMessage($message, $type = 'info') {
    $date = date('Y-m-d H:i:s');
    echo '<div class="' . $type . '"><span class="time">[' . $date . ']</span> ' . $message . '</div>';
}

// Log start of process
logMessage('Starting email check process...', 'info');

// Check if the required file exists
if (!file_exists('check_and_send_pending_emails.php')) {
    logMessage('ERROR: The check_and_send_pending_emails.php file is missing!', 'error');
    echo '</div></div></body></html>';
    ob_end_flush();
    exit;
}

// Capture output from the check script
ob_start();
include 'check_and_send_pending_emails.php';
$result = ob_get_contents();
ob_end_clean();

// Process the result
$lines = explode('<br>', $result);
foreach ($lines as $line) {
    if (!empty(trim($line))) {
        if (strpos($line, 'Email Sent: Yes') !== false) {
            logMessage(strip_tags($line), 'success');
        } elseif (strpos($line, 'Email Sent: No') !== false) {
            logMessage(strip_tags($line), 'error');
        } else {
            logMessage(strip_tags($line), 'info');
        }
    }
}

// Log completion
logMessage('Check completed. Will check again in ' . $minutes . ' minutes...', 'info');

// Close HTML
echo '</div>';
echo '<p>Page will automatically refresh in ' . $minutes . ' minutes. Last check: ' . date('Y-m-d H:i:s') . '</p>';
echo '</div></body></html>';

// Flush output buffer
ob_end_flush();
?> 