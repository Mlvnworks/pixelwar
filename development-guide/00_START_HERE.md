# PHVN Development Guide

All project instructions live in this folder and apply to the current gamified CSS game system for students.

## Available Guides

- `01_SETUP_AND_ENVIRONMENT.md` - environment setup, local workflow, app direction, and development rules
- `02_STRUCTURE_AND_RUNTIME.md` - codebase structure, bootstrap flow, and where each part belongs
- `03_EXTENSION_AND_EXAMPLES.md` - examples for adding pages, handlers, shared helpers, and gameplay-style features
- `CUSTOM_SETTING_PRIORITY.md` - default principles plus the active project-specific priorities

## Recommended Reading Order

1. `00_START_HERE.md`
2. `CUSTOM_SETTING_PRIORITY.md`
3. `01_SETUP_AND_ENVIRONMENT.md`
4. `02_STRUCTURE_AND_RUNTIME.md`
5. `03_EXTENSION_AND_EXAMPLES.md`

## Development Guide

Use this folder as the single source of truth for:

- project setup
- file placement rules
- runtime flow
- extension examples
- active UI and architecture priorities

## Example Use Case

If you want to add a new student-facing feature such as a score page or a gameplay UI block:

1. Open `CUSTOM_SETTING_PRIORITY.md` first to confirm the visual and structural rules
2. Open `02_STRUCTURE_AND_RUNTIME.md` to see where `pages/`, `components/`, `classes/`, and `submissions/` are used
3. Open `03_EXTENSION_AND_EXAMPLES.md` to copy the page and handler pattern
4. Open `01_SETUP_AND_ENVIRONMENT.md` if the feature needs database or mail configuration
