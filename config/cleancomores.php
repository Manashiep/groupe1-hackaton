<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redevance de collecte
    |--------------------------------------------------------------------------
    |
    | Montant payé par chaque habitant à chaque passage du camion dans son
    | village. Modifiable sans toucher au code : DUE_AMOUNT=500 dans le .env.
    |
    */

    'due_amount' => (int) env('DUE_AMOUNT', 300),

    'currency' => 'KMF',

];
