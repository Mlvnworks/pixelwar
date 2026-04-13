<?php

class PageMeta
{
    public function siteDescription(): string
    {
        return 'Gamified CSS game for students';
    }

    public function titleFor(string $page): string
    {
        return match ($page) {
            'landing' => APP_NAME . ' | Learn CSS Through Play',
            'login' => APP_NAME . ' | Login',
            'signup' => APP_NAME . ' | Sign Up',
            'home' => APP_NAME . ' | Home',
            'challenge' => APP_NAME . ' | Challenge Brief',
            'challenges' => APP_NAME . ' | Challenges',
            'player-analytics' => APP_NAME . ' | Player Analytics',
            'settings' => APP_NAME . ' | Settings',
            'pixelwar' => APP_NAME . ' | Pixelwar Game Test',
            default => APP_NAME,
        };
    }

    public function descriptionFor(string $page): string
    {
        return match ($page) {
            'landing' => 'Pixelwar landing page for a gamified CSS learning experience.',
            'login' => 'Login page for Pixelwar learners.',
            'signup' => 'Signup page for Pixelwar learners.',
            'home' => 'Pixelwar learner home page.',
            'challenge' => 'Pixelwar challenge details, instructions, mechanics, and start page.',
            'challenges' => 'Search and filter Pixelwar CSS matching challenges.',
            'player-analytics' => 'Detailed Pixelwar player analytics page.',
            'settings' => 'Update Pixelwar player profile, avatar, and email settings.',
            'pixelwar' => 'Pixelwar game test for students built inside the PHP page routing system.',
            default => $this->siteDescription(),
        };
    }
}
