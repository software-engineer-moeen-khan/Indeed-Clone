<?php

declare(strict_types=1);

namespace App\Support;

final class JobDescriptionSanitizer
{
    /**
     * Convert scraped NJP page text into compact, safe rich text similar to
     * what the Filament RichEditor stores for manually-created jobs.
     */
    public static function sanitize(?string $description, ?string $jobTitle = null, ?string $employer = null): string
    {
        $raw = trim((string) $description);

        if ($raw === '') {
            return self::fallback($jobTitle, $employer);
        }

        // Remove executable/style blocks before converting markup to text.
        $raw = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', "\n", $raw) ?? $raw;
        $raw = preg_replace(
            '~<\s*(?:br\s*/?|/p|/div|/li|/ul|/ol|/h[1-6]|/section|/article|/tr|/td)\s*>~i',
            "\n",
            $raw
        ) ?? $raw;

        $plain = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = str_replace(["\xC2\xA0", "\r\n", "\r"], [' ', "\n", "\n"], $plain);

        $lines = preg_split('/\n/u', $plain) ?: [];
        $clean = [];
        $cssDepth = 0;
        $seenContent = false;

        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/[\t ]+/u', ' ', (string) $line));

            if ($cssDepth > 0) {
                $cssDepth += substr_count($line, '{') - substr_count($line, '}');
                $cssDepth = max(0, $cssDepth);
                continue;
            }

            if ($line !== '' && self::startsCssBlock($line)) {
                $cssDepth = max(0, substr_count($line, '{') - substr_count($line, '}'));
                continue;
            }

            if ($line !== '' && self::isCssLine($line)) {
                continue;
            }

            if ($line === '') {
                if ($clean !== [] && end($clean) !== '') {
                    $clean[] = '';
                }
                continue;
            }

            if (self::isBoilerplate($line, $jobTitle, $employer)) {
                continue;
            }

            if ($seenContent && self::isFooterMarker($line)) {
                break;
            }

            if (self::isMetadataLine($line)) {
                continue;
            }

            $clean[] = $line;
            $seenContent = true;
        }

        while ($clean !== [] && end($clean) === '') {
            array_pop($clean);
        }

        $text = trim(implode("\n", $clean));
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
        $text = self::sliceFromDescriptionMarker($text);
        $text = preg_replace('/(?:\n\s*)?Source:\s*National Jobs? Portal(?:\s*\(NJP\))?\s*$/iu', '', $text) ?? $text;
        $text = trim($text);

        if (mb_strlen($text) < 40) {
            return self::fallback($jobTitle, $employer);
        }

        // A scraped page should never create a multi-screen wall of navigation text.
        if (mb_strlen($text) > 12000) {
            $text = mb_substr($text, 0, 12000);
            $text = preg_replace('/\s+\S*$/u', '', $text) ?? $text;
        }

