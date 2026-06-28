<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: /InviteMEme/login.html');
    exit;
}

require 'database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

// Handle actions: delete or toggle active
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        if ($action === 'delete') {
            $st = $db->prepare('DELETE FROM invitation_templates WHERE id = :id');
            $st->execute([':id' => $id]);
            $message = 'Шаблонът беше изтрит.';
        } elseif ($action === 'toggle') {
            // legacy toggle (kept for compatibility)
            $st = $db->prepare('UPDATE invitation_templates SET is_active = NOT is_active WHERE id = :id');
            $st->execute([':id' => $id]);
            $message = 'Статусът на шаблона е обновен.';
        } elseif ($action === 'set_active') {
            $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;
            $st = $db->prepare('UPDATE invitation_templates SET is_active = :is_active WHERE id = :id');
            $st->execute([':is_active' => $is_active, ':id' => $id]);
            $message = 'Статусът на шаблона е зададен.';
        }
    }
}

$typeFilter = $_GET['type'] ?? '';
$where = '';
$params = [];
if ($typeFilter === 'standard' || $typeFilter === 'meme') {
    $where = 'WHERE type = :type';
    $params[':type'] = $typeFilter;
}

$q = $db->prepare("SELECT id, name, type, image_path, is_active, created_at FROM invitation_templates $where ORDER BY created_at DESC");
$q->execute($params);
$templates = $q->fetchAll();

?>
<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Управление на шаблони</title>
    <link rel="stylesheet" href="styles/edit_templates.css">
    <style>
        .actions form {
            display: inline-block;
            margin: 0 4px;
        }

        .filter-links {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .filter-links a {
            text-decoration: underline;
            color: black;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            transition: 0.3s;
        }

        .filter-links a:hover {
            color: var(--orange);
        }

        .import-export-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin: 20px 0;
        }

        .action-btn {
            display: inline-block;
            padding: 10px 18px;
            background: var(--orange);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: .2s;
        }

        .action-btn:hover {
            background: var(--turquoise);
        }
    </style>
</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <a id="home-link" href="home_teacher.php">InviteMEme</a>
            </div>

            <nav>
                <ul>
                    <li><a href="create_template.php">Създаване на шаблон</a></li>
                    <li><a href="edit_templates.php" id="active-menu">Управление на шаблони</a></li>
                    <li><a href="profile.php">Профил</a></li>
                    <li><a href="auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <h2>Управление на шаблони</h2>
        <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>

        <div class="filter-links">
            <a href="edit_templates.php">Всички</a> |
            <a href="edit_templates.php?type=standard">Standard</a> |
            <a href="edit_templates.php?type=meme">Meme</a>
        </div>

        <div class="import-export-buttons">
            <button type="submit" form="exportForm" class="btn">
                Export 
            </button>

            <a href="import_templates.php" class="btn">
                Import 
            </a>
        </div>

        <?php if (empty($templates)): ?>
            <p>Няма шаблони.</p>
        <?php else: ?>
            <form method="POST" action="export_templates.php" id="exportForm">

                <table>
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Име</th>
                            <th>Тип</th>
                            <th>Изображение</th>
                            <th>Активен</th>
                            <th>Действия</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($templates as $t): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="template_ids[]" value="<?= $t['id'] ?>">
                                </td>

                                <td><?= htmlspecialchars($t['name']) ?></td>
                                <td><?= htmlspecialchars($t['type']) ?></td>

                                <td>
                                    <?php if (!empty($t['image_path']) && file_exists($t['image_path'])): ?>
                                        <img src="<?= htmlspecialchars($t['image_path']) ?>" style="height:60px;">
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td><?= $t['is_active'] ? 'Да' : 'Не' ?></td>

                                <td class="actions">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="set_active">
                                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                        <select name="is_active">
                                            <option value="1" <?= $t['is_active'] ? 'selected' : '' ?>>Активен</option>
                                            <option value="0" <?= !$t['is_active'] ? 'selected' : '' ?>>Неактивен</option>
                                        </select>
                                        <button type="submit">Задай</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php endif; ?>

    </main>
</body>

</html>
<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        const checked = this.checked;

        document.querySelectorAll('input[name="template_ids[]"]').forEach(cb => {
            cb.checked = checked;
        });
    });
</script>