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

namespace App\Adapter;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class MemoryAdapter extends Adapter
{
    public function __construct()
    {
        $cache = new ArrayAdapter(
            // the default lifetime (in seconds) for cache items that do not define their
            // own lifetime, with a value 0 causing items to be stored indefinitely (i.e.
            // until the current PHP process finishes)
            $defaultLifetime = 0,

            // if true, the values saved in the cache are serialized before storing them
            $storeSerialized = true,

            // the maximum lifetime (in seconds) of the entire cache (after this time, the
            // entire cache is deleted to avoid stale data from consuming memory)
            $maxLifetime = 0,

            // the maximum number of items that can be stored in the cache. When the limit
            // is reached, cache follows the LRU model (least recently used items are deleted)
            $maxItems = 0
        );
        parent::__construct($cache, 'memory');
    }
}
