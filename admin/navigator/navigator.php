<?php
$adminBasePath = dirname(__DIR__);
$rootPath = dirname($adminBasePath);
$pageFiles = glob($adminBasePath . '/pages/*.php') ?: [];
$normalizedContent = preg_match('/^[a-z0-9\-]+$/i', $content) === 1 ? $content : 'dashboard';
$allowedPages = array_map(static function (string $file): string {
    return basename($file, '.php');
}, $pageFiles);
$isAllowedPage = in_array($normalizedContent, $allowedPages, true);
$pageStyleFile = $adminBasePath . '/styling/page/' . $normalizedContent . '.css';
$adminTitles = [
    'dashboard' => 'Admin Dashboard | ' . APP_NAME,
    'teachers' => 'Teacher Management | ' . APP_NAME,
    'settings' => 'Admin Settings | ' . APP_NAME,
];
$appName = $adminTitles[$normalizedContent] ?? ('Admin Panel | ' . APP_NAME);
$appDescription = 'Admin workspace for managing Pixelwar teachers and platform controls.';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($appDescription, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        arcade: {
                            cream: '#fff7e8',
                            peach: '#ffd9a8',
                            yellow: '#ffd166',
                            orange: '#ff8c42',
                            coral: '#f97373',
                            cyan: '#4cc9f0',
                            mint: '#8bd3c7',
                            ink: '#26190f',
                            panel: '#fffdf6'
                        }
                    },
                    fontFamily: {
                        arcade: ['"Press Start 2P"', 'cursive'],
                        body: ['"Space Grotesk"', 'sans-serif']
                    }
                }
            }
        };
    </script>

    <link rel="stylesheet" href="../styling/style.css">
    <link rel="stylesheet" href="./styling/style.css">

    <?php if ($isAllowedPage && is_file($pageStyleFile)) : ?>
        <link rel="stylesheet" href="./styling/page/<?= htmlspecialchars($normalizedContent, ENT_QUOTES, 'UTF-8') ?>.css">
    <?php endif; ?>

    <link rel="shortcut icon" href="../assets/img/icon.png" type="image/x-icon">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
</head>

<body class="bg-arcade-cream font-body text-arcade-ink antialiased">
    <script>
        try {
            if (localStorage.getItem('pixelwarDarkMode') === 'on') {
                document.body.classList.add('pixelwar-dark-mode');
            }
        } catch (error) {
            document.body.classList.remove('pixelwar-dark-mode');
        }
    </script>
    <?php
    if ($isAllowedPage) {
        include $adminBasePath . '/components/navbar.php';
        require $adminBasePath . '/pages/' . $normalizedContent . '.php';
        include $adminBasePath . '/components/footer.php';
        $tools->alert();
    } else {
        http_response_code(404);
        require $rootPath . '/components/404.php';
    }
    ?>
</body>

</html>
