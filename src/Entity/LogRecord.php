<?php

/*
 * This file is part of the CleverAge/ProcessBundleDemo package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use CleverAge\UiProcessBundle\Entity\LogRecord as BaseLogRecord;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table]
class LogRecord extends BaseLogRecord
{
}
