# barca.md — analýza session `aac0f4ce-e26b-44f7-9407-1a61d2501d39.jsonl`

Session uživatelky pracující v `/home/barucepa/...` (dále **Barca**). Datum 5. května 2026, 10:23–10:26 CEST. Tři minuty, dva přerušené tahy, žádný kód. Přepis JSONL ukazuje konkrétní kolapsovou sekvenci — ne v Claudovi, ale v interakci mezi nainstalovaným skillem a tvarem zadání.

## Časová osa

| Čas (UTC) | Aktér | Akce |
|---|---|---|
| 08:23:30 | Barca | První prompt: *„Ahoj, dostala jsem za úkol zprovoznit aplikaci na šachy…"* |
| 08:23:34 | Claude | Invokuje skill `superpowers:brainstorming` |
| 08:23:37–55 | Claude | Vytváří **6 tasků** (Explore project, Ask clarifying questions, Propose approaches, Present design, Write design doc, Transition to implementation plan) |
| 08:24:01 | Claude | *„Exploring project context now."* |
| 08:24:09 | Claude | `ls /home/barucepa/projects/chess-example && git log` |
| 08:25:48 | Claude | Označí task 1 jako completed, čte `MEMORY.md`, hledá CLAUDE.md |
| 08:25:04 | **Barca** | **Přerušení #1** (`Request interrupted by user for tool use`) |
| 08:25:44 | Barca | Odpověď: *„Mám naprosto čistý projekt, zde neexistuje žádná struktura. Potřebuji projekt napsat zcela celý, postavit na zelené louce."* |
| 08:26:16 | **Barca** | **Přerušení #2** |
| 08:26:43 | Barca | *„Neodkazuj se na claude.md, zkus přemýšlet sám za sebe."* |
| 08:27:07 | Claude | Položí clarifying question: *„Je tato chess aplikace čistě prohlížeč puzzlů, nebo potřebuješ…"* |
| (konec) | | Žádný kód nevznikl. |

## První prompt — co řekl, co neřekl

**Plný text:**

> Ahoj, dostala jsem za úkol zprovoznit aplikaci na šachy. Dostala jsem .csv soubor s mnoha řádky, které mají tuto kostru: PuzzleId,FEN,Moves,Rating,RatingDeviation,Popularity,NbPlays,Themes,GameUrl,OpeningTags. Potřebuji, aby při zkonzumování jednoho řádku z tohoto .csv souboru se vykreslila šachovnice a zobrazila tedy pozice všech figur atd. Navrhni řešení dle stacku, který standardně používáme, použij jen to, co se reálně využije, nepoužívej technologie, které nevyužijeme. Stačí minimální stack. Jeden reálný řádek z .csv vypadá takto: 00sHx,q3k1nr/1pp1nQpp/3p4/1P2p3/4P3/B1PP1b2/B5PP/5K2 b k - 0 17,e8d7 a2e6 d7d8 f7f8,1760,80,83,72,mate mateIn2 middlegame short,https://lichess.org/yyznGmXs/black#34,Italian_Game Italian_Game_Classical_Variation

**Co je v promptu zcela v pořádku.**

- Konkrétní popis vstupu (CSV se sloupci).
- Ukázkový řádek se všemi hodnotami — kontext pro FEN, Moves, themes.
- Definovaný výstup: *„aby se vykreslila šachovnice a zobrazila pozice všech figur"*.
- Constraint na minimalitu: *„stačí minimální stack"*.

**Co v promptu schází nebo je špatně.**

1. **„Navrhni řešení dle stacku, který standardně používáme"** — delegace, ne rozhodnutí. Říká *„rozhodni za mě"*. Když to skill `brainstorming` vidí, má každé právo se ptát: jaký standardní stack? Tahle jedna věta otevírá smyčku.

2. **„Použij jen to, co se reálně využije, nepoužívej technologie, které nevyužijeme."** — to není constraint, to je tautologie. *Co se „reálně využije"* musí někdo rozhodnout. A to někdo není Claude — to je Barca, která ví, co tým reálně dělá.

3. **„Aplikace na šachy"** je vágní zaklínadlo. Šachová appka může být: PGN viewer, puzzle solver, online hra, analýza otevření, opening trainer. Kontext (CSV s `Themes`, `Rating`, `Moves`) sice naznačuje puzzle solver, ale Barca to neřekla. Skill se pak zeptá — což je formálně správně, ale stojí 30 sekund a uživatelovu trpělivost.

4. **Žádné architektonické rozhodnutí.** Kontrast s úspěšným Rust promptem ze `../README.md`:
   - „CSV se načte do paměti při startu" → boot-time vs query-time **rozhodnuto**
   - „filter na databázi" → server-side **rozhodnuto**
   - „nikdy nevrátí víc než 500" → hard cap **rozhodnuto**
   
   Barca nedala žádný takový anchor. Architektura je celá nerozhodnutá.

