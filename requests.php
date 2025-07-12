<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Handle form submission (send new swap request)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['to_user_id'], $_POST['skill_requested'], $_POST['skill_offered'])
) {
    $to_user_id = $_POST['to_user_id'];
    $skill_requested = trim($_POST['skill_requested']);
    $skill_offered = trim($_POST['skill_offered']);
    $message = trim($_POST['message'] ?? '');
    if ($to_user_id && $skill_requested && $skill_offered) {
        // Prevent duplicate pending requests
        $stmt = $conn->prepare("SELECT id FROM swaps WHERE requester_id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $user_id, $to_user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $msg = "<span class='error-msg'>You already have a pending request with this user.</span>";
        } else {
            $stmt = $conn->prepare("INSERT INTO swaps (requester_id, receiver_id, skill_requested, skill_offered, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("iisss", $user_id, $to_user_id, $skill_requested, $skill_offered, $message);
    if ($stmt->execute()) {
                $msg = "<span class='success-msg'>Swap request sent!</span>";
            } else {
                $msg = "<span class='error-msg'>Error: {$stmt->error}</span>";
            }
        }
    } else {
        $msg = "<span class='error-msg'>Please fill in all fields.</span>";
    }
}

// Handle Accept/Reject actions for pending requests
if (isset($_POST['swap_action'], $_POST['swap_id']) && in_array($_POST['swap_action'], ['accept', 'reject'])) {
    $swap_id = intval($_POST['swap_id']);
    $action = $_POST['swap_action'];
    // Only allow receiver to accept/reject
    $stmt = $conn->prepare("SELECT * FROM swaps WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $swap_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows) {
        $new_status = $action === 'accept' ? 'accepted' : 'rejected';
        $stmt2 = $conn->prepare("UPDATE swaps SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt2->bind_param("si", $new_status, $swap_id);
        if ($stmt2->execute()) {
            $msg = "<span class='success-msg'>Request has been $new_status.</span>";
        } else {
            $msg = "<span class='error-msg'>Error: {$stmt2->error}</span>";
        }
    } else {
        $msg = "<span class='error-msg'>Invalid request or already handled.</span>";
    }
}

// Handle sending a new message for a swap
if (isset($_POST['send_message'], $_POST['swap_id'], $_POST['message_text'])) {
    $swap_id = intval($_POST['swap_id']);
    $msg_text = trim($_POST['message_text']);
    // Check if user is part of the swap
    $stmt = $conn->prepare("SELECT * FROM swaps WHERE id = ? AND (requester_id = ? OR receiver_id = ?)");
    $stmt->bind_param("iii", $swap_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows && $msg_text !== '') {
        $stmt2 = $conn->prepare("INSERT INTO swap_messages (swap_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt2->bind_param("iis", $swap_id, $user_id, $msg_text);
        $stmt2->execute();
    }
}

// Fetch list of users to show in dropdown
$users = $conn->query("SELECT id, name FROM users WHERE id != $user_id");
// Fetch pending requests (where user is receiver)
$pending = $conn->query("SELECT s.*, u.name as requester_name FROM swaps s JOIN users u ON s.requester_id = u.id WHERE s.receiver_id = $user_id AND s.status = 'pending' ORDER BY s.created_at DESC");
// Fetch completed swaps (where user is requester or receiver)
$completed = $conn->query("SELECT s.*, u1.name as requester_name, u2.name as receiver_name FROM swaps s JOIN users u1 ON s.requester_id = u1.id JOIN users u2 ON s.receiver_id = u2.id WHERE (s.requester_id = $user_id OR s.receiver_id = $user_id) AND (s.status = 'completed' OR s.status = 'accepted') ORDER BY s.updated_at DESC");

// Helper: fetch messages for a swap
function fetch_swap_messages($conn, $swap_id) {
    $stmt = $conn->prepare("SELECT m.*, u.name, u.profile_photo FROM swap_messages m JOIN users u ON m.sender_id = u.id WHERE m.swap_id = ? ORDER BY m.sent_at ASC");
    $stmt->bind_param("i", $swap_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Helper: count unread messages for a swap for the current user
function count_unread_messages($conn, $swap_id, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM swap_messages WHERE swap_id = ? AND sender_id != ? AND (seen IS NULL OR seen = 0)");
    $stmt->bind_param("ii", $swap_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? intval($row['unread']) : 0;
}

// Build a list of all swaps (pending and completed) for sidebar
$all_swaps = [];
if ($pending->num_rows) foreach ($pending as $swap) $all_swaps[] = $swap;
if ($completed->num_rows) foreach ($completed as $swap) $all_swaps[] = $swap;
// Determine selected swap
$selected_swap_id = $_GET['swap'] ?? ($all_swaps[0]['id'] ?? null);
$selected_swap = null;
foreach ($all_swaps as $s) if ($s['id'] == $selected_swap_id) $selected_swap = $s;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Requests | SkillSwap</title>
<link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .requests-main {
            max-width: 900px;
            margin: 0 auto;
            padding: 2.5rem 1rem 1rem 1rem;
        }
        .card-section {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 #b3e5fc55;
            padding: 2rem 2rem 1.5rem 2rem;
            margin-bottom: 2rem;
        }
        .card-section h2 {
            color: #1976d2;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
        }
        .swap-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .swap-list li {
            margin-bottom: 1.1rem;
            color: #1976d2;
            font-size: 1.05rem;
            background: #f8faff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #b3e5fc22;
            padding: 1rem 1.2rem;
            display: flex;
            flex-direction: column;
        }
        .swap-list .swap-meta {
            font-size: 0.98rem;
            color: #2196f3;
            margin-bottom: 0.3rem;
        }
        .swap-list .swap-status {
            font-weight: 600;
            color: #fff;
            background: #2196f3;
            border-radius: 6px;
            padding: 0.2em 0.8em;
            display: inline-block;
            margin-top: 0.5em;
        }
        .swap-list .swap-status.completed {
            background: #43e97b;
        }
        .swap-list .swap-status.pending {
            background: #ffb300;
        }
        .swap-list .swap-status.rejected {
            background: #ff6b6b;
        }
        .swap-list .swap-status.accepted {
            background: #1976d2;
        }
        .swap-list .swap-skills {
            margin: 0.2em 0 0.2em 0;
        }
        .swap-list .swap-skills span {
            background: #eaf6ff;
            color: #1976d2;
            border-radius: 6px;
            padding: 0.2em 0.7em;
            margin-right: 0.5em;
            font-size: 0.95em;
            font-weight: 600;
        }
        .swap-list .swap-date {
            font-size: 0.92em;
            color: #6c757d;
            margin-top: 0.2em;
        }
        .success-msg, .error-msg {
            display: block;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.7rem 1rem;
        }
        .success-msg {
            color: #2196f3;
            background: #eaf6ff;
        }
        .error-msg {
            color: #ff6b6b;
            background: #fff0f0;
        }
        .swap-form {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 #b3e5fc55;
            padding: 2rem 2rem 1.5rem 2rem;
            margin-bottom: 2rem;
        }
        .swap-form label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #1565c0;
        }
        .swap-form select,
        .swap-form input[type="text"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1.5px solid #b3e5fc;
            background: #f8faff;
            color: #1976d2;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            outline: none;
            transition: box-shadow 0.2s, border 0.2s;
        }
        .swap-form select:focus,
        .swap-form input[type="text"]:focus {
            box-shadow: 0 0 0 2px #b3e5fc;
            border: 1.5px solid #2196f3;
            background: #eaf6ff;
        }
        .swap-form button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 0.5rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
            box-shadow: 0 2px 8px #2196f322;
        }
        .swap-form button:hover {
            background: linear-gradient(90deg, #1565c0 0%, #2196f3 100%);
            transform: translateY(-2px) scale(1.03);
        }
        @media (max-width: 700px) {
            .requests-main { padding: 1rem 0.2rem; }
            .card-section, .swap-form { padding: 1.2rem 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="requests-main">
        <form method="POST" class="swap-form">
<h2>Send Swap Request</h2>
            <?php if (isset($msg)) echo $msg; ?>
            <label for="to_user_id">Send To:</label>
    <select name="to_user_id" required>
                <option value="">-- Select User --</option>
        <?php while($u = $users->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
        <?php endwhile; ?>
            </select>
            <label for="skill_requested">Skill Requested:</label>
            <input type="text" name="skill_requested" required>
            <label for="skill_offered">Skill Offered:</label>
            <input type="text" name="skill_offered" required>
            <label for="message">Message (optional):</label>
            <input type="text" name="message">
    <button type="submit">Send Request</button>
</form>
        <div class="card-section">
            <h2>Pending Requests</h2>
            <ul class="swap-list">
                <?php if ($pending->num_rows): foreach ($pending as $swap): ?>
                    <li>
                        <div class="swap-meta">From: <?= htmlspecialchars($swap['requester_name']) ?></div>
                        <div class="swap-skills">
                            <span><?= htmlspecialchars($swap['skill_requested']) ?></span>
                            <span><?= htmlspecialchars($swap['skill_offered']) ?></span>
                        </div>
                        <div class="swap-date">Requested on <?= date('M d, Y', strtotime($swap['created_at'])) ?></div>
                        <span class="swap-status pending">Pending</span>
                        <?php if ($swap['receiver_id'] == $user_id): ?>
                        <form method="POST" style="margin-top:0.7em;display:flex;gap:0.7em;">
                            <input type="hidden" name="swap_id" value="<?= $swap['id'] ?>">
                            <button type="submit" name="swap_action" value="accept" style="background:#43e97b;color:#fff;border:none;padding:0.4em 1.2em;border-radius:6px;font-weight:600;cursor:pointer;">Accept</button>
                            <button type="submit" name="swap_action" value="reject" style="background:#ff6b6b;color:#fff;border:none;padding:0.4em 1.2em;border-radius:6px;font-weight:600;cursor:pointer;">Reject</button>
                        </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; else: ?>
                    <li>No pending requests.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="card-section">
            <h2>Completed Swaps</h2>
            <ul class="swap-list">
                <?php if ($completed->num_rows): foreach ($completed as $swap): ?>
                    <li>
                        <div class="swap-meta">Between: <?= htmlspecialchars($swap['requester_name']) ?> &amp; <?= htmlspecialchars($swap['receiver_name']) ?></div>
                        <div class="swap-skills">
                            <span><?= htmlspecialchars($swap['skill_requested']) ?></span>
                            <span><?= htmlspecialchars($swap['skill_offered']) ?></span>
                        </div>
                        <div class="swap-date">Completed on <?= date('M d, Y', strtotime($swap['updated_at'])) ?></div>
                        <span class="swap-status completed">Completed</span>
                        <?php if (!empty($swap['message'])): ?>
                        <div class="swap-meta" style="margin-top:0.5em;font-size:0.97em;color:#1976d2;">Message: <?= htmlspecialchars($swap['message']) ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; else: ?>
                    <li>No completed swaps yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>
