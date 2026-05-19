<?php

declare(strict_types=1);

namespace NeueSk\DevPreset;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin, ktorý po inštalácii / aktualizácii balíka
 * synchronizuje tooling konfigurácie do koreňa projektu.
 *
 * Filozofia:
 *  - "managed" súbory (phpstan/rector/pint/pest config, git hooks, CI)
 *    sa PREPISUJÚ pri každom update — to je tá globálna aktualizovateľnosť.
 *    Sú označené hlavičkou, nemajú sa ručne editovať v projekte.
 *  - "seed" súbory (.gitignore, .gitattributes, .env.example doplnky)
 *    sa kopírujú LEN ak v projekte ešte neexistujú — projekt si ich
 *    potom vlastní a preset ich už neprepisuje.
 *
 * Výsledok: vylepšíš preset → `composer update neue-sk/dev-preset`
 * v ktoromkoľvek projekte → najnovší tooling, bez ručného kopírovania.
 */
final class PresetPlugin implements PluginInterface, EventSubscriberInterface
{
    private const MANAGED_MARKER = '# >>> neue-sk/dev-preset (managed — needratuj ručne, prepíše sa pri update) <<<';

    private Composer $composer;

    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'sync',
            ScriptEvents::POST_UPDATE_CMD => 'sync',
        ];
    }

    public function sync(Event $event): void
    {
        $projectRoot = $this->projectRoot();
        $packageRoot = __DIR__ . '/..';

        // Beží len v reálnom projekte, nie pri vývoji samotného presetu.
        if (realpath($projectRoot) === realpath($packageRoot)) {
            return;
        }

        $this->io->write('<info>neue-sk/dev-preset:</info> synchronizujem tooling konfigurácie...');

        // Managed súbory — vždy prepíš (zdroj pravdy je balík).
        $managed = [
            'config/pint.json' => 'pint.json',
            'config/phpstan.neon' => 'phpstan.neon',
            'config/rector.php' => 'rector.php',
            'config/Pest.php' => 'tests/Pest.php',
            'config/gitleaks.toml' => '.gitleaks.toml',
            'resources/ci/quality.yml' => '.github/workflows/quality.yml',
        ];

        foreach ($managed as $src => $dest) {
            $this->copyManaged("{$packageRoot}/{$src}", "{$projectRoot}/{$dest}");
        }

        // Seed súbory — kopíruj len ak chýbajú (projekt si ich vlastní).
        $seed = [
            'config/gitignore' => '.gitignore',
            'config/gitattributes' => '.gitattributes',
        ];

        foreach ($seed as $src => $dest) {
            $this->copySeed("{$packageRoot}/{$src}", "{$projectRoot}/{$dest}");
        }

        // Git hooky — nainštaluj/aktualizuj a sprav spustiteľnými.
        $this->installHooks($packageRoot, $projectRoot);

        $this->io->write('<info>neue-sk/dev-preset:</info> hotovo. Spusti <comment>composer quality</comment> na overenie.');
    }

    private function projectRoot(): string
    {
        // Koreň projektu = adresár, kde je composer.json ktorý balík ťahá.
        return dirname($this->composer->getConfig()->get('vendor-dir'));
    }

    private function copyManaged(string $src, string $dest): void
    {
        if (! is_file($src)) {
            $this->io->writeError("  <warning>chýba zdroj: {$src}</warning>");

            return;
        }

        $this->ensureDir(dirname($dest));
        copy($src, $dest);
        $this->io->write('  managed → ' . $this->relative($dest));
    }

    private function copySeed(string $src, string $dest): void
    {
        if (is_file($dest)) {
            return; // už existuje — nevlastníme ho.
        }

        if (! is_file($src)) {
            return;
        }

        $this->ensureDir(dirname($dest));
        copy($src, $dest);
        $this->io->write('  seed → ' . $this->relative($dest));
    }

    private function installHooks(string $packageRoot, string $projectRoot): void
    {
        $gitDir = "{$projectRoot}/.git";

        if (! is_dir($gitDir)) {
            return; // nie je git repo (napr. CI checkout bez .git) — preskoč.
        }

        $hooksDest = "{$gitDir}/hooks";
        $this->ensureDir($hooksDest);

        foreach (['pre-commit', 'commit-msg'] as $hook) {
            $src = "{$packageRoot}/resources/hooks/{$hook}";

            if (! is_file($src)) {
                continue;
            }

            $target = "{$hooksDest}/{$hook}";
            copy($src, $target);
            @chmod($target, 0o755);
            $this->io->write('  hook → .git/hooks/' . $hook);
        }
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            @mkdir($dir, 0o755, true);
        }
    }

    private function relative(string $path): string
    {
        return str_replace($this->projectRoot() . '/', '', $path);
    }
}
