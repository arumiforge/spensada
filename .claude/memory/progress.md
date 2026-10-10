# Progress

Update this file at the end of every task that changes the repo: move
finished items to "Done", keep "Next" and "Open questions" current, and keep
the log to the last 10 entries.

## Current state (2026-10-10)

- FASE-01 L01-01 to L01-08 built on `claude/fase-01-akun-akses-6jkkp8` (PR open): account tables, login limits, sesi/wajib-ganti filters, login/logout, change password, admin:pertama/admin:pulihkan, staff accounts, activity log, system check, panel menu. 300 PHPUnit tests and 90 node tests pass locally (MySQL 8.0 in the cloud sandbox). L01-09 kiosk prototype not started.
- FASE-00 merged (PR #17). L00-09 run by the owner on Laragon (PHP 8.3.28, MySQL 8.4.3): `aplikasi:cek` all good, `node --test` 90 pass; `composer test` had 7 failures from the `.env` baseURL leaking into tests, fixed in the follow-up PR (owner to re-run `composer test`).
- Discovery docs `docs/00`–`docs/15`: done (Sessions 1–11), merged in PR #14.
- Project license: MIT, copyright Arumi Studios (D-10).
- Owner decisions D-01 (Windows + Laragon production), D-02 (Bootstrap 5), D-04 (one PR per phase), D-05 (keep doc-defined names): confirmed and synced into `docs/` (see `decisions.md`).
- Code: Composer appstarter, CodeIgniter 4.7.5 in `vendor/`. FASE-00 foundation: config, MySQLi driver (+07:00), `Jam`, named locks, transactions, filters and `HakAkses`, error pages, Bootstrap/font/icon assets, layouts, components, `Label.php`, `/login` view, JS format module, `pengaturan` + seeders, `aplikasi:cek`, route access and `hak` attribute tests.
- Claude memory folder, attribution settings, brand assets: added.
- `README.md`: project README with all implementation stages. `LICENSE`: MIT, Arumi Studios.

## Next

1. Owner: review and test the FASE-01 PR on Laragon (`composer test`, `node --test`, `php spark migrate`, `php spark admin:pertama`, log in at `/login`), merge it, then tag `main` as `r1-fase-01`.
2. L01-09 kiosk prototype on the temporary branch `prototipe-kiosk` (not merged): needs the school's station laptop, webcam, USB scanner and sample cards.
3. FASE-02 must add: `wali_kelas` role in `Services\Akun\Peran`, the birth-date password rule (`GantiPassword::galat()` argument), "Kelas yang diampu" on staff accounts, FKs `akun.siswa_id` and `log_aktivitas.rombel_id`, student login retest (L02-09).
4. Tests: feature tests reset `routes`, `router`, `request`, `response` in `setUp()`; a PUT sent as POST + `_method` needs `router` reset per request. Log in with `Tests\Support\AkunTrait` (`buatAkun()`, `sesiAkun()`). Parallel agents use their own test DB: `env 'database.tests.database=spensada_test_x' vendor/bin/phpunit` (NamedLockTest expects `spensada_test`). `encryption.key` is fixed in `phpunit.dist.xml`.

## Open questions for the owner

- FASE-01: password rule order. `docs/12` SEC-03 checks the common list before username/NISN, but AC-AKN-02-05 expects the NISN message for e.g. `zebra0012345678` (`zebra` is in the list). Code checks username/NISN first. Confirm, then update SEC-03.
- FASE-01: message for an admin removing their own Admin role while other admins exist (not in `docs/11` §5.1): "Anda tidak dapat mencabut role Admin dari akun sendiri." Confirm.
- FASE-01: the login page privacy notice (SEC-68) has no `pengaturan` key in R1; it stays empty until the school provides text (OQ-18).

- OQ-20 — before FASE-09: production server location (Windows VPS or school machine), approval of an ACME client such as win-acme for Let's Encrypt, and running services via Windows service + Task Scheduler instead of the Laragon app with auto-logon (`docs/07` §16.4).
- D-07: the landscape logo says "PRIMA" — is that intended for the Spensada app?
- D-03: approve a minifier library, or keep "vendor `.min` files only, no build step"?
- School inputs (not urgent yet): sample cards and one station laptop for the FASE-01 kiosk prototype; OQ-08, OQ-18, OQ-19 before the R1 trial.

## Log

| Date | Branch | Work |
|---|---|---|
| 2026-10-10 | `claude/fase-01-akun-akses-6jkkp8` | FASE-01 L01-01 to L01-08: lead built tables, services, filters, routes, labels, menu; parallel agents built login, password, admin commands + system check, staff accounts, activity log. Added eye/eye-off/copy/lock-open icons, SecLists common-password list. |
| 2026-10-10 | `claude/tahap-1-uh4a06` | FASE-00 follow-up: tests ignore the `.env` baseURL; owner answers synced: seed school name/address (`docs/06` §6.1), focus-ring exception (`docs/08` UI-74), `belum` code (`docs/08` §4.2), MySQL < 8.4 stays a failure; `docs/15` §12 FASE-00 row filled. |
| 2026-10-10 | `claude/tahap-1-uh4a06` | FASE-00 L00-01 to L00-08 (parallel sub-agents per step). Helper now pre-rounds to 15 digits so 57,5% → 58% on any PHP version, and prints "Rp 0" instead of "-Rp 0". |
| 2026-10-10 | `claude/readme-implementation-stages-87jury` | Replaced framework `README.md` with a project README (Indonesian) listing every stage from discovery to R3, from `docs/14` and `docs/15`. L00-01 and L00-09 still own later README updates. |
| 2026-10-10 | `sky/busy-goodall-fswgci` | License decision D-10 (MIT) recorded in `docs/07`, `14`, `15`, `00` and memory. |
| 2026-10-10 | `sky/busy-goodall-fswgci` | Owner answered D-01, D-02, D-04, D-05; synced `docs/07`, `08`, `12`, `14`, `00`, `01`, `04`, `09`. Session 11: wrote `docs/15-implementation-phases.md`. Added OQ-20. |
| 2026-10-10 | `sky/zen-heisenberg-d72c3e` | Added `CLAUDE.md`, `.claude/memory/`, `.claude/settings.json` (no attribution, owner git identity), `format_helper.php` + test, logo/favicon/app icons. |
