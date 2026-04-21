<?php

final class SupabaseStorage
{
    private string $url;
    private string $key;
    private string $bucket;
    private string $folder;
    private bool $allowsLocalFileUploads = false;

    public function __construct(?string $url, ?string $key, ?string $bucket, string $folder = 'avatars')
    {
        $this->url = rtrim((string) $url, '/');
        $this->key = (string) $key;
        $this->bucket = trim((string) $bucket, '/');
        $this->folder = trim($folder, '/');
    }

    public function isConfigured(): bool
    {
        return $this->url !== ''
            && $this->key !== ''
            && $this->bucket !== ''
            && !str_contains($this->url, 'your-project-ref')
            && $this->key !== 'your-service-role-key';
    }

    public function allowLocalFileUploadsForTesting(): void
    {
        $this->allowsLocalFileUploads = PHP_SAPI === 'cli';
    }

    /**
     * @param array<string, mixed> $file
     */
    public function uploadProfileImage(array $file, int $userId): string
    {
        $this->ensureConfigured();
        $this->assertUploadIsValid($file);

        $tmpName = (string) $file['tmp_name'];
        $mimeType = $this->detectMimeType($tmpName);
        $extension = $this->extensionForMimeType($mimeType);
        $objectPath = $this->objectPath($userId, $extension);
        $uploadUrl = $this->url . '/storage/v1/object/' . rawurlencode($this->bucket) . '/' . $this->encodeObjectPath($objectPath);
        $contents = file_get_contents($tmpName);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded profile image.');
        }

        if (function_exists('curl_init')) {
            $this->uploadWithCurl($uploadUrl, $contents, $mimeType);
        } else {
            $this->uploadWithStreamContext($uploadUrl, $contents, $mimeType);
        }

