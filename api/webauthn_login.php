<?php
session_start();
require_once '../config/db.php';

// Require library
require_once '../includes/webauthn_lib/WebAuthn-master/src/WebAuthn.php';
$WebAuthn = new \lbuchs\WebAuthn\WebAuthn('StanDigital', 'localhost', ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'none', 'packed', 'tpm']);

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
        false, // allow un-registered
        true, // user verification
        true, // user presence
        false // require user handle
    );
    
    $_SESSION['webauthn_challenge'] = $WebAuthn->getChallenge();
    
    header('Content-Type: application/json');
    echo json_encode($getArgs);
    exit();
}

if ($action === 'process') {
    $clientDataJSON = base64_decode($_POST['clientDataJSON'] ?? '');
    $authenticatorData = base64_decode($_POST['authenticatorData'] ?? '');
    $signature = base64_decode($_POST['signature'] ?? '');
    $userHandle = isset($_POST['userHandle']) ? base64_decode($_POST['userHandle']) : null;
    $id = base64_decode($_POST['id'] ?? '');
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
        $WebAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $cred['public_key'], $challenge, null, true, true);
        
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
    } catch (Exception $ex) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => $ex->getMessage()]);
    }
    exit();
}