        return self::toRichText($text);
    }

    private static function startsCssBlock(string $line): bool
    {
        if (! str_contains($line, '{')) {
            return false;
        }

        return preg_match(
            '/^(?:@(?:media|font-face|keyframes|supports)|body\b|html\b|:root\b|[.#][\w-]+|[a-z][\w-]*(?:\s+[.#a-z][^{]*)?)\s*\{/iu',
            $line
        ) === 1;
    }

    private static function isCssLine(string $line): bool
    {
        if (preg_match('/^[{}]+$/', $line) === 1 || str_starts_with($line, '/*') || str_starts_with($line, '*/')) {
            return true;
        }

        return preg_match(
            '/^(?:font(?:-family|-style|-weight|-size|-optical-sizing)?|color|background(?:-color)?|display|position|margin(?:-[a-z]+)?|padding(?:-[a-z]+)?|width|height|min-width|max-width|min-height|max-height|line-height|letter-spacing|border(?:-[a-z]+)?|box-shadow|text-align|text-decoration|overflow|z-index|top|right|bottom|left|gap|flex(?:-[a-z]+)?|grid(?:-[a-z]+)?|opacity|transform|transition|cursor|content)\s*:/iu',
            $line
        ) === 1;
    }

    private static function isBoilerplate(string $line, ?string $jobTitle, ?string $employer): bool
    {
        $normalized = self::normalize($line);

        $exact = [
            'national job portal',
            'national jobs portal',
            'government of pakistan',
            'sign in',
            'sign up',
            'open main menu',
            'close main menu',
            'home',
            'find jobs',
            'browse categories',
            'success stories',
            'job seekers',
            'employers',
            'about us',
            'contact us',
            'faq',
            'faqs',
            'privacy policy',
            'terms conditions',
            'terms and conditions',
            'skip to content',
            'facebook',
            'twitter',
            'linkedin',
            'whatsapp',
            'email',
        ];

        if (in_array($normalized, $exact, true)) {
            return true;
        }

        if ($jobTitle && $normalized === self::normalize($jobTitle)) {
            return true;
        }

        if ($employer && $normalized === self::normalize($employer)) {
            return true;
        }

        return preg_match('/^(?:share|save job|bookmark|apply now|back to jobs)$/iu', trim($line)) === 1;
    }

    private static function isMetadataLine(string $line): bool
    {
        return preg_match(
            '/^(?:type|posted|experience|share|location|job type|employment type|salary|application deadline|deadline|available till)\s*:\s*.+$/iu',
            $line
        ) === 1;
    }

    private static function isFooterMarker(string $line): bool
    {
        return preg_match(
            '/^(?:related jobs|similar jobs|other jobs|quick links|useful links|follow us|copyright|all rights reserved)\b/iu',
            trim($line)
        ) === 1;
    }

    private static function sliceFromDescriptionMarker(string $text): string
    {
        $lines = preg_split('/\n/u', $text) ?: [];
        $best = null;

        foreach ($lines as $index => $line) {
            $marker = self::normalize($line);

            if (! in_array($marker, ['job description', 'position description', 'description'], true)) {
                continue;
            }

            $tail = trim(implode("\n", array_slice($lines, $index + 1)));

            if (mb_strlen($tail) >= 80) {
                $best = $tail;
            }
        }

        return $best ?? $text;
    }

    private static function toRichText(string $text): string
    {
        $blocks = preg_split('/\n{2,}/u', trim($text)) ?: [];
        $html = [];

        foreach ($blocks as $block) {
            $lines = array_values(array_filter(
                array_map('trim', preg_split('/\n+/u', trim($block)) ?: []),
                static fn (string $line): bool => $line !== ''
            ));

            if ($lines === []) {
                continue;
            }

            $allBullets = true;
            $items = [];

            foreach ($lines as $line) {
                if (preg_match('/^(?:[•*\-–—]|\d+[.)])\s+(.+)$/u', $line, $match) !== 1) {
                    $allBullets = false;
                    break;
                }

                $items[] = trim($match[1]);
            }

            if ($allBullets && $items !== []) {
                $html[] = '<ul>'.implode('', array_map(
                    static fn (string $item): string => '<li>'.self::escape($item).'</li>',
                    $items
                )).'</ul>';
                continue;
            }

            if (count($lines) === 1 && mb_strlen($lines[0]) <= 80 && preg_match('/[:：]$/u', $lines[0]) === 1) {
                $html[] = '<p><strong>'.self::escape(rtrim($lines[0], ':：')).'</strong></p>';
                continue;
            }

            $html[] = '<p>'.self::escape(implode(' ', $lines)).'</p>';
        }

        return implode('', $html);
    }

    private static function fallback(?string $jobTitle, ?string $employer): string
    {
        $parts = [];

        if (trim((string) $jobTitle) !== '') {
            $parts[] = trim((string) $jobTitle);
        }

        if (trim((string) $employer) !== '') {
            $parts[] = 'at '.trim((string) $employer);
        }

        $intro = $parts !== [] ? implode(' ', $parts).'. ' : '';

        return '<p>'.self::escape($intro.'Please review the official National Jobs Portal listing for complete job details and application instructions.').'</p>';
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
