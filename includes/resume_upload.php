<?php
/**
 * Handles a resume file upload from $_FILES[$field_name].
 * Returns the new relative path (e.g. "uploads/resumes/xxx.pdf") on success,
 * null if no file was uploaded (not an error),
 * or throws via returning ['error' => '...'] on failure.
 *
 * Usage:
 *   $result = handle_resume_upload('resume');
 *   if (isset($result['error'])) { $error = $result['error']; }
 *   elseif ($result['path']) { $resume_path = $result['path']; }
 */
function handle_resume_upload($field_name, $upload_dir = null) {
    if (empty($_FILES[$field_name]['name'])) {
        return ['path' => null, 'error' => null]; // no file selected
    }

    $file = $_FILES[$field_name];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Error uploading file.'];
    }

    // Validate file type by extension + mime
    $allowed_ext = ['pdf', 'doc', 'docx'];
    $allowed_mime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($ext, $allowed_ext) || !in_array($mime, $allowed_mime)) {
        return ['path' => null, 'error' => 'Resume must be a PDF, DOC, or DOCX file.'];
    }

    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['path' => null, 'error' => 'Resume file must be under 5MB.'];
    }

    // Resolve upload directory (uploads/resumes/ from project root)
    if ($upload_dir === null) {
        $upload_dir = dirname(__DIR__) . '/uploads/resumes';
    }
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $unique_name = 'resume_' . uniqid() . '_' . time() . '.' . $ext;
    $destination = $upload_dir . '/' . $unique_name;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['path' => null, 'error' => 'Failed to save uploaded file.'];
    }

    return ['path' => 'uploads/resumes/' . $unique_name, 'error' => null];
}