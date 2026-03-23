<?php

return [
    /*
    | Minimum units per line item when ordering souvenirs (cannot be overridden below this).
    */
    'min_purchase_quantity' => (int) (env('SOUVENIR_MIN_PURCHASE_QTY', 10)),

    /*
    | Free shipping: order must be within city AND subtotal >= this amount (EUR/CHF).
    */
    'free_shipping_min_amount' => (float) (env('SOUVENIR_FREE_SHIPPING_MIN', 1000)),

    /*
    | Default shipping charge when not eligible for free shipping (EUR/CHF).
    */
    'default_shipping_charge' => (float) (env('SOUVENIR_SHIPPING_CHARGE', 25)),

    /*
    | Minimum days from today for requested delivery to be accepted without "request review".
    | If requested_delivery_date is closer, we set delivery_too_close = true and message shown.
    */
    'min_delivery_days' => (int) (env('SOUVENIR_MIN_DELIVERY_DAYS', 3)),

    /*
    | Cities considered "within city" for free shipping (comma-separated or array).
    */
    'within_city_names' => array_filter(array_map('trim', explode(',', env('SOUVENIR_WITHIN_CITY', 'Zurich,Geneva,Bern')))),
];
