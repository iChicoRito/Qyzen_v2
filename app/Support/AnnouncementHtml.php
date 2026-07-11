<?php

namespace App\Support;

class AnnouncementHtml
{
    private const ALLOWED_TAGS = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><a>';

    public static function sanitize(?string $html): string
    {
        $html = preg_replace('/<(script|style|iframe|object|embed)\b[^>]*>.*?<\/\1>/is', '', $html ?? '') ?? '';
        $html = strip_tags($html ?? '', self::ALLOWED_TAGS);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+(?:style|src)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';

        return preg_replace_callback('/\s+href\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', function (array $match): string {
            $url = $match[1] ?? $match[2] ?? $match[3] ?? '';

            return preg_match('/^(?:https?:|mailto:|\/|#)/i', $url) ? ' href="'.e($url).'"' : '';
        }, $html) ?? '';
    }
}
