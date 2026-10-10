# Progress

Update this file at the end of every task that changes the repo: move
finished items to "Done", keep "Next" and "Open questions" current, and keep
the log to the last 10 entries.

## Current state (2026-10-10)

- Discovery docs `docs/00`–`docs/14`: done (Sessions 1–10).
- `docs/15-implementation-phases.md` (Session 11): **not written yet**.
- Code: CodeIgniter 4.7.4 skeleton without Composer appstarter. No app code except `format_helper.php`.
- Claude memory folder, attribution settings, brand assets: added.

## Next

1. Owner answers the open questions below (D-01, D-02, D-04, D-05, D-07 in `decisions.md`).
2. Update `docs/` for the DOCS PENDING decisions (one `docs/` branch).
3. Session 11: write `docs/15-implementation-phases.md`.
4. FASE-00 (Fondasi): appstarter migration, config, filters, layouts, error pages, JS format module matching `format_helper.php`, favicon/head tags in layouts.

## Open questions for the owner

- D-01: Production on Windows + Laragon instead of a Linux VPS — confirm, so `docs/07` §16 and `docs/12` §16 can be rewritten.
- D-02: Bootstrap 5 replaces the custom CSS decision — confirm, and whether the 40 KB CSS budget (UI-71) is dropped or raised.
- D-04: One PR per phase replaces one PR per feature — confirm.
- D-05: Keep Indonesian names that docs already define (tables, routes, methods, CSS classes), or switch all identifiers to English?
- D-07: The landscape logo says "PRIMA" — is that intended for the Spensada app?
- D-03: Approve a minifier library, or keep "vendor `.min` files only, no build step"?

## Log

| Date | Branch | Work |
|---|---|---|
| 2026-10-10 | `sky/zen-heisenberg-d72c3e` | Added `CLAUDE.md`, `.claude/memory/`, `.claude/settings.json` (no attribution, owner git identity), `format_helper.php` + test, logo/favicon/app icons. |
