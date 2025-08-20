<?php
session_start();
// error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])==0)
	{	
header('location:index');
}
else{
$userEmail = $_SESSION['alogin'];
$sql = "SELECT base_link, public_link, username FROM admin WHERE email = :email LIMIT 1";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_OBJ);
$public_link = $admin->public_link;

   
// Store success data in variables instead of echoing script immediately
$show_success_modal = false;
$generated_link = '';

if (isset($_POST['generate'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (!empty($name) && !empty($email)) {
        // Generate unique public_id (hash name + timestamp + random)
        $public_id = md5(uniqid(mt_rand(), true));

        $sql = "INSERT INTO marketers (name, public_id, email) VALUES (:name, :public_id, :email)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->bindParam(':public_id', $public_id, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();

        $lastInsertId = $dbh->lastInsertId();

        if ($lastInsertId) {
            // $generated_link = urlencode($public_link)."/marketer?marketer_id=" . urlencode($public_id) . "&trxid=XXX&traffic_source=XXX";
            $generated_link = rtrim($public_link, '/') . "/market?marketer_id=" . urlencode($public_id) . "&trxid=xxx&trfsrc=xxx";
            $show_success_modal = true;
        } else {
            echo "<script>alert('Error creating marketer');</script>";
        }
    } else {
        echo "<script>alert('Please fill all fields');</script>";
    }
}

	?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="au theme template">
    <meta name="author" content="Hau Nguyen">
    <meta name="keywords" content="au theme template">

    <!-- Title Page-->
    <title>Create Marketer</title>

    <!-- Fontfaces CSS-->
    <link href="css/font-face.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <link href="vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">

    <!-- Bootstrap CSS-->
    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all">

    <!-- Vendor CSS-->
    <link href="vendor/animsition/animsition.min.css" rel="stylesheet" media="all">
    <link href="vendor/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet" media="all">
    <link href="vendor/wow/animate.css" rel="stylesheet" media="all">
    <link href="vendor/css-hamburgers/hamburgers.min.css" rel="stylesheet" media="all">
    <link href="vendor/slick/slick.css" rel="stylesheet" media="all">
    <link href="vendor/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="vendor/perfect-scrollbar/perfect-scrollbar.css" rel="stylesheet" media="all">

    <!-- Main CSS-->
    <link href="css/theme.css" rel="stylesheet" media="all">

    <!-- Beautiful Modal CSS -->
    <style>
        .success-modal .modal-dialog {
            max-width: 500px;
            margin: 10% auto;
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-modal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .success-modal .modal-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 30px 30px 20px;
            text-align: center;
            position: relative;
        }

        .success-modal .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            animation: checkmark 0.6s ease-in-out 0.3s both;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-modal .success-icon i {
            font-size: 36px;
            color: white;
        }

        .success-modal .modal-title {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .success-modal .close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: white;
            opacity: 0.8;
            font-size: 24px;
            transition: opacity 0.3s;
        }

        .success-modal .close:hover {
            opacity: 1;
            color: white;
        }

        .success-modal .modal-body {
            padding: 30px;
            text-align: center;
        }

        .success-modal .success-message {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .success-modal .link-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }

        .success-modal .link-container:hover {
            border-color: #28a745;
            background: #f1f8f4;
        }

        .success-modal .link-label {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            display: block;
        }

        .success-modal .generated-link {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #007bff;
            word-break: break-all;
            line-height: 1.4;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .success-modal .copy-btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .success-modal .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
            background: linear-gradient(135deg, #0056b3, #004085);
        }

        .success-modal .copy-btn:active {
            transform: translateY(0);
        }

        .success-modal .copy-btn.copied {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .success-modal .copy-feedback {
            font-size: 14px;
            color: #28a745;
            font-weight: 600;
            margin-top: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .success-modal .copy-feedback.show {
            opacity: 1;
        }

        .success-modal .modal-footer {
            border: none;
            padding: 0 30px 30px;
            text-align: center;
        }

        .success-modal .done-btn {
            background: #6c757d;
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .success-modal .done-btn:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
    </style>

</head>

<body class="animsition">
    <div class="page-wrapper">
        <!-- HEADER MOBILE-->

        <!-- END HEADER MOBILE-->

        <!-- MENU SIDEBAR-->
    <?php include 'includes/sidebar.php' ?>

        <!-- END MENU SIDEBAR-->

        <!-- PAGE CONTAINER-->
        <div class="page-container">
            <!-- HEADER DESKTOP-->
    <?php include 'includes/header.php' ?>
          
            <!-- HEADER DESKTOP-->

            <!-- MAIN CONTENT-->
            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">Create Marketer</div>
                                    <div class="card-body">
                                        <div class="card-title">
                                            <h3 class="text-center title-2">Marketer</h3>
                                        </div>
                                        <hr>
                                        <form  method="post">
                   
                                            <div class="form-group has-success">
                                                <label for="cc-name" class="control-label mb-1">Marketer Name</label>
                                                <input id="cc-name" name="name" type="text" class="form-control cc-name valid" data-val="true" data-val-required="Please enter the marketer name"
                                                    autocomplete="cc-name" aria-required="true" aria-invalid="false" aria-describedby="cc-name-error">
                                                <span class="help-block field-validation-valid" data-valmsg-for="cc-name" data-valmsg-replace="true"></span>
                                            </div>
                                            <div class="form-group">
                                                <label for="cc-number" class="control-label mb-1">Email</label>
                                                <input id="cc-number" name="email" type="email" class="form-control cc-number identified visa" value="" data-val="true"
                                                    data-val-required="Please marketer email" data-val-cc-number="Please enter a valid card number"
                                                    autocomplete="cc-number">
                                                <span class="help-block" data-valmsg-for="cc-number" data-valmsg-replace="true"></span>
                                            </div>
 
                                            <div>
                                                <button id="payment-button" name="generate" type="submit" class="btn btn-lg btn-info btn-block">
                                                    <i class="fa fa-link fa-lg"></i>&nbsp;
                                                    <span id="payment-button-amount">Generate Link</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>


                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Beautiful Success Modal -->
    <div class="modal fade success-modal" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div class="success-icon">
                        <i class="fa fa-check"></i>
                    </div>
                    <h5 class="modal-title" id="successModalLabel">Success!</h5>
                </div>
                <div class="modal-body">
                    <div class="success-message">
                        🎉 Marketer has been created successfully!<br>
                        Your unique referral link is ready to use.
                    </div>
                    
                    <div class="link-container">
                        <span class="link-label">
                            <i class="fa fa-link"></i> Generated Link
                        </span>
                        <div class="generated-link" id="generatedLinkText"></div>
                        <button class="btn copy-btn" id="copyBtn" onclick="copyToClipboard()">
                            <i class="fa fa-copy"></i> Copy Link
                        </button>
                        <div class="copy-feedback" id="copyFeedback">
                            <i class="fa fa-check-circle"></i> Link copied to clipboard!
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn done-btn" data-dismiss="modal">
                        <i class="fa fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Jquery JS-->
    <script src="vendor/jquery-3.2.1.min.js"></script>
    <!-- Bootstrap JS-->
    <script src="vendor/bootstrap-4.1/popper.min.js"></script>
    <script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
    <!-- Vendor JS       -->
    <script src="vendor/slick/slick.min.js">
    </script>
    <script src="vendor/wow/wow.min.js"></script>
    <script src="vendor/animsition/animsition.min.js"></script>
    <script src="vendor/bootstrap-progressbar/bootstrap-progressbar.min.js">
    </script>
    <script src="vendor/counter-up/jquery.waypoints.min.js"></script>
    <script src="vendor/counter-up/jquery.counterup.min.js">
    </script>
    <script src="vendor/circle-progress/circle-progress.min.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="vendor/chartjs/Chart.bundle.min.js"></script>
    <script src="vendor/select2/select2.min.js">
    </script>

    <!-- Main JS-->
    <script src="js/main.js"></script>

    <!-- Enhanced Modal Script -->
    <?php if ($show_success_modal): ?>
    <script>
        let currentLink = '<?php echo addslashes($generated_link); ?>';
        
        $(document).ready(function(){
            $('#generatedLinkText').text(currentLink);
            $('#successModal').modal('show');
            resetCopyButton();
        });
        
        function copyToClipboard() {
            if (!currentLink) return;
            
            // Modern copy method with fallback
            if (navigator.clipboard && window.isSecureContext) {
                // Use the Clipboard API if available
                navigator.clipboard.writeText(currentLink).then(function() {
                    updateCopyButton();
                }, function(err) {
                    // Fallback to older method
                    fallbackCopyTextToClipboard(currentLink);
                });
            } else {
                // Fallback for older browsers
                fallbackCopyTextToClipboard(currentLink);
            }
        }
        
        function fallbackCopyTextToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-999999px';
            textarea.style.top = '-999999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            
            try {
                document.execCommand('copy');
                updateCopyButton();
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }
            
            document.body.removeChild(textarea);
        }
        
        function updateCopyButton() {
            const copyBtn = document.getElementById('copyBtn');
            const copyFeedback = document.getElementById('copyFeedback');
            
            copyBtn.innerHTML = '<i class="fa fa-check"></i> Copied!';
            copyBtn.classList.add('copied');
            copyFeedback.classList.add('show');
            
            // Reset after 3 seconds
            setTimeout(() => {
                resetCopyButton();
            }, 3000);
        }
        
        function resetCopyButton() {
            const copyBtn = document.getElementById('copyBtn');
            const copyFeedback = document.getElementById('copyFeedback');
            
            if (copyBtn && copyFeedback) {
                copyBtn.innerHTML = '<i class="fa fa-copy"></i> Copy Link';
                copyBtn.classList.remove('copied');
                copyFeedback.classList.remove('show');
            }
        }
        
        // Reset copy button when modal is closed
        $('#successModal').on('hidden.bs.modal', function () {
            resetCopyButton();
        });
    </script>
    <?php endif; ?>

</body>

</html>
<!-- end document-->
<?php } ?>