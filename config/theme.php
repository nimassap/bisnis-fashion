<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Theme Presets
|--------------------------------------------------------------------------
|
| Every colour the interface uses is derived from the seven tokens below.
| Components never hard-code a colour: they reference CSS variables that
| this configuration feeds, which is what makes Custom Theme possible.
|
| Semantic status colours (success / warning / danger / info) are declared
| in CSS instead of here, so a custom palette can never make "over budget"
| indistinguishable from "on track".
|
*/

/*
| Neutrals shared by the coloured presets.
|
| Each preset overrides `background` with the same grey carrying a few percent
| of its own primary. It is a few points of saturation, not a colour — but it
| is the difference between cards laid on a surface that belongs to the theme
| and cards laid on the same slab of grey whatever palette is chosen.
*/
$neutralLight = [
    'background' => '#f6f7f9',
    'card' => '#ffffff',
    'sidebar' => '#ffffff',
    'text' => '#0f172a',
    'border' => '#e2e8f0',
];

$neutralDark = [
    'background' => '#0b1120',
    'card' => '#141d33',
    'sidebar' => '#0f172a',
    'text' => '#e6edf8',
    'border' => '#26324c',
];

return [

    'modes' => ['light', 'dark', 'system', 'custom'],

    'default_mode' => 'system',

    'default_preset' => 'blue',

    'tokens' => ['primary', 'accent', 'background', 'card', 'sidebar', 'text', 'border'],

    /*
    |--------------------------------------------------------------------------
    | Contrast Floor
    |--------------------------------------------------------------------------
    |
    | Minimum WCAG contrast ratio a custom palette must keep between text and
    | its background before the appearance screen warns the user.
    |
    */

    'minimum_contrast_ratio' => 4.5,

    /*
    |--------------------------------------------------------------------------
    | Status Colours
    |--------------------------------------------------------------------------
    |
    | Deliberately outside the user's control. If a custom palette could recolour
    | "over budget", the warning would stop meaning anything.
    |
    */

    'status' => [

        /*
        | Muted on purpose.
        |
        | These colours cover large areas — chart bars, progress fills, the
        | expense figure on every row — and a saturated traffic-light red and
        | green shout across a page of money. A deep sage and a warm brick say
        | the same thing quietly, and hold the same meaning.
        |
        | Each one still clears 4.5:1 against the card it is read on, because
        | they carry text as well as fill shapes.
        */
        'light' => [
            'success' => '#1f7a5a',
            'warning' => '#a16207',
            'danger' => '#b0413e',
            'info' => '#2a6f97',
        ],
        'dark' => [
            'success' => '#7bd3ad',
            'warning' => '#e8b563',
            'danger' => '#eb9088',
            'info' => '#7fc3e8',
        ],
    ],

    'presets' => [

        'blue' => [
            'label' => 'Blue',
            'light' => ['primary' => '#2563eb', 'accent' => '#0284c7', 'background' => '#eef1f8'] + $neutralLight,
            'dark' => ['primary' => '#60a5fa', 'accent' => '#38bdf8', 'background' => '#0f182b'] + $neutralDark,
        ],

        'emerald' => [
            'label' => 'Emerald',
            'light' => ['primary' => '#047857', 'accent' => '#0d9488', 'background' => '#ecf2f3'] + $neutralLight,
            'dark' => ['primary' => '#34d399', 'accent' => '#5eead4', 'background' => '#0d1b26'] + $neutralDark,
        ],

        'purple' => [
            'label' => 'Purple',
            'light' => ['primary' => '#7c3aed', 'accent' => '#9333ea', 'background' => '#f1eff9'] + $neutralLight,
            'dark' => ['primary' => '#a78bfa', 'accent' => '#c084fc', 'background' => '#13172b'] + $neutralDark,
        ],

        'rose' => [
            'label' => 'Rose',
            'light' => ['primary' => '#e11d48', 'accent' => '#db2777', 'background' => '#f5eef2'] + $neutralLight,
            'dark' => ['primary' => '#fb7185', 'accent' => '#f472b6', 'background' => '#171625'] + $neutralDark,
        ],

        'orange' => [
            'label' => 'Orange',
            'light' => ['primary' => '#ea580c', 'accent' => '#d97706', 'background' => '#f6f1f0'] + $neutralLight,
            'dark' => ['primary' => '#fb923c', 'accent' => '#fbbf24', 'background' => '#171721'] + $neutralDark,
        ],

        'cyan' => [
            'label' => 'Cyan',
            'light' => ['primary' => '#0891b2', 'accent' => '#0e7490', 'background' => '#ecf3f6'] + $neutralLight,
            'dark' => ['primary' => '#22d3ee', 'accent' => '#67e8f9', 'background' => '#0c1b2a'] + $neutralDark,
        ],

        /*
        | Pine and moss on warm paper. Emerald runs bright and cool; this one
        | is the same colour after dark.
        */
        'forest' => [
            'label' => 'Forest',
            'light' => [
                'primary' => '#3d6b4a',
                'accent' => '#5f8f60',
                'background' => '#edf2ec',
                'card' => '#fdfefc',
                'sidebar' => '#f4f7f2',
                'text' => '#29332b',
                'border' => '#dbe4d9',
            ],
            'dark' => [
                'primary' => '#86c193',
                'accent' => '#a8d4ac',
                'background' => '#101710',
                'card' => '#1a241b',
                'sidebar' => '#0c110c',
                'text' => '#e4eee5',
                'border' => '#2c3a2e',
            ],
        ],

        /*
        | Violet with the volume down, on lilac paper.
        */
        'lavender' => [
            'label' => 'Lavender',
            'light' => [
                'primary' => '#6558a8',
                'accent' => '#8b7cc8',
                'background' => '#efedf8',
                'card' => '#fefdff',
                'sidebar' => '#f6f4fb',
                'text' => '#2e2a3f',
                'border' => '#e2ddf0',
            ],
            'dark' => [
                'primary' => '#b0a3e0',
                'accent' => '#c7bcec',
                'background' => '#12111c',
                'card' => '#1d1b2b',
                'sidebar' => '#0e0d16',
                'text' => '#e8e5f4',
                'border' => '#302d45',
            ],
        ],

        /*
        | The one palette whose two colours are not neighbours: teal into
        | violet. Every gradient in the interface — the mark, the avatar, the
        | main button — crosses the whole way between them.
        */
        'aurora' => [
            'label' => 'Aurora',
            'light' => [
                'primary' => '#0f7a76',
                'accent' => '#7c5cc4',
                'background' => '#e9f3f2',
                'card' => '#ffffff',
                'sidebar' => '#f4faf9',
                'text' => '#1f2d33',
                'border' => '#d5e6e4',
            ],
            'dark' => [
                'primary' => '#5fd0c4',
                'accent' => '#ab8ef0',
                'background' => '#0c1618',
                'card' => '#152325',
                'sidebar' => '#091214',
                'text' => '#dff0ef',
                'border' => '#24393c',
            ],
        ],

        /*
        | A deep jewel red. Rose is a bright pink; this is the darker, older
        | version of the same idea.
        */
        'ruby' => [
            'label' => 'Ruby',
            'light' => [
                'primary' => '#9b2c42',
                'accent' => '#b94a61',
                'background' => '#f6ecee',
                'card' => '#fffcfd',
                'sidebar' => '#fbf2f4',
                'text' => '#352025',
                'border' => '#eed7dc',
            ],
            'dark' => [
                'primary' => '#ea8a9c',
                'accent' => '#f2aab7',
                'background' => '#170f11',
                'card' => '#251719',
                'sidebar' => '#12090b',
                'text' => '#f4e5e8',
                'border' => '#3c272b',
            ],
        ],

        /*
        | Warm neutrals, which is what most of these presets are not. Paper and
        | clay rather than paper and steel.
        */
        'sand' => [
            'label' => 'Sand',
            'light' => [
                'primary' => '#96634a',
                'accent' => '#bf8654',
                'background' => '#f5efe6',
                'card' => '#fffdf9',
                'sidebar' => '#faf4ea',
                'text' => '#3b3128',
                'border' => '#e8dccb',
            ],
            'dark' => [
                'primary' => '#dcab7c',
                'accent' => '#ecc59f',
                'background' => '#17120d',
                'card' => '#231c15',
                'sidebar' => '#120e0a',
                'text' => '#f1e8dc',
                'border' => '#3b3127',
            ],
        ],

        /*
        | Muted arctic blues on cool slate — the quietest of the coloured
        | palettes, for people who want the numbers to do the talking.
        */
        'nordic' => [
            'label' => 'Nordic',
            'light' => [
                'primary' => '#456b8a',
                'accent' => '#6a95ac',
                'background' => '#eaeff4',
                'card' => '#ffffff',
                'sidebar' => '#f6f9fb',
                'text' => '#2e3440',
                'border' => '#dbe4ec',
            ],
            'dark' => [
                'primary' => '#93b8d1',
                'accent' => '#aed0e0',
                'background' => '#141c26',
                'card' => '#1f2a37',
                'sidebar' => '#101720',
                'text' => '#e2e9f1',
                'border' => '#313f4f',
            ],
        ],

        /*
        | Wine and mauve. Deeper and dustier than Rose, which runs bright.
        */
        'plum' => [
            'label' => 'Plum',
            'light' => [
                'primary' => '#8a3a63',
                'accent' => '#b5548c',
                'background' => '#f6ecf2',
                'card' => '#fffcfe',
                'sidebar' => '#fbf2f7',
                'text' => '#3a2331',
                'border' => '#ecd8e4',
            ],
            'dark' => [
                'primary' => '#e08fba',
                'accent' => '#eeacd0',
                'background' => '#170f14',
                'card' => '#26181f',
                'sidebar' => '#130c10',
                'text' => '#f3e5ed',
                'border' => '#3e2a35',
            ],
        ],

        /*
        | Terracotta into amber. Where Orange is a signal colour, this one is
        | the light at the end of the day.
        */
        'sunset' => [
            'label' => 'Sunset',
            'light' => [
                'primary' => '#b34428',
                'accent' => '#d97b3f',
                'background' => '#faeee6',
                'card' => '#fffbf7',
                'sidebar' => '#fdf3ec',
                'text' => '#3a2a21',
                'border' => '#f0dbcd',
            ],
            'dark' => [
                'primary' => '#f28f6b',
                'accent' => '#f7b980',
                'background' => '#19110c',
                'card' => '#261911',
                'sidebar' => '#140d09',
                'text' => '#f6e7dc',
                'border' => '#3f2b20',
            ],
        ],

        'monochrome' => [
            'label' => 'Monochrome',
            'light' => [
                'primary' => '#1f2937',
                'accent' => '#4b5563',
                'background' => '#eceded',
                'card' => '#ffffff',
                'sidebar' => '#ffffff',
                'text' => '#111827',
                'border' => '#e5e5e5',
            ],
            'dark' => [
                'primary' => '#e5e7eb',
                'accent' => '#9ca3af',
                'background' => '#151515',
                'card' => '#171717',
                'sidebar' => '#111111',
                'text' => '#fafafa',
                'border' => '#262626',
            ],
        ],

        'midnight' => [
            'label' => 'Midnight',
            'light' => [
                'primary' => '#4338ca',
                'accent' => '#6366f1',
                'background' => '#ececfd',
                'card' => '#ffffff',
                'sidebar' => '#ffffff',
                'text' => '#1e1b4b',
                'border' => '#e0e7ff',
            ],
            'dark' => [
                'primary' => '#818cf8',
                'accent' => '#a5b4fc',
                'background' => '#0c1122',
                'card' => '#101830',
                'sidebar' => '#0a1024',
                'text' => '#dbe3f5',
                'border' => '#1e2a4a',
            ],
        ],

    ],

];
