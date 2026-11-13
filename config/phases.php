<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Steps
|--------------------------------------------------------------------------
|
| Setup Brand happens once per business; the nine steps after it repeat for
| every collection (one couple set). Order matters: a step can only be
| closed once the step before it is closed and its requirements are met.
|
| Requirements come from data the owner fills in (see StepRequirements), not
| from ticking boxes. `tasks` is only for things with nothing to fill in;
| titles live in lang/id/phases.php under "<step>.tasks.<task>". The owner
| can add their own checklist items on any step.
|
*/

return [

    'setup' => [
        'scope' => 'business',
        'icon' => 'sparkles',
        'tasks' => [],
    ],

    'model' => [
        'scope' => 'collection',
        'icon' => 'shirt',
        'tasks' => [],
    ],

    'maker' => [
        'scope' => 'collection',
        'icon' => 'briefcase',
        'tasks' => [],
    ],

    'fabric' => [
        'scope' => 'collection',
        'icon' => 'layers',
        'tasks' => [],
    ],

    'trims' => [
        'scope' => 'collection',
        'icon' => 'tag',
        'tasks' => [],
    ],

    'sampling' => [
        'scope' => 'collection',
        'icon' => 'refresh',
        'tasks' => [],
    ],

    'costing' => [
        'scope' => 'collection',
        'icon' => 'hand-coins',
        'tasks' => [],
    ],

    'production' => [
        'scope' => 'collection',
        'icon' => 'zap',
        'tasks' => [
            'send_golden_sample' => ['gate' => false],
        ],
    ],

    'prelaunch' => [
        'scope' => 'collection',
        'icon' => 'camera',
        'tasks' => [
            'store_decoration' => ['gate' => false],
            'shipping_payment' => ['gate' => false],
            'teaser' => ['gate' => false],
        ],
    ],

    'launch' => [
        'scope' => 'collection',
        'icon' => 'trending-up',
        'tasks' => [],
    ],

];
