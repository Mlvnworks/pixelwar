<?php

function pixelwarOptimizedImageUrl(string $url, int $width = 96, ?int $height = null, int $quality = 72, string $resize = 'cover'): string
{
    $url = trim($url);

    if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return $url;
    }

    $width = max(16, min(1200, $width));
    $height = $height !== null ? max(16, min(1200, $height)) : $width;
    $quality = max(35, min(90, $quality));
    $resize = in_array($resize, ['cover', 'contain', 'fill'], true) ? $resize : 'cover';
    $parts = parse_url($url);
    $path = (string) ($parts['path'] ?? '');

    if (str_contains($path, '/storage/v1/render/image/public/')) {
        return $url;
    }

    $publicPrefix = '/storage/v1/object/public/';
    if (!str_contains($path, $publicPrefix)) {
        return pixelwarOptimizedProviderImageUrl($url, $width);
    }

    if (!defined('SUPABASE_IMAGE_TRANSFORM_ENABLED') || SUPABASE_IMAGE_TRANSFORM_ENABLED !== true) {
        return $url;
    }

    $renderPath = str_replace($publicPrefix, '/storage/v1/render/image/public/', $path);
    $query = http_build_query([
        'width' => $width,
        'height' => $height,
        'resize' => $resize,
        'quality' => $quality,
    ]);

    return (string) ($parts['scheme'] ?? 'https')
        . '://'
        . (string) ($parts['host'] ?? '')
        . (isset($parts['port']) ? ':' . (int) $parts['port'] : '')
        . $renderPath
        . '?'
        . $query;
}

function pixelwarAvatarUrl(string $url, int $size = 96): string
{
    return pixelwarOptimizedImageUrl($url, $size, $size, 72, 'cover');
}

function pixelwarOptimizedProviderImageUrl(string $url, int $size): string
{
    $parts = parse_url($url);
    $host = strtolower((string) ($parts['host'] ?? ''));

    if (!str_contains($host, 'googleusercontent.com')) {
        return $url;
    }

    if (preg_match('/=s\d+(?:-[a-z]+)*$/', $url) === 1) {
        return (string) preg_replace('/=s\d+(?:-[a-z]+)*$/', '=s' . $size . '-c', $url);
    }

    if (($parts['query'] ?? '') === '') {
        return rtrim($url, '/') . '=s' . $size . '-c';
    }

    return $url;
}
