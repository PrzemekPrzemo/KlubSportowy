<?php

namespace App\Helpers;

class View
{
    private string $layout = 'main';

    /** @var array<string,string>|null sport_key => folder name (np. 'football' => 'Football') */
    private static ?array $sportFolderMap = null;

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function render(string $__template, array $__data = []): void
    {
        extract($__data, EXTR_SKIP);

        $viewFile = self::resolveTemplate($__template);
        if ($viewFile === null) {
            throw new \RuntimeException("View not found: $__template");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = ROOT_PATH . '/app/Views/layouts/' . $this->layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function partial(string $__template, array $__data = []): string
    {
        extract($__data, EXTR_SKIP);
        $viewFile = self::resolveTemplate($__template);
        if ($viewFile === null) {
            return '';
        }
        ob_start();
        require $viewFile;
        return ob_get_clean();
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Rozwiązuje nazwę widoku ('football/teams/index') do absolutnej ścieżki.
     *
     * Kolejność szukania:
     *  1) Moduł per-sport: app/Sports/<Folder>/views/<rest>.php
     *     (gdy pierwszy segment to klucz sportu zarejestrowany w manifeście)
     *  2) Centralne app/Views/<template>.php
     *
     * Zwraca null gdy nie znaleziono żadnego z plików.
     */
    public static function resolveTemplate(string $template): ?string
    {
        $parts = explode('/', $template, 2);
        if (count($parts) === 2) {
            $sportKey = $parts[0];
            $folder   = self::sportFolderFor($sportKey);
            if ($folder !== null) {
                $modulePath = ROOT_PATH . '/app/Sports/' . $folder . '/views/' . $parts[1] . '.php';
                if (file_exists($modulePath)) {
                    return $modulePath;
                }
            }
        }

        $centralPath = ROOT_PATH . '/app/Views/' . $template . '.php';
        if (file_exists($centralPath)) {
            return $centralPath;
        }

        return null;
    }

    /**
     * Mapuje sport_key (np. 'football') na nazwę folderu w app/Sports
     * (np. 'Football'). Buduje cache z manifestów sportów.
     */
    private static function sportFolderFor(string $sportKey): ?string
    {
        if (self::$sportFolderMap === null) {
            self::$sportFolderMap = [];
            $base = ROOT_PATH . '/app/Sports';
            if (is_dir($base)) {
                foreach (glob($base . '/*/manifest.php') as $manifestPath) {
                    try {
                        $manifest = require $manifestPath;
                    } catch (\Throwable) {
                        continue;
                    }
                    if (!is_array($manifest) || empty($manifest['key'])) {
                        continue;
                    }
                    $folder = basename(dirname($manifestPath));
                    self::$sportFolderMap[(string)$manifest['key']] = $folder;
                }
            }
        }
        return self::$sportFolderMap[$sportKey] ?? null;
    }

    /** Reset cache mapy folderów sportów — używane w testach. */
    public static function resetSportFolderMap(): void
    {
        self::$sportFolderMap = null;
    }
}
