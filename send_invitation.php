<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require 'database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_all' && !empty($_POST['invitation_id'])) {
    $invitation_id = (int)$_POST['invitation_id'];

    // Fetch invitation (ensure owner)
    $stmt = $db->prepare("
        SELECT i.*, 
            u.first_name, 
            u.last_name, 
            u.faculty_number, 
            u.email AS user_email
        FROM invitations i
        JOIN users u ON u.id = i.user_id
        WHERE i.id = :id AND i.user_id = :uid
        LIMIT 1");
    $stmt->execute(['id' => $invitation_id, 'uid' => $_SESSION['user_id']]);
    $inv = $stmt->fetch();
    if (!$inv) {
        $message = 'Invitation not found.';
    } else {
        // Determine semester range (Feb 1 - Jul 31 current year)
        $year = date('Y');
        $start = "$year-02-01 00:00:00";
        $end = "$year-07-31 23:59:59";

        $rstmt = $db->prepare('SELECT email FROM users WHERE email IS NOT NULL AND email <> "" AND created_at BETWEEN :start AND :end');
        $rstmt->execute(['start' => $start, 'end' => $end]);
        $recipients = $rstmt->fetchAll();

        if (empty($recipients)) {
            $message = 'Няма регистрирани студенти за този семестър.';
        } else {
            $success = 0;
            $failed = 0;

            // Mailer config from env (optional)
            $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $smtpUser = getenv('SMTP_USER') ?: 'invitememe.team@gmail.com';
            $smtpPass = getenv('SMTP_PASS') ?: 'vfvkwhfjbbkwlxvq';
            $smtpPort = getenv('SMTP_PORT') ?: 587;

            foreach ($recipients as $r) {
                $to = $r['email'];

                $mail = new PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                try {
                    if (!empty($smtpHost)) {
                        $mail->isSMTP();
                        $mail->Host = $smtpHost;
                        $mail->SMTPAuth = true;
                        $mail->Username = $smtpUser;
                        $mail->Password = $smtpPass;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = $smtpPort;
                    } else {
                        $mail->isMail();
                    }

                    $mail->setFrom($smtpUser, 'InviteMEme');
                    $mail->addAddress($to);
                    $mail->Subject = 'Покана: ' . ($inv['title'] ?? 'Invitation');

                    $body = "<p>Здравейте,</p>
                            <p>Изпращаме ви покана за презентация:</p>
                            <p><strong>Тема: </strong>" . htmlspecialchars($inv['title']) . "</p>
                            <p><strong>Дата: </strong>" . htmlspecialchars($inv['presentation_date'] ?? '') . "</p>
                            <p><strong>Час: </strong>" . htmlspecialchars($inv['presentation_time'] ?? '') . "</p>
                            <p><strong>Зала: </strong>" . htmlspecialchars($inv['room'] ?? '') . "</p>
                            <p><strong>Презентиращ: </strong>" . htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name']) . "</p>";

                    if (!empty($inv['description'])) {
                        $body .= "<p><strong>Описание: </strong></p>
                         <p>" . nl2br(htmlspecialchars($inv['description'])) . "</p>";
                    }

                    // Attach generated image if exists
                    $imageFile = __DIR__ . '/' . $inv['generated_image_path'];
                    if (!empty($inv['generated_image_path']) && file_exists($imageFile)) {
                        $mail->addAttachment($imageFile);
                    }

                    $mail->isHTML(true);
                    $mail->Body = $body;

                    $mail->send();
                    $status = 'sent';
                    $sent_at = date('Y-m-d H:i:s');
                    $success++;
                } catch (Exception $e) {
                    $status = 'failed';
                    $sent_at = null;
                    $failed++;
                    error_log('send_invitation: mail error to ' . $to . ' - ' . $mail->ErrorInfo);
                }

                // Insert recipient record
                // Update existing recipient row (pending) if present, otherwise insert new
                $update = $db->prepare('UPDATE invitation_recipients SET status = :status, sent_at = :sent_at WHERE invitation_id = :invitation_id AND recipient_email = :recipient_email');
                $update->execute([
                    ':status' => $status,
                    ':sent_at' => $sent_at,
                    ':invitation_id' => $invitation_id,
                    ':recipient_email' => $to
                ]);

                if ($update->rowCount() === 0) {
                    $ins = $db->prepare('INSERT INTO invitation_recipients (invitation_id, recipient_email, status, sent_at) 
                                             VALUES (:invitation_id, :recipient_email, :status, :sent_at)');
                    $ins->execute([
                        ':invitation_id' => $invitation_id,
                        ':recipient_email' => $to,
                        ':status' => $status,
                        ':sent_at' => $sent_at
                    ]);
                }
            }

            $message = "Изпратени имейли: $success, неуспешни: $failed";
        }
    }
}

// List invitations for current user
$list = $db->prepare('SELECT id, title, presentation_date, presentation_time, generated_image_path, created_at FROM invitations WHERE user_id = :uid ORDER BY created_at DESC');
$list->execute(['uid' => $_SESSION['user_id']]);
$invitations = $list->fetchAll();

?>
<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Изпращане на покани</title>
    <link rel="stylesheet" href="styles/edit_templates.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: .5rem;
            border-bottom: 1px solid #ddd;
        }

        .small-img {
            height: 80px;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <a id="home-link" href="home_student.php">InviteMEme</a>
            </div>

            <nav>
                <ul>
                    <li><a href="create_invitation.php">Създаване на покана</a></li>
                    <li><a href="#" id="active-menu">Изпращане</a></li>
                    <li><a href="status.php">Статус</a></li>
                    <li><a href="profile.php">Профил</a></li>
                    <li><a href="auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <h2>Вашите създадени покани</h2> <br>
        <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if (empty($invitations)): ?>
            <p>Нямате запазени покани.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Заглавие</th>
                        <th>Дата</th>
                        <th>Създадено</th>
                        <th>Картина</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invitations as $inv): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($inv['title']); ?></td>
                            <td><?php echo htmlspecialchars($inv['presentation_date'] . ' ' . $inv['presentation_time']); ?></td>
                            <td><?php echo htmlspecialchars($inv['created_at']); ?></td>
                            <td><?php if (!empty($inv['generated_image_path']) && file_exists($inv['generated_image_path'])): ?>
                                    <img src="<?= htmlspecialchars($inv['generated_image_path']); ?>" class="small-img" alt="inv">
                                <?php else: ?> - <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="invitation_id" value="<?php echo (int)$inv['id']; ?>">
                                    <input type="hidden" name="action" value="send_all">
                                    <div class="actions">

                                        <button type="submit">Изпрати на всички студенти (фев-юли)</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>

</html>