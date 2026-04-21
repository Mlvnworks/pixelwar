<?php

final class ChallengeCreationService
{
    public function __construct(
        private mysqli $connection,
        private ChallengeRepository $challenges,
        private ActivityLogRepository $activityLogs
    ) {
    }

    /**
     * @param array{name:string,instruction:string,difficulty:string,html:string,css:string} $data
     * @return array{challenge_id:int,html_source:string,css_source:string}
     */
    public function create(int $userId, array $data): array
    {
        $name = trim($data['name']);
        $instruction = trim($data['instruction']);
        $difficultyName = strtolower(trim($data['difficulty']));
        $html = trim($data['html']);
        $css = trim($data['css']);

        $this->validate($name, $instruction, $difficultyName, $html, $css);

        $difficulty = $this->challenges->findDifficultyByName($difficultyName);

        if ($difficulty === null) {
            throw new RuntimeException('Selected difficulty is not available.');
        }

        $baseFileName = 'teacher-' . $userId . '-' . $this->slug($name);
        $htmlStorage = $this->htmlStorage();
        $cssStorage = $this->cssStorage();

        $htmlUrl = '';
        $cssUrl = '';

        try {
            $htmlUrl = $htmlStorage->uploadTextObject($html, $baseFileName, 'html', 'text/html');
            $cssUrl = $cssStorage->uploadTextObject($css, $baseFileName, 'css', 'text/css');

            $this->connection->begin_transaction();

            $challengeId = $this->challenges->createChallenge(
                $userId,
                (int) $difficulty['difficulty_id'],
                $name,
                $instruction,
                $htmlUrl,
                $cssUrl,
                1
            );
            $this->activityLogs->create($userId, 'challenge', 'Created challenge "' . $name . '".');

            $this->connection->commit();

            return [
                'challenge_id' => $challengeId,
                'html_source' => $htmlUrl,
                'css_source' => $cssUrl,
            ];
        } catch (Throwable $err) {
            try {
                $this->connection->rollback();
            } catch (Throwable) {
            }

            foreach ([[$htmlStorage, $htmlUrl], [$cssStorage, $cssUrl]] as [$storage, $url]) {
                if ($storage instanceof SupabaseStorage && is_string($url) && $url !== '') {
                    try {
                        $storage->deletePublicObject($url);
                    } catch (Throwable $deleteError) {
                        error_log('Pixelwar challenge source cleanup failed: ' . $deleteError->getMessage());
                    }
                }
            }

            throw $err;
        }
    }

    /**
     * @param array{name:string,instruction:string,difficulty:string,html:string,css:string} $data
     * @return array{challenge_id:int,html_source:string,css_source:string}
     */
    public function update(int $teacherId, int $challengeId, array $data): array
    {
        if ($challengeId <= 0) {
            throw new InvalidArgumentException('Choose a valid challenge to edit.');
        }

        $existing = $this->challenges->findCreatedChallenge($challengeId);

        if ($existing === null) {
            throw new InvalidArgumentException('Challenge not found.');
        }

        if ((int) $existing['user_id'] !== $teacherId) {
            throw new InvalidArgumentException('You can only edit your own challenge.');
        }

        $name = trim($data['name']);
        $instruction = trim($data['instruction']);
        $difficultyName = strtolower(trim($data['difficulty']));
        $html = trim($data['html']);
        $css = trim($data['css']);

        $this->validate($name, $instruction, $difficultyName, $html, $css);

        $difficulty = $this->challenges->findDifficultyByName($difficultyName);

        if ($difficulty === null) {
            throw new RuntimeException('Selected difficulty is not available.');
        }

        $baseFileName = 'teacher-' . $teacherId . '-challenge-' . $challengeId . '-' . $this->slug($name);
        $htmlStorage = $this->htmlStorage();
        $cssStorage = $this->cssStorage();
        $htmlUrl = '';
        $cssUrl = '';
        $oldHtmlUrl = (string) $existing['html_source'];
        $oldCssUrl = (string) $existing['css_source'];

        try {
            $htmlUrl = $htmlStorage->uploadTextObject($html, $baseFileName, 'html', 'text/html');
            $cssUrl = $cssStorage->uploadTextObject($css, $baseFileName, 'css', 'text/css');

            $this->connection->begin_transaction();

            $this->challenges->updateChallenge(
                $challengeId,
                (int) $difficulty['difficulty_id'],
                $name,
                $instruction,
                $htmlUrl,
                $cssUrl,
                1
            );
            $this->activityLogs->create($teacherId, 'challenge_update', 'Updated challenge "' . $name . '".');

            $this->connection->commit();

            foreach ([[$htmlStorage, $oldHtmlUrl], [$cssStorage, $oldCssUrl]] as [$storage, $url]) {
                if ($storage instanceof SupabaseStorage && is_string($url) && $url !== '') {
                    try {
                        $storage->deletePublicObject($url);
                    } catch (Throwable $deleteError) {
                        error_log('Pixelwar old challenge source cleanup failed: ' . $deleteError->getMessage());
                    }
                }
            }

            return [
                'challenge_id' => $challengeId,
                'html_source' => $htmlUrl,
                'css_source' => $cssUrl,
            ];
        } catch (Throwable $err) {
            try {
                $this->connection->rollback();
            } catch (Throwable) {
            }

            foreach ([[$htmlStorage, $htmlUrl], [$cssStorage, $cssUrl]] as [$storage, $url]) {
                if ($storage instanceof SupabaseStorage && is_string($url) && $url !== '') {
                    try {
                        $storage->deletePublicObject($url);
                    } catch (Throwable $deleteError) {
                        error_log('Pixelwar challenge update cleanup failed: ' . $deleteError->getMessage());
                    }
                }
            }

            throw $err;
        }
    }

