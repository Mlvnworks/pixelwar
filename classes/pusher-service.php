<?php

final class PusherService
{
    public function __construct(
        private string $appId,
        private string $key,
        private string $secret,
        private string $cluster
    ) {
    }

    public function isConfigured(): bool
    {
        $values = [
            trim($this->appId),
            trim($this->key),
            trim($this->secret),
            trim($this->cluster),
        ];

        foreach ($values as $value) {
            if ($value === '' || str_starts_with($value, 'your-pusher-')) {
                return false;
            }
        }

        return true;
    }

    public function trigger(string $channel, string $event, array $data): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $body = json_encode([
            'name' => $event,
            'channel' => $channel,
            'data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!is_string($body)) {
            throw new RuntimeException('Unable to encode Pusher payload.');
        }

        $path = '/apps/' . rawurlencode($this->appId) . '/events';
        $params = [
            'auth_key' => $this->key,
            'auth_timestamp' => (string) time(),
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        ];
        ksort($params);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $stringToSign = "POST\n{$path}\n{$query}";
        $signature = hash_hmac('sha256', $stringToSign, $this->secret);
        $url = 'https://api-' . $this->cluster . '.pusher.com' . $path . '?' . $query . '&auth_signature=' . $signature;

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false || $statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('Pusher trigger failed: ' . ($error !== '' ? $error : ('HTTP ' . $statusCode)));
        }

        return true;
    }
}
