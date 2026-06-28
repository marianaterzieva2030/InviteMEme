<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require 'database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

if (isset($_POST['save_fb'])) {
    $stmt = $db->prepare(
        "UPDATE invitations
        SET fb_link=:fb
        WHERE id=:id AND user_id=:uid AND is_approved='approved'
    "
    );

    $stmt->execute([
        ':fb' => trim($_POST['fb_link']),
        ':id' => (int)$_POST['invitation_id'],
        ':uid' => $_SESSION['user_id']
    ]);

    header("Location: status.php?saved=1");
    exit;
}

$stmt = $db->prepare(
    "SELECT 
        id, 
        title, 
        presentation_date, 
        presentation_time, 
        room, 
        generated_image_path, 
        created_at,
        is_approved,
        fb_link
    FROM invitations 
    WHERE user_id = :uid 
    ORDER BY created_at DESC
"
);

$stmt->execute([':uid' => $_SESSION['user_id']]);
$invitations = $stmt->fetchAll();

// Aggregate recipient statuses for each invitation
$statusMap = [];
foreach ($invitations as $inv) {
    $s = $db->prepare("SELECT status, COUNT(*) AS cnt FROM invitation_recipients WHERE invitation_id = :iid GROUP BY status");
    $s->execute([':iid' => $inv['id']]);
    $rows = $s->fetchAll();
    $counts = ['pending' => 0, 'sent' => 0, 'failed' => 0];
    foreach ($rows as $r) {
        $counts[$r['status']] = (int)$r['cnt'];
    }
    if ($counts['pending'] > 0) {
        $agg = 'pending';
    } elseif ($counts['failed'] > 0 && $counts['sent'] === 0) {
        $agg = 'failed';
    } elseif ($counts['sent'] > 0 && $counts['failed'] === 0) {
        $agg = 'sent';
    } elseif ($counts['sent'] > 0 && $counts['failed'] > 0) {
        $agg = 'mixed';
    } else {
        $agg = 'pending';
    }
    $statusMap[$inv['id']] = ['status' => $agg, 'counts' => $counts];
}

?>
<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Статус на поканите</title>
    <link rel="stylesheet" href="styles/edit_templates.css">
</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <?php if ((($user['role'] ?? $_SESSION['role'] ?? '') === 'teacher')): ?>
                    <a id="home-link" href="home_teacher.php">InviteMEme</a>
                <?php else: ?>
                    <a id="home-link" href="home_student.php">InviteMEme</a>
                <?php endif; ?>
            </div>
            <nav>
                <ul>
                    <li><a href="create_invitation.php">Създаване на покана</a></li>
                    <li><a href="send_invitation.php">Изпращане</a></li>
                    <li><a href="#" id="active-menu">Статус</a></li>
                    <li><a href="profile.php">Профил</a></li>
                    <li><a href="auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <h2>Статус на поканите</h2>
        <p>Тук са показани всички запазени покани и статусът им.</p> <br>
        <table>
            <thead>
                <tr>
                    <th>Преглед</th>
                    <th>Тема</th>
                    <th>Дата / Час</th>
                    <th>Зала</th>
                    <th>Създадена</th>
                    <th>Статус</th>
                    <th>Действия</th>
                    <th>Линк FB</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invitations)): ?>
                    <tr>
                        <td colspan="7">Няма запазени покани.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invitations as $inv):
                        $meta = $statusMap[$inv['id']];
                        $status = $meta['status'];
                        $counts = $meta['counts'];
                        $img = $inv['generated_image_path'];
                    ?>
                        <tr>
                            <td>
                                <?php if ($img): ?>
                                    <img src="<?= htmlspecialchars($img) ?>" class="template-preview" alt="preview">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($inv['title']) ?></td>
                            <td><?= htmlspecialchars($inv['presentation_date']) ?> <?= htmlspecialchars($inv['presentation_time']) ?></td>
                            <td><?= htmlspecialchars($inv['room']) ?></td>
                            <td><?= htmlspecialchars($inv['created_at']) ?></td>
                            <td>
                                <?php if ($status === 'pending'): ?>
                                    <strong style="color:#f39c12;">Очаква изпращане</strong>
                                <?php elseif ($status === 'sent'): ?>
                                    <strong style="color:green;">Изпратена</strong>
                                <?php elseif ($status === 'failed'): ?>
                                    <strong style="color:#e74c3c;">Неуспешна</strong>
                                <?php else: ?>
                                    <strong>Смесено</strong>
                                <?php endif; ?>
                                <div style="font-size:0.85rem; color:#666; margin-top:6px;">
                                    <?= $counts['sent'] ?> изпратени / <?= $counts['failed'] ?> неуспешни / <?= $counts['pending'] ?> очакващи
                                </div>
                            </td>
                            <td class="actions">
                                <?php if ($img && file_exists(__DIR__ . '/' . $img)): ?>
                                    <a class="btn" href="<?= htmlspecialchars($img) ?>" download>Изтегли PNG</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($inv['is_approved'] != 'approved'): ?>
                                    <span style="color:#888;">
                                        Достъпно след одобрение
                                    </span>

                                <?php else: ?>
                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="invitation_id"
                                            value="<?= $inv['id'] ?>">

                                        <input
                                            type="url"
                                            name="fb_link"
                                            value="<?= htmlspecialchars($inv['fb_link']) ?>"
                                            placeholder="https://facebook.com/...">

                                        <button class="btn" name="save_fb">
                                            Запази
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

</body>

</html>