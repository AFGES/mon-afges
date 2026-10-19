<?php

declare(strict_types=1);

return [
    'action' => 'Export delegates',

    'completed' => [
        'title' => 'Delegate export completed',
        'body' => '{0} No delegate exported.|{1} 1 delegate exported.|[2,*] :count delegates exported.',
        'failed' => '{1} 1 row failed to export.|[2,*] :count rows failed to export.',
    ],
];
