<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrivatizeChatAttachments extends Command
{
    protected $signature = 'chat:privatize-attachments {--dry-run : Report files without moving anything}';
    protected $description = 'Move legacy chat uploads out of public storage after verifying an identical private copy';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('chat_attachments');
        $files = $public->allFiles('chat-attachments');
        $moved = $failed = 0;
        foreach ($files as $path) {
            if (!preg_match('#\Achat-attachments/([a-zA-Z0-9._-]+)\z#D', $path, $matches) || in_array($matches[1], ['.', '..'], true)) {
                $this->error('Unexpected file path; left unchanged: '.$path);
                $failed++;
                continue;
            }
            if ($this->option('dry-run')) continue;
            $target = $matches[1];
            try {
                if (!$private->exists($target)) {
                    $stream = $public->readStream($path);
                    if (!is_resource($stream)) throw new \RuntimeException('Could not read the source file.');
                    try {
                        if (!$private->put($target, $stream)) throw new \RuntimeException('Could not save the private copy.');
                    } finally {
                        if (is_resource($stream)) fclose($stream);
                    }
                }
                if (!hash_equals($this->checksum($public, $path), $this->checksum($private, $target))) {
                    throw new \RuntimeException('Private copy differs from the source; neither copy was deleted.');
                }
                if (!$public->delete($path)) throw new \RuntimeException('The public copy could not be removed.');
                $moved++;
            } catch (\Throwable $error) {
                $this->error($path.': '.$error->getMessage());
                $failed++;
            }
        }
        $this->info($this->option('dry-run') ? count($files).' legacy attachment(s) found; no files changed.' : $moved.' attachment(s) moved to private storage.');
        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function checksum($disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (!is_resource($stream)) throw new \RuntimeException('Could not verify the file.');
        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
