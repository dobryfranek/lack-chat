<?php

session_start();

header("Content-Type: application/json");

define("MESSAGES_FILE", "messages.db");

function encrypt_message($message, $password) {
    $key = hash("sha256", $password, true);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($message, "AES-256-CBC", $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decrypt_message($message, $password) {
    $key = hash("sha256", $password, true);
    $data = base64_decode($message);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    $result = openssl_decrypt($encrypted, "AES-256-CBC", $key, 0, $iv);
    if ($result == false) {
        return "errorerrorerror618734067";
    } else {
        return $result;
    }
}

$identity = isset($_POST["identity"]) ? $_POST["identity"] : null;
$message = isset($_POST["message"]) ? $_POST["message"] : null;
$password = isset($_POST["password"]) ? $_POST["password"] : null;
$last_id = isset($_POST["last_id"]) ? intval($_POST["last_id"]) : 0;
$asks_for_identity = isset($_POST["ask_for_identity"]) ? true : false;

if ($identity) {
    $_SESSION["identity"] = $identity;
}

if ($asks_for_identity) {
    if (isset($_SESSION["identity"])) {
        echo(json_encode(["identity" => $_SESSION["identity"]]));
        die();
    } else {
        echo(json_encode(["error" => "no identity"]));
        die();
    }
}

$db = new PDO('sqlite:' . MESSAGES_FILE);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identity TEXT NOT NULL,
    message TEXT NOT NULL,
    timestamp INTEGER NOT NULL
)");

if ($_SESSION["identity"] && $message && $password) {
    $encrypted = encrypt_message($message, $password);


    $stmt = $db->prepare("INSERT INTO messages (identity, message, timestamp) VALUES (:identity, :message, :timestamp)");
    $stmt->execute([
        ':identity' => $_SESSION["identity"],
        ':message' => $encrypted,
        ':timestamp' => time()
    ]);

    echo json_encode(["status" => "ok"]);
    exit;
}

if (!$message && $password) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE id > :last_id ORDER BY id ASC");
    $stmt->execute([':last_id' => $last_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['message'] = decrypt_message($row['message'], $password);
    }

    echo json_encode($rows, JSON_PRETTY_PRINT);
    exit;
}
?>
