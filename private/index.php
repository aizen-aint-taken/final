<?php


$SuperAdmin = isset($_GET['SuperAdmin']) && $_GET['SuperAdmin'] === true;

if (!$SuperAdmin) {
    header("Location: index.php");
    exit();
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../private/index.css">
    <title>Forgot Pass</title>
</head>

<body>
    <form action="">
        <div class="form-group">
            <label for="ForgotPass1" class="form-label">Password</label>
            <input type="password" name="password" id="ForgotPass1" class="form-control"
                placeholder="Enter Your Password"
                aria-describedby="helpId" />
        </div>


        <div class="form-group">
            <label for="Confirm Pass">Confirm Password</label>
            <input type="password" name="confirm_password" id="ConfirmPass" class="form-control"
                placeholder="Confirm Your Password" aria-describedby="helpId" />
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>

    </form>
</body>
<script src="../private/pass.js"></script>

</html>