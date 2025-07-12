<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch all swaps (pending and completed) for sidebar
$pending = $conn->query("SELECT s.*, u1.name as requester_name, u1.profile_photo as requester_photo, u2.name as receiver_name, u2.profile_photo as receiver_photo FROM swaps s JOIN users u1 ON s.requester_id = u1.id JOIN users u2 ON s.receiver_id = u2.id WHERE s.requester_id = $user_id OR s.receiver_id = $user_id ORDER BY s.updated_at DESC");
$all_swaps = [];
if ($pending->num_rows) foreach ($pending as $swap) $all_swaps[] = $swap;
// Determine selected swap
$selected_swap_id = $_GET['swap'] ?? ($all_swaps[0]['id'] ?? null);
$selected_swap = null;
foreach ($all_swaps as $s) if ($s['id'] == $selected_swap_id) $selected_swap = $s;
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
// Handle sending a new message for a swap
if (isset($_POST['send_message'], $_POST['swap_id'], $_POST['message_text'])) {
    $swap_id = intval($_POST['swap_id']);
    $msg_text = trim($_POST['message_text']);
    // Debug log
    $log_entry = date('Y-m-d H:i:s') . " | user_id: $user_id | swap_id: $swap_id | msg: $msg_text\n";
    file_put_contents(__DIR__ . '/chat_debug.log', $log_entry, FILE_APPEND);
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
    // Redirect to avoid resubmission
    header("Location: chat.php?swap=$swap_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat | SkillSwap</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%); min-height: 100vh; }
        .chat-app-container { display: flex; height: 80vh; max-width: 950px; margin: 2.5rem auto 0 auto; background: rgba(255,255,255,0.98); border-radius: 22px; box-shadow: 0 8px 32px #b3e5fc77; overflow: hidden; }
        .chat-sidebar { width: 320px; background: #f4faff; border-right: 1.5px solid #eaf6ff; display: flex; flex-direction: column; padding: 0; overflow-y: auto; }
        .chat-sidebar-header { position:sticky;top:0;z-index:2; padding: 1.2rem 1.5rem 1rem 1.5rem; font-weight: 700; color: #1976d2; font-size: 1.2rem; border-bottom: 1.5px solid #eaf6ff; background: #fff; }
        .chat-list { list-style: none; margin: 0; padding: 0; }
        .chat-list-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; cursor: pointer; border-bottom: 1px solid #eaf6ff; transition: background 0.15s; position: relative; border-radius: 0; }
        .chat-list-item.active, .chat-list-item:hover { background: #eaf6ff; }
        .chat-list-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #2196f3 0%, #4fc3f7 100%); color: #fff; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .chat-list-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
        .chat-list-info { flex: 1; min-width: 0; }
        .chat-list-name { font-weight: 600; color: #1976d2; font-size: 1.05rem; margin-bottom: 0.2em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-list-lastmsg { color: #6c757d; font-size: 0.97rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-list-unread { background: #ff6b6b; color: #fff; border-radius: 50%; font-size: 0.9em; font-weight: 700; padding: 0.2em 0.6em; position: absolute; right: 1.2rem; top: 1.2rem; }
        .chat-main { flex: 1; display: flex; flex-direction: column; background: #f8faff; position: relative; }
        .chat-main-header { position:sticky;top:0;z-index:2; display: flex; align-items: center; gap: 1rem; padding: 1.2rem 1.5rem 1rem 1.5rem; border-bottom: 1.5px solid #eaf6ff; background: #fff; border-top-left-radius: 0; border-top-right-radius: 0; }
        .chat-main-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #2196f3 0%, #4fc3f7 100%); color: #fff; font-weight: 700; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .chat-main-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
        .chat-main-name { font-weight: 700; color: #1976d2; font-size: 1.1rem; }
        .chat-messages-area { flex: 1; overflow-y: auto; padding: 1.2rem 1.5rem 1.2rem 1.5rem; display: flex; flex-direction: column; gap: 1.1em; background: #f4faff; }
        .chat-bubble-row { display: flex; align-items: flex-end; gap: 0.5em; justify-content: flex-start; }
        .chat-bubble-row.me { justify-content: flex-end; }
        .chat-bubble { max-width: 70%; padding: 0.8em 1.2em; border-radius: 18px; font-size: 1.04em; box-shadow: 0 2px 12px #b3e5fc33; background: #fff; color: #1976d2; text-align: left; word-break: break-word; position: relative; border: 1.5px solid #eaf6ff; }
        .chat-bubble.me { background: linear-gradient(135deg, #4fc3f7 0%, #2196f3 100%); color: #fff; border: none; }
        .chat-bubble-timestamp { font-size: 0.82em; color: #6c757d; margin-top: 0.3em; text-align: right; }
        .chat-bubble.me .chat-bubble-timestamp { color: #eaf6ff; }
        .chat-input-area { display: flex; gap: 0.5em; align-items: center; padding: 1.1rem 1.5rem 1.3rem 1.5rem; border-top: 1.5px solid #eaf6ff; background: #fff; }
        .chat-input { flex: 1; padding: 0.8em 1.1em; border-radius: 14px; border: 1.5px solid #b3e5fc; background: #f8faff; color: #1976d2; font-size: 1.07em; transition: border 0.2s, box-shadow 0.2s; }
        .chat-input:focus { border: 1.5px solid #2196f3; box-shadow: 0 0 0 2px #b3e5fc55; outline: none; }
        .chat-send-btn { background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%); color: #fff; border: none; padding: 0.8em 1.5em; border-radius: 14px; font-weight: 700; cursor: pointer; font-size: 1.07em; transition: background 0.2s, transform 0.2s; box-shadow: 0 2px 8px #2196f322; }
        .chat-send-btn:hover { background: linear-gradient(90deg, #1565c0 0%, #2196f3 100%); transform: scale(1.04); }
        @media (max-width: 900px) { .chat-app-container { flex-direction: column; height: auto; min-height: 80vh; } .chat-sidebar { width: 100%; min-height: 120px; border-right: none; border-bottom: 1.5px solid #eaf6ff; } .chat-main { min-height: 400px; } }
    </style>
</head>
<body>
    <div class="chat-app-container">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">Chats</div>
            <ul class="chat-list">
                <?php foreach ($all_swaps as $swap):
                    $other_id = $swap['requester_id'] == $user_id ? $swap['receiver_id'] : $swap['requester_id'];
                    $other_name = $swap['requester_id'] == $user_id ? ($swap['receiver_name'] ?? '') : ($swap['requester_name'] ?? '');
                    $other_photo = $swap['requester_id'] == $user_id ? ($swap['receiver_photo'] ?? '') : ($swap['requester_photo'] ?? '');
                    $unread = count_unread_messages($conn, $swap['id'], $user_id);
                    // Fetch last message
                    $last_msg_row = $conn->query("SELECT message, sent_at FROM swap_messages WHERE swap_id = {$swap['id']} ORDER BY sent_at DESC LIMIT 1")->fetch_assoc();
                    $last_msg = $last_msg_row['message'] ?? ($swap['message'] ?? 'No messages yet.');
                ?>
                <li class="chat-list-item<?= $swap['id'] == $selected_swap_id ? ' active' : '' ?>" onclick="window.location.href='chat.php?swap=<?= $swap['id'] ?>'">
                    <div class="chat-list-avatar">
                        <?php if (!empty($other_photo) && file_exists($other_photo)): ?>
                            <img src="<?= htmlspecialchars($other_photo) ?>" alt="<?= htmlspecialchars($other_name) ?>">
                        <?php else: ?>
                            <?= htmlspecialchars(substr($other_name,0,2)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="chat-list-info">
                        <div class="chat-list-name"><?= htmlspecialchars($other_name) ?></div>
                        <div class="chat-list-lastmsg"><?= htmlspecialchars($last_msg) ?></div>
                    </div>
                    <?php if ($unread > 0): ?>
                        <span class="chat-list-unread">+<?= $unread ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <!-- Main Chat Area -->
        <div class="chat-main">
            <?php if ($selected_swap):
                $other_name = $selected_swap['requester_id'] == $user_id ? ($selected_swap['receiver_name'] ?? '') : ($selected_swap['requester_name'] ?? '');
                $other_photo = $selected_swap['requester_id'] == $user_id ? ($selected_swap['receiver_photo'] ?? '') : ($selected_swap['requester_photo'] ?? '');
            ?>
            <div class="chat-main-header">
                <div class="chat-main-avatar">
                    <?php if (!empty($other_photo) && file_exists($other_photo)): ?>
                        <img src="<?= htmlspecialchars($other_photo) ?>" alt="<?= htmlspecialchars($other_name) ?>">
                    <?php else: ?>
                        <?= htmlspecialchars(substr($other_name,0,2)) ?>
                    <?php endif; ?>
                </div>
                <div class="chat-main-name"><?= htmlspecialchars($other_name) ?></div>
            </div>
            <div class="chat-messages-area" id="chat-messages-area">
                <?php $msgs = fetch_swap_messages($conn, $selected_swap['id']);
                if ($msgs->num_rows):
                    foreach ($msgs as $msg):
                        $isMe = $msg['sender_id'] == $user_id;
                ?>
                <div class="chat-bubble-row<?= $isMe ? ' me' : '' ?>">
                    <?php if (!$isMe): ?>
                        <div class="chat-main-avatar" style="width:28px;height:28px;font-size:0.95rem;">
                            <?php if (!empty($msg['profile_photo']) && file_exists($msg['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars($msg['profile_photo']) ?>" alt="<?= htmlspecialchars($msg['name']) ?>">
                            <?php else: ?>
                                <?= htmlspecialchars(substr($msg['name'],0,2)) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="chat-bubble<?= $isMe ? ' me' : '' ?>">
                        <?= htmlspecialchars($msg['message']) ?>
                        <div class="chat-bubble-timestamp">
                            <?= date('M d, H:i', strtotime($msg['sent_at'])) ?>
                        </div>
                    </div>
                    <?php if ($isMe): ?>
                        <div class="chat-main-avatar" style="width:28px;height:28px;font-size:0.95rem;">
                            <?php if (!empty($msg['profile_photo']) && file_exists($msg['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars($msg['profile_photo']) ?>" alt="<?= htmlspecialchars($msg['name']) ?>">
                            <?php else: ?>
                                <?= htmlspecialchars(substr($msg['name'],0,2)) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach;
                else:
                    echo '<div style="color:#6c757d;text-align:center;">No messages yet.</div>';
                endif; ?>
            </div>
            <form method="POST" class="chat-input-area" autocomplete="off">
                <input type="hidden" name="swap_id" value="<?= $selected_swap['id'] ?>">
                <input type="text" name="message_text" class="chat-input" placeholder="Type a message..." required autocomplete="off">
                <button type="submit" name="send_message" class="chat-send-btn">Send</button>
            </form>
            <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#b3e5fc;font-size:2.2rem;flex-direction:column;">
                <i class="fas fa-comments" style="font-size:3.5rem;margin-bottom:1.2rem;"></i>
                <div style="color:#1976d2;font-size:1.2rem;font-weight:600;">Select a chat to start messaging</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    // Auto-scroll to latest message
    var chatArea = document.getElementById('chat-messages-area');
    if (chatArea) chatArea.scrollTop = chatArea.scrollHeight;
    </script>
</body>
</html> 