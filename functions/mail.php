<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';


function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'classifid.sorry@gmail.com';
        $mail->Password = 'szawlzuvroaogprj';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8';
        $mail->setFrom('classifid.sorry@gmail.com', 'Events');
        $mail->addAddress($to);

        $mail->Subject = $subject;
        $mail->Body    = $body;

        $result = $mail->send();

        // Додаткова перевірка після відправки
        if (!$result) {
            error_log("PHPMailer send() returned false for: $to");
            return false;
        }

        return true;


    } catch (Exception $e) {
        return false; // ❗ ВАЖЛИВО
    }
}
