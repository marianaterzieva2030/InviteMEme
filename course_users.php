<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: /InviteMEme/login.html');
    exit;
}

$edition_id = $_SESSION['teacher_edition_id'];

require 'database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

// filters and sort
$studyYear = $_GET['study_year'] ?? '';
$major = $_GET['major'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

$where = ["edition_id = :eid", "role = 'student'"];
$params = [':eid' => $edition_id];

if ($studyYear !== '') {
    $where[] = "study_year = :study_year";
    $params[':study_year'] = $studyYear;
}

if ($major !== '') {
    $where[] = "major = :major";
    $params[':major'] = $major;
}

$orderBy = "last_name ASC, first_name ASC";

switch ($sort) {

    case 'name_desc':
        $orderBy = "first_name DESC";
        break;

    case 'faculty_asc':
        $orderBy = "faculty_number ASC";
        break;

    case 'faculty_desc':
        $orderBy = "faculty_number DESC";
        break;

    case 'name_asc':
    default:
        $orderBy = "first_name ASC";
}

$sql = "SELECT * FROM users
    WHERE " . implode(" AND ", $where) . "
    ORDER BY $orderBy
";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Статистики</title>
    <link rel="stylesheet" href="styles/course.css">
    <style>
        th,
        td {
            padding: .5rem;
            border-bottom: 1px solid #ddd;
        }
    </style>
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
                            <li><a href="#">Потребители</a></li>
                            <li><a href="course_stats.php">Статистики</a></li>
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
        <h2>Регистрирани потребители в курса</h2> <br>

        <div class="import-export-buttons">
            <button type="submit" form="exportForm" class="btn">
                Export студенти
            </button>

            <a href="import_students.php" class="btn">
                Import студенти
            </a>
        </div>

        <form method="GET" class="filters">
            <label>Курс</label>
            <select name="study_year">
                <option value="">Всички</option>

                <option value="1"
                    <?= $studyYear == '1' ? 'selected' : '' ?>>
                    1
                </option>

                <option value="2"
                    <?= $studyYear == '2' ? 'selected' : '' ?>>
                    2
                </option>

                <option value="3"
                    <?= $studyYear == '3' ? 'selected' : '' ?>>
                    3
                </option>

                <option value="4"
                    <?= $studyYear == '4' ? 'selected' : '' ?>>
                    4
                </option>
            </select>


            <label>Специалност</label>
            <select name="major">

                <option value="">Всички</option>

                <option value="Софтуерно инженерство"
                    <?= $major == 'Софтуерно инженерство' ? 'selected' : '' ?>>
                    Софтуерно инженерство
                </option>

                <option value="Компютърни науки"
                    <?= $major == 'Компютърни науки' ? 'selected' : '' ?>>
                    Компютърни науки
                </option>

                <option value="Информатика"
                    <?= $major == 'Информатика' ? 'selected' : '' ?>>
                    Информатика
                </option>

                <option value="Информационни системи"
                    <?= $major == 'Информационни системи' ? 'selected' : '' ?>>
                    Информационни системи
                </option>

            </select>


            <label>Сортиране</label>
            <select name="sort">

                <option value="name_asc"
                    <?= $sort == 'name_asc' ? 'selected' : '' ?>>
                    Име ↑
                </option>

                <option value="name_desc"
                    <?= $sort == 'name_desc' ? 'selected' : '' ?>>
                    Име ↓
                </option>

                <option value="faculty_asc"
                    <?= $sort == 'faculty_asc' ? 'selected' : '' ?>>
                    Фак. номер ↑
                </option>

                <option value="faculty_desc"
                    <?= $sort == 'faculty_desc' ? 'selected' : '' ?>>
                    Фак. номер ↓
                </option>

            </select>

            <button class="btn" type="submit">Приложи</button>
        </form>
        <?php if (empty($users)): ?>
            <p>Няма записи.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Студент</th>
                        <th>Фак. номер</th>
                        <th>Имейл</th>
                        <th>Курс</th>
                        <th>Специалност</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(trim($u['first_name'] . ' ' . $u['last_name'])); ?></td>
                            <td><?php echo htmlspecialchars($u['faculty_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['study_year']); ?></td>
                            <td><?php echo htmlspecialchars($u['major']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>

</html>