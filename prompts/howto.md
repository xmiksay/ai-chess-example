# howto.md — co s tím dál

Souhrn obou analýz (`Tony.md`, `barca.md`) a doporučení, jak pracovat dál. Cílí na člověka, který vidí, že jeho prompty u Claude rotují ve smyčkách, a hledá fix. Cílí taky na junior kolegu, kterému tahle situace přijde důvěrně známá.

## Tři pozorování, která se opakují

**1. Úspěch promptu = kolik architektury jsi udělal v hlavě, než jsi začal psát.**
Úspěšný Rust prompt z `../README.md` měl 4 věty a každá byla *rozhodnutí* — CSV do paměti, filter na DB, max 500, zobraz pozici. Žádná otázka, žádná delegace. Proto stačily 2 prompty na hotovou aplikaci.

**2. Delegace architektury vždy spustí smyčku.**
Jedno: *„dle stacku, který standardně používáme"* (Barca → Claude přes CLAUDE.md).
Dvě: *„vyber technologie, které jsou pro Claude nejjednodušší"* (uživatel → Tony).
Tři: *„podívej se na můj projekt"* (junior → senior).
Strukturálně to samé. Někdo musí to rozhodnutí udělat. Pokud ho neuděláš ty, udělá ho někdo s horším kontextem.

**3. Skills override prompty, ne naopak.**
`superpowers:brainstorming` má HARD-GATE *„Do NOT write code until design is approved"*. Žádné množství *„DO NOT ASK QUESTIONS"* v uživatelově promptu HARD-GATE neobejde. Když chceš jiné chování, musíš změnit setup, ne prompt.

## Konkrétní chyby z analyzovaných materiálů

### Z Barca session (`barca.md`)

| Chyba | Symptom | Fix |
|---|---|---|
| Delegace na CLAUDE.md (*„standardní stack"*) | Claude scanuje cizí git history, najde Nette/Postgres/Docker | Říct buď konkrétní stack, nebo *„zelená louka, vyber a jeď"* |
| `superpowers:brainstorming` automaticky aktivní | 6 tasků, žádný kód, dvě přerušení | Odebrat plugin nebo aktivovat jen ručně |
| *„Aplikace na šachy"* — vágní | Skill se ptá, co přesně | *„Puzzle solver: render pozici z FEN, hráč hraje druhý tah z Moves, app validuje."* |

### Z PROMPTY TONY.txt (`Tony.md`)

| Chyba | Symptom | Fix |
|---|---|---|
| Persona prompt místo task promptu | *„You are a senior Symfony developer"* + 200 řádků pravidel | Začít *„Build X. Constraints: A, B, C."* — bez persony |
| TDD + vertical slices pro 5-min demo | 30 minut byrokracie před prvním řádkem kódu | Pro demo: žádné formální testy, žádné approval gates |
| Delegace na meta-LLM | Tonyho doporučení driftuje mezi 3 fundamentálně jinými architekturami | Architekturu udělat sám, Tony není kolega |
| Devět negativních constraintů, žádná pozitivní rozhodnutí | Claude má prostor volit + smyčku | *„Stack je: Symfony + AssetMapper + chess.js + cm-chessboard. SQLite. Konec."* |
| Hromadění instrukcí (50+ pravidel) | Délka roste, kvalita klesá | 4–6 vět, každá rozhodnutí |
| ALL CAPS „DO NOT ASK QUESTIONS" | Pokus překřičet skill | Skill odebrat, ne překřičet |

## Šablona dobrého prvního promptu

Pokud nevíš jak začít, použij tuhle šablonu. Vyplň všechna místa. Pokud na některé nemáš odpověď, **rozhodni se** předtím, než pošleš prompt — ne v promptu, ne v dialogu.

```
Postav [konkrétní výstup, ne „aplikace na X"].

Stack: [jazyk, framework, DB, frontend lib]. Bez [1-2 věci, které víš
že nechceš a hrozí že je Claude přidá].

Datový vstup: [formát, ukázka, kde to leží].

Architekturní rozhodnutí:
- [boot-time vs query-time, kde leží data, jak velká je memory footprint]
- [server-side vs client-side render]
- [hard cap / scaling decision]
- [error handling boundary, pokud netriviální]

Cílový stav: [co uvidím v browseru / v terminálu / v souboru].
```

Příklad pro tenhle úkol (Barca by mohla napsat):

```
Postav puzzle solver. Symfony + Doctrine + SQLite + Twig +
chess.js + cm-chessboard z CDN. Bez Stimulus, bez Encore.

Vstup: lichess_db_puzzle.csv.zst (~300 MB, ~5,9M řádků).
Sloupce PuzzleId, FEN, Moves, Rating, ...

Rozhodnutí:
- Import: streaming přes zstdcat → SQLite, prvních 10 000 řádků,
  console command s --limit.
- Render: server-side Twig + JS chessboard z assets nebo CDN.
- FEN → aplikuj první UCI tah z Moves → ukaž pozici uživateli →
  validuj druhý UCI tah jako řešení.

Cíl: GET /puzzle/random ukáže náhodnou úlohu. Hráč udělá tah.
App ukáže Correct/Wrong + Next.
```

To je 12 řádků, žádná delegace, každé rozhodnutí pojmenované. Tenhle prompt by skill ani neměl moc o čem brainstormovat — všechno klíčové je řečeno.

## Co dělat, když přesto vidíš smyčku

**Fáze 1 — diagnóza, 30 sekund.**

- Přerušil tě Claude otázkou? → Tvoje zadání má díru. Zaplň ji.
- Začal Claude vytvářet tasky a *„before writing code, let me explore"*? → Aktivoval se skill. Buď ho ukotvi explicitními rozhodnutími, nebo skill odeber.
- Začal Claude scanovat cizí git history? → CLAUDE.md vnesl kontext, který si nepřál. Řekni *„nezohledňuj projektový kontext, zelená louka"*.
- Vrátil Claude něco fundamentálně jiného, než jsi čekal (jiný stack, jiná architektura)? → Tvoje zadání bylo dvojznačné. Nezačni reagovat — **přepiš první prompt** a pošli znovu.

**Fáze 2 — co dělat / co nedělat.**

| Dělej | Nedělej |
|---|---|
| Přidávej pozitivní rozhodnutí (*„stack je X, Y, Z"*) | Přidávej negativní zákazy (*„nepoužívej W, V, U"*) |
| Přepiš první prompt, jakmile vidíš smyčku | Hromaď instrukce v dialogu („počkej, ještě dodám…") |
| Odeber skill / plugin, který ti vadí | Snaž se ho překřičet promptem |
| Pojmenuj jeden konkrétní výstup (*„GET /puzzle/random ukáže…"*) | Mluv obecně (*„aplikace na šachy"*) |
| Říkej *„rozhodni za mě a pokračuj, neptej se"* — pokud jsi OK s libovolnou volbou | Říkej *„dle stacku, který standardně používáme"* — pokud nevíš, jaký to je |

**Fáze 3 — když i tohle selže.**

Smyčka, která nemizí ani po 2 přepsaných promptech, je signál, že **architekturu sis nerozmyslel**. Žádná promptová technika to nezachrání. Vstaň od počítače, vezmi si tužku, nakresli si systém na papír (komponenty, datový tok, kde je co uloženo, kde se renderuje, kde je validace). Pak teprve znovu k Claudovi. Tohle není urážka — je to nejrychlejší cesta k hotovému kódu.

## Doporučení pro setup

Pokud děláš různě velké projekty (i 5-minutové demo, i vícetýdenní feature):

1. **Skills aktivovat ručně, ne auto-triggerem.** `superpowers:brainstorming` má smysl pro vícetýdenní feature s otevřeným scope. Nemá smysl pro jednorázové demo. Pokud máš plugin nainstalovaný globálně, riskuješ, že ti zabije i triviální úkoly.
2. **Globální `~/CLAUDE.md` drž ploché a popisné, ne preskriptivní.** *„Primary language: Rust, frameworks Tokio/Axum"* je popis profilu — užitečný. *„Each project must have its own CLAUDE.md, always read it first"* je instrukce — ta v zelená-louka projektech vede ke scanování cizích historií.
3. **Per-projekt `CLAUDE.md` piš, až když máš co popsat.** Pro nový projekt vytvořit prázdný/skeleton CLAUDE.md je past — Claude ho najde a začne se na něj odkazovat, i když je v něm jen šum.

## Doporučení pro juniora

Pokud jsi junior a vidíš, že Claude před tebou produkuje hotový kód za 2 prompty, kdežto ty padáš do smyček:

1. **Není to o promptingu.** Je to o tom, že seniori si rozmysleli architekturu předem. Ten *„krátký prompt"* je špička ledovce — pod ním je 30 minut myšlení, které neviděls.
2. **Nedoptávej se LLM na věci, které máš rozhodnout ty.** Pokud nevíš, jaký framework použít, *neptej se Claude*, ptej se zkušenějšího kolegy nebo si napiš pro sebe srovnání. Rozhodnutí sis musíš vlastnit.
3. **Krátké prompty napodobuj až po dvou architekturních rozhodnutích.** *„Postav webovku"* + nic dalšího = smyčka. *„Postav webovku v Symfony+SQLite, render server-side, data v SQLite z importu"* + nic dalšího = funguje.
4. **Smyčka je signál**, ne ostuda. Když vidíš smyčku, přerušuj a přepisuj zadání. Neodpovídej v dialogu — ten dialog je už ztracený.

## Zápatí

Tahle howto vznikla z konkrétních materiálů ve stejném adresáři:

- `barca.md` — analýza JSONL session, kde Claude + brainstorming skill + CLAUDE.md zaklesly Barcu do 6-task brainstormu na úkol velikosti „render board z CSV řádku".
- `Tony.md` — analýza meta-promptingu přes externí LLM, kde uživatel hledal *„ideální prompt"* místo aby si rozmyslel architekturu.

Obě jsou variace stejného principu: **úspěch promptu měří, kolik architektury jsi udělal, ne kolik instrukcí jsi napsal**. Krátký prompt + udělaná architektura → dva tahy. Dlouhý prompt + neudělaná architektura → smyčka.
