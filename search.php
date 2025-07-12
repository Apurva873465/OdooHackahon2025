<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Modern blue gradient background and floating card style
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Skills | SkillSwap</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4f8cff 0%, #1e3c72 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .floating-search {
            background: rgba(30, 40, 80, 0.92);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.25), 0 2px 8px 0 #4f8cff44;
            border-radius: 24px;
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 420px;
            width: 100%;
            margin: 2rem auto;
            color: #fff;
            position: relative;
            animation: floatIn 0.8s cubic-bezier(.39,.575,.56,1.000);
        }
        .floating-search h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: #e3f0ff;
        }
        .floating-search input[type="text"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: none;
            background: rgba(255,255,255,0.10);
            color: #e3f0ff;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            outline: none;
            transition: box-shadow 0.2s;
        }
        .floating-search input:focus {
            box-shadow: 0 0 0 2px #4f8cff;
            background: rgba(255,255,255,0.18);
        }
        .floating-search button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            background: linear-gradient(90deg, #4f8cff 0%, #1e3c72 100%);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 0.5rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }
        .floating-search button:hover {
            background: linear-gradient(90deg, #1e3c72 0%, #4f8cff 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .results {
            margin-top: 2rem;
        }
        .result-item {
            background: rgba(44, 62, 80, 0.95);
            border-radius: 16px;
            box-shadow: 0 4px 16px 0 #4f8cff22;
            padding: 1.5rem;
            margin-bottom: 1.2rem;
            color: #e3f0ff;
            animation: floatIn 0.7s cubic-bezier(.39,.575,.56,1.000);
        }
        .result-item h3 {
            margin: 0 0 0.5rem 0;
            color: #4f8cff;
        }
        .result-item a {
            color: #fff;
            background: #4f8cff;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .result-item a:hover {
            background: #1e3c72;
        }
        @keyframes floatIn {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .skill-tag {
            display: inline-block;
            background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 6px;
            padding: 0.3em 0.9em;
            margin: 0 0.2em 0.3em 0;
            box-shadow: 0 2px 8px 0 #2196f322;
            letter-spacing: 0.5px;
            transition: filter 0.2s;
        }
        .skill-tag.highlight-skill {
            filter: brightness(1.25) drop-shadow(0 0 6px #2196f3cc);
            border: 2px solid #1976d2;
        }
    </style>
</head>
<body>
    <div class="floating-search">
        <form method="GET">
            <h2>Search Skills</h2>
            <input name="skill" type="text" placeholder="e.g. Python, Design, Cooking" required autofocus>
            <button type="submit">Search</button>
        </form>
        <?php
        if (isset($_GET['skill']) && !empty($_GET['skill'])) {
            $db = Database::getInstance();
            $searchTerm = trim($_GET['skill']);
            $s = '%' . $searchTerm . '%';
            $users = $db->fetchAll("SELECT * FROM users WHERE skills_offered LIKE ? AND privacy = 'public'", [$s]);
            echo "<div class='results'>";
            if ($users && count($users) > 0) {
                foreach ($users as $row) {
                    echo "<div class='result-item'>\n";
                    echo "<h3>" . htmlspecialchars($row['name']) . "</h3>\n";
                    // Show skills as tags, highlight searched skill
                    $skills = array_map('trim', explode(',', $row['skills_offered']));
                    echo "<div style='margin-bottom:0.7rem;'>";
                    foreach ($skills as $skill) {
                        $isMatch = stripos($skill, $searchTerm) !== false;
                        echo "<span class='skill-tag" . ($isMatch ? " highlight-skill" : "") . "'>" . htmlspecialchars($skill) . "</span> ";
                    }
                    echo "</div>";
                    echo "<p><strong>Skills Wanted:</strong> " . htmlspecialchars($row['skills_wanted']) . "</p>\n";
                    echo "<a href='send_request.php?id=" . $row['id'] . "'>Send Swap Request</a>\n";
                    echo "</div>";
                }
            } else {
                echo "<p>No matching users found for '<strong>" . htmlspecialchars($searchTerm) . "</strong>'</p>";
            }
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
