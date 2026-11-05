<?php

namespace App\Support;

final class YoutubeVideo
{
    /**
     * @return non-empty-string|null
     */
    public static function id(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'], $parts['scheme'])) {
            return null;
        }

        if (! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        $host = (string) preg_replace('/^www\./', '', $host);

        if (! in_array($host, ['youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com'], true)) {
            return null;
        }

        $id = null;

        if ($host === 'youtu.be') {
            $id = trim((string) ($parts['path'] ?? ''), '/');
        } else {
            $path = trim((string) ($parts['path'] ?? ''), '/');

            if (preg_match('#^(?:embed|shorts|live)/([A-Za-z0-9_-]{11})(?:/|$)#', $path, $matches) === 1) {
                $id = $matches[1];
            }

            parse_str((string) ($parts['query'] ?? ''), $query);

            if (isset($query['v']) && is_string($query['v']) && $query['v'] !== '') {
                $id = $query['v'];
            }
        }

        if (! is_string($id) || preg_match('/^[A-Za-z0-9_-]{11}$/', $id) !== 1) {
            return null;
        }

        return $id;
    }

    public static function watchUrl(?string $url): ?string
    {
        $id = self::id($url);

        return $id === null ? null : 'https://www.youtube.com/watch?v='.$id;
    }

    public static function embedUrl(?string $url): ?string
    {
        $id = self::id($url);

        return $id === null ? null : 'https://www.youtube-nocookie.com/embed/'.$id;
    }
}
