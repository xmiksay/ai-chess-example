# Šachové hádanky

Webová aplikace pro procházení a interaktivní řešení šachových hádanek z [Lichess puzzle databáze](https://database.lichess.org/#puzzles). Repo obsahuje dvě paralelní implementace stejného produktu — jednu v Rustu, druhou v PHP/Symfony — postavené pro porovnání přístupů.

## Implementace

| | [`rust/`](rust/README.md) | [`php/`](php/README.md) |
|---|---|---|
| **Backend** | Axum (Rust 1.75+) | Symfony 7, Doctrine ORM 3 |
| **Frontend** | Vue 3 SPA z CDN, [chessground](https://github.com/lichess-org/chessground), chess.js | Twig + Stimulus, [cm-chessboard](https://github.com/shaack/cm-chessboard) |
| **Datový model** | Celá CSV v paměti (~2 GB RAM, ~11 s startup) | SQLite (`var/data.db`), normalizované schéma |
| **Stránkování** | Lineární průchod, hard limit 500 / dotaz | Keyset pagination na `(rating, id)` |
| **Render** | JSON API + client-side Vue | Server-side Twig, minimum JS |
| **Port** | `3000` | `8765` |

Pro build, parametry a API detaily viz [`rust/README.md`](rust/README.md) a [`php/README.md`](php/README.md).

## Data

Obě implementace pracují se souborem `lichess_db_puzzle.csv.zst` z [database.lichess.org](https://database.lichess.org/#puzzles) (~296 MB komprimovaně, ~5,9 M úloh). Rust verze čte přímo komprimovaný stream do paměti. PHP verze importuje řádky do SQLite přes `app:puzzles:import` (s `--limit` a `--batch` parametry).

## Vytvořeno pomocí Claude Code

Obě implementace byly vygenerovány v [Claude Code](https://claude.com/claude-code) sezení. Níže jsou doslovně přepsané prompty z těchto sezení — slouží jako záznam komunikace s LLM, která vedla k funkčnímu výsledku, a jako příklad, co stačilo říct.

### Rust verze (2 prompty, ~hodina)

> Napis webovou aplikaci, ktera projde databazi sachovych partii a zobrazi uzivateli diagramy k reseni.
> Pri startu aplikace se nacte CSV do pameti.
> Pri hledani se aplikuje na databazi filtr (nikdy nevrati vice nez 500 diagramu najednou)
> Zobraz vyslednou sachovou partii

> Vytvor README.md a CLAUDE.md s popisem aplikace

### PHP verze (7 promptů, debugging-heavy)

> Napis mi applikaci na reseni sachovych diagramu. Stack bude v PHP, + simple sablona na prohlizeni. Pujde vyfiltrovat ulohy podle kriterii popsane v CSV. PHP ma problem s contextem a nacitat cely CSV soubor muze byt slozite. Pridej nejakou jednoduchou databazi, napr. SQL lite a napln ji skriptem diagramy z CSV. Muzes pouzit napr Symfony,Doctrine ORM,twig sablony a StimulusJS pro interakci

> sqllite ninstalovane, pokracuj

> Chessboard ma spatne style, sachovnice se zobrazuje cela cerna

> Chessboard se ted nezobrazuje vubec

> Jak vytvorim znovu databazi?

> Aplikace se mi sekne na hlasce "Nacitam"

> ```
> GET http://127.0.0.1:8765/assets/cm-chessboard/extensions/markers/markers.css net::ERR_ABORTED 500
> GET http://127.0.0.1:8765/assets/styles/app-cT2YKNc.css net::ERR_ABORTED 500
> GET http://127.0.0.1:8765/assets/app--q683F4.js net::ERR_ABORTED 500
> GET http://127.0.0.1:8765/assets/stimulus_bootstrap-xCO4u8H.js net::ERR_ABORTED 500
> GET http://127.0.0.1:8765/assets/@symfony/stimulus-bundle/loader-IOzBDLu.js net::ERR_ABORTED 500
> ```

## Co rozhodlo o úspěchu

Pozorování z těchto konkrétních dvou sezení — ne obecná pravidla, ale to, co tady opravdu zafungovalo:

1. **První prompt nese architekturu.** Rust prompt #1 zafixoval tři klíčové věci hned: *načti CSV při startu*, *aplikuj filtr*, *nikdy víc než 500*. Agent nemusel hádat datový model ani tvar requestu. PHP prompt #1 udělal totéž — pojmenoval stack (Symfony / Doctrine / Twig / Stimulus) i workaround (SQLite, protože PHP CSV neunese). Žádné z dvou sezení nepotřebovalo dlouhou výměnu o architektuře.

2. **Přiznat limity jazyka předem ušetří odbočky.** *„PHP ma problem s contextem a nacitat cely CSV soubor muze byt slozite. Pridej nejakou jednoduchou databazi"* — uživatel slepou uličku (1 GB do PHP paměti) odřízl ve stejné větě, kde ji pojmenoval. Agent šel rovnou na funkční import skript.

3. **Při debugu popisuj symptom, ne diagnózu.** PHP prompty 3, 4, 6 — *„sachovnice se zobrazuje cela cerna"*, *„se ted nezobrazuje vubec"*, *„sekne na hlasce 'Nacitam'"* — popisují, co uživatel vidí. To dává agentovi prostor diagnostikovat. Prompt 7 šel ještě dál a vlepil přímo browser console output. Každý se vyřešil v jednom kole.

4. **Proč Rust 2 prompty a PHP 7?** Ne proto, že by Rust „uměl s Claudem líp". Protože měl menší povrch. Rust verze má dvě pohyblivé části (Axum + Vue z CDN). PHP verze pět (Symfony routing + AssetMapper + Stimulus + Doctrine + cm-chessboard CSS). Víc švů = víc překvapení, která vylezou až v prohlížeči. Lekce je o scope, ne o jazyku — vyber stack, jehož švy ti drží v hlavě, nebo si rezervuj čas na debug.

5. **Krátké follow-upy stačí — někdy jsou optimální.** *„sqllite ninstalovane, pokracuj"* a *„Jak vytvorim znovu databazi?"* mají dvě až čtyři slova a obě odblokují postup. Když má agent kontext, nemusíš znovu opisovat projekt na každém kole.

## Licence

Datová sada (`lichess_db_puzzle.csv.zst`) je pod CC0 z lichess.org. Kód v tomto repu zatím bez explicitní licence — kontaktuj autora.
