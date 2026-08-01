<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include __DIR__ . "/../config/database.php";

$user_id   = intval($_POST['user_id']   ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email']     ?? '');
$password  = trim($_POST['password']  ?? '');

if ($user_id <= 0 || $full_name === '' || $email === '') {
    echo json_encode([
        "success" => false,
        "message" => "User ID, nama, dan email wajib diisi",
    ]);
    exit;
}

// Check if user exists
$check = $conn->prepare("SELECT id FROM users WHERE id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User tidak ditemukan"]);
    exit;
}

// Check email duplicate (exclude current user)
$dupEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
$dupEmail->bind_param("si", $email, $user_id);
$dupEmail->execute();
$dupEmail->store_result();

if ($dupEmail->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Email sudah digunakan oleh user lain",
    ]);
    exit;
}

// Update
if ($password !== '') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?"
    );
    $stmt->bind_param("sssi", $full_name, $email, $hash, $user_id);
} else {
    $stmt = $conn->prepare(
        "UPDATE users SET full_name = ?, email = ? WHERE id = ?"
    );
    $stmt->bind_param("ssi", $full_name, $email, $user_id);
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Profil berhasil diperbarui",
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal memperbarui profil: " . $conn->error,
    ]);
}
