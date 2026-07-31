<?php

header("Content-Type: application/json");

include "config/database.php";

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if ($email == '' || $password == '') {
    echo json_encode([
        "success" => false,
        "message" => "Email dan Password wajib diisi"
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, email, password FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Email tidak ditemukan"
    ]);
    exit;
}

$user = $result->fetch_assoc();

if (password_verify($password, $user['password'])) {

    unset($user['password']);

    echo json_encode([
        "success" => true,
        "message" => "Login berhasil",
        "user" => $user
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Password salah"
    ]);

}