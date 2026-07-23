#!/bin/bash
# Shorten the sshd idle timeout for this dedicated SFTP service so that
# demo.sftp_stale_connection can reproduce flysystem-process-bundle issue #24
# (stale cached SFTP connection) in a few seconds instead of the ~60 minutes
# reported in the wild. Run by the atmoz/sftp entrypoint (/etc/sftp.d/*) before
# sshd starts. Only this "short timeout" SFTP service mounts it; the normal
# `sftp` service keeps OpenSSH defaults.
set -e
{
    echo "ClientAliveInterval 3"
    echo "ClientAliveCountMax 1"
} >> /etc/ssh/sshd_config
