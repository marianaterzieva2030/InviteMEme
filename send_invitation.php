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
$selected_recipients = [];

$recipient_query = $db->prepare(
    'SELECT id, first_name, last_name, email, role
     FROM users
     WHERE email IS NOT NULL
       AND id <> :uid
       AND (edition_id = :eid OR role = "teacher")
     ORDER BY CASE WHEN role = "teacher" THEN 0 ELSE 1 END, first_name, last_name'
);

// check role
if ($_SESSION['user_role'] === 'student') {
    $recipient_query->execute([
        'uid' => $_SESSION['user_id'], 
        'eid' => $_SESSION['edition_id']]
    );
}
else {
    $recipient_query->execute([
        'uid' => $_SESSION['user_id'], 
        'eid' => $_SESSION['teacher_edition_id']]
    );
}

$recipient_list = $recipient_query->fetchAll();
$allowedEmails = array_column($recipient_list, 'email');

// send invitation
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])
    && $_POST['action'] === 'send_selected'
    && !empty($_POST['invitation_id'])
) {
    $invitation_id = (int)$_POST['invitation_id'];
    $selected_recipients = $_POST['recipient_emails'] ?? [];

    if (!is_array($selected_recipients)) {
        $selected_recipients = [$selected_recipients];
    }

    $selected_recipients = array_unique(array_map('trim', $selected_recipients));
    $selected_recipients = array_filter($selected_recipients);

    if (empty($selected_recipients)) {
        $message = 'Моля, изберете поне един получател от списъка.';
    } else {
        $invalidEmails = array_values(array_diff($selected_recipients, $allowedEmails));
        if (!empty($invalidEmails)) {
            $message = 'Невалидни или неразрешени имейл адреси: ' . htmlspecialchars(implode(', ', $invalidEmails));
        } else {
            // Fetch invitation (ensure owner)
            $stmt = $db->prepare(
                "SELECT i.*, 
                u.first_name, 
                u.last_name, 
                u.faculty_number, 
                u.email AS user_email
                FROM invitations i
                JOIN users u ON u.id = i.user_id
                WHERE i.id = :id AND i.user_id = :uid
                LIMIT 1"
            );

            $stmt->execute(['id' => $invitation_id, 'uid' => $_SESSION['user_id']]);
            $inv = $stmt->fetch();

            if (!$inv) {
                $message = 'Поканата не е намерена.';
            } else if ($inv['is_approved'] !== 'approved') {
                $message = 'Тази покана не е одобрена за изпращане.';
            } else {
                $success = 0;
                $failed = 0;

                // Mailer config from env
                $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
                $smtpUser = getenv('SMTP_USER') ?: 'invitememe.team@gmail.com';
                $smtpPass = getenv('SMTP_PASS') ?: 'vfvkwhfjbbkwlxvq';
                $smtpPort = getenv('SMTP_PORT') ?: 587;

                foreach ($selected_recipients as $to) {
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
}

// List invitations for current user
$list = $db->prepare(
    "SELECT
        id,
        title,
        presentation_date,
        presentation_time,
        generated_image_path,
        created_at,
        edition_id,
        is_approved
    FROM invitations
    WHERE user_id = :uid AND edition_id = :eid
    ORDER BY created_at DESC
"
);

// check role
if ($_SESSION['user_role'] === 'student') {
    $list->execute([
        'uid' => $_SESSION['user_id'], 
        'eid' => $_SESSION['edition_id']]
    );
}
else {
    $list->execute([
        'uid' => $_SESSION['user_id'], 
        'eid' => $_SESSION['teacher_edition_id']]
    );
}

$invitations = $list->fetchAll();

$students = [];
$teachers = [];

foreach ($recipient_list as $recipient) {
    if ($recipient['role'] === 'teacher') {
        $teachers[] = $recipient;
    } else {
        $students[] = $recipient;
    }
}
?>


<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Изпращане на покани</title>
    <link rel="stylesheet" href="styles/edit_templates.css">
    <style>
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
                <?php if ((($user['role'] ?? $_SESSION['user_role'] ?? '') === 'teacher')): ?>
                    <a id="home-link" href="home_teacher.php">InviteMEme</a>
                <?php else: ?>
                    <a id="home-link" href="home_student.php">InviteMEme</a>
                <?php endif; ?>
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
        <?php if (empty($invitations) && $_SESSION['user_role'] === 'student'): ?>
            <p>Нямате запазени покани.</p>
        <?php else: ?>
            <form method="POST" id="sendSelectionForm">
                <input type="hidden" name="action" value="send_selected">
                <div style="margin-bottom: 1rem;">
                    <p style="font-size:0.95rem; color:#555; margin:0.5rem 0;">
                        Изберете един или повече имейли от всички
                        регистрирани потребители от този семестър.
                    </p>
                    <label for="recipient_emails"><strong>Получатели:</strong></label>
                    <select id="recipient_emails"
                        name="recipient_emails[]"
                        multiple
                        size="12">

                        <optgroup label="Преподаватели">
                            <?php foreach ($recipient_list as $r): ?>
                                <?php if ($r['role'] === 'teacher'): ?>
                                    <option value="<?= htmlspecialchars($r['email']) ?>">
                                        <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'] . ' (' . $r['email'] . ')') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>

                        <optgroup label="Студенти">
                            <?php foreach ($recipient_list as $r): ?>
                                <?php if ($r['role'] === 'student'): ?>
                                    <option value="<?= htmlspecialchars($r['email']) ?>">
                                        <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'] . ' (' . $r['email'] . ')') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <div class="recipient-buttons">
                        <button type="button" id="selectAllBtn">
                            Избери всички
                        </button>

                        <button type="button" id="clearBtn">
                            Изчисти избора
                        </button>
                    </div>
                </div>

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

                                <td class="actions">
                                    <?php if ($inv['is_approved'] === 'approved' || $_SESSION['user_role'] === 'teacher'): ?>
                                        <button class="btn" type="submit" name="invitation_id"
                                            value="<?= (int)$inv['id'] ?>">
                                            Изпрати
                                        </button>

                                    <?php elseif ($inv['is_approved'] === 'pending'): ?>
                                        <span style="color:#e67e22;font-weight:bold;">
                                            Очаква одобрение
                                        </span>

                                    <?php else: ?>
                                        <span style="color:#c0392b;font-weight:bold;">
                                            Не е одобрена
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php endif; ?>
    </main>
</body>

<script>
    const recipientSelect = document.getElementById("recipient_emails");

    recipientSelect.addEventListener("mousedown", function(e) {
        if (e.target.tagName !== "OPTION") {
            return;
        }

        e.preventDefault();

        e.target.selected = !e.target.selected;
    });

    const selectAllBtn = document.getElementById("selectAllBtn");
    const clearBtn = document.getElementById("clearBtn");

    selectAllBtn.addEventListener("click", () => {
        Array.from(recipientSelect.options).forEach(option => {
            option.selected = true;
        });
    });

    clearBtn.addEventListener("click", () => {
        Array.from(recipientSelect.options).forEach(option => {
            option.selected = false;
        });
    });
</script>

</html>