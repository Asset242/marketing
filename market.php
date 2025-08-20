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


// Get base link from DB
$sql = "SELECT base_link, check_link, username FROM admin LIMIT 1";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_OBJ);
$base_link = $admin->base_link;
$checkLinkUrl = $admin->check_link;


// Simple validation
if (!$marketer_id || !$txref || !$trfsrc) {
    http_response_code(400);
    echo "Error: Missing required parameters.";
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
        http_response_code(500);
        echo "Database error: " . $e->getMessage();
        exit();
    }

// Call the check_link endpoint
// $checkLinkUrl = "https://your-api-domain.com/check_link"; 
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
    http_response_code(500);
    echo "Invalid response from check_link endpoint.";
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
    http_response_code(403);
    echo "<h2>An Error Occured</h2>";
    exit();
}

// If 300 or 301, proceed to insert into DB and redirect
if ($statusKey === 300 || $statusKey === 301) {
    $redirect_url = rtrim($base_link, '/') . "&trxid=" . urlencode($txref) . "&trfsrc=" . urlencode($trfsrc);
    header("Location: $redirect_url");
    exit();
}

// If other status, return error
http_response_code(400);
echo "Unexpected status code: {$statusKey}";
exit();

