<?php
// Require library FIRST so session can unserialize ByteBuffer objects
require_once '../includes/webauthn_lib/WebAuthn-master/src/WebAuthn.php';
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
        'preferred' // requireUserVerification
    );
    
    $_SESSION['webauthn_challenge'] = $WebAuthn->getChallenge();
    
    header('Content-Type: application/json');
    echo json_encode($createArgs);
    exit();
}

if ($action === 'process') {
    $clientDataJSON = base64_decode(strtr($_POST['clientDataJSON'] ?? '', '-_', '+/'));
    $attestationObject = base64_decode(strtr($_POST['attestationObject'] ?? '', '-_', '+/'));
    $challenge = $_SESSION['webauthn_challenge'] ?? '';
    
    try {
        $data = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, false, true, false);
        
        $credentialId = base64_encode($data->credentialId);
        $publicKey = $data->credentialPublicKey;
        
        $conn->exec("CREATE TABLE IF NOT EXISTS webauthn_credentials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            credential_id VARCHAR(255) NOT NULL,
            public_key TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        $stmt = $conn->prepare("INSERT INTO webauthn_credentials (user_id, credential_id, public_key) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $credentialId, $publicKey]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (\Throwable $ex) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => $ex->getMessage() . ' in ' . basename($ex->getFile()) . ':' . $ex->getLine()]);
    }
    exit();
}
