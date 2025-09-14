<?php
// Show errors for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php'; // Ensure correct path to autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// === Collect Form Data ===
$first_name        = $_POST['first_name'] ?? '';
$surname           = $_POST['surname'] ?? '';
$email             = $_POST['email'] ?? '';
$phone             = $_POST['phone'] ?? '';
$nationality       = $_POST['nationality'] ?? '';
$city              = $_POST['city'] ?? '';
$program           = $_POST['program'] ?? '';

$financial_support = $_POST['financial_support'] ?? '';
$referral_source   = $_POST['referral_source'] ?? '';
$agent_name        = $_POST['agent_name'] ?? '';
$agent_company     = $_POST['agent_company'] ?? '';

$disability        = $_POST['disability'] ?? 'No';
$disability_details = $_POST['disability_details'] ?? '';

$consent           = isset($_POST['consent']) ? "Yes" : "No";

// === Build Message ===
$message  = "New Application Received:\n\n";
$message .= "First Name: $first_name\n";
$message .= "Surname: $surname\n";
$message .= "Email: $email\n";
$message .= "Phone (WhatsApp): $phone\n";
$message .= "Nationality: $nationality\n";
$message .= "Current City in UK: $city\n";
$message .= "Chosen Program: $program\n\n";

$message .= "Financial Support: $financial_support\n";
$message .= "Referral Source: $referral_source\n";
if ($referral_source === "Agent") {
    $message .= "Agent Name: $agent_name\n";
    $message .= "Agent Company: $agent_company\n";
}
$message .= "\n";

$message .= "Disability: $disability\n";
if ($disability === "Yes" && !empty($disability_details)) {
    $message .= "Disability Details: $disability_details\n";
}
$message .= "\nConsent Given: $consent\n";

// === File Upload Handling ===
$uploads = [];
$upload_dir = __DIR__ . "/uploads/";

// Create uploads folder if missing
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

function saveFile($fileField, $upload_dir, &$uploads) {
    if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
        $filename = preg_replace("/[^A-Z0-9._-]/i", "_", basename($_FILES[$fileField]['name']));
        if ($_FILES[$fileField]['size'] > 10 * 1024 * 1024) {
            return; // Skip if too big (10MB max)
        }
        $target = $upload_dir . time() . "_" . $filename;
        if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $target)) {
            $uploads[$fileField] = $target;
        }
    }
}

// Save all uploads
saveFile('diploma', $upload_dir, $uploads);
saveFile('residence', $upload_dir, $uploads);
saveFile('passport', $upload_dir, $uploads);
saveFile('passport_photo', $upload_dir, $uploads);
saveFile('nin', $upload_dir, $uploads);
saveFile('address_proof', $upload_dir, $uploads);
saveFile('cv', $upload_dir, $uploads);

// === PHPMailer Config for Namecheap Private Email ===
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'mail.privateemail.com'; // Private Email SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'admin@graffeducationrecruitment.co.uk'; // full email
    $mail->Password   = 'YOUR_PRIVATE_EMAIL_PASSWORD'; // mailbox password
    $mail->SMTPSecure = 'ssl'; // or 'tls'
    $mail->Port       = 465;   // 465 for SSL, 587 for TLS

    // Sender & Recipient
    $mail->setFrom('admin@graffeducationrecruitment.co.uk', 'Graff Education Recruitment');
    $mail->addAddress('admin@graffeducationrecruitment.co.uk'); // Admin inbox

    // Email Content
    $mail->isHTML(false); // plain text
    $mail->Subject = "New Student Application - Graff Education Recruitment";
    $mail->Body    = $message;

    // Attachments
    foreach ($uploads as $field => $filePath) {
        if (file_exists($filePath)) {
            $mail->addAttachment($filePath, basename($filePath));
        }
    }

    // Send Email
    if ($consent === "Yes") {
        if ($mail->send()) {
            header("Location: thank-you.html");
            exit();
        } else {
            echo "❌ Email could not be sent.";
        }
    } else {
        echo "❌ Consent required before submission.";
    }

} catch (Exception $e) {
    echo "❌ Mailer Error: {$mail->ErrorInfo}";
}
