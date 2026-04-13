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
            'home' => APP_NAME . ' | Pixelwar Game Test',
            default => APP_NAME,
        };
    }

    public function descriptionFor(string $page): string
    {
        return match ($page) {
            'home' => 'Pixelwar game test for students built inside the PHVN PHP page routing system.',
            default => $this->siteDescription(),
        };
    }
}
