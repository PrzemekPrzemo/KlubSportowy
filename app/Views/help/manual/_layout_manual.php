<?php
/**
 * Marker layoutu manualu — włączany przez każdą stronę zawodnika/rodzica.
 *
 * Strony manualu mają następującą strukturę:
 *
 *     <?php
 *     $page = [
 *       'title'        => '…',
 *       'category'     => 'Zawodnik',          // lub 'Rodzic'
 *       'group'        => 'Pierwsze kroki',     // pasujący do grupy w manifeście
 *       'last_updated' => '2026-05-15',
 *       'reading_time' => '2 min',
 *     ];
 *     include __DIR__ . '/../_layout_manual.php';
 *     ?>
 *     <h1>…</h1>
 *     ...treść HTML...
 *
 * Metadane dla aktywnej strony są też zapisane w manifeście
 * {@see \App\Controllers\HelpController::manuals()} — to one mają
 * pierwszeństwo przy renderze (kontroler przekazuje `$page` do widoku).
 * Lokalna tablica `$page` w pliku strony służy jako self-documenting nagłówek
 * i pozwala edytorom IDE szybko podejrzeć, czego strona dotyczy.
 *
 * Plik ten celowo NIC nie robi w trakcie renderu — pełna rama (sidebar,
 * breadcrumbs, mockup-style itd.) pochodzi z `help/manual/page.php`.
 */
