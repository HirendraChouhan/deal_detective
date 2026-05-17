<?php
use PHPMailer\PHPMailer\PHPMailer;

require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';
require '../libs/PHPMailer/src/Exception.php';

function sendPriceAlert($to, $product, $price){
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com';
        $mail->Password = 'your_app_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your_email@gmail.com', 'Deal Detective');
        $mail->addAddress($to);

        $mail->Subject = "🔥 Price Drop Alert!";
        $mail->Body = "Price dropped for $product. New price: ₹$price";

        $mail->send();
    } catch (Exception $e) {
        echo "Mail Error: {$mail->ErrorInfo}";
    }
}
