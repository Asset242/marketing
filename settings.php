<?php
session_start();
error_reporting(0);
include('includes/config.php');

if(strlen($_SESSION['alogin']) == 0) {
    header('location:index');
    exit;
}

$userEmail = $_SESSION['alogin'];
$msg = "";
$error = "";

// Fetch current links
$sql = "SELECT base_link, check_link, public_link, username FROM admin WHERE email = :email LIMIT 1";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_OBJ);

if (!$admin) {
    die("Admin user not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $base_link = trim($_POST['base_link'] ?? '');
    $public_link = trim($_POST['public_link'] ?? '');
    $check_link = trim($_POST['check_link'] ?? '');

    // Basic validation
    if (empty($base_link) || empty($public_link) || empty($check_link)) {
        $error = "Both Base Link and Public Link are required.";
    } else if (!filter_var($base_link, FILTER_VALIDATE_URL) || !filter_var($public_link, FILTER_VALIDATE_URL) || !filter_var($check_link, FILTER_VALIDATE_URL)) {
        $error = "Please enter valid URLs for both links.";
    } else {
        // Update in DB
        $updateSql = "UPDATE admin SET base_link = :base_link, public_link = :public_link, check_link = :check_link WHERE email = :email";
        $updateStmt = $dbh->prepare($updateSql);
        $updateStmt->bindParam(':base_link', $base_link, PDO::PARAM_STR);
        $updateStmt->bindParam(':public_link', $public_link, PDO::PARAM_STR);
        $updateStmt->bindParam(':check_link', $check_link, PDO::PARAM_STR);
        $updateStmt->bindParam(':email', $userEmail, PDO::PARAM_STR);

        if ($updateStmt->execute()) {
            $msg = "Links updated successfully.";
            // Refresh current values
            $admin->base_link = $base_link;
            $admin->public_link = $public_link;
        } else {
            $error = "Error updating links. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Update Links</title>

    <!-- Bootstrap CSS -->
    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all" />

    <!-- FontAwesome -->
    <link href="vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet" media="all" />

    <!-- Custom CSS -->
    <link href="css/theme.css" rel="stylesheet" media="all" />
</head>

<body class="animsition">
    <div class="page-wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php' ?>

        <div class="page-container">
            <!-- Header -->
            <?php include 'includes/header.php' ?>

            <!-- Main Content -->
            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">

                        <h2 class="mb-4">Update Base and Public Links</h2>

                        <?php if ($msg): ?>
                            <div class="alert alert-success"><?php echo htmlentities($msg); ?></div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlentities($error); ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <div class="form-group">
                                <label for="base_link">Base Link</label>
                                <input type="url" class="form-control" id="base_link" name="base_link" 
                                       value="<?php echo htmlentities($admin->base_link); ?>" required />
                                <!-- <small class="form-text text-muted">Example: https://www.martad.com</small> -->
                            </div>

                            <div class="form-group">
                                <label for="public_link">Public Link</label>
                                <input type="url" class="form-control" id="public_link" name="public_link" 
                                       value="<?php echo htmlentities($admin->public_link); ?>" required />
                                <!-- <small class="form-text text-muted">Example: https://public.martad.com</small> -->
                            </div>
                            <div class="form-group">
                                <label for="check_link">Subscription Verification Endpoint</label>
                                <input type="url" class="form-control" id="check_link" name="check_link" 
                                       value="<?php echo htmlentities($admin->check_link); ?>" required />
                                <!-- <small class="form-text text-muted">Example: https://public.martad.com</small> -->
                            </div>
                            <button type="submit" class="btn btn-primary">Update Links</button>
                        </form>

                    </div>
                </div>
            </div>
            <!-- END MAIN CONTENT -->
        </div>
    </div>

    <!-- jQuery JS -->
    <script src="vendor/jquery-3.2.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="vendor/bootstrap-4.1/popper.min.js"></script>
    <script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
</body>

</html>
