# Extension And Handler Examples

## Purpose

This guide gives concrete examples for extending the project using the current structure.

## Development Guide

### Add A New Request Handler

1. Choose the correct panel and domain folder, for example `submissions/auth/` or `teacher/submissions/auth/`
2. Create one endpoint file with this pattern: `<number>_<clear_action>.endpoint.php`
3. Keep the endpoint focused on one action only
4. Keep shared helper functions in the domain loader or a `00_*_helpers.php` file
5. Exit early or return for unmatched requests

### Add A New Shared Helper

1. Create or extend a class in `classes/`
2. Require it in `submission.php`
3. Instantiate it there if it should be globally available during page rendering
4. Prefer helpers when the same page data, labels, or behavior would otherwise be repeated

### Add A New Database Operation

1. Create or extend a categorized repository in `classes/`
2. Put the SQL query inside a clearly named repository method
3. Require the class in the relevant `submission.php`
4. Instantiate it once, for example `$challengeRepository = new ChallengeRepository($connection)`
5. Call the method from the endpoint file

Do not write `prepare()`, raw `SELECT`, raw `INSERT`, raw `UPDATE`, or transaction logic directly inside `submissions/` files.

### Add A New Component

1. Create a PHP partial in `components/`
2. Keep it focused on rendering
3. Pass only the minimum required data into it
4. Keep it aligned with the retro arcade light theme

## Example Use Case

### Example: Add A Score Reset Handler

Create `classes/score-repository.php`:

```php
<?php
class ScoreRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function resetForUser(int $userId): void
    {
        $statement = $this->connection->prepare('UPDATE user_scores SET score = 0 WHERE user_id = ?');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $statement->close();
    }
}
```

Require and instantiate it from `submission.php`.

Create `submissions/game/10_reset_score.endpoint.php`:

```php
<?php
if ($requestMethod !== 'POST') {
    return;
}

if (!isset($_POST['reset_score'])) {
    return;
}

$scoreRepository->resetForUser((int) $_SESSION['user_id']);

$_SESSION['alert'] = [
    'error' => false,
    'content' => 'Score state reset successfully.',
];
```

Then create or update the domain loader `submissions/game.php`:

```php
<?php
$gameEndpointFiles = glob(__DIR__ . '/game/*.endpoint.php') ?: [];
sort($gameEndpointFiles, SORT_NATURAL);

foreach ($gameEndpointFiles as $gameEndpointFile) {
    require $gameEndpointFile;
}
```

The root `submission.php` auto-loads `submissions/game.php`, and the loader auto-loads its endpoint files.

### Example: Add A Teacher Endpoint

Create `teacher/submissions/challenges/10_create_challenge.endpoint.php`:

```php
<?php
if ($teacherRequestMethod !== 'POST' || $teacherRequestedPage !== 'challenges') {
    return;
}

if (!isset($_POST['create_challenge'])) {
    return;
}

$_SESSION['alert'] = [
    'error' => false,
    'content' => 'Challenge draft saved.',
];

teacherPanelRedirect('challenges');
```

Then create or update `teacher/submissions/challenges.php`:

```php
<?php
$teacherChallengeEndpointFiles = glob(__DIR__ . '/challenges/*.endpoint.php') ?: [];
sort($teacherChallengeEndpointFiles, SORT_NATURAL);

foreach ($teacherChallengeEndpointFiles as $teacherChallengeEndpointFile) {
    require $teacherChallengeEndpointFile;
}
```

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
