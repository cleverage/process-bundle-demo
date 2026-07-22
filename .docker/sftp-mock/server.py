#!/usr/bin/env python3
"""
Minimal mock SFTP server (paramiko) used to reproduce the EXACT flysystem-process-bundle
issue #24 error "Expected NET_SFTP_HANDLE or NET_SFTP_STATUS. Got packet type".

It serves files normally, but after a short idle period on an established SFTP
session it injects one stray SFTP packet (SSH_FXP_NAME) onto the channel WHILE
KEEPING THE SSH TRANSPORT OPEN. This mimics enterprise SFTP servers (e.g. the
client's SAP server) that expire the SFTP session / leave a stray or late response
instead of tearing down the TCP transport the way OpenSSH does. phpseclib then reads
that stray packet on the next reused operation and raises the issue #24 error
(whereas a clean OpenSSH disconnect yields "Connection closed prematurely").
"""
import os
import socket
import threading
import time

import paramiko
from paramiko.sftp import CMD_NAME

ROOT = os.environ.get("SFTP_MOCK_ROOT", "/data")
IDLE = float(os.environ.get("SFTP_MOCK_IDLE", "4"))
HOST_KEY = paramiko.RSAKey.generate(2048)


class Server(paramiko.ServerInterface):
    def check_auth_password(self, username, password):
        if username == "sftp" and password == "password":
            return paramiko.AUTH_SUCCESSFUL
        return paramiko.AUTH_FAILED

    def get_allowed_auths(self, username):
        return "password"

    def check_channel_request(self, kind, chanid):
        if kind == "session":
            return paramiko.OPEN_SUCCEEDED
        return paramiko.OPEN_FAILED_ADMINISTRATIVELY_PROHIBITED


class StubSFTPHandle(paramiko.SFTPHandle):
    def stat(self):
        try:
            return paramiko.SFTPAttributes.from_stat(os.fstat(self.readfile.fileno()))
        except OSError as e:
            return paramiko.SFTPServer.convert_errno(e.errno)


class StubSFTPServer(paramiko.SFTPServerInterface):
    def _realpath(self, path):
        return ROOT + self.canonicalize(path)

    def list_folder(self, path):
        p = self._realpath(path)
        out = []
        for fname in os.listdir(p):
            attr = paramiko.SFTPAttributes.from_stat(os.stat(os.path.join(p, fname)))
            attr.filename = fname
            out.append(attr)
        return out

    def stat(self, path):
        try:
            return paramiko.SFTPAttributes.from_stat(os.stat(self._realpath(path)))
        except OSError as e:
            return paramiko.SFTPServer.convert_errno(e.errno)

    def lstat(self, path):
        try:
            return paramiko.SFTPAttributes.from_stat(os.lstat(self._realpath(path)))
        except OSError as e:
            return paramiko.SFTPServer.convert_errno(e.errno)

    def open(self, path, flags, attr):
        p = self._realpath(path)
        try:
            fd = os.open(p, flags, 0o666)
        except OSError as e:
            return paramiko.SFTPServer.convert_errno(e.errno)
        if flags & os.O_WRONLY:
            fstr = "ab" if flags & os.O_APPEND else "wb"
        elif flags & os.O_RDWR:
            fstr = "a+b" if flags & os.O_APPEND else "r+b"
        else:
            fstr = "rb"
        try:
            f = os.fdopen(fd, fstr)
        except OSError as e:
            return paramiko.SFTPServer.convert_errno(e.errno)
        fobj = StubSFTPHandle(flags)
        fobj.filename = p
        fobj.readfile = f
        fobj.writefile = f
        return fobj


class InjectingSFTPServer(paramiko.SFTPServer):
    """Serves normally, then injects one stray SFTP packet after IDLE seconds."""

    def start_subsystem(self, name, transport, channel):
        self._last = time.time()
        self._seen = False
        self._injected = False
        self._alive = True
        threading.Thread(target=self._watch, daemon=True).start()
        try:
            super().start_subsystem(name, transport, channel)
        finally:
            self._alive = False

    def _read_packet(self):
        self._last = time.time()
        self._seen = True
        return super()._read_packet()

    def _watch(self):
        while getattr(self, "_alive", False):
            time.sleep(0.5)
            if self._seen and not self._injected and (time.time() - self._last) > IDLE:
                try:
                    # Stray SSH_FXP_NAME (type 104): body just needs valid framing;
                    # phpseclib reads the type, expects HANDLE/STATUS, and throws.
                    msg = paramiko.Message()
                    msg.add_int(0)  # request id
                    msg.add_int(0)  # count
                    self._send_packet(CMD_NAME, msg)
                    print("[sftp-mock] injected stray NAME packet after idle", flush=True)
                except Exception as exc:  # noqa: BLE001
                    print(f"[sftp-mock] inject failed: {exc}", flush=True)
                self._injected = True


def handle(client, addr):
    try:
        t = paramiko.Transport(client)
        t.add_server_key(HOST_KEY)
        t.set_subsystem_handler("sftp", InjectingSFTPServer, StubSFTPServer)
        t.start_server(server=Server())
        while t.is_active():
            time.sleep(1)
    except Exception as exc:  # noqa: BLE001
        print(f"[sftp-mock] session error: {exc}", flush=True)


def main():
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    sock.bind(("", 22))
    sock.listen(10)
    print(f"[sftp-mock] listening on :22 root={ROOT} idle={IDLE}s", flush=True)
    while True:
        client, addr = sock.accept()
        threading.Thread(target=handle, args=(client, addr), daemon=True).start()


if __name__ == "__main__":
    main()
