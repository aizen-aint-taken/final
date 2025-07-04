<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['super_email'];

    $factory = (new Factory)->withServiceAccount('../private/firebase-credentials.json');
    $auth = $factory->createAuth();

    try {
        $user = $auth->getUserByEmail($email);
        $_SESSION['verified_superadmin_email'] = $email;
        header('Location: reset.php');
        exit;
    } catch (Exception $e) {
        $error = "Email is not registered in Firebase.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify Super Admin Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/assets/css/main-super-admin.css">

</head>

<body>
    <div class="container">
        <div class="card card-custom">
            <h4 class="text-center mb-4">Verify Super Admin Email</h4>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Firebase Email</label>
                    <input type="email" name="super_email" class="form-control" placeholder="Registered Firebase Email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify Email</button>
            </form>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mt-3" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>