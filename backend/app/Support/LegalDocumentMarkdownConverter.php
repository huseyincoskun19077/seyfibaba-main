<?php

namespace App\Support;

class LegalDocumentMarkdownConverter
{
    public static function toHtml(string $markdown): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $markdown);
        $html = [];
        $inList = false;
        $paragraph = [];

        $flushParagraph = static function () use (&$html, &$paragraph): void {
            if ($paragraph === []) {
                return;
            }

            $text = trim(implode(' ', $paragraph));
            if ($text !== '') {
                $html[] = '<p>'.self::inline($text).'</p>';
            }
            $paragraph = [];
        };

        $closeList = static function () use (&$html, &$inList): void {
            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $flushParagraph();
                $closeList();
                continue;
            }

            if ($trimmed === '---') {
                $flushParagraph();
                $closeList();
                $html[] = '<hr>';
                continue;
            }

            if (preg_match('/^#{1}\s+(.+)$/', $trimmed, $matches)) {
                $flushParagraph();
                $closeList();
                $html[] = '<h2>'.self::inline($matches[1]).'</h2>';
                continue;
            }

            if (preg_match('/^#{2,3}\s+(.+)$/', $trimmed, $matches)) {
                $flushParagraph();
                $closeList();
                $html[] = '<h3>'.self::inline($matches[1]).'</h3>';
                continue;
            }

            if (preg_match('/^#{4,6}\s+(.+)$/', $trimmed, $matches)) {
                $flushParagraph();
                $closeList();
                $html[] = '<h4>'.self::inline($matches[1]).'</h4>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                $flushParagraph();
                if (!$inList) {
                    $html[] = '<ul>';
                    $inList = true;
                }
                $html[] = '<li>'.self::inline($matches[1]).'</li>';
                continue;
            }

            $closeList();
            $paragraph[] = $trimmed;
        }

        $flushParagraph();
        $closeList();

        return implode("\n", $html);
    }

    private static function inline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
        $escaped = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $escaped);

        return $escaped;
    }
}
