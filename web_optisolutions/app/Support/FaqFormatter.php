<?php

namespace App\Support;

/**
 * Turns an admin-written FAQ answer into safe HTML.
 *
 * Supported (everything else is escaped as plain text):
 *   **bold**                      -> <strong>
 *   [label](https://… or /path)   -> link
 *   new lines                     -> <br>
 *   {operating_hours} {address} {contact_no}  -> live clinic info (Contact / Clinic Hours settings)
 *   {services}                    -> list of the currently available services
 */
class FaqFormatter
{
    public static function format(?string $answer, array $clinic = [], array $serviceTitles = []): string
    {
        $text = e((string) $answer);

        // **bold**
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

        // [label](url) — only http(s), site-relative paths and #anchors
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $url = html_entity_decode($m[2], ENT_QUOTES);
            if (!preg_match('#^(https?://|/|\#)#i', $url)) {
                return $m[0];
            }
            $external = preg_match('#^https?://#i', $url) ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="'.e($url).'"'.$external.'>'.$m[1].'</a>';
        }, $text);

        // Live clinic details (kept in spans so the site's live sync can refresh them).
        $fields = [
            'operating_hours' => 'Clinic hours have not been set yet.',
            'address'         => 'Address has not been set yet.',
            'contact_no'      => 'our clinic number (not set yet)',
        ];
        foreach ($fields as $key => $empty) {
            $value = trim((string) ($clinic[$key] ?? ''));
            $text = str_replace(
                '{'.$key.'}',
                '<span data-clinic-field="'.$key.'" data-clinic-empty="'.e($empty).'">'.e($value !== '' ? $value : $empty).'</span>',
                $text
            );
        }

        $text = str_replace('{services}', e(self::joinList($serviceTitles)), $text);

        return nl2br($text, false);
    }

    private static function joinList(array $items): string
    {
        $items = array_values(array_filter(array_map('trim', $items), fn ($i) => $i !== ''));
        $n = count($items);
        if ($n === 0) return 'a range of medical services';
        if ($n === 1) return $items[0];
        if ($n === 2) return $items[0].' and '.$items[1];
        return implode(', ', array_slice($items, 0, -1)).', and '.$items[$n - 1];
    }
}