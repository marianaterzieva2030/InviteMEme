<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: /InviteMEme/login.html');
    exit;
}

$edition_id = $_SESSION['teacher_edition_id'];

$where = ["i.edition_id = :eid"];
$params = [':eid' => $edition_id];

if(!empty($_GET['study_year'])){
    $where[] = "u.study_year = :study_year";
    $params[':study_year'] = (int)$_GET['study_year'];
}

if(!empty($_GET['major'])){
    $where[] = "u.major = :major";
    $params[':major'] = $_GET['major'];
}

if(!empty($_GET['presentation_date'])){
    $where[] = "i.presentation_date = :presentation_date";
    $params[':presentation_date'] = $_GET['presentation_date'];
}

if(!empty($_GET['status'])){
    $where[] = "i.is_approved = :status";
    $params[':status'] = $_GET['status'];
}

$order = " ORDER BY i.presentation_date DESC";

switch($_GET['sort'] ?? ''){
    case 'name_asc':
        $order = "ORDER BY u.last_name ASC,u.first_name ASC";
        break;

    case 'name_desc':
        $order = "ORDER BY u.last_name DESC,u.first_name DESC";
        break;

    case 'fn_asc':
        $order = "ORDER BY u.faculty_number ASC";
        break;

    case 'fn_desc':
        $order = "ORDER BY u.faculty_number DESC";
        break;

    case 'date_asc':
        $order = "ORDER BY i.presentation_date ASC";
        break;

    case 'date_desc':
        $order = "ORDER BY i.presentation_date DESC";
        break;
}

require 'database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['invitation_id'], $_POST['status']))
{
    $allowed = ['pending','approved','declined'];

    if (in_array($_POST['status'], $allowed, true)) {

        $stmt = $db->prepare("
            UPDATE invitations
            SET is_approved = :status
            WHERE id = :id
        ");

        $stmt->execute([
            ':status' => $_POST['status'],
            ':id' => (int)$_POST['invitation_id']
        ]);
    }

    header("Location: course_stats.php");
    exit;
}

$sql = 
"SELECT
    i.id,
    i.title,
    i.presentation_date,
    i.generated_image_path,
    i.fb_link,
    i.is_approved,

    u.first_name,
    u.last_name,
    u.faculty_number,
    u.major,
    u.study_year

    FROM invitations i

    JOIN users u
    ON u.id=i.user_id

    WHERE
    ".implode(" AND ",$where)."

    " . $order; // adding the order query


$stmt = $db->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Статистики</title>
    <link rel="stylesheet" href="styles/course.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div title="Home Page" class="logo">
                <a id="home-link" href="home_teacher.php">InviteMEme</a>
            </div>

            <nav>
                <ul>
                    <li class="dropdown">

                        <a href="course_edition.php?id=<?= $edition_id ?>">
                            <?= htmlspecialchars($_SESSION['teacher_edition_code']) ?> ▼
                        </a>

                        <ul class="dropdown-menu">
                            <li><a href="course_users.php">Потребители</a></li>
                            <li><a href="#">Статистики</a></li>
                            <li><a href="create_invitation.php">Създаване на покана</a></li>
                            <li><a href="send_invitation.php">Изпращане</a></li>
                            <li><a href="status.php">Статус</a></li>
                            <li><a href="course_settings.php">Настройки</a></li>
                        </ul>

                    </li>

                    <li><a href="create_template.php">Създаване на шаблон</a></li>
                    <li><a href="edit_templates.php">Управление на шаблони</a></li>
                    <li><a href="profile.php">Профил</a></li>
                    <li><a href="auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <h2>Статистики</h2> <br>
        <form method="GET" class="filters">

            <select name="study_year">
                <option value="">Всички курсове</option>
                <?php for($i=1;$i<=4;$i++): ?>
                    <option value="<?= $i ?>"
                        <?= ($_GET['study_year'] ?? '') == $i ? 'selected' : '' ?>>
                        <?= $i ?> курс
                    </option>
                <?php endfor; ?>
            </select>

            <select name="major">
                <option value="">Всички специалности</option>
                <option value="Софтуерно инженерство">Софтуерно инженерство</option>
                <option value="Компютърни науки">Компютърни науки</option>
                <option value="Информатика">Информатика</option>
                <option value="Информационни системи">Информационни системи</option>
            </select>

            <input
                type="date"
                name="presentation_date"
                value="<?= htmlspecialchars($_GET['presentation_date'] ?? '') ?>">

            <select name="statuses">
                <option value="">Всички статуси</option>
                <option value="pending">Изчаква</option>
                <option value="approved">Одобрена</option>
                <option value="declined">Неодобрена</option>
            </select>

            <select name="sort">
                <option value="">Сортиране</option>
                <option value="name_asc">Име ↑</option>
                <option value="name_desc">Име ↓</option>
                <option value="fn_asc">ФН ↑</option>
                <option value="fn_desc">ФН ↓</option>
                <option value="date_asc">Дата ↑</option>
                <option value="date_desc">Дата ↓</option>
            </select>

            <button class="btn">Филтрирай</button>
        </form>

        <?php if (empty($rows)): ?>
            <p>Няма записи.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Студент</th>
                        <th>Фак. номер</th>
                        <th>Тема</th>
                        <th>Дата</th>
                        <th>Изображение</th>
                        <th>Линк FB</th>
                        <th>Статус</th>
                    </tr>
                    </thead>

                    <tbody>
                        <?php foreach($rows as $r): ?>
                        <tr>
                            <td> <?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?> </td>

                            <td> <?= htmlspecialchars($r['faculty_number']) ?> </td>

                            <td> <?= htmlspecialchars($r['title']) ?> </td>

                            <td> <?= htmlspecialchars($r['presentation_date']) ?> </td>

                            <td>
                                <?php if(!empty($r['generated_image_path']) && file_exists($r['generated_image_path'])): ?>

                                <img
                                    src="<?= htmlspecialchars($r['generated_image_path']) ?>"
                                    class="small-img">

                                <?php else: ?> —
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if(!empty($r['fb_link'])): ?>

                                <a href="<?= htmlspecialchars($r['fb_link']) ?>" target="_blank">
                                    Публикация
                                </a>
                                <?php else: ?> —
                                <?php endif; ?>

                            </td>

                            <td>
                                <form method="POST">

                                    <input type="hidden" name="invitation_id" value="<?= $r['id'] ?>">

                                    <select class="status" name="status" onchange="this.form.submit()">
                                        <option value="pending"
                                            <?= $r['is_approved']=='pending' ? 'selected' : '' ?>>
                                            Изчаква
                                        </option>

                                        <option value="approved"
                                            <?= $r['is_approved']=='approved' ? 'selected' : '' ?>>
                                            Одобрена
                                        </option>

                                        <option value="declined"
                                            <?= $r['is_approved']=='declined' ? 'selected' : '' ?>>
                                            Неодобрена
                                        </option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>

<script>
    document.querySelectorAll('select[name="status"]').forEach(select=>{

        function update(){
            select.classList.remove(
                'status-pending',
                'status-approved',
                'status-declined'
            );

            select.classList.add(
                'status-' + select.value
            );
        }

        update();
        select.addEventListener('change',update);
    });

</script>

</html>
