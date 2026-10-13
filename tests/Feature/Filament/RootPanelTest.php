<?php

declare(strict_types=1);

use Filament\Facades\Filament;

test('login page renders through the panel middleware stack', function () {
    $this->get(Filament::getPanel('root')->getLoginUrl())->assertOk();
});
