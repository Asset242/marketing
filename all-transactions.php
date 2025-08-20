<?php
session_start();
include('includes/config.php');
if(strlen($_SESSION['alogin'])==0) {   
    header('location:index');
    exit;
}
$userEmail = $_SESSION['alogin'];
$sql2 = "SELECT base_link, public_link, username FROM admin WHERE email = :email LIMIT 1";
$stmt = $dbh->prepare($sql2);
$stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_OBJ);
$public_link = $admin->public_link;
// Fetch transactions joined with marketers
$sql = "
    SELECT t.id, t.trx_id, t.marketer_id, t.source, t.msisdn, t.status, t.created_at,
           m.name AS marketer_name, m.public_id AS marketer_public_id
    FROM transaction t
    LEFT JOIN marketers m ON t.marketer_id = m.public_id
    ORDER BY t.created_at ASC
";
$query = $dbh->prepare($sql);
$query->execute();
$transactions = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>All Transactions</title>

    <!-- Bootstrap CSS -->
    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all" />
    
    <!-- FontAwesome -->
    <link href="vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet" media="all" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" />

    <!-- Custom CSS -->
    <link href="css/theme.css" rel="stylesheet" media="all" />

    <style>
        .status-badge {
            padding: 4px 10px;
            border-radius: 5px;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .status-pending {
            background-color: #f0ad4e;
        }
        .status-success {
            background-color: #5cb85c;
        }
        .status-failed {
            background-color: #d9534f;
        }
    </style>
</head>

<body class="animsition">
    <div class="page-wrapper">
        <?php include 'includes/sidebar.php' ?>

        <div class="page-container">
            <?php include 'includes/header.php' ?>

            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">

                        <h2 class="mb-4">All Transactions</h2>

                        <div class="table-responsive m-b-40">
                            <table id="transactionsTable" class="table table-border table-data3">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Transaction ID</th>
                                        <th>Marketer Name</th>
                                        <th>Source</th>
                                        <th>MSISDN</th>
                                        <th>Status</th>
                                        <th>Copy Marketer Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $tx):
                                        // $link = "http://localhost/marketing/admin/market?marketer_id=" . urlencode($tx['marketer_public_id']) . "&trxid=XXX&traffic_source=XXX";
                                        $link = rtrim($public_link, '/') ."/market?marketer_id=" . urlencode($tx['marketer_public_id']) . "&trxid=xxx&trfsrc=xxx";
                                        // $link = rtrim($public_link, '/') . "/market?marketer_id=" . urlencode($row['public_id']) . "&trxid=XXX&traffic_source=XXX";


                                        // Status labels & colors
                                        $statusLabels = [
                                            0 => ['Pending', 'status-pending'],
                                            1 => ['Success', 'status-success'],
                                            2 => ['Failed', 'status-failed'],
                                        ];
                                        $status = isset($statusLabels[$tx['status']]) ? $statusLabels[$tx['status']] : ['Unknown', 'status-pending'];
                                    ?>
                                    <tr>
                                        <td><?= date('F d, Y h:i:s A', strtotime(htmlentities($tx['created_at']))) ?></td>
                                        <td><?= htmlspecialchars($tx['trx_id']) ?></td>
                                        <td>
                                            <?php if ($tx['marketer_public_id'] && $tx['marketer_name']): ?>
                                                <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener noreferrer">
                                                    <?= htmlspecialchars($tx['marketer_name']) ?>
                                                </a>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($tx['source']) ?></td>
                                        <td><?= htmlspecialchars($tx['msisdn']) ?></td>
                                        <td><span class="status-badge <?= $status[1] ?>"><?= $status[0] ?></span></td>
                                        <td>
                                            <?php if ($tx['marketer_public_id']): ?>
                                            <button class="btn btn-sm btn-primary copy-btn" data-link="<?= htmlspecialchars($link) ?>">
                                                <i class="fa fa-copy"></i> Copy
                                            </button>
                                            <?php else: ?>
                                            N/A
                                            <?php endif; ?>
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
        $('#transactionsTable').DataTable({
            "order": [[0, "desc"]],
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Disable ordering on Copy button column
            ]
        });

        $('.copy-btn').click(function() {
            var link = $(this).data('link');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(link).then(function() {
                    alert('Marketer link copied to clipboard!');
                }, function() {
                    alert('Failed to copy the link.');
                });
            } else {
                // Fallback for older browsers
                var tempInput = $("<input>");
                $("body").append(tempInput);
                tempInput.val(link).select();
                document.execCommand("copy");
                tempInput.remove();
                alert('Marketer link copied to clipboard!');
            }
        });
    });
    </script>
</body>

</html>
