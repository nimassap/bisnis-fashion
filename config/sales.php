<?php

declare(strict_types=1);

return [

    // A variant with this many pieces or fewer left is flagged for restock.
    'low_stock_threshold' => 3,

    // Sold share of what was produced that counts as selling fast.
    'fast_sell_through_percent' => 70,

    // The cash book category an order's money is recorded under.
    'cash_category' => 'Penjualan',

    'max_order_lines' => 20,

];
