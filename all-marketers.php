<?php
session_start();
include('includes/config.php');
if(strlen($_SESSION['alogin'])==0)
{   
    header('location:index');
    exit;
}
$userEmail = $_SESSION['alogin'];
$sql1 = "SELECT base_link, public_link, username FROM admin WHERE email = :email LIMIT 1";
$stmt = $dbh->prepare($sql1);
$stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_OBJ);
$public_link = $admin->public_link;

// Fetch marketers with transaction counts
$sql = "
    SELECT m.id, m.name, m.email, m.public_id, m.created_at,
        COALESCE(SUM(CASE WHEN t.status = 0 THEN 1 ELSE 0 END),0) AS pending_count,
        COALESCE(SUM(CASE WHEN t.status = 1 THEN 1 ELSE 0 END),0) AS success_count,
        COALESCE(SUM(CASE WHEN t.status = 2 THEN 1 ELSE 0 END),0) AS failed_count
    FROM marketers m
    LEFT JOIN transaction t ON m.public_id = t.marketer_id
    GROUP BY m.id
    ORDER BY m.created_at DESC
";
$query = $dbh->prepare($sql);
$query->execute();
$marketers = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>All Marketers</title>

    <!-- Bootstrap CSS -->
    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all" />
    
    <!-- FontAwesome -->
    <link href="vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet" media="all" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" />

    <!-- Custom CSS -->
    <link href="css/theme.css" rel="stylesheet" media="all" />
</head>

<body class="animsition">
    <div class="page-wrapper">
        <?php include 'includes/sidebar.php' ?>

        <div class="page-container">
            <?php include 'includes/header.php' ?>

            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">

                        <h2 class="mb-4">All Marketers</h2>

                        <div class="table-responsive m-b-40">
                            <table id="marketersTable" class="table table-border table-data3">
                                <thead>
                                    <tr>
                                        <th>Date Created</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Marketing Link</th>
                                        <th>Pending</th>
                                        <th>Success</th>
                                        <th>Failed</th>
                                        <th>Copy Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($marketers as $row): 
                                        // $link = "http://localhost/marketing/admin/market?marketer_id=" . urlencode($row['public_id']) . "&trxid=XXX&traffic_source=XXX";
                                        $link = rtrim($public_link, '/') . "/market?marketer_id=" . urlencode($row['public_id']) . "&trxid=xxx&trfsrc=xxx";

                                    ?>
                                    <tr>
                                        <td><?php echo date('F d, Y h:i:s A', strtotime(htmlentities($row['created_at'])));?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener noreferrer">
                                                <?= htmlspecialchars($row['public_id']) ?>
                                            </a>
                                        </td>
                                        <td><?= (int)$row['pending_count'] ?></td>
                                        <td><?= (int)$row['success_count'] ?></td>
                                        <td><?= (int)$row['failed_count'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary copy-btn" data-link="<?= htmlspecialchars($link) ?>">
                                                <i class="fa fa-copy"></i> Copy
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery JS -->
    <script src="vendor/jquery-3.2.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="vendor/bootstrap-4.1/popper.min.js"></script>
    <script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#marketersTable').DataTable({
            "order": [[0, "desc"]],
            "columnDefs": [
                { "orderable": false, "targets": 7 } // Disable ordering on copy button column
            ]
        });

        $('.copy-btn').click(function() {
            var link = $(this).data('link');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(link).then(function() {
                    alert('Link copied to clipboard!');
                }, function() {
                    alert('Failed to copy link.');
                });
            } else {
                // Fallback for older browsers
                var tempInput = $("<input>");
                $("body").append(tempInput);
                tempInput.val(link).select();
                document.execCommand("copy");
                tempInput.remove();
                alert('Link copied to clipboard!');
            }
        });
    });
    </script>
</body>

</html>
