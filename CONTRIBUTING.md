Contributing
============

First of all, **thank you** for contributing, **you are awesome**!

Here are a few rules to follow in order to ease code reviews, and discussions before
maintainers accept and merge your work.

This project demonstrates the features of `cleverage/process-bundle` and of its bridge bundles: a feature (or the
reproduction of a bug, a regression check) is demonstrated by a process configuration, named with a `demo.` prefix,
in its own file under `config/packages/process/` (one file per process, or per family of processes sharing a prefix).
Give it a `description` and a `help` with the command to run and the expected result. PHP code under `src/` is only
the glue the processes need (entities, fixtures, DTOs...).

A bug or a feature of a bundle is fixed or developed in the bundle itself (its clone under `vendor/cleverage/`), not
in this project.

You MUST run the quality & test suites.

Please, write [commit messages that make sense](https://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html),
and [rebase your branch](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) before submitting your Pull Request.

One may ask you to [squash your commits](https://gitready.com/advanced/2009/02/10/squashing-commits-with-rebase.html)
too. This is used to "clean" your Pull Request before merging it (we don't want
commits such as `fix tests`, `fix 2`, `fix 3`, etc.).

Thank you!

## Running the quality & test suites

The project runs in Docker (`.docker/compose.yaml`): no need to have PHP installed. The **Makefile** runs the commands
in the containers:

```bash
make quality
```

```bash
make tests
```

`make tests` checks that the container compiles and that the configuration of every demo process is valid.
