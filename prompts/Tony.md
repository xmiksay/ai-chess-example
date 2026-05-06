# Tony.md — analýza meta-promptingu

Analýza souboru `PROMPTY TONY.txt`. Uživatel se snaží přes externí LLM (zde nazývaný *Tony*, pravděpodobně ChatGPT) sestavit „ideální prompt" pro Claude. Tři iterace, každá výrazně jiná. Žádná nevedla ke spuštěnému kódu.

## Iterace 1 — enterprise persona

**Vstup uživatele.** Cesta k souboru `lichess_db_puzzle.csv.zst`, otázka *„pomůžeš mi udělat ideální prompt a navrhnout technologie? Stačí vanilla JS?"* a vlepený stávající persona prompt: *„You are a senior Symfony developer working inside an existing Symfony project. Your main rule is vertical slice development."* Následuje cca 200 řádků pravidel — TDD, RED/GREEN/REFACTOR, restrictions, before-editing checklist, after-step summary.

**Tonyho výstup.** Doporučí Symfony + Twig + Stimulus + chess.js + cm-chessboard + SQLite, navrhne 6 vertikálních kroků, přidá testing requirements, security pravidla, code quality pravidla, „prompt pro Claude — konkrétní task prompt", a nakonec *bezpečnostní brzdu* (*„when unsure about a file, do not guess, ask"*).

**Co je tu špatně.**

1. **Persona prompt místo task promptu.** „You are a senior Symfony developer in an existing Symfony project" sděluje *jak má Claude přemýšlet*, ne *co má udělat*. Persona navíc vychází z premisy „existující Symfony projekt" — což je v rozporu s úkolem „postav demo z nuly".

2. **TDD + vertical slices + multi-approval gates pro 5-minutové demo.** Každý vertical step podle promptu vyžaduje goal/scope/affected files/test command/expected behavior + uživatelské schválení. Pro one-page demo je to 30 minut byrokracie před prvním řádkem kódu.

3. **Tony nevrátí jednodušší výstup, vrátí ještě bureaukratičtější.** Když uživatel dá Tonymu *enterprise* vstup, Tony zesílí enterprise výstup. Tony **nezpochybňuje premisu** — reflektuje vibe a amplifikuje. To je strukturální vlastnost meta-promptingu, ne chyba modelu.

## Iterace 2 — „nejjednodušší pro Claude"

**Vstup uživatele.** *„Vyber do promptu technologie, které jsou pro Claude AI nejjednodušší. Aplikace bude demo a nesmí používat technologie, které budou zpomalovat vývoj jejího MVP. Nesmíš vycházet z technologií, které momentálně používám."*

