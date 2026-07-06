# Contributing

Contributions are welcome via pull requests.

## Development setup

- Run `composer install`; all dependencies (including the laranail toolchain)
  resolve from Packagist.
- The JS packages live under `packages/` as npm workspaces: `npm install`,
  `npm run build`, `npx vitest run`.
- To develop against a local sibling checkout of `laranail/package-tools` or
  `laranail/console`, add a `path` repository to your local `composer.json`
  without committing it (a committed path repository breaks every consumer
  install).

## Process

1. Fork the project and create a branch.
2. Code, test, commit and push.
3. Open a pull request describing your change.

## Guidelines

- Style: run `composer pint-fix` (Laravel Pint preset, `declare(strict_types=1)`).
- Static analysis: `composer phpstan` must pass.
- Refactoring rules: `composer rector` must be clean (dry-run).
- Tests: `composer test` must pass; add tests for new behavior. This package uses
  **Pest** on Orchestra Testbench.
- `composer lint` runs Pint + PHPStan + Rector together.
- Commits follow [Conventional Commits](https://www.conventionalcommits.org/) with a
  scope where one fits, e.g. `feat(consent): ...`, `fix(policies): ...`.
