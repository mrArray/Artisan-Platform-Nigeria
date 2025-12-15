<?php
/**
 * Document Upload Handler
 * 
 * Handles document uploads for artisan profiles
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Ensure only artisans can access
requireLogin();
requireRole('artisan');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: /artisan/profile.php');
        exit;
    }

    $documentType = trim($_POST['document_type'] ?? '');
    $userId = $_SESSION['user_id'];

    // Validate inputs
    $errors = [];

    if (empty($documentType)) {
        $errors[] = 'Document type is required';
    }

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a valid file to upload';
    }

    // Validate file
    if (empty($errors)) {
        $file = $_FILES['document'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];

        // Check file size (5MB max)
        if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = 'File size must not exceed 5MB';
        }

        // Check file extension
        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG';
        }
    }

    if (empty($errors)) {
        try {
            // Get artisan profile ID
            $stmt = getDB()->prepare('SELECT id FROM artisan_profiles WHERE user_id = ?');
            $stmt->execute([$userId]);
            $artisanProfile = $stmt->fetch();

            if (!$artisanProfile) {
                $_SESSION['error'] = 'Artisan profile not found';
                header('Location: /artisan/profile.php');
                exit;
            }

            $artisanId = $artisanProfile['id'];

            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/documents/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = 'documents/' . $uniqueFileName;
            $fullPath = $uploadDir . $uniqueFileName;

            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $fullPath)) {
                // Insert into database
                $stmt = getDB()->prepare('
                    INSERT INTO documents (artisan_id, document_type, file_name, file_path, file_size)
                    VALUES (?, ?, ?, ?, ?)
                ');

                $stmt->execute([
                    $artisanId,
                    $documentType,
                    $fileName,
                    $filePath,
                    $fileSize
                ]);

                $_SESSION['success'] = 'Document uploaded successfully';
            } else {
                $_SESSION['error'] = 'Failed to upload file. Please try again.';
            }

        } catch (PDOException $e) {
            error_log('Document upload error: ' . $e->getMessage());
            $_SESSION['error'] = 'Database error occurred. Please try again.';
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    header('Location: /artisan/profile.php');
    exit;
}

// If accessed directly via GET, redirect
header('Location: /artisan/profile.php');
exit;
