<?php

declare(strict_types=1);

return [

    // The size rows a new model's chart starts with. Measurement points
    // are per gender, in lang/id/products.php under "default_points".
    'default_sizes' => ['S', 'M', 'L', 'XL'],

    // A sample measurement further than this from the size chart, in cm,
    // is flagged for revision.
    'sample_tolerance_cm' => 1,

    // Photos and swatches: what a phone camera produces, within reason.
    'image_max_kb' => 5120,
    'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],

];
