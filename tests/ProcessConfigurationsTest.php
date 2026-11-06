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

namespace App\Tests;

use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Smoke test of the demo: the container compiles and the configuration of every demo process is valid (task codes,
 * references between tasks, options of the process).
 */
#[CoversNothing]
final class ProcessConfigurationsTest extends KernelTestCase
{
    public function testEveryDemoProcessIsValid(): void
    {
        self::bootKernel();
        $registry = self::getContainer()->get('cleverage_process.registry.process_configuration');
        self::assertInstanceOf(ProcessConfigurationRegistry::class, $registry);

        $codes = $this->getDemoProcessCodes();
        self::assertNotEmpty($codes);
        foreach ($codes as $code) {
            self::assertSame($code, $registry->getProcessConfiguration($code)->getCode());
        }
    }

    /**
     * The codes of the processes defined under config/packages/process/.
     *
     * @return list<string>
     */
    private function getDemoProcessCodes(): array
    {
        $codes = [];
        $files = (new Finder())->files()->in(\dirname(__DIR__).'/config/packages/process')->name('*.yaml');
        foreach ($files as $file) {
            /** @var array{clever_age_process?: array{configurations?: array<string, mixed>}} $config */
            $config = Yaml::parseFile($file->getPathname(), Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS);
            foreach (array_keys($config['clever_age_process']['configurations'] ?? []) as $code) {
                $codes[] = (string) $code;
            }
        }
        sort($codes);

        return $codes;
    }
}
