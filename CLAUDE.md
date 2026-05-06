# CLAUDE.md — Chess Puzzles

Webová aplikace pro řešení šachových hádanek z Lichess databáze. Repo obsahuje **dvě paralelní implementace** stejné aplikace.

## Layout

```
rust/   — Axum + Vue 3 SPA (CDN), CSV-in-RAM, JSON API
php/    — Symfony 7 + Twig + Stimulus, SQLite, keyset pagination
```

Detailní dokumentace je v [`rust/README.md`](rust/README.md) a [`php/README.md`](php/README.md). Tento soubor jen orientuje.

## Quick start

### Rust

```bash
cd rust
cargo run --release
# poslouchá na 0.0.0.0:3000, potřebuje lichess_db_puzzle.csv.zst v rust/
```

### PHP

```bash
cd php
PHP_INI_SCAN_DIR=$PWD/.php-conf.d composer install --no-security-blocking
PHP_INI_SCAN_DIR=$PWD/.php-conf.d php bin/console app:puzzles:import var/sample_puzzles.csv
PHP_INI_SCAN_DIR=$PWD/.php-conf.d php -S 127.0.0.1:8765 -t public public/index.php
```

## Sdílená doménová konvence (Lichess)

První tah v `Moves` poli je **setup tah soupeře** — frontend (Rust i PHP) ho přehraje automaticky. Uživatel pak hraje druhý tah, soupeř třetí, atd. `userColor` je opačná strana než FEN side-to-move. Platí pro obě implementace.

## Pracovní postup

- Změna v `rust/`: `cd rust && cargo check` po každé úpravě Rustu, refresh prohlížeče po úpravě `static/index.html`.
- Změna v `php/`: `PHP_INI_SCAN_DIR=$PWD/.php-conf.d php -S …` server běží, refresh stačí. Po změně schématu `php bin/console doctrine:schema:update --force` (viz `php/README.md`).
- Nikdy needituj v root — vše je v subdirech. Root drží jen meta soubory (`README.md`, `CLAUDE.md`, `.gitignore`).
