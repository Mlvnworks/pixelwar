# Custom Setting Priority

Use this file to define project-level coding priorities and any additional instructions that should be followed during development.

## Default

### DRY (Don't Repeat Yourself)

Every piece of knowledge or logic should have a single, unambiguous representation within the system.

### KISS (Keep It Simple, Stupid)

Prefer the simplest solution that correctly solves the current problem.

### Refactoring

Continuously clean up and restructure existing code without changing external behavior, especially when removing duplication and improving clarity.

### YAGNI (You Ain't Gonna Need It)

Do not add functionality until it is actually needed.

### Separation of Concerns (SoC)

Keep each file, class, and function focused on one responsibility.

## Custom

Add project-specific rules below this section.

- Prefer reusable logic in `classes/` over repeating logic in `pages/`
- Keep request handling inside `submissions/`
- Keep configuration inside `.env` and `config.php`
- Keep shared UI inside `components/`

- always prioritize using tailwind in styling
- you can use libraries for animation, icons etc...
- use retro & arcade light theme.
- Improve UI/UX

- website description: gamified CSS game for students
