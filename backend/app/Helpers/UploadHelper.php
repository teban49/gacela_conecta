<?php

namespace App\Helpers;

class UploadHelper
{
    public const IMAGE_TYPES = ['png', 'jpg', 'webp'];
    public const PDF_TYPES = ['pdf'];

    public static function detectType(string $path): ?string
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }
        $signature = (string) fread($handle, 12);
        fclose($handle);

        return match (true) {
            str_starts_with($signature, '%PDF-') => 'pdf',
            str_starts_with($signature, "\x89PNG\r\n\x1a\n") => 'png',
            str_starts_with($signature, "\xFF\xD8\xFF") => 'jpg',
            str_starts_with($signature, 'RIFF') && substr($signature, 8, 4) === 'WEBP' => 'webp',
            default => null,
        };
    }

    /**
     * Validates an uploaded file by content signature and moves it to $directory.
     * Returns ['path' => absolute path, 'type' => ext] or ['error' => message].
     */
    public static function store(?array $file, string $directory, array $allowedTypes, int $maxBytes, ?string $storedName = null): ?array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return ['error' => 'No fue posible recibir el archivo.'];
        }
        if (($file['size'] ?? 0) < 1 || $file['size'] > $maxBytes) {
            return ['error' => 'El archivo debe pesar menos de ' . (int) ($maxBytes / 1024 / 1024) . ' MB.'];
        }

        $type = self::detectType($file['tmp_name']);
        if ($type === null || !in_array($type, $allowedTypes, true)) {
            return ['error' => 'Formato no permitido. Formatos válidos: ' . strtoupper(implode(', ', $allowedTypes)) . '.'];
        }

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            return ['error' => 'No fue posible preparar el almacenamiento de archivos.'];
        }

        $name = $storedName ?? (bin2hex(random_bytes(16)) . '.' . $type);
        $path = $directory . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ['error' => 'No fue posible guardar el archivo.'];
        }

        return ['path' => $path, 'name' => $name, 'type' => $type];
    }
}
