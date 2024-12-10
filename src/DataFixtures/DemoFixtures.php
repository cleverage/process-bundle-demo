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

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DemoFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $author1 = new Author();
        $author1->setFirstName('Stephen');
        $author1->setLastname('King');
        $manager->persist($author1);

        $book1 = new Book();
        $book1->setAuthor($author1);
        $book1->setTitle('It');
        $manager->persist($book1);

        $book2 = new Book();
        $book2->setAuthor($author1);
        $book2->setTitle('Salem');
        $manager->persist($book2);

        $author2 = new Author();
        $author2->setFirstName('Ray');
        $author2->setLastname('Bradbury');
        $manager->persist($author2);

        $book3 = new Book();
        $book3->setAuthor($author2);
        $book3->setTitle('Fahrenheit 451');
        $manager->persist($book3);

        $author3 = new Author();
        $author3->setFirstName('Bram');
        $author3->setLastname('Stoker');
        $manager->persist($author3);

        $book4 = new Book();
        $book4->setAuthor($author3);
        $book4->setTitle('Dracula');
        $manager->persist($book4);

        $manager->flush();
    }
}
