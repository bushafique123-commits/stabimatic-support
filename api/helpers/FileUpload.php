<?php

final class FileUpload
{
    public static function validateImage(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'Image upload failed'];
        }

        if (($file['size'] ?? 0) > (int) config('upload.max_bytes')) {
            return ['valid' => false, 'message' => 'Image exceeds maximum size'];
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($extension, config('upload.allowed_extensions', []), true)) {
            return ['valid' => false, 'message' => 'Image extension is not allowed'];
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, config('upload.allowed_mimes', []), true)) {
            return ['valid' => false, 'message' => 'Image type is not allowed'];
        }

        if (@getimagesize($file['tmp_name']) === false) {
            return ['valid' => false, 'message' => 'Uploaded file is not a valid image'];
        }

        return ['valid' => true, 'message' => 'OK'];
    }

    public static function storeImage(array $file): string
    {
        $validation = self::validateImage($file);
        if (!$validation['valid']) {
            throw new RuntimeException($validation['message']);
        }

        $relativePath = trim(config('upload.path'), '/');
        $directory = dirname(__DIR__) . '/' . $relativePath;
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Unable to store uploaded image');
        }

        return $relativePath . '/' . $filename;
    }

    public static function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $path = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
        $base = realpath(dirname(__DIR__) . '/' . trim(config('upload.path'), '/'));
        $target = realpath($path);

        if ($base && $target && strpos($target, $base) === 0 && is_file($target)) {
            unlink($target);
        }
    }
}
