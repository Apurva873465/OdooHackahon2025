<?php
include 'includes/db.php'; include 'includes/auth.php';
$id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $offered = $_POST['skills_offered'];
    $wanted = $_POST['skills_wanted'];
    $avail = $_POST['availability'];
    $sql = "UPDATE users SET skills_offered=?, skills_wanted=?, availability=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $offered, $wanted, $avail, $id);
    $stmt->execute(); header("Location: dashboard.php");
}
?>
<link rel="stylesheet" href="assets/css/style.css">
<div class="form-container">
<form method="POST">
    <h2>Update Skills</h2>
    Offered: <input name="skills_offered"><br>
    Wanted: <input name="skills_wanted"><br>
    Availability: <input name="availability"><br>
    <button>Save</button>
</form>
</div>