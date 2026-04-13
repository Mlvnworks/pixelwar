<?php
$errorPageTitle = $errorPageTitle ?? '500 | Server Error';
$errorPageHeading = $errorPageHeading ?? 'Server Error';
$errorPageMessage = $errorPageMessage ?? 'Something failed during startup or request handling. Check your configuration and server logs.';
$errorPageActionHref = $errorPageActionHref ?? './';
$errorPageActionLabel = $errorPageActionLabel ?? 'Back to Home';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($errorPageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        arcade: {
                            cream: '#fff7e8',
                            yellow: '#ffd166',
                            orange: '#ff8c42',
                            coral: '#f97373',
                            cyan: '#4cc9f0',
                            ink: '#26190f',
                            panel: '#fffdf6'
                        }
                    },
                    fontFamily: {
                        arcade: ['"Press Start 2P"', 'cursive'],
                        body: ['"Space Grotesk"', 'sans-serif']
                    },
                    boxShadow: {
                        arcade: '0 24px 60px rgba(38, 25, 15, 0.14)'
                    }
                }
            }
        };
    </script>
</head>

<body class="flex min-h-screen items-center justify-center bg-arcade-cream px-4 py-24 font-body text-arcade-ink">
    <section class="w-full max-w-2xl rounded-[32px] border-4 border-arcade-ink/10 bg-arcade-panel p-8 text-center shadow-arcade md:p-12">
        <p class="font-arcade text-[11px] uppercase tracking-[0.28em] text-arcade-coral">500</p>
        <h1 class="mt-6 text-4xl font-bold tracking-tight md:text-6xl"><?= htmlspecialchars($errorPageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="mt-5 text-base leading-8 text-arcade-ink/70"><?= htmlspecialchars($errorPageMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <a
            href="<?= htmlspecialchars($errorPageActionHref, ENT_QUOTES, 'UTF-8') ?>"
            class="mt-8 inline-flex rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cyan px-6 py-3 text-sm font-semibold text-arcade-ink no-underline transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
            <?= htmlspecialchars($errorPageActionLabel, ENT_QUOTES, 'UTF-8') ?>
        </a>
    </section>
</body>

</html>