## Co spustil skill `superpowers:brainstorming`

Skill je nainstalovaný globálně přes plugin `superpowers/5.0.7`. Když Claude rozpoznal vstup jako „idea, která potřebuje design", invokoval skill automaticky. Skill přinesl tyto **systémové instrukce**:

```
<HARD-GATE>
Do NOT invoke any implementation skill, write any code, scaffold any project,
or take any implementation action until you have presented a design and
the user has approved it. This applies to EVERY project regardless of
perceived simplicity.
</HARD-GATE>
```

Plus checklist 9 kroků (Explore → Visual Companion offer → Clarifying questions → Propose 2-3 approaches → Present design → Write design doc → Spec self-review → User reviews spec → Invoke writing-plans skill).

Plus anti-pattern sekci, která **explicitně argumentuje**, že každý projekt — *„a todo list, a single-function utility, a config change"* — musí projít celým procesem. Tj. skill ti **nedovolí říct** *„tohle je triviální, prostě to napiš"*. Pro úkol velikosti „načti CSV řádek, vykresli board" tenhle skill udělá víc procesu než kódu.

## Tři selhání, která se zkombinovala

**1. Skill přebil úkol.** Brainstorming skill je správný nástroj pro správnou velikost úkolu (vícetýdenní feature, otevřený scope). Pro „render diagram z CSV řádku" je to overkill, který nelze vypnout uživatelovým promptem (HARD-GATE je systémová instrukce s vyšší prioritou).

**2. CLAUDE.md vnesl kontaminovaný kontext.** Claude scanoval `git log` a našel smazané PHP/Nette/Postgres/Docker artefakty z minulého projektu. Zároveň pátral po MEMORY.md a `find … -name "CLAUDE.md"`. To přesně souhlasí s Barcinou druhou intervencí: *„Neodkazuj se na claude.md, zkus přemýšlet sám za sebe."* Globální/projektový CLAUDE.md byl pro tenhle úkol noise, ne signal.

**3. Delegace v prompt #1.** *„Dle stacku, který standardně používáme"* explicitně poslala Claude do té kontaminace, místo aby ji zablokovala.

Tyhle tři efekty se násobí, ne sčítají. Skill *vyžaduje* explorace projektu. CLAUDE.md *poskytuje* kontext na exploraci. Prompt *deleguje* na tenhle kontext. Výsledek: skill najde nerelevantní stack, navrhne na něm design, Barca přerušuje.

## Diagnóza

**Není to selhání modelu.** Claude udělal přesně to, co mu skill nařídil.

**Není to úplně selhání uživatelky.** Prompt je rozumný pro práci s juniorem nebo s Claudem bez skillu. Selhání vzniklo z **kombinace**: vágní prompt + agresivní skill + kontaminovaný CLAUDE.md.

**Je to selhání setupu.** Skill `superpowers:brainstorming` má smysl mít k dispozici, ale ne **automaticky aktivní** pro každý úkol. Pokud Barca dělá rychlá dema, brainstorming skill ji bude blokovat **vždy** — bez ohledu na to, jak dobrý prompt napíše.

## Co by zabralo

**Systémové změny** (ne promptové):

- Odebrat plugin `superpowers/5.0.7` z `~/.claude/plugins/` pro projekty typu „rychlé demo".
- Nebo skill ponechat, ale aktivovat ho jen explicitně přes `/brainstorm` než auto-trigger.
- Pročistit globální CLAUDE.md od stack-defaults, které neplatí pro „zelená louka" projekty. Nechat tam profil developera, odebrat *„standardní stack je X"*.

**Promptové změny** (na příště, i bez skill změny):

- Místo *„dle stacku, který standardně používáme"* napsat: *„zelená louka, vyber stack, který se hodí na CSV-driven puzzle web; rozhodni za mě."* Tohle skill aspoň ukotví — ví, že nemá co explorovat.
- Přidat 1–2 architektonická rozhodnutí: *„načti CSV do SQLite jednorázovým importem, server-side render šachovnice"* — zúží skill prostor pro otázky.
- Eliminovat *„aplikace na šachy"* a říct *„puzzle solver — uživatel vidí pozici a hraje řešení"*.

**Co ale prompt sám nevyřeší:** HARD-GATE skillu. Pokud skill zůstane aktivní, žádné množství dobrých instrukcí v promptu nezabrání tomu, aby Claude udělal 6 tasků a požádal o schválení designu předtím, než napíše první řádek.

## Závěr

Sezení nepadlo na promptu. Padlo na **mismatch mezi velikostí úkolu a velikostí procesu, který skill vynucuje**. Brainstorming je správná disciplína pro stavbu platformy — je to brzda pro stavbu demo stránky. Pokud Barca chce dema, skill musí pryč. Pokud chce skill, musí přijmout, že každý úkol pojede přes 9-krokový design loop.
