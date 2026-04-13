<?php

class ChallengeCatalog
{
    public static function all(): array
    {
        return [
            'button-border-basics' => [
                'slug' => 'button-border-basics',
                'title' => 'Button Border Basics',
                'level' => 'Easy',
                'levelClass' => 'challenge-difficulty--easy',
                'focus' => 'border, radius, padding',
                'estimate' => '5 min',
                'reward' => '+20 pts',
                'author' => 'Mika Reyes',
                'description' => 'Rebuild a tiny call-to-action button by placing the exact CSS rules in the right selector.',
                'objective' => 'Match a compact CTA button by placing border, radius, spacing, and color rules in the correct selector.',
                'instructions' => [
                    'Inspect the target design before starting the arena.',
                    'Watch for the border thickness, rounded corners, and button padding.',
                    'Place only the CSS properties that belong to the highlighted selector.',
                    'Use the live preview to compare your placement against the static target.',
                ],
                'mechanics' => [
                    'Drag a property chip into the selector container where it belongs.',
                    'Incorrect properties are marked early so you can remove them before finishing.',
                    'The challenge is complete when all required properties are placed correctly.',
                ],
                'comments' => [
                    [
                        'player' => 'PixelRookie',
                        'posted' => '2 hours ago',
                        'body' => 'Good starter challenge. The padding and border radius are the parts to watch first.',
                    ],
                    [
                        'player' => 'CSSRunner',
                        'posted' => 'Yesterday',
                        'body' => 'I finished faster after checking the selector highlight before dragging anything.',
                    ],
                    [
                        'player' => 'BorderBuddy',
                        'posted' => '2 days ago',
                        'body' => 'The target preview helped me spot that the radius was larger than my first guess.',
                    ],
                    [
                        'player' => 'TinyCascade',
                        'posted' => '3 days ago',
                        'body' => 'Nice quick challenge. I used it to practice reading the selector cards first.',
                    ],
                ],
            ],
            'card-shadow-match' => [
                'slug' => 'card-shadow-match',
                'title' => 'Card Shadow Match',
                'level' => 'Medium',
                'levelClass' => 'challenge-difficulty--medium',
                'focus' => 'box-shadow, spacing',
                'estimate' => '8 min',
                'reward' => '+35 pts',
                'author' => 'Jules Tan',
                'description' => 'Match a chunky arcade card with a clean border, soft panel background, and pixel-style shadow.',
                'objective' => 'Rebuild an arcade-style card by matching the shadow depth, border, spacing, and panel color.',
                'instructions' => [
                    'Compare the card edge, shadow offset, and inner spacing first.',
                    'Keep layout properties separate from typography properties.',
                    'Use the target hover/click inspector to identify the selector before placing a chip.',
                    'Check the live preview after each placement to avoid stacking wrong rules.',
                ],
                'mechanics' => [
                    'Shared properties are grouped with a count badge in the property tray.',
                    'Dragging a property back to the tray restores its available count.',
                    'Selector containers turn green when their required properties are complete.',
                ],
                'comments' => [
                    [
                        'player' => 'BoxShadowFan',
                        'posted' => '3 hours ago',
                        'body' => 'The shadow offset is the giveaway. Once that matched, the rest was straightforward.',
                    ],
                    [
                        'player' => 'LayoutKid',
                        'posted' => 'Yesterday',
                        'body' => 'I kept putting spacing on the wrong selector. The live preview helped catch it early.',
                    ],
                    [
                        'player' => 'PanelPilot',
                        'posted' => '2 days ago',
                        'body' => 'The card shadow was easier after I stopped adjusting the text rules first.',
                    ],
                    [
                        'player' => 'ArcadeBox',
                        'posted' => '4 days ago',
                        'body' => 'Good medium challenge. The grouped duplicate properties saved me from guessing.',
                    ],
                ],
            ],
            'hero-text-alignment' => [
                'slug' => 'hero-text-alignment',
                'title' => 'Hero Text Alignment',
                'level' => 'Hard',
                'levelClass' => 'challenge-difficulty--hard',
                'focus' => 'text-align, font-size',
                'estimate' => '10 min',
                'reward' => '+50 pts',
                'author' => 'Kai Santos',
                'description' => 'Tune typography and alignment until the live preview lines up with the reference design.',
                'objective' => 'Match the hero typography by placing alignment, size, color, and spacing properties correctly.',
                'instructions' => [
                    'Read the target design from top to bottom: badge, title, subtitle, then CTA.',
                    'Prioritize font size, line height, and alignment before decorative rules.',
                    'Use the selector containers as a checklist for what is missing.',
                    'Finish by comparing vertical rhythm between the title, subtitle, and CTA.',
                ],
                'mechanics' => [
                    'The target design is static; links and buttons inside it do not navigate.',
                    'Hovering or clicking target areas highlights the matching selector container.',
                    'The progress bar updates as correct CSS properties are placed.',
                ],
                'comments' => [
                    [
                        'player' => 'HeroFixer',
                        'posted' => '1 hour ago',
                        'body' => 'This felt harder than it looked. The title spacing was the final piece for me.',
                    ],
                    [
                        'player' => 'TypeTuner',
                        'posted' => '2 days ago',
                        'body' => 'Check line-height before color. That made the target much easier to match.',
                    ],
                    [
                        'player' => 'SelectorMage',
                        'posted' => '3 days ago',
                        'body' => 'Hovering the target before placing each property kept me from mixing title and subtitle styles.',
                    ],
                    [
                        'player' => 'PixelProof',
                        'posted' => '5 days ago',
                        'body' => 'Hard but fair. The final spacing pass is where the design really clicked.',
                    ],
                ],
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        $challenges = self::all();
        return $challenges[$slug] ?? null;
    }

    public static function first(): array
    {
        return array_values(self::all())[0];
    }
}
