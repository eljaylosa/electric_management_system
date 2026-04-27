<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';



function sendCredentialsEmail($email, $username, $password, $meter_no) {

  $mail = new PHPMailer(true);

  try {
      $mail->isSMTP();
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;

      $mail->Username = 'eljaygenegalosa@gmail.com';
      $mail->Password = 'pbbbrloajqrkasvd';

      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = 587;

      $mail->setFrom('eljaygenegalosa@gmail.com', 'Electric MS');
      $mail->addAddress($email);

      $mail->isHTML(true);
      $mail->Subject = 'Your Electric Management System Account';

      $mail->Body = "
          <h3>Account Approved</h3>
          <p>Your account has been created successfully.</p>
          <br>
          <b>Username:</b> {$username}<br>
          <b>Password:</b> {$password}<br>
          <b>Meter No:</b> {$meter_no}<br>
          <br>
          <p>Please login and change your password immediately.</p>
      ";

      $mail->send();
      return true;

  } catch (Exception $e) {
      return [
          'status' => false,
          'error' => $e->getMessage()
      ];
  }
}

function sendOTPEmail($email, $otp) {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'eljaygenegalosa@gmail.com';
        $mail->Password = 'pbbbrloajqrkasvd';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('eljaygenegalosa@gmail.com', 'Electric MS');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "OTP Code";

        $mail->Body = "
            <h2>Your OTP</h2>
            <h1>$otp</h1>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $e->getMessage());
        return false;
    }
}

function sendRejectionEmail($email, $name, $reason) {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'eljaygenegalosa@gmail.com';
        $mail->Password = 'pbbbrloajqrkasvd';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('eljaygenegalosa@gmail.com', 'Electric MS');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Account Request Rejected';

        $mail->Body = "
            <h3>Request Rejected</h3>
            <p>Hello {$name},</p>
            <p>We regret to inform you that your account request has been rejected.</p>

            <p><b>Reason:</b></p>
            <p style='color:red;'>$reason</p>

            <br>
            <p>You may contact the administrator for further clarification.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $e->getMessage());
        return false;
    }
}

function sendContactEmail($name, $email, $message) {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'eljaygenegalosa@gmail.com';
        $mail->Password = 'pbbbrloajqrkasvd';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // sender = system
        $mail->setFrom('eljaygenegalosa@gmail.com', 'Electric MS');

        // receiver = ADMIN EMAIL
        $mail->addAddress('eljaygenegalosa@gmail.com');

        // ⭐ IMPORTANT: so admin can reply directly
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "New Support Message from $name";

        $mail->Body = "
            <h3>New Message from User</h3>
            <p><b>Name:</b> $name</p>
            <p><b>Email:</b> $email</p>
            <br>
            <p><b>Message:</b></p>
            <p>$message</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $e->getMessage());
        return false;
    }
}