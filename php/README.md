# Šachové úlohy

PHP/Symfony aplikace pro prohlížení a interaktivní řešení šachových úloh
z [Lichess puzzle databáze](https://database.lichess.org/#puzzles).

## Stack

- PHP 8.2+, Symfony 7, Doctrine ORM 3, Twig
- AssetMapper + Stimulus (Symfony UX)
- SQLite (`var/data.db`)
- [cm-chessboard](https://github.com/shaack/cm-chessboard) pro šachovnici

## Příprava prostředí

Aplikace potřebuje PHP rozšíření, která Arch defaultně neaktivuje:
`pdo_sqlite`, `sqlite3`, `iconv`, `intl`. Repo obsahuje lokální
`.php-conf.d/extensions.ini`, které je zapne přes `PHP_INI_SCAN_DIR`.

```bash
# Jednou: nainstaluj sqlite extension (vyžaduje sudo)
sudo pacman -S php-sqlite

# Composer
PHP_INI_SCAN_DIR=$PWD/.php-conf.d composer install --no-security-blocking
```

## Import úloh

```bash
# Stáhni Lichess puzzle CSV (https://database.lichess.org/#puzzles, ~1GB)
# nebo použij přiložený var/sample_puzzles.csv (10 úloh) pro rychlou zkoušku.

PHP_INI_SCAN_DIR=$PWD/.php-conf.d php bin/console app:puzzles:import \
    var/sample_puzzles.csv

# Velký import s limitem:
PHP_INI_SCAN_DIR=$PWD/.php-conf.d php bin/console app:puzzles:import \
    /cesta/k/lichess_db_puzzle.csv --limit=100000 --batch=2000
```

## Spuštění dev serveru

```bash
PHP_INI_SCAN_DIR=$PWD/.php-conf.d php -S 127.0.0.1:8765 -t public public/index.php
# nebo: symfony serve  (pokud máš symfony CLI)

# Otevři: http://127.0.0.1:8765/
```

## Schema datbáze

- `puzzle` — hlavní tabulka, PK = lichess `PuzzleId`.
- `theme`, `opening` — lookup tabulky tagů.
- `puzzle_theme`, `puzzle_opening` — M:N join tabulky s reverse kompozitními indexy.

Stránkování je **keyset** na `(rating, id)` — žádný `OFFSET`,
škáluje na 5M řádků.

## Konvence Lichess úlohy

- `FEN` udává pozici, kde **na tahu je soupeř**.
- První tah v `Moves` je soupeřův "setup" tah, který se zahraje automaticky.
- Uživatel pak hraje druhý, čtvrtý, … tah; aplikace ověřuje proti zbytku UCI.
