<?php

declare(strict_types=1);

return [

    /*
    | Sales channels a new business starts with. Fees are rough estimates
    | for fashion sellers and change often: the owner is told to check the
    | current numbers in each Seller Center and edit them.
    */
    'default_channels' => [
        ['name' => 'Shopee', 'fee_percent' => '10', 'fixed_fee' => '0', 'is_active' => true],
        ['name' => 'TikTok Shop', 'fee_percent' => '8', 'fixed_fee' => '0', 'is_active' => true],
        ['name' => 'Tokopedia', 'fee_percent' => '8', 'fixed_fee' => '0', 'is_active' => false],
        ['name' => 'Website', 'fee_percent' => '2.5', 'fixed_fee' => '0', 'is_active' => false],
        ['name' => 'Langsung (IG/WA)', 'fee_percent' => '0', 'fixed_fee' => '0', 'is_active' => false],
    ],

    // Per-piece cost lines a new model starts with, at Rp 0.
    'default_cost_items' => ['Label', 'Hangtag', 'Packaging', 'Aksesoris'],

    // Suggested prices are rounded up to a multiple of this.
    'price_rounding' => 1000,

];
