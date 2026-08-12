<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Check if logged in for registration
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$user = getCurrentUser();
if (!$user) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

// Require library
require_once '../includes/webauthn_lib/WebAuthn-master/src/WebAuthn.php';
$rpId = $_SERVER['HTTP_HOST'];
$WebAuthn = new \lbuchs\WebAuthn\WebAuthn('StanDigital', $rpId, ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'none', 'packed', 'tpm'], true);

$action = $_GET['action'] ?? '';

if ($action === 'getArgs') {
    // Generate createArgs
    $createArgs = $WebAuthn->getCreateArgs(
        $user['id'], // user id
        $user['username'], // user name
        $user['username'], // user display name
        20, // timeout
        false, // requireResidentKey
        \lbuchs\WebAuthn\WebAuthn::REQUIRE_USER_VERIFICATION_PREFERRED
    );
    
    $_SESSION['webauthn_challenge'] = $WebAuthn->getChallenge();
    
    header('Content-Type: application/json');
    echo json_encode($createArgs);
    exit();
}

if ($action === 'process') {
    $clientDataJSON = base64_decode($_POST['clientDataJSON'] ?? '');
    $attestationObject = base64_decode($_POST['attestationObject'] ?? '');
    $challenge = $_SESSION['webauthn_challenge'] ?? '';
    
    try {
        $data = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, \lbuchs\WebAuthn\WebAuthn::REQUIRE_USER_VERIFICATION_PREFERRED, true, false);
        
        $credentialId = base64_encode($data->credentialId);
        $publicKey = $data->credentialPublicKey;
        
        $stmt = $conn->prepare("INSERT INTO webauthn_credentials (user_id, credential_id, public_key) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $credentialId, $publicKey]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (Exception $ex) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => $ex->getMessage()]);
    }
    exit();
}
