<?php

session_start();
include('includes/config.php'); // your PDO $dbh connection

// Get GET params
$marketer_id = isset($_GET['marketer_id']) ? trim($_GET['marketer_id']) : null;
$txref = isset($_GET['trxid']) ? trim($_GET['trxid']) : null;
$trfsrc = isset($_GET['trfsrc']) ? trim($_GET['trfsrc']) : null;

// Get msisdn from request header (assuming header name is 'Msisdn')
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$msisdn = isset($headers['msisdn']) ? trim($headers['msisdn']) : null;
// $msisdn = 2348033705129;
// $msisdn = 2348033705120;

// Check if request is HTTPS and redirect to HTTP
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $redirect_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirect_url", true, 301);
    exit();
}

// Alternative check for HTTPS (in case of load balancer)
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $redirect_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirect_url", true, 301);
    exit();
}

// Get base link from DB
$sql = "SELECT base_link, check_link, username FROM admin LIMIT 1";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_OBJ);
$base_link = $admin->base_link;
$checkLinkUrl = $admin->check_link;

// Simple validation
if (!$marketer_id || !$txref || !$trfsrc) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script>
        // Force HTTP redirect if HTTPS is detected (client-side fallback)
        if (location.protocol === 'https:') {
            location.replace('http:' + window.location.href.substring(window.location.protocol.length));
        }
        </script>
    </head>
    <body>
        <h2>Error: Missing required parameters.</h2>
    </body>
    </html>
    <?php
    http_response_code(400);
    exit();
}

try {
    $status_pending = 0;
    $sql = "INSERT INTO transaction (marketer_id, trx_id, source, msisdn, status) 
            VALUES (:marketer_id, :trx_id, :source, :msisdn, :status)";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':marketer_id', $marketer_id, PDO::PARAM_STR);
    $stmt->bindParam(':trx_id', $txref, PDO::PARAM_STR);
    $stmt->bindParam(':source', $trfsrc, PDO::PARAM_STR);
    $stmt->bindParam(':msisdn', $msisdn, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status_pending, PDO::PARAM_INT);
    $stmt->execute();
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script>
        // Force HTTP redirect if HTTPS is detected (client-side fallback)
        if (location.protocol === 'https:') {
            location.replace('http:' + window.location.href.substring(window.location.protocol.length));
        }
        </script>
    </head>
    <body>
        <h2>Database error occurred.</h2>
    </body>
    </html>
    <?php
    http_response_code(500);
    exit();
}

// Call the check_link endpoint
$postData = [
    "action" => "OER",
    "msisdn" => $msisdn
];

$ch = curl_init($checkLinkUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/x-www-form-urlencoded",
    "Accept: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

// Decode the JSON response
$responseData = json_decode($response, true);

// If decoding failed or status key missing
if (!is_array($responseData) || !isset($responseData['status'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script>
        // Force HTTP redirect if HTTPS is detected (client-side fallback)
        if (location.protocol === 'https:') {
            location.replace('http:' + window.location.href.substring(window.location.protocol.length));
        }
        </script>
    </head>
    <body>
        <h2>Invalid response from check_link endpoint.</h2>
    </body>
    </html>
    <?php
    http_response_code(500);
    exit();
}

$statusKey = (int)$responseData['status']; // convert to int just in case

// If 200, user already exists → stop and show message
if ($statusKey === 200) {
    try {
        $sql = "UPDATE transaction 
                SET status = 2 -- or whatever you use for 'failed'
                WHERE trx_id = :trx_id AND msisdn = :msisdn";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':trx_id', $txref, PDO::PARAM_STR);
        $stmt->bindParam(':msisdn', $msisdn, PDO::PARAM_STR);
        $stmt->execute();
    } catch (Exception $e) {
        // log error if needed
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script>
        // Force HTTP redirect if HTTPS is detected (client-side fallback)
        if (location.protocol === 'https:') {
            location.replace('http:' + window.location.href.substring(window.location.protocol.length));
        }
        </script>
    </head>
    <body>
        <h2>An Error Occurred</h2>
    </body>
    </html>
    <?php
    http_response_code(403);
    exit();
}

// If 300 or 301, proceed to insert into DB and redirect
if ($statusKey === 300 || $statusKey === 301) {
    $redirect_url = rtrim($base_link, '/') . "&trxid=" . urlencode($txref) . "&trfsrc=" . urlencode($trfsrc);
    header("Location: $redirect_url");
    exit();
}

// If other status, return error
?>
<!DOCTYPE html>
<html>
<head>
    <script>
    // Force HTTP redirect if HTTPS is detected (client-side fallback)
    if (location.protocol === 'https:') {
        location.replace('http:' + window.location.href.substring(window.location.protocol.length));
    }
    </script>
</head>
<body>
    <h2>Unexpected status code: <?php echo htmlspecialchars($statusKey); ?></h2>
</body>
</html>
<?php
http_response_code(400);
exit();