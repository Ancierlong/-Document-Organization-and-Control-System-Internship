<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Load PHPMailer
require '../db_connect.php'; // Assuming this file establishes the $conn (mysqli connection) variable

/**
* Generates a random string of specified length from a given character set.
* @param int $length
* @param string $characters
* @return string
* @throws Exception
*/
function generateRandomString(int $length = 6, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string {
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[random_int(0, $charactersLength - 1)];
  }
  return $randomString;
}

/**
* Updates the user password in the database.
* @param mysqli $conn The database connection.
* @param int $userId The ID of the user to update.
* @param string $hashedPassword The already hashed password to store.
* @return bool True on success, False on failure.
*/
function updateUserPassword(mysqli $conn, int $userId, string $hashedPassword): bool {
  $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
  if ($stmt === false) {
    error_log("Password Update Prepare failed: (" . $conn->errno . ") " . $conn->error);
    return false;
  }
  $stmt->bind_param("si", $hashedPassword, $userId);
  if ($stmt->execute()) {
    $success = $stmt->affected_rows > 0;
    $stmt->close();
    return $success; // True if rows were affected, false otherwise (or on execute error)
  } else {
    error_log("Password Update Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    $stmt->close();
    return false;
  }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Basic validation
  if (empty($_POST["reset_user_id"]) || empty($_POST["email"])) {
    $_SESSION['message'] = "Error: User ID and email are required.";
    header("Location: view_user_list.php");
    exit();
  }

  // Sanitize and validate input
  $id = filter_input(INPUT_POST, 'reset_user_id', FILTER_VALIDATE_INT);
  $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

  if ($id === false || $id <= 0) {
    $_SESSION['message'] = "Error: Invalid User ID provided.";
    header("Location: view_user_list.php");
    exit();
  }
  if ($email === false) {
    $_SESSION['message'] = "Error: Invalid Email address provided.";
    header("Location: view_user_list.php");
    exit();
  }

  // --- Define Fallback Password ---
  // $fallbackPassword = '12345'; // No longer needed

  try {
    // --- Generate the potential new random password for email ---
    $newPassword = generateRandomString(6);

    // --- Attempt to send email with the NEW random password ---
    $mail = new PHPMailer(true);
    $emailSentSuccessfully = false; // Flag to track email status

    try {
      // Server settings (Consider moving to config)
      $mail->isSMTP();
      $mail->Host = 'smtp-relay.brevo.com';
      $mail->SMTPAuth = true;
      $mail->Username = '885c73002@smtp-brevo.com'; // Keep secure!
      $mail->Password = 'xsmtpsib-606085b4b5cbc22dd0e9238ee4637f04edd50a94f763e9e6714d346b1ccef5ad-b3E0rDxagLM5pB8N'; // Keep secure!
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = 587;

      // Sender & Recipient
      $mail->setFrom('gelocalong@gmail.com', 'CCS DMS Admin');
      $mail->addAddress($email);

      // Email Content
      $mail->isHTML(true);
      $mail->Subject = 'CCS Department Document Management System - Password Reset';
      // Email body contains the RANDOMLY generated password
      $mail->Body  = '<h1>Password Reset</h1><p>Hello! Your password has been reset.</p><p>Your new temporary password is: <strong>' . htmlspecialchars($newPassword) . '</strong></p><p>Please log in using this password and change it immediately via your profile settings.</p>';
      $mail->AltBody = 'Hello! Your password has been reset. Your new temporary password is: ' . $newPassword . ' Please log in and change it immediately.';

      // Send Email
      $mail->send();
      $emailSentSuccessfully = true; // Set flag on success

    } catch (Exception $e) {
      // Email sending failed. Flag remains false.
      error_log("Mailer Error: " . $mail->ErrorInfo); // Log the specific mailer error
      // We will handle the DB update and session message outside this inner catch block
    }

    // --- Now, update the database based on email success/failure ---

    if ($emailSentSuccessfully) {
      // Email sent, so update DB with the HASHED RANDOM password
      $hashedPasswordToStore = password_hash($newPassword, PASSWORD_DEFAULT);
      if (updateUserPassword($conn, $id, $hashedPasswordToStore)) {
        $_SESSION['message'] = "Password reset successfully! A new temporary password (" . htmlspecialchars($newPassword) . ") has been sent to the user's email.";
        // NOTE FOR view_user_list.php: Display this message, maybe as a SUCCESS pop-up.
      } else {
        // DB update failed even though email supposedly sent
        $_SESSION['message'] = "Error: Email sent, but failed to update the password in the database for user ID $id. Please check logs.";
        // NOTE FOR view_user_list.php: Display this message as an ERROR pop-up.
      }
    } else {
      // Email failed, so update DB with a HASHED RANDOMLY GENERATED fallback password
      $fallbackPassword = generateRandomString(8); // Generate a slightly longer fallback password
      $hashedPasswordToStore = password_hash($fallbackPassword, PASSWORD_DEFAULT);
      if (updateUserPassword($conn, $id, $hashedPasswordToStore)) {
        // Set the specific failure message indicating fallback password
        $_SESSION['message'] = "Email failed to send (check internet/config). Password has been reset to a temporary password: " . htmlspecialchars($fallbackPassword) . ". Please inform the user manually.";
        // NOTE FOR view_user_list.php: Display this message as a WARNING/INFO pop-up.
      } else {
        // DB update failed AND email failed
        $_SESSION['message'] = "Error: Email failed to send AND failed to update password to a fallback for user ID $id. No changes made. Please check logs.";
         // NOTE FOR view_user_list.php: Display this message as a SEVERE ERROR pop-up.
      }
    }


  } catch (Exception $e) { // Catch potential errors from random_int or other general issues
    error_log("Password Reset General Error: " . $e->getMessage());
    $_SESSION['message'] = "An unexpected error occurred during the password reset process: " . htmlspecialchars($e->getMessage());
    // NOTE FOR view_user_list.php: Display this message as an ERROR pop-up.
  }

  // Close connection if it's still open
  if ($conn) {
    $conn->close();
  }

  // Redirect back to the user list page regardless of outcome
  header("Location: view_user_list.php");
  exit();

} else {
  // If not a POST request, redirect away
  header("Location: index.php");
  exit();
}
?>