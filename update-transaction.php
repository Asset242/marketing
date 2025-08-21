<?php
// webhook.php

header('Content-Type: application/json');

// Include DB connection
include('includes/config.php'); // Assumes $dbh is your PDO connection

try {
    // Get raw JSON input
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    // Check if JSON decoding failed
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Invalid JSON payload"
        ]);
        exit();
    }

    // Required fields
    if (empty($data['msisdn'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Missing required parameters"
        ]);
        exit();
    }

    $msisdn = trim($data['msisdn']);

    // Update only the latest transaction based on created_at
    $sql = "UPDATE transaction 
            SET status = 1 
            WHERE id = (
                SELECT id FROM (
                    SELECT id FROM transaction 
                    WHERE msisdn = :msisdn 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ) t
            )";

    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':msisdn', $msisdn, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Successfully updated
        echo json_encode([
            "status" => "success",
            "message" => "Latest transaction updated successfully"
        ]);
    } else {
        // No matching record
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Transaction not found"
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
