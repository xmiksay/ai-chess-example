# CLAUDE.md — Chess Puzzles

Webová aplikace pro řešení šachových hádanek z Lichess databáze. Backend v Rust/Axum, frontend Vue 3 SPA bez build kroku.

## Build & Run

```bash
cargo check              # kompilační kontrola (vždy po změně Rustu)
cargo build --release    # produkční binárka → target/release/chess-puzzles
cargo run --release      # build + spuštění
```

Default bind je `0.0.0.0:3000`. Pro lokální vývoj typicky `--bind 127.0.0.1:3000`.

## Architektura

```
src/main.rs          — Axum server, CSV loader, search handler (vše v jednom souboru)
static/index.html    — Vue 3 SPA (CDN: vue, tailwind, chessground, chess.js)
lichess_db_puzzle.csv.zst  — vstupní data (~296 MB komprimovaně, ~1 GB rozbalené)
```

### Klíčové konvence

- **CSV se načítá při startu** přímo ze zstd streamu (`zstd::Decoder` nad `BufReader`). Veškerá data jsou v paměti po celou dobu běhu — `Vec<Puzzle>` s `Box<str>` fieldy.
- **Hard limit 500** výsledků na search dotaz je definován jako `const HARD_LIMIT` v `main.rs`. Frontend tento limit zobrazuje (`/api/meta`).
- **State** je `Arc<AppState>` obsahující `puzzles`, `by_id` (HashMap), `themes` (precomputed), `rating_min/max`.
- **Filtrace** je lineární průchod přes všechny puzzly — pro 6M záznamů ~stovky ms v release buildu. Není potřeba index.
- **Sort `random`** používá reservoir-style replacement během průchodu, aby se nemuselo držet všechno v paměti. Ostatní řazení sortí až výsledky stránky.

### Frontend logika (Lichess puzzle konvence)

První tah v `Moves` poli je **setup tah soupeře** — frontend ho přehraje automaticky. Uživatel pak hledá tah druhý. Po správném tahu uživatele přehraje frontend tah třetí (soupeř) a tak dále.

- `userColor = chess.turn() === 'w' ? 'black' : 'white'` (opačná strana než FEN side-to-move)
- Špatný tah: chess.js stav neaktualizujeme, jen se zavolá `cg.set({ fen: chess.fen() })` pro vrácení šachovnice
- Promoce: defaultně dáma (`uci += 'q'` při tahu pěšcem na 1./8. řadu)
- Validace UCI: porovnání stringu s očekávaným tahem, s tolerancí pro chybějící promoční písmeno

### API

- `GET /api/meta` — celkový počet, témata, rozsah ratingu, hard_limit
- `GET /api/search?rating_min=&rating_max=&theme=&opening=&q=&sort=&limit=&offset=` — limit clampován na 500
- `GET /api/puzzle/:id` — O(1) lookup přes HashMap

## Závislosti

Rust:
- `axum` 0.7 — HTTP server
- `tokio` — async runtime
- `csv` + `serde` — parsing CSV
- `zstd` — dekomprese vstupního souboru
- `clap` — CLI parametry
- `tracing` + `tracing-subscriber` — logy
- `tower-http` — `ServeDir` pro statické soubory, `TraceLayer`

Frontend (vše z CDN, žádný npm/build):
- Vue 3.5.13 — `vue.global.prod.js` z unpkg
- Tailwind CSS — Play CDN
- chessground 9.1.1 — board rendering (z unpkg + esm.sh)
- chess.js 1.0.0 — pravidla, FEN parsing, legal moves (z esm.sh)

## Pracovní postup

1. Po každé úpravě `src/main.rs`: `cargo check`
2. Po větších změnách: `cargo build --release` a restart serveru
3. Po úpravě `static/index.html`: pouze refresh prohlížeče
4. Server načítá CSV ~7 s + indexace ~4 s — počítej s tím při restartech

## Možná rozšíření

- Trvalé indexy (theme → Vec<index>, rating bucket → Vec<index>) pro rychlejší filtraci
- Persistence statistik uživatele (vyřešené hádanky, vlastní rating)
- Multi-puzzle tréninkový mód (následující po vyřešení)
- Promoce přes UI (místo automatické dámy)
- Engine analýza (Stockfish via WASM) po vyřešení
