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

namespace App\Flysystem;

use League\Flysystem\PhpseclibV3\ConnectivityChecker;
use phpseclib3\Net\SFTP;

class SftpConnectivityChecker implements ConnectivityChecker
{
    public function isConnected(SFTP $connection): bool
    {
        if (!$connection->isConnected()) {
            return false;
        }

        try {
            // A transport-level ping() (SSH2::ping opens a separate keep-alive
            // channel) cannot detect a de-synchronized SFTP stream: if the server
            // left a stray/late packet on the SFTP channel, ping() still succeeds.
            // So probe at the SFTP protocol level - if the stream is desynced this
            // round-trip reads the stray packet and throws, and we force a reconnect.
            $connection->stat('.');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
