<?php
/** @var string $clubName */
/** @var string $primaryColor */
/** @var string $logoUrl */
use App\Helpers\View;
$primary = $primaryColor ?? '#EE2C28';
$name    = $clubName ?? 'ClubDesk';
$logo    = $logoUrl ?? '/favicon.svg';
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?= View::e($primary) ?>">
    <title>Offline — <?= View::e($name) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f9fa;
            color: #212529;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
            max-width: 480px;
            padding: 2rem;
        }
        .logo {
            max-width: 120px;
            max-height: 120px;
            margin-bottom: 1.5rem;
        }
        .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: <?= View::e($primary) ?>;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: <?= View::e($primary) ?>;
        }
        p {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 0.625rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background-color: <?= View::e($primary) ?>;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { filter: brightness(0.9); }
        .hint {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="<?= View::e($logo) ?>" alt="<?= View::e($name) ?>" class="logo" onerror="this.style.display='none'">
        <div class="icon">&#x1F4F6;</div>
        <h1>Brak po&#322;&#261;czenia</h1>
        <p>Nie&nbsp;mo&#380;na po&#322;&#261;czy&#263; si&#281; z&nbsp;portalem klubu. Niekt&oacute;re funkcje s&#261; niedost&#281;pne offline. Sprawd&#378; po&#322;&#261;czenie z&nbsp;internetem.</p>
        <button class="btn" onclick="window.location.reload()">Spr&oacute;buj ponownie</button>
        <div class="hint">Aplikacja zapisa&#322;a w pami&#281;ci podr&#281;cznej ostatnio odwiedzane strony — wr&oacute;&#263; do nich z menu nawigacji.</div>
    </div>
</body>
</html>
