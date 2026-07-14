<?php
// ============================================================
// ObiFunds – includes/iotec_functions.php
// ioTec Pay helper functions
// Docs: https://iotec.io/api-docs/pay
// ============================================================

require_once __DIR__ . '/iotec_config.php';

/**
 * Get OAuth2 access token from ioTec identity server.
 * Token URL: https://id.iotec.io/connect/token
 * Token expires in 300 seconds (5 min).
 *
 * @return string|null  Access token or null on failure
 */
function getIotecAccessToken(): ?string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => IOTEC_AUTH_URL,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => IOTEC_CLIENT_ID,
            'client_secret' => IOTEC_CLIENT_SECRET,
            'grant_type'    => IOTEC_GRANT_TYPE,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log('ioTec cURL error (token): ' . $curlErr);
        return null;
    }

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    error_log("ioTec Token Error (HTTP $httpCode): $response");
    return null;
}

/**
 * Initiate a mobile money collection (charge a customer).
 * Endpoint: POST https://pay.iotec.io/api/collections/collect
 *
 * Sandbox test numbers:
 *   011177777x → Success
 *   011177799x → Failed
 *   011177778x → Pending
 *   011177779x → SentToVendor
 *
 * @param int|string $donation_id  Your internal donation ID (used as externalId)
 * @param float      $amount       Amount in UGX (min 500)
 * @param string     $phone        Payer's MSISDN e.g. 0711234567 or 256711234567
 * @param string     $donor_name   Payer's display name
 * @param string     $description  Short note for the payer
 *
 * @return array  ['success'=>bool, 'request_id'=>string|null, 'message'=>string|null, 'raw'=>array]
 */
function initiateIotecPayment(
    $donation_id,
    float $amount,
    string $phone,
    string $donor_name = '',
    string $description = 'Donation'
): array {
    $token = getIotecAccessToken();
    if (!$token) {
        return ['success' => false, 'message' => 'Failed to authenticate with ioTec.'];
    }

    $walletId  = IOTEC_SANDBOX ? IOTEC_TEST_WALLET_ID : IOTEC_LIVE_WALLET_ID;
    $externalId = 'DON-' . $donation_id . '-' . time();

    $payload = [
        'category'                   => 'MobileMoney',
        'currency'                   => IOTEC_CURRENCY,
        'walletId'                   => $walletId,
        'externalId'                 => $externalId,
        'payer'                      => normalisePhone($phone),
        'payerName'                  => $donor_name ?: 'Donor',
        'payerNote'                  => $description,
        'payeeNote'                  => 'ObiFunds donation #' . $donation_id,
        'amount'                     => (float)$amount,
        'transactionChargesCategory' => 'ChargeWallet',  // platform absorbs charges
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => IOTEC_PAY_BASE . '/api/collections/collect',
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log('ioTec cURL error (collect): ' . $curlErr);
        return ['success' => false, 'message' => 'Network error contacting ioTec.'];
    }

    $data = json_decode($response, true) ?? [];

    if ($httpCode === 200 || $httpCode === 201) {
        return [
            'success'    => true,
            'request_id' => $data['id'] ?? null,   // UUID — store this to check status later
            'status'     => $data['status'] ?? 'Pending',
            'raw'        => $data,
        ];
    }

    error_log("ioTec Collect Error (HTTP $httpCode): $response");
    return [
        'success' => false,
        'message' => $data['message'] ?? $data['title'] ?? "Payment initiation failed (HTTP $httpCode).",
        'raw'     => $data,
    ];
}

/**
 * Check the status of a collection by its ioTec request ID.
 * Endpoint: GET https://pay.iotec.io/api/collections/status/{requestId}
 *
 * @param string $requestId  The `id` returned when the collection was initiated
 * @return array
 */