    private function validate(string $name, string $instruction, string $difficulty, string $html, string $css): void
    {
        $errors = [];

        if (strlen($name) < 4 || strlen($name) > 150) {
            $errors[] = 'Challenge name must be 4-150 characters.';
        }

        if (strlen($instruction) < 20) {
            $errors[] = 'Instruction must be at least 20 characters.';
        }

        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $errors[] = 'Choose a valid difficulty.';
        }

        if ($html === '') {
            $errors[] = 'HTML source is required.';
        }

        if ($css === '') {
            $errors[] = 'CSS source is required.';
        }

        if (preg_match('/<style\b[\s\S]*?>[\s\S]*?<\/style>/i', $html) === 1) {
            $errors[] = 'HTML must not contain internal CSS.';
        }

        if (preg_match('/\sstyle\s*=\s*([\'"]).*?\1/i', $html) === 1) {
            $errors[] = 'HTML must not contain inline style attributes.';
        }

        if (preg_match('/<link\b[^>]*rel\s*=\s*([\'"])stylesheet\1[^>]*>/i', $html) === 1) {
            $errors[] = 'HTML must not link stylesheets.';
        }

        if (preg_match('/<script\b[\s\S]*?>[\s\S]*?<\/script>/i', $html) === 1) {
            $errors[] = 'HTML must not contain scripts.';
        }

        if (preg_match('/<\/?(?:html|head|body|meta|title)\b/i', $html) === 1) {
            $errors[] = 'HTML must contain target markup only, not full document tags.';
        }

        if (preg_match('/<\/?[a-z][\s\S]*?>/i', $css) === 1) {
            $errors[] = 'CSS must not contain HTML tags.';
        }

        if (preg_match('/javascript\s*:|expression\s*\(|<script\b|<style\b/i', $css) === 1) {
            $errors[] = 'CSS contains unsafe or invalid source.';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }
    }

    private function slug(string $value): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '');
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'challenge';
    }

    private function htmlStorage(): SupabaseStorage
    {
        return new SupabaseStorage(
            SUPABASE_URL,
            SUPABASE_SERVICE_ROLE_KEY,
            SUPABASE_STORAGE_BUCKET,
            SUPABASE_STORAGE_CHALLENGE_HTML_FOLDER
        );
    }

    private function cssStorage(): SupabaseStorage
    {
        return new SupabaseStorage(
            SUPABASE_URL,
            SUPABASE_SERVICE_ROLE_KEY,
            SUPABASE_STORAGE_BUCKET,
            SUPABASE_STORAGE_CHALLENGE_CSS_FOLDER
        );
    }
}
