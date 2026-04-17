# DDrop

DDrop je webová aplikace zaměřená na sledování produktových dropů. Uživatel může procházet seznam dostupných dropů, zobrazit detail konkrétního produktu a pracovat s daty uloženými v databázi. Součástí projektu je také ukázková AI sekce v detailu dropu a návrh rozšíření o Discord bota pro doplňkové notifikace a watchlist funkce.

## Co aplikace umí

- zobrazit seznam dostupných dropů
- zobrazit detail dropu
- ukládání a čtení dat z databáze
- ukázkovou AI sekci u konkrétního dropu
- základ pro oblíbené dropy a watchlist
- návrh napojení na Discord bota pro notifikace a price tracking

## Použité technologie

- PHP
- HTML
- CSS
- PostgreSQL
- Docker
- compose.yml

## Architektura projektu

Projekt se skládá z několika částí:

1. webová aplikace
2. databáze

Webová aplikace komunikuje s databází, zapisuje do ní a čte z ní data. Databáze je samostatná služba spuštěná odděleně od aplikace. Projekt je připravený pro běh v Dockeru pomocí `compose.yml`.

## Databáze

Aplikace používá PostgreSQL databázi pro ukládání dropů a dalších souvisejících dat. Z databáze se data nejen čtou, ale i zapisují. Tím je splněn požadavek na reálnou komunikaci aplikace s databází.

## AI funkce

Aplikace obsahuje ukázkovou AI sekci v detailu dropu. V této veřejné verzi repozitáře není napojení na neveřejné AI služby nebo privátní endpointy součástí kódu. AI část je zde ponechána jako rozšiřitelný základ projektu.

## Discord bot

Součástí návrhu projektu je také Discord bot napsaný v Node.js. Bot je zamýšlen jako doplňkový nástroj napojený na stejnou databázi jako webová aplikace a slouží pro watchlist, price tracking a upozornění. V této veřejné verzi repozitáře není Discord bot hlavní součástí nasazení webové aplikace.

## Spuštění projektu

1. naklonovat repozitář z GitHubu
2. v kořeni projektu mít soubor `compose.yml`
3. nastavit připojení k databázi
4. spustit kontejnery
5. otevřít aplikaci v prohlížeči

## Cíl projektu

Cílem projektu je vytvořit funkční systém pro správu a sledování produktových dropů. Projekt propojuje webovou aplikaci, databázi a návrh dalšího rozšíření o AI a Discord bota. Výsledkem je praktická aplikace, na které je možné ukázat komunikaci s databází a připravenost systému pro další funkce.

## Poznámka

Tato veřejná verze repozitáře neobsahuje žádné privátní API klíče, hesla, tokeny ani neveřejné endpointy.
