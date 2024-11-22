<?php

/*
 * This file is part of the CleverAge/ProcessBundleDemo package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use CleverAge\UiProcessBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordEncoder)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@clever-age.com');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($this->passwordEncoder->hashPassword($admin, 'admin@clever-age.com'));

        $manager->persist($admin);

        $manager->flush();
    }
}
