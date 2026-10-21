<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundleDemo package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

// "with_constructor_extractor" only exists since Symfony 7.3: not setting it is deprecated there
// (its default changes in 8.0), while Symfony 6.4 rejects it as an unrecognized option.
return static function (ContainerConfigurator $container): void {
    if (Kernel::VERSION_ID >= 70300) {
        $container->extension('framework', [
            'property_info' => ['with_constructor_extractor' => true],
        ]);
    }
};