        return $this->url . '/storage/v1/object/public/' . rawurlencode($this->bucket) . '/' . $this->encodeObjectPath($objectPath);
    }

    public function deletePublicObject(string $publicUrl): bool
    {
        $this->ensureConfigured();

        $objectPath = $this->objectPathFromPublicUrl($publicUrl);

        if ($objectPath === null) {
            return false;
        }

        $deleteUrl = $this->url . '/storage/v1/object/' . rawurlencode($this->bucket);
        $payload = json_encode(['prefixes' => [$objectPath]]);

        if (!is_string($payload)) {
            throw new RuntimeException('Unable to prepare Supabase delete request.');
        }

        if (function_exists('curl_init')) {
            $this->deleteWithCurl($deleteUrl, $payload);
        } else {
            $this->deleteWithStreamContext($deleteUrl, $payload);
        }

        return true;
    }

    public function uploadTextObject(string $contents, string $fileBaseName, string $extension, string $mimeType): string
    {
        $this->ensureConfigured();

        $extension = strtolower(trim($extension, '. '));
        $mimeType = trim($mimeType);

        if ($contents === '') {
            throw new RuntimeException('Source file content cannot be empty.');
        }

        if (!in_array($extension, ['html', 'css'], true)) {
            throw new RuntimeException('Unsupported source file extension.');
        }

        if (!in_array($mimeType, ['text/html', 'text/css'], true)) {
            throw new RuntimeException('Unsupported source file type.');
        }

        if (($extension === 'html' && $mimeType !== 'text/html') || ($extension === 'css' && $mimeType !== 'text/css')) {
            throw new RuntimeException('Source file extension and MIME type do not match.');
        }

        $safeBaseName = strtolower(preg_replace('/[^a-z0-9\-]+/i', '-', $fileBaseName) ?? 'challenge-source');
        $safeBaseName = trim($safeBaseName, '-') ?: 'challenge-source';
        $objectPath = $this->folder !== ''
            ? $this->folder . '/' . $safeBaseName . '-' . bin2hex(random_bytes(8)) . '.' . $extension
            : $safeBaseName . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $uploadUrl = $this->url . '/storage/v1/object/' . rawurlencode($this->bucket) . '/' . $this->encodeObjectPath($objectPath);

        if (function_exists('curl_init')) {
            $this->uploadWithCurl($uploadUrl, $contents, $mimeType);
        } else {
            $this->uploadWithStreamContext($uploadUrl, $contents, $mimeType);
        }

        return $this->url . '/storage/v1/object/public/' . rawurlencode($this->bucket) . '/' . $this->encodeObjectPath($objectPath);
    }

    public static function readPublicTextObject(string $publicUrl): string
    {
        $publicUrl = trim($publicUrl);

        if ($publicUrl === '' || filter_var($publicUrl, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Source URL is invalid.');
        }

        if (function_exists('curl_init')) {
            $curl = curl_init($publicUrl);

            if ($curl === false) {
                throw new RuntimeException('Unable to start source file request.');
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_MAXREDIRS => 3,
            ]);

            $response = curl_exec($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if (!is_string($response) || $statusCode < 200 || $statusCode >= 300) {
                throw new RuntimeException('Unable to load source file: ' . ($error !== '' ? $error : 'HTTP ' . $statusCode));
            }

            return $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'ignore_errors' => true,
                'timeout' => 20,
            ],
        ]);
        $response = file_get_contents($publicUrl, false, $context);
        $statusLine = $http_response_header[0] ?? '';

        if (!is_string($response) || !preg_match('/\s2\d\d\s/', $statusLine)) {
            throw new RuntimeException('Unable to load source file: ' . $statusLine);
        }

        return $response;
    }

    private function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Supabase storage is not configured. Set SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, and SUPABASE_STORAGE_BUCKET.');
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    private function assertUploadIsValid(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload a profile image before continuing.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || (!$this->allowsLocalFileUploads && !is_uploaded_file($tmpName))) {
            throw new RuntimeException('Profile image upload could not be verified.');
        }

        $size = (int) ($file['size'] ?? 0);
        $maxSize = 2 * 1024 * 1024;

        if ($size <= 0 || $size > $maxSize) {
            throw new RuntimeException('Profile image must be 2MB or smaller.');
        }

        $this->extensionForMimeType($this->detectMimeType($tmpName));
    }

    private function detectMimeType(string $path): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        if (!is_string($mimeType)) {
            throw new RuntimeException('Unable to inspect the profile image type.');
        }

        return $mimeType;
    }

    private function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => throw new RuntimeException('Profile image must be JPG, PNG, WEBP, or GIF.'),
        };
    }

    private function objectPath(int $userId, string $extension): string
    {
        $fileName = sprintf('user-%d-%s.%s', $userId, bin2hex(random_bytes(8)), $extension);

        return $this->folder !== '' ? $this->folder . '/' . $fileName : $fileName;
    }

    private function encodeObjectPath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private function objectPathFromPublicUrl(string $publicUrl): ?string
    {
        $publicUrlParts = parse_url($publicUrl);
        $baseUrlParts = parse_url($this->url);
        $publicHost = (string) ($publicUrlParts['host'] ?? '');
        $baseHost = (string) ($baseUrlParts['host'] ?? '');

        if ($publicHost === '' || $baseHost === '' || strcasecmp($publicHost, $baseHost) !== 0) {
            return null;
        }

        $path = trim((string) ($publicUrlParts['path'] ?? ''), '/');
        $segments = array_map('rawurldecode', explode('/', $path));
        $expectedPrefix = ['storage', 'v1', 'object', 'public', $this->bucket];

        if (array_slice($segments, 0, count($expectedPrefix)) !== $expectedPrefix) {
            return null;
        }

        $objectSegments = array_slice($segments, count($expectedPrefix));
        $objectPath = implode('/', $objectSegments);

        if ($objectPath === '') {
            return null;
        }

        if ($this->folder !== '' && !str_starts_with($objectPath, $this->folder . '/')) {
            return null;
        }

        return $objectPath;
    }

    private function uploadWithCurl(string $uploadUrl, string $contents, string $mimeType): void
    {
        $curl = curl_init($uploadUrl);

        if ($curl === false) {
            throw new RuntimeException('Unable to start Supabase upload.');
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $contents,
            CURLOPT_HTTPHEADER => $this->headers($mimeType),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('Supabase upload failed: ' . ($error !== '' ? $error : (string) $response));
        }
    }

    private function uploadWithStreamContext(string $uploadUrl, string $contents, string $mimeType): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $this->headers($mimeType)),
                'content' => $contents,
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ]);
        $response = file_get_contents($uploadUrl, false, $context);
        $statusLine = $http_response_header[0] ?? '';

        if ($response === false || !preg_match('/\s2\d\d\s/', $statusLine)) {
            throw new RuntimeException('Supabase upload failed: ' . (is_string($response) ? $response : $statusLine));
        }
    }

    private function deleteWithCurl(string $deleteUrl, string $payload): void
    {
        $curl = curl_init($deleteUrl);

        if ($curl === false) {
            throw new RuntimeException('Unable to start Supabase delete request.');
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $this->jsonHeaders(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('Supabase delete failed: ' . ($error !== '' ? $error : (string) $response));
        }
    }

    private function deleteWithStreamContext(string $deleteUrl, string $payload): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE',
                'header' => implode("\r\n", $this->jsonHeaders()),
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ]);
        $response = file_get_contents($deleteUrl, false, $context);
        $statusLine = $http_response_header[0] ?? '';

        if ($response === false || !preg_match('/\s2\d\d\s/', $statusLine)) {
            throw new RuntimeException('Supabase delete failed: ' . (is_string($response) ? $response : $statusLine));
        }
    }

    /**
     * @return array<int, string>
     */
    private function headers(string $mimeType): array
    {
        return [
            'Authorization: Bearer ' . $this->key,
            'apikey: ' . $this->key,
            'Content-Type: ' . $mimeType,
            'x-upsert: false',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function jsonHeaders(): array
    {
        return [
            'Authorization: Bearer ' . $this->key,
            'apikey: ' . $this->key,
            'Content-Type: application/json',
        ];
    }
}
