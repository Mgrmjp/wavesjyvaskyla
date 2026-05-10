# Code Standards

## Baseline Rules

- Use spaces for indentation.
- Use LF line endings.
- Use UTF-8 encoding.
- Keep trailing whitespace trimmed (except Markdown line breaks).
- Use 2 spaces by default; use 4 spaces in PHP files.

## Formatting

Formatting is enforced with Prettier for:

- CSS
- JavaScript / MJS
- JSON
- Markdown
- YAML

Run:

```bash
npm run format
```

Validate formatting without changing files:

```bash
npm run format:check
```

## Linting

- JavaScript linting: ESLint
- CSS linting: Stylelint (Tailwind at-rules supported)

Run all checks:

```bash
npm run lint
```
