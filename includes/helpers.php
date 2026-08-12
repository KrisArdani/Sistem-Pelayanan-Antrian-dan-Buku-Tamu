<?php
// SPST BPS Kota Tegal - Helper & Utility Functions (DRY Principles)

/**
 * Validasi NIK (Nomor Induk Kependudukan - Wajib 16 digit angka)
 * 
 * @param string $nik
 * @return bool
 */
function validateNIK($nik) {
    $nik = trim((string)$nik);
    return preg_match('/^[0-9]{16}$/', $nik) === 1;
}

function validateNIKField($nik) {
    return validateNIK($nik);
}

/**
 * Validasi Panjang Password Minimal
 * 
 * @param string $password
 * @param int $minLen
 * @return bool
 */
function validatePasswordLength($password, $minLen = 6) {
    return strlen((string)$password) >= $minLen;
}

/**
 * Validasi Field Wajib Ada & Tidak Kosong
 * 
 * @param array $requiredFields Array nama field yang wajib
 * @param array $inputData Data input ($_POST, JSON body, dll)
 * @return array Array nama field yang belum diisi (kosong jika semua valid)
 */
function validateRequiredFields($requiredFields, $inputData) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($inputData[$field]) || trim((string)$inputData[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

function validateRequired($requiredFields, $inputData) {
    return validateRequiredFields($requiredFields, $inputData);
}

/**
 * Dapatkan Tanggal Hari Ini (Format Y-m-d)
 * 
 * @return string
 */
function getTodayDate() {
    return date('Y-m-d');
}

/**
 * Dapatkan User ID dari Sesi Aktif
 * 
 * @return int
 */
function getCurrentUserId() {
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Dapatkan User Role dari Sesi Aktif
 * 
 * @return string
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? 'guest';
}
