<?php
include "../config/conn.php";
session_start();

if (!isset($_SESSION['verified_superadmin_email'])) {
    header('Location: super-admin.php');
    exit;
}

$result = $conn->query("SELECT * FROM admin WHERE role = 'sa'");

$super_admin_mysql_email = [];
$super_admin_users = [];
while ($row = $result->fetch_assoc()) {
    $super_admin_mysql_email[] = $row['email'];
    $super_admin_users[] = [
        'username' => $row['name'],
        'email' => $row['email']
    ];
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_email = $_POST['super_admin_email'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE LOWER(email) = ? AND role = 'sa'");
        $email_lower = strtolower($selected_email);
        $stmt->bind_param("s", $email_lower);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE admin SET password = ? WHERE LOWER(email) = ?");
            $update->bind_param("ss", $hashed, $email_lower);
            $update->execute();
            $success = "Password updated successfully for $selected_email.  Redirecting to login...";
            unset($_SESSION['verified_superadmin_email']);
            $redirection = true;
        } else {
            $error = "Super admin email not found or not authorized.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Super Admin Password</title>
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/assets/css/main-super-admin.css">
</head>

<body>
    <div class="container">
        <div class="card card-custom">
            <h1 class="text-center mb-4"></h1>
            <h4 class="text-center mb-4">Reset Super Admin Password</h4>
            <form method="POST">
                <div class="mb-3">
                    <label for="super_admin_email" class="form-label">Super Admin Email</label>
                    <select name="super_admin_email" id="super_admin_email" class="form-select" required>
                        <?php foreach ($super_admin_mysql_email as $email): ?>
                            <option value="<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br>
                    <div class="mb-3">
                        <label for="searchInput" class="form-label">Or Search your Username here</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by username" autocomplete="off">
                        <div id="usernameResults" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mt-3" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success mt-3" role="alert"><?php echo $success; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
    const superAdminUsers = <?php echo json_encode($super_admin_users); ?>;
    const searchInput = document.getElementById('searchInput');
    const resultsDiv = document.getElementById('usernameResults');
    const emailSelect = document.getElementById('super_admin_email');

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        resultsDiv.innerHTML = '';
        if (query.length < 2) return;
        const matches = superAdminUsers.filter(item => item.username.toLowerCase().includes(query));
        if (matches.length === 0) {
            resultsDiv.innerHTML = '<div class="list-group-item">No results found</div>';
            return;
        }
        matches.forEach(item => {
            const div = document.createElement('div');
            div.className = 'list-group-item list-group-item-action';
            div.textContent = `${item.username} (${item.email})`;
            div.addEventListener('click', () => {
                for (let i = 0; i < emailSelect.options.length; i++) {
                    if (emailSelect.options[i].value.toLowerCase() === item.email.toLowerCase()) {
                        emailSelect.selectedIndex = i;
                        break;
                    }
                }
                searchInput.value = item.username;
                resultsDiv.innerHTML = '';
            });
            resultsDiv.appendChild(div);
        });
    });
    window.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.innerHTML = '';
        }
    });
</script>
<?php if (isset($redirect) && $redirect): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 3000);
    </script>
<?php endif; ?>

</html>