<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true,
    )
    ->withSkip([
        // DeleteRecordUseCase keeps its (now unused) repository ctor argument for BC.
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector::class => [
            __DIR__ . '/src/Application/UseCases/Entity/DeleteRecordUseCase.php',
        ],
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector::class => [
            __DIR__ . '/src/Application/UseCases/Entity/DeleteRecordUseCase.php',
        ],
        // Rector тут типує властивості як MockObject, але краще тримати інтерфейсний тип:
        // PHPUnit's createMock(X::class) повертає MockObject який також є X, і PHPStan
        // через інтерфейсний тип краще верифікує argument compatibility.
        \Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector::class,
    ])
    ->withImportNames(removeUnusedImports: true);
