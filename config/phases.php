<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Business Phases
|--------------------------------------------------------------------------
|
| The six phases from preparation to running, in order.
|
| `scope` says where a phase lives:
|  - business: happens once, for the brand as a whole.
|  - collection: repeats for every collection. A collection is one
|    production run of one couple set (a women's and a men's model).
|
| A phase can only be closed once the phase before it is closed and every
| one of its gate tasks is done. For a collection the phase before
| "design" is the business's brand phase.
|
| `tasks` is the starter checklist copied into a new business or
| collection. Titles live in lang/id/phases.php under
| "<phase>.tasks.<task>". Once copied they are ordinary tasks the owner can
| rename, delete or add to.
|
*/

return [

    'foundation' => [
        'scope' => 'business',
        'icon' => 'sparkles',
        'tasks' => [
            'research_market' => ['gate' => true],
            'research_competitors' => ['gate' => false],
            'positioning' => ['gate' => true],
            'name_candidates' => ['gate' => false],
            'name_check' => ['gate' => false],
            'name_final' => ['gate' => true],
            'logo' => ['gate' => true],
            'brand_kit' => ['gate' => false],
            'label_design' => ['gate' => false],
            'hangtag_design' => ['gate' => false],
            'trademark' => ['gate' => false],
        ],
    ],

    'design' => [
        'scope' => 'collection',
        'icon' => 'shirt',
        'tasks' => [
            'moodboard' => ['gate' => false],
            'models' => ['gate' => true],
            'matching' => ['gate' => false],
            'tech_pack' => ['gate' => true],
            'fabric_research' => ['gate' => false],
            'fabric_test' => ['gate' => false],
            'fabric_final' => ['gate' => true],
            'size_chart' => ['gate' => true],
            'description' => ['gate' => false],
        ],
    ],

    'sourcing' => [
        'scope' => 'collection',
        'icon' => 'briefcase',
        'tasks' => [
            'maker_shortlist' => ['gate' => false],
            'maker_quotes' => ['gate' => false],
            'sample_order' => ['gate' => false],
            'sample_review' => ['gate' => false],
            'sample_approved' => ['gate' => true],
            'maker_deal' => ['gate' => true],
            'label' => ['gate' => true],
            'hangtag' => ['gate' => true],
            'packaging' => ['gate' => false],
        ],
    ],

    'costing' => [
        'scope' => 'collection',
        'icon' => 'hand-coins',
        'tasks' => [
            'cogs' => ['gate' => true],
            'channel_fees' => ['gate' => false],
            'price' => ['gate' => true],
            'margin_check' => ['gate' => false],
            'break_even' => ['gate' => false],
            'capital_ready' => ['gate' => true],
        ],
    ],

    'production' => [
        'scope' => 'collection',
        'icon' => 'layers',
        'tasks' => [
            'purchase_order' => ['gate' => true],
            'production_tracking' => ['gate' => false],
            'quality_check' => ['gate' => true],
            'photoshoot' => ['gate' => false],
            'launch_content' => ['gate' => false],
            'platform_setup' => ['gate' => true],
            'teaser' => ['gate' => false],
        ],
    ],

    'launch' => [
        'scope' => 'collection',
        'icon' => 'trending-up',
        'tasks' => [
            'launch_day' => ['gate' => true],
            'ads' => ['gate' => false],
            'endorsement' => ['gate' => false],
            'first_review' => ['gate' => true],
            'restock_plan' => ['gate' => false],
        ],
    ],

];
