# Extension And Handler Examples

## Purpose

This guide gives concrete examples for extending the project using the current structure.

## Development Guide

### Add A New Request Handler

1. Create a PHP file in `submissions/`
2. Keep it focused on one action only
3. Let `submission.php` auto-load it
4. Exit early for unmatched requests

### Add A New Shared Helper

1. Create or extend a class in `classes/`
2. Require it in `submission.php`
3. Instantiate it there if it should be globally available during page rendering
4. Prefer helpers when the same page data, labels, or behavior would otherwise be repeated

### Add A New Component

1. Create a PHP partial in `components/`
2. Keep it focused on rendering
3. Pass only the minimum required data into it
4. Keep it aligned with the retro arcade light theme

## Example Use Case

### Example: Add A Score Reset Handler

Create `submissions/reset-score.php`:

```php
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!isset($_POST['reset_score'])) {
    return;
}

$_SESSION['alert'] = [
    'error' => false,
    'content' => 'Score state reset successfully.',
];
```

This will be auto-loaded by `submission.php`.

### Example: Add A New Helper Class

Create `classes/game-copy.php`:

```php
<?php
class GameCopy
{
    public function heading(string $page): string
    {
        return match ($page) {
            'home' => 'Learn through play',
            'scoreboard' => 'Scoreboard',
            default => 'Pixelwar',
        };
    }
}
```

Then require and instantiate it in `submission.php` if the layout needs it.

### Example: Add A Reusable Notice Component

Create `components/notice.php`:

```php
<section class="container py-4">
    <div class="rounded-[24px] border-4 border-arcade-ink/10 bg-arcade-panel p-5 text-arcade-ink shadow-arcade">
        <?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
</section>
```

Then include it from a page:

```php
<?php
$message = 'This is a reusable notice.';
include __DIR__ . '/../components/notice.php';
```
