# Progress

Update this file at the end of every task that changes the repo: move
finished items to "Done", keep "Next" and "Open questions" current, and keep
the log to the last 10 entries.

## Current state (2026-10-10)

- Discovery docs `docs/00`–`docs/15`: done (Sessions 1–11), merged in PR #14.
- Project license: MIT, copyright Arumi Studios (D-10). `LICENSE` file is still the framework's until FASE-00 L00-01.
- Owner decisions D-01 (Windows + Laragon production), D-02 (Bootstrap 5), D-04 (one PR per phase), D-05 (keep doc-defined names): confirmed and synced into `docs/` (see `decisions.md`).
- Code: CodeIgniter 4.7.4 skeleton without Composer appstarter. No app code except `format_helper.php`.
- Claude memory folder, attribution settings, brand assets: added.
- `README.md`: project README with all implementation stages (replaces the framework README early; `LICENSE` is unchanged until L00-01).

## Next

1. FASE-00 (Fondasi) on branch `fase-00-fondasi`, steps L00-01 to L00-09 in `docs/15` §4.

## Open questions for the owner

- OQ-20 — before FASE-09: production server location (Windows VPS or school machine), approval of an ACME client such as win-acme for Let's Encrypt, and running services via Windows service + Task Scheduler instead of the Laragon app with auto-logon (`docs/07` §16.4).
- D-07: the landscape logo says "PRIMA" — is that intended for the Spensada app?
- D-03: approve a minifier library, or keep "vendor `.min` files only, no build step"?
- School inputs (not urgent yet): sample cards and one station laptop for the FASE-01 kiosk prototype; OQ-08, OQ-18, OQ-19 before the R1 trial.

## Log

| Date | Branch | Work |
|---|---|---|
| 2026-10-10 | `claude/readme-implementation-stages-87jury` | Replaced framework `README.md` with a project README (Indonesian) listing every stage from discovery to R3, from `docs/14` and `docs/15`. L00-01 and L00-09 still own later README updates. |
| 2026-10-10 | `sky/busy-goodall-fswgci` | License decision D-10 (MIT) recorded in `docs/07`, `14`, `15`, `00` and memory. |
| 2026-10-10 | `sky/busy-goodall-fswgci` | Owner answered D-01, D-02, D-04, D-05; synced `docs/07`, `08`, `12`, `14`, `00`, `01`, `04`, `09`. Session 11: wrote `docs/15-implementation-phases.md`. Added OQ-20. |
| 2026-10-10 | `sky/zen-heisenberg-d72c3e` | Added `CLAUDE.md`, `.claude/memory/`, `.claude/settings.json` (no attribution, owner git identity), `format_helper.php` + test, logo/favicon/app icons. |
