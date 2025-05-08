<?php
/**
 * Thesis Routing System - Email Cron Job Setup
 * 
 * This file provides instructions on how to set up a cron job or scheduled task
 * to automatically check for and send pending email notifications.
 */

// Display instructions
echo "Thesis Routing System - Email Notification Setup\n\n";
echo "This script helps you set up automatic email notifications for your Thesis Routing System.\n";
echo "The system will check for completed adviser approvals and send notifications to students.\n\n";

// Check if the required file exists
if (!file_exists('check_and_send_pending_emails.php')) {
    echo "ERROR: The check_and_send_pending_emails.php file is missing.\n";
    echo "Please make sure it exists in the root directory of your TRS installation.\n";
    exit(1);
}

// Display Linux/Unix cron instructions
echo "===== LINUX/UNIX CRON SETUP =====\n\n";
echo "To set up a cron job on your Linux/Unix server, follow these steps:\n\n";
echo "1. Open your crontab for editing:\n";
echo "   $ crontab -e\n\n";
echo "2. Add the following line to run the script every hour:\n";
echo "   0 * * * * php " . realpath("check_and_send_pending_emails.php") . " >/dev/null 2>&1\n\n";
echo "   Or to run it every 15 minutes:\n";
echo "   */15 * * * * php " . realpath("check_and_send_pending_emails.php") . " >/dev/null 2>&1\n\n";

// Display Windows Task Scheduler instructions
echo "===== WINDOWS SCHEDULED TASK SETUP =====\n\n";
echo "To set up a scheduled task on your Windows server, follow these steps:\n\n";
echo "1. Open Task Scheduler (search for 'Task Scheduler' in the Start menu)\n";
echo "2. Click 'Create Basic Task' in the right panel\n";
echo "3. Enter a name like 'TRS Email Notifications' and click Next\n";
echo "4. Select how often you want to run the task (e.g., Daily) and click Next\n";
echo "5. Set the start time and recurrence pattern, then click Next\n";
echo "6. Select 'Start a program' and click Next\n";
echo "7. In the Program/script field, enter: " . PHP_BINARY . "\n";
echo "8. In the Add arguments field, enter: " . realpath("check_and_send_pending_emails.php") . "\n";
echo "9. Click Next, review your settings, and click Finish\n\n";

// Display XAMPP instructions
echo "===== XAMPP SETUP (DEVELOPMENT ENVIRONMENT) =====\n\n";
echo "If you're using XAMPP for development, you can set up a recurring PHP script:\n\n";
echo "1. Create a file called 'run_email_check.php' with the following content:\n\n";
echo "<?php\n";
echo "// Define the script to run\n";
echo "\$script = '" . realpath("check_and_send_pending_emails.php") . "';\n";
echo "\n";
echo "// Run the script\n";
echo "include \$script;\n";
echo "\n";
echo "// Schedule the next run (e.g., in 15 minutes)\n";
echo "header('Refresh: 900;url=' . \$_SERVER['PHP_SELF']);\n";
echo "?>\n\n";
echo "2. Open this file in your browser and keep the tab open\n";
echo "   The script will automatically run every 15 minutes as long as the browser tab remains open\n\n";

// Provide some additional tips
echo "===== ADDITIONAL TIPS =====\n\n";
echo "1. Make sure your email settings in the script are correct\n";
echo "2. Test the script manually first by running: php " . realpath("check_and_send_pending_emails.php") . "\n";
echo "3. Check your server's error logs if emails are not being sent\n";
echo "4. Consider setting up a log rotation for the email_debug.log file\n\n";

echo "Setup instructions completed. Your Thesis Routing System will now automatically send\n";
echo "email notifications when all adviser feedback is approved.\n";
?> 