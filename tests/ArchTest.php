<?php

declare(strict_types=1);

arch('all package code declares strict types')
    ->expect('Simtabi\Laranail\AiCompliance')
    ->toUseStrictTypes();

arch('no debug calls ship')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('enums are string backed')
    ->expect('Simtabi\Laranail\AiCompliance\Enums')
    ->toBeStringBackedEnums();

arch('pipeline classes are final')
    ->expect('Simtabi\Laranail\AiCompliance\Policy')
    ->classes()
    ->toBeFinal();

arch('http controllers are final')
    ->expect('Simtabi\Laranail\AiCompliance\Http')
    ->classes()
    ->toBeFinal();
