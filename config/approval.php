<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Approval Link Lifetime
    |--------------------------------------------------------------------------
    |
    | Number of days the signed approve/reject links sent to approvers
    | remain valid before they are refused.
    |
    */

    'link_expiry_days' => (int) env('APPROVAL_LINK_EXPIRY_DAYS', 7),

];
