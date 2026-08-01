<?php

header("Content-Type: application/json");

include __DIR__ . "/../config/database.php";

$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if ($full_name == '' || $email == '' || $password == '') {
    echo json_encode([
        "success" => false,
        "message" => "Semua field wajib diisi"
    ]);
    exit;
}

// Cek email
$check = $conn->prepare("SELECT id FROM users WHERE email=?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Email sudah digunakan"
    ]);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users(full_name,email,password) VALUES(?,?,?)");
$stmt->bind_param("sss", $full_name, $email, $hash);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Register berhasil"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Register gagal"
    ]);
}