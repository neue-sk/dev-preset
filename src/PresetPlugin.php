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
 * Composer plugin that, after package install/update,
 * synchronizes tooling configuration into the project root.
 *
 * Philosophy:
 *  - "managed" files (phpstan/rector/pint/pest config, git hooks, CI)
 *    are OVERWRITTEN on every update — this enables global updatability.
 *    They are marked with a header and should not be edited manually in the project.
 *  - "seed" files (.gitignore, .gitattributes, .env.example additions)
 *    are copied ONLY if they do not already exist — the project then owns them
 *    and the preset no longer updates them.
 *
 * Result: improve the preset → `composer update neue-sk/dev-preset`
 * in any project → latest tooling without manual copying.
 */
final class PresetPlugin implements PluginInterface, EventSubscriberInterface
{
    private const MANAGED_MARKER = '# >>> neue-sk/dev-preset (managed — do not edit manually, will be overwritten on update) <<<';

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

        if (realpath($projectRoot) === realpath($packageRoot)) {
            return;
        }

        $this->io->write('<info>neue-sk/dev-preset:</info> syncing tooling configuration...');

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

        $seed = [
            'config/gitignore' => '.gitignore',
            'config/gitattributes' => '.gitattributes',
        ];

        foreach ($seed as $src => $dest) {
            $this->copySeed("{$packageRoot}/{$src}", "{$projectRoot}/{$dest}");
        }

        $this->installHooks($packageRoot, $projectRoot);

        $this->io->write('<info>neue-sk/dev-preset:</info> done. Use <comment>composer quality</comment> to check.');
    }

    private function projectRoot(): string
    {
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
            return;
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
            return;
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
