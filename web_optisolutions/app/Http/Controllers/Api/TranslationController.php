<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TranslationController extends Controller
{
    /**
     * Translate text using the free MyMemory Translation API
     * (no signup, no API key, no billing needed).
     *
     * Auto-detects whether the text is Tagalog or English using a simple
     * word-list heuristic (MyMemory itself doesn't support auto-detect),
     * then translates it into the counterpart language.
     *
     * Expects: { text: string }
     * Returns: { translatedText: string, detectedSourceLanguage: 'tl'|'en' }
     */
    public function translate(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
        ]);

        $text = $request->text;
        $sourceLang = $this->detectLanguage($text);
        $targetLang = $sourceLang === 'tl' ? 'en' : 'tl';

        try {
            $response = Http::get('https://api.mymemory.translated.net/get', array_filter([
                'q'        => $text,
                'langpair' => "{$sourceLang}|{$targetLang}",
                // Optional: adding an email raises the daily free limit
                // from 5,000 to 50,000 characters. No verification needed.
                'de'       => config('services.mymemory.email'),
            ]));

            if (!$response->successful()) {
                return response()->json([
                    'translatedText'         => $text,
                    'detectedSourceLanguage' => $sourceLang,
                ]);
            }

            $translated = $response->json('responseData.translatedText');

            return response()->json([
                'translatedText'         => html_entity_decode($translated ?? $text),
                'detectedSourceLanguage' => $sourceLang,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'translatedText'         => $text,
                'detectedSourceLanguage' => $sourceLang,
            ]);
        }
    }

    /**
     * Very lightweight Tagalog vs English detector. Counts how many common
     * Tagalog function words appear in the text. Good enough for short
     * chat messages; not meant to be linguistically rigorous.
     */
    private function detectLanguage(string $text): string
    {
        $tagalogWords = [
            'ang', 'ng', 'mga', 'sa', 'na', 'po', 'opo', 'hindi', 'oo',
            'ako', 'ikaw', 'siya', 'kami', 'tayo', 'sila', 'ito', 'iyan',
            'iyon', 'kelan', 'kailan', 'paano', 'saan', 'bakit', 'ano',
            'sino', 'gusto', 'ayaw', 'meron', 'wala', 'naman', 'din',
            'rin', 'lang', 'lamang', 'yung', 'yan', 'kayo', 'ninyo',
            'natin', 'namin', 'ba', 'kasi', 'kung', 'pag', 'tapos',
            'magkano', 'pwede', 'puwede', 'salamat', 'maganda', 'mahal',
        ];

        $normalized = ' ' . strtolower(preg_replace('/[^\p{L}\s]/u', ' ', $text)) . ' ';

        $hits = 0;
        foreach ($tagalogWords as $word) {
            if (str_contains($normalized, " {$word} ")) {
                $hits++;
            }
        }

        return $hits > 0 ? 'tl' : 'en';
    }
}