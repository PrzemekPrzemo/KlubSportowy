<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;

class BackupController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $dir   = ROOT_PATH . '/storage/backups';
        $files = [];
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.sql.gz') as $f) {
                $files[] = [
                    'name'    => basename($f),
                    'size'    => filesize($f),
                    'created' => date('Y-m-d H:i:s', filemtime($f)),
                ];
            }
            foreach (glob($dir . '/*.sql') as $f) {
                $files[] = [
                    'name'    => basename($f),
                    'size'    => filesize($f),
                    'created' => date('Y-m-d H:i:s', filemtime($f)),
                ];
            }
        }
        usort($files, fn($a, $b) => strcmp($b['created'], $a['created']));

        $this->render('admin/backups', [
            'title' => 'Kopie zapasowe',
            'files' => $files,
        ]);
    }

    public function create(): void
    {
        Csrf::verify();
        $config = file_exists(ROOT_PATH . '/config/database.local.php')
            ? require ROOT_PATH . '/config/database.local.php'
            : require ROOT_PATH . '/config/database.php';

        $dir = ROOT_PATH . '/storage/backups';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $path     = $dir . '/' . $filename;

        $cmd = sprintf(
            'mysqldump --host=%s --port=%d --user=%s --password=%s --single-transaction --quick %s > %s 2>&1',
            escapeshellarg($config['host']),
            (int)$config['port'],
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['dbname']),
            escapeshellarg($path)
        );

        $output = [];
        $code   = 0;
        exec($cmd, $output, $code);

        if ($code !== 0 || !file_exists($path) || filesize($path) === 0) {
            // Fallback: PHP-based dump (basic tables export)
            $this->phpDump($path, $config);
        }

        // Gzip if possible
        if (file_exists($path) && function_exists('gzopen')) {
            $gz = gzopen($path . '.gz', 'w9');
            if ($gz) {
                gzwrite($gz, file_get_contents($path));
                gzclose($gz);
                @unlink($path);
                $filename .= '.gz';
            }
        }

        Session::flash('success', 'Kopia zapasowa utworzona: ' . $filename);
        $this->redirect('admin/backups');
    }

    public function download(string $file): void
    {
        $file = basename($file); // prevent path traversal
        $path = ROOT_PATH . '/storage/backups/' . $file;
        if (!file_exists($path)) {
            Session::flash('error', 'Plik nie istnieje.');
            $this->redirect('admin/backups');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function delete(string $file): void
    {
        Csrf::verify();
        $file = basename($file);
        $path = ROOT_PATH . '/storage/backups/' . $file;
        if (file_exists($path)) {
            @unlink($path);
            Session::flash('success', 'Kopia usunięta.');
        }
        $this->redirect('admin/backups');
    }

    /** Fallback PHP dump when mysqldump is not available. */
    private function phpDump(string $path, array $config): void
    {
        $db = Database::pdo();
        $tables = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        $out = "-- KlubSportowy backup " . date('Y-m-d H:i:s') . "\n";
        $out .= "SET NAMES utf8mb4;\nSET foreign_key_checks = 0;\n\n";

        foreach ($tables as $table) {
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $out .= $create['Create Table'] . ";\n\n";

            $rows = $db->query("SELECT * FROM `{$table}`");
            while ($row = $rows->fetch(\PDO::FETCH_NUM)) {
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $db->quote($v), $row);
                $out .= "INSERT INTO `{$table}` VALUES (" . implode(',', $vals) . ");\n";
            }
            $out .= "\n";
        }

        $out .= "SET foreign_key_checks = 1;\n";
        file_put_contents($path, $out);
    }
}
