# DDrop

DDrop je webová aplikace zaměřená na sledování produktových dropů, hlavně sneaker releasů. Uživatel může procházet dostupné dropy, zobrazit detail konkrétního produktu, nastavit cílovou cenu pro sledování a pracovat s daty uloženými v PostgreSQL databázi.

Tento repozitář obsahuje školní verzi projektu. Školní verze je zjednodušená tak, aby šla jednoduše spustit přes Docker Compose a aby ukazovala hlavní části projektu: webovou aplikaci, PostgreSQL databázi, přihlášení, price watchlist a AI shrnutí.

Vedle této školní verze existuje i širší hlavní DDrop projekt, který mám rozpracovaný na vlastním serveru. Ten obsahuje další funkce, například Discord bota, pokročilejší price tracking a napojení na externí služby. Tyto části nejsou přímo součástí tohoto odevzdaného školního repozitáře.

---

## Co obsahuje tato školní verze

Školní verze v tomto repozitáři obsahuje:

- zobrazení seznamu dostupných dropů
- detail konkrétního dropu
- přihlášení pomocí demo účtu uloženého v databázi
- uložení cílové ceny do price watchlistu
- zobrazení price watchlistu v uživatelském účtu
- AI shrnutí dropu přes OpenAI-compatible API konfiguraci
- ukládání historie AI generování do databáze
- PostgreSQL databázi
- Docker Compose konfiguraci
- PHP backend
- HTML/CSS frontend
- JavaScript pro AI generování bez reloadu stránky

---

## Použité technologie

- PHP
- HTML
- CSS
- JavaScript
- PostgreSQL
- Docker
- Docker Compose
- PDO

---

## Architektura školní verze

Projekt je rozdělený na dvě služby:

1. `web` — PHP aplikace běžící v Apache containeru
2. `db` — PostgreSQL databáze

Aplikace se k databázi připojuje přes hostname `db`, protože `db` je název databázové služby v `compose.yml`.

Docker Compose vytvoří interní síť, ve které se kontejnery vidí podle názvu služby. Webová aplikace se tedy nepřipojuje na `localhost`, ale na službu `db`.

---

## Databáze

Projekt používá PostgreSQL databázi. Databáze obsahuje tyto tabulky:

- `users`
- `drops`
- `watchlist`
- `ai_generations`

Aplikace z databáze čte i do ní zapisuje.

Čtení z databáze:

- seznam dropů na homepage
- detail konkrétního dropu
- uživatel při přihlášení
- watchlist v uživatelském účtu

Zápis do databáze:

- vytvoření demo uživatele
- seed základních dropů
- uložení cílové ceny do watchlistu
- uložení historie AI generování

Databáze se inicializuje přímo z aplikace přes `db.php`. To znamená, že při spuštění aplikace se vytvoří potřebné tabulky, pokud ještě neexistují.

---

## Demo účet

Při prvním spuštění se automaticky vytvoří demo účet:

```text
Email: demo@example.test
Heslo: demo123
