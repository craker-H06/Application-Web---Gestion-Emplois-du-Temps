<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendMail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP pour Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'votre-email@gmail.com'; // Remplacez par votre adresse Gmail
        $mail->Password = 'votre-mot-de-passe-application'; // Remplacez par le mot de passe d'application généré
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Options de débogage (à décommenter en cas de problème)
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = function($str, $level) {
        //     error_log("SMTP Debug: $str");
        // };

        // Destinataires
        $mail->setFrom('votre-email@gmail.com', 'Système de Planning'); // Même adresse que Username
        $mail->addAddress($to);

        // Contenu
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email : {$mail->ErrorInfo}");
        return false;
    }
}
?> 