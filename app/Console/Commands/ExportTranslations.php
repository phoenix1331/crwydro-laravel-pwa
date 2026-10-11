<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

/**
 * Export lang/{locale}/app.php to flat JSON for the PWA, so the bundled (and
 * precached) client can translate with no signal. Runs before every build.
 */
#[Signature('crwydro:export-lang')]
#[Description('Export app translations to resources/js/lang/*.json for offline use')]
class ExportTranslations extends Command
{
    public const LOCALES = ['en', 'cy'];

    public function handle(): int
    {
        $directory = resource_path('js/lang');
        File::ensureDirectoryExists($directory);

        foreach (self::LOCALES as $locale) {
            $messages = Arr::dot(require lang_path("{$locale}/app.php"));

            File::put("{$directory}/{$locale}.json", json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

            $this->components->info('Exported '.count($messages)." {$locale} strings");
        }

        return self::SUCCESS;
    }
}
