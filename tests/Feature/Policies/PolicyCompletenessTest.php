<?php

declare(strict_types=1);

dataset('policyClasses', function (): array {
    $policies = [];

    foreach (glob(dirname(__DIR__, 3).'/app/Policies/*Policy.php') as $file) {
        $name = basename($file, '.php');
        $policies[$name] = ["App\\Policies\\{$name}"];
    }

    return $policies;
});

test('declares every Shield policy method', function (string $policyClass) {
    $expectedMethods = [...config('filament-shield.policies.methods'), 'deleteAny'];

    foreach ($expectedMethods as $method) {
        expect(method_exists($policyClass, $method))->toBeTrue("{$policyClass} is missing {$method}()");
    }
})->with('policyClasses');
