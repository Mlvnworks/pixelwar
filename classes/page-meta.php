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
            'forgot-password' => APP_NAME . ' | Forgot Password',
            'signup' => APP_NAME . ' | Sign Up',
            'email-verification' => APP_NAME . ' | Verify Email',
            'profile-setup' => APP_NAME . ' | Account Setup',
            'home' => APP_NAME . ' | Home',
            'challenge' => APP_NAME . ' | Challenge Brief',
            'challenges' => APP_NAME . ' | Challenges',
            'player-analytics' => APP_NAME . ' | Player Analytics',
            'room' => APP_NAME . ' | Room Lobby',
            'settings' => APP_NAME . ' | Settings',
            'versus' => APP_NAME . ' | 1v1 Arena',
            'pixelwar' => APP_NAME . ' | Pixelwar Game Test',
            default => APP_NAME,
        };
    }

    public function descriptionFor(string $page): string
    {
        return match ($page) {
            'landing' => 'Pixelwar landing page for a gamified CSS learning experience.',
            'login' => 'Login page for Pixelwar learners.',
            'forgot-password' => 'Find a Pixelwar account by username or email.',
            'signup' => 'Signup page for Pixelwar learners.',
            'email-verification' => 'Verify a Pixelwar account email with an OTP code.',
            'profile-setup' => 'Complete your Pixelwar account setup details before entering the platform.',
            'home' => 'Pixelwar learner home page.',
            'challenge' => 'Pixelwar challenge details, instructions, mechanics, and start page.',
            'challenges' => 'Search and filter Pixelwar CSS matching challenges.',
            'player-analytics' => 'Detailed Pixelwar player analytics page.',
            'room' => 'Pixelwar room lobby with challenge details and joined players.',
            'settings' => 'Update Pixelwar player profile, avatar, and email settings.',
            'versus' => 'Find online Pixelwar players and send a 1v1 duel invite.',
            'pixelwar' => 'Pixelwar game test for students built inside the PHP page routing system.',
            default => $this->siteDescription(),
        };
    }
}
