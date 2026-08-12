<?php
// Require library FIRST so session can unserialize ByteBuffer objects
require_once '../includes/webauthn_lib/WebAuthn-master/src/WebAuthn.php';
session_start();
require_once '../config/db.php';

$rpId = $_SERVER['HTTP_HOST'];
$WebAuthn = new \lbuchs\WebAuthn\WebAuthn('StanDigital', $rpId, ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'none', 'packed', 'tpm'], true);

$action = $_GET['action'] ?? '';

if ($action === 'getArgs') {
    // Get all credential ids
    $stmt = $conn->query("SELECT credential_id FROM webauthn_credentials");
    $credentials = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $allowedCredentials = [];
    foreach ($credentials as $credId) {
        $allowedCredentials[] = base64_decode($credId);
    }
    
    // Generate getArgs
    $getArgs = $WebAuthn->getGetArgs(
        $allowedCredentials, // allowed credentials
        20, // timeout
        true, // allowUsb
        true, // allowNfc
        true, // allowBle
        true, // allowHybrid
        true, // allowInternal
        'preferred' // requireUserVerification
    );
    
    $_SESSION['webauthn_challenge'] = $WebAuthn->getChallenge();
    
    header('Content-Type: application/json');
    echo json_encode($getArgs);
    exit();
}

if ($action === 'process') {
    $clientDataJSON = base64_decode(strtr($_POST['clientDataJSON'] ?? '', '-_', '+/'));
    $authenticatorData = base64_decode(strtr($_POST['authenticatorData'] ?? '', '-_', '+/'));
    $signature = base64_decode(strtr($_POST['signature'] ?? '', '-_', '+/'));
    $userHandle = isset($_POST['userHandle']) ? base64_decode(strtr($_POST['userHandle'], '-_', '+/')) : null;
    $id = base64_decode(strtr($_POST['id'] ?? '', '-_', '+/'));
    $challenge = $_SESSION['webauthn_challenge'] ?? '';
    
    $credIdBase64 = base64_encode($id);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM webauthn_credentials WHERE credential_id = ?");
        $stmt->execute([$credIdBase64]);
        $cred = $stmt->fetch();
        
        if (!$cred) {
            throw new Exception("Credential not found");
        }
        
        // processGet
        $WebAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $cred['public_key'], $challenge, null, false, true);
        
        // Log user in
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$cred['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['last_activity'] = time();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("User not found");
        }
    } catch (\Throwable $ex) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => $ex->getMessage() . ' in ' . basename($ex->getFile()) . ':' . $ex->getLine()]);
    }
    exit();
}