function checkIotecStatus(string $requestId): array {
    $token = getIotecAccessToken();
    if (!$token) {
        return ['success' => false, 'message' => 'Failed to authenticate with ioTec.'];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => IOTEC_PAY_BASE . '/api/collections/status/' . urlencode($requestId),
        CURLOPT_HTTPGET        => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true) ?? [];

    return [
        'success'  => ($httpCode === 200),
        'status'   => $data['status'] ?? null,   // 'Success' | 'Failed' | 'Pending' | 'SentToVendor'
        'raw'      => $data,
        'http_code'=> $httpCode,
    ];
}

/**
 * Look up a collection by your own externalId (transaction_reference).
 * Endpoint: GET https://pay.iotec.io/api/collections/external-id/{externalId}
 * Useful as a fallback when iotec_transaction_id was never stored.
 *
 * @param string $externalId  Your reference e.g. DON-123-abc
 * @return array  includes 'id' (ioTec UUID) and 'status'
 */
function checkIotecStatusByExternalId(string $externalId): array {
    $token = getIotecAccessToken();
    if (!$token) {
        return ['success' => false, 'message' => 'Failed to authenticate with ioTec.'];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => IOTEC_PAY_BASE . '/api/collections/external-id/' . urlencode($externalId),
        CURLOPT_HTTPGET        => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true) ?? [];

    // Response may be an array of results — take the most recent
    if (isset($data[0])) $data = $data[0];

    return [
        'success'   => ($httpCode === 200),
        'id'        => $data['id'] ?? null,
        'status'    => $data['status'] ?? null,
        'raw'       => $data,
        'http_code' => $httpCode,
    ];
}

/**
 * Normalise a Ugandan phone number to MSISDN format (256XXXXXXXXX).
 * Accepts: 07XX, 256XX, +256XX
 */
function normalisePhone(string $phone): string {
    $phone = preg_replace('/\D/', '', $phone); // strip non-digits
    if (str_starts_with($phone, '0')) {
        $phone = '256' . substr($phone, 1);
    } elseif (str_starts_with($phone, '+256')) {
        $phone = substr($phone, 1);
    }
    return $phone;
}

/**
 * Poll ioTec for pending donations and update local DB when payment settles.
 * Needed on localhost where IPN callbacks cannot reach the app.
 */
function syncDonationStatusFromIotec(mysqli $conn, int $donation_id): ?string
{
    $res = $conn->query(
        "SELECT donation_id, campaign_id, amount, status, transaction_reference, iotec_transaction_id
         FROM donations WHERE donation_id = $donation_id LIMIT 1"
    );
    $don = $res ? $res->fetch_assoc() : null;
    if (!$don) {
        return null;
    }

    $status = $don['status'];
    if ($status !== 'pending') {
        return $status;
    }

    if (!empty($don['iotec_transaction_id'])) {
        $check = checkIotecStatus($don['iotec_transaction_id']);
    } else {
        $check = checkIotecStatusByExternalId($don['transaction_reference']);
        if ($check['success'] && !empty($check['id'])) {
            $uuid = $conn->real_escape_string($check['id']);
            $conn->query(
                "UPDATE donations SET iotec_transaction_id = '$uuid' WHERE donation_id = $donation_id"
            );
        }
    }

    $iotecStatus = strtolower($check['status'] ?? '');

    if ($iotecStatus === 'success') {
        $conn->query(
            "UPDATE donations SET status = 'completed', payment_date = NOW()
             WHERE donation_id = $donation_id AND status = 'pending'"
        );
        if ($conn->affected_rows > 0) {
            $amount = (float)$don['amount'];
            $campaign_id = (int)$don['campaign_id'];
            $conn->query(
                "UPDATE campaigns
                 SET raised_amount = raised_amount + $amount,
                     contributor_count = contributor_count + 1
                 WHERE campaign_id = $campaign_id"
            );
        }
        return 'completed';
    }

    if ($iotecStatus === 'failed' || $iotecStatus === 'cancelled') {
        $conn->query("UPDATE donations SET status = 'failed' WHERE donation_id = $donation_id");
        return 'failed';
    }

    return 'pending';
}