**Tonyho výstup.** Stack ponechá Symfony+Doctrine+Twig+SQLite, jen odebere Stimulus, Tailwind, Webpack Encore, Docker. Přidá negativní list („Do not use React/Vue/Stimulus/Tailwind/Encore/Messenger/API Platform/Docker/external APIs/complex frontend architecture"). Přidá tvrdé „Do not pause for approval. Do not ask Should I proceed?".

**Co je tu špatně.**

1. **Premisa „nejjednodušší pro Claude" je delegace.** Tony to neví. Tony hádá. Skutečná odpověď („Symfony s AssetMapper + chess.js z CDN" nebo *„Axum + Vue z CDN"*) vyplývá z toho, co umí uživatel + co je v dokumentaci frameworků — ne z meta-LLM, který nikdy s Claudem na šachovém demu nepracoval.

2. **„Jednoduchost" se neměří odebíráním knihoven.** Měří se počtem **integračních švů**, které musí Claude napsat. Symfony+Doctrine+Twig+AssetMapper má pět švů. Plain PHP + jeden index.php má jeden šev. Symfony s odebraným Stimulusem **nemá méně švů** — má jen jiný frontend. Tony tu hloubku nechápe a optimalizuje špatnou veličinu.

3. **Devět zákazů, žádná pozitivní rozhodnutí.** *„Do not use React/Vue/Stimulus/Tailwind/Encore/Messenger/API Platform/Docker/complex frontend architecture"* — devět negativních constraintů. Co má Claude **dělat** se nedozví. Negativní constraint zužuje prostor; pozitivní rozhodnutí ho fixuje. Pro úspěch potřebuješ druhé.

4. **„Do not pause for approval" se snaží overridenout chování, které prompt overridenout nemůže.** Pokud má uživatel nainstalovaný `superpowers:brainstorming` skill (viz `barca.md`), HARD-GATE skillu má vyšší prioritu než uživatelův prompt. Žádné množství „DO NOT ASK" v promptu HARD-GATE neobejde. Tony tohle neví a slibuje fix, který fungovat nemůže.

## Iterace 3 — anti-Symfony, anti-architecture

**Vstup uživatele.** *„Uprav tento prompt, aby Claude AI mohl vypracovat demo webovou aplikaci ze souboru lichess_db_puzzle.csv.zst. Musíme použít technologie, aby nám claude.ai vygeneroval aplikaci co nejrychleji. Ignoruj technologie, které používám."*

**Tonyho výstup.** Úplně vyřadí Symfony. Plain PHP 8.2+ s jediným `index.php` a manual file-based routingem. Přidá ALL CAPS sekce, *„IMPORTANT: Do NOT ask any questions, Do NOT ask for confirmation, Generate the full working solution immediately"*, *„Working demo in under 5 minutes setup time"*. Výstup je **slepený do jedné řádky bez newlines** (copy-paste artefakt) — desítky odrážek nasekaných do jedné věty.

**Co je tu špatně.**

1. **Překorekce: rezignace na framework dělá demo pomalejší, ne rychlejší.** Plain PHP + manual routing + raw PDO znamená, že **víc kódu** musí Claude napsat sám. Symfony šablony, routing, DI — to všechno *šetří* token-time, protože Claude jen orchestruje hotové building blocks. Tony otočil nápravu naruby.

2. **Slepený výstup bez newlines.** *„TECH STACK (MANDATORY – DO NOT CHANGE):Backend:PHP (plain PHP 8.2+)NO Symfony, NO frameworksSingle entry point (index.php)..."* — pokud uživatel tohle vlepí Claudovi, Claude buď ten chaos parsuje (a spálí prvních pár tahů), nebo se rozhodne nepochopit a začne hádat. Formální vada promptu, kterou Tony nezachytil.

3. **ALL CAPS + osm „DO NOT" instrukcí.** Křik nahrazuje strukturu. Stále neřeší root cause (skills override prompty). Stále jsou to negativní constrainty.

4. **Cíl *„Working demo in under 5 minutes setup time"*.** Marketingová věta, ne technický constraint. Claude to nemůže měřit ani garantovat. „Setup time" se rozpouští v rukou agenta — neví, co počítat (kompilace? import dat? odpověď browseru?).

## Strukturální problém celého meta-promptingu

**1. Drift premisy.** Mezi iteracemi 1 → 2 → 3 se fundamentální architektonická volba změnila třikrát: *Symfony enterprise s TDD* → *Symfony lite* → *žádný framework, plain PHP*. Tony jen reflektoval vibe každého nového vstupu. **Nezpochybnil**, že delta v zadání nedává smysl.

**2. Délka roste, kvalita klesá.** Iterace 1 má cca 200 řádků pravidel. Iterace 3 cca 40 imperativů ve slepené řádce. Žádná se neblíží úspěšné formuli z `../README.md` (4 věty = 4 rozhodnutí, hotová app ve 2 promptech).

**3. Hromadění instrukcí místo rozhodování.** Každá iterace přidává pravidla. *„Do not ask questions. Do not refactor. Do not introduce frameworks. Do not pause."* Pravidla nahrazují to, co prompt skutečně potřebuje — pojmenování architektury. Žádné množství instrukcí nezakryje, že uživatel **nezvolil**, jestli chce databázi nebo paměť, jestli chce server-side nebo client-side filter, jestli vlastní 5 minut nebo 5 hodin.

**4. Meta-prompting řeší špatný problém.** Uživatel věří, že prompt engineering je úzké hrdlo. Není. Úzké hrdlo je, že **uživatel se nerozhodl**, co chce. Tony tu rozhodovací práci neudělá. Tony ji jen rozmaže do víc textu, který vypadá jako odpověď, ale je to pořád ta samá nerozhodnost v lepším formátování.

**5. Delegace na Tonyho = stejná chyba jako delegace na CLAUDE.md.** Pokaždé, když uživatel přesune rozhodnutí o architektuře někam mimo svou hlavu, agent (Claude / Tony / junior) musí to rozhodnutí udělat za něj — a smyčka začíná tam, kde nemá kontext to rozhodnout dobře.

## Závěr

PROMPTY TONY.txt **není ukázka toho, jak vylepšovat prompty**. Je to ukázka toho, jak meta-prompting maskuje neudělanou architektonickou práci. Tři iterace nepřinášejí lepší prompt — přinášejí tři různé promptové verze pro tři různé nerozhodnuté projekty.

Lék není „lepší meta-prompt" ani „přísnější Tony". Lék je **udělat architekturu v hlavě**, zapsat ji jako 3–5 pozitivních rozhodnutí, a poslat to Claudovi přímo. Bez Tonyho.
