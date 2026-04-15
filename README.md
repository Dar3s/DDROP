DDrop

DDrop je webová aplikace zaměřená na sledování produktových dropů. Uživatel může procházet seznam dostupných dropů, zobrazit detail konkrétního produktu, registrovat se, přihlásit se a pracovat s daty uloženými v databázi. Součástí projektu je také AI funkce pro automatické shrnutí dropu a Discord bot pro doplňkové notifikace a práci s watchlistem.

Co aplikace umí

zobrazit seznam dostupných dropů
zobrazit detail dropu
registraci a přihlášení uživatele
ukládání a čtení dat z databáze
AI shrnutí konkrétního dropu
základ pro oblíbené dropy a watchlist
napojení na Discord bota pro upozornění a price tracking

Použité technologie

PHP
HTML
CSS
databáze
Docker
compose.yml
Ollama jako vzdálená AI služba
Node.js pro Discord bota

Architektura projektu

Projekt se skládá z několika částí:

webová aplikace
databáze
AI služba
Discord bot

Webová aplikace komunikuje s databází, zapisuje do ní a čte z ní data. Databáze je samostatná služba. AI část je řešena přes Ollamu, která běží na jiném serveru než samotná aplikace. Aplikace tuto AI službu volá přes HTTP rozhraní. Další součástí projektu je Discord bot, který je napojený na databázi a umožňuje doplňkové funkce mimo webové rozhraní.

Databáze

Aplikace používá databázi pro ukládání uživatelů, dropů a dalších souvisejících dat. Z databáze se data nejen čtou, ale i zapisují. Tím je splněn požadavek na reálnou komunikaci aplikace s databází.

AI funkce

Aplikace využívá AI pro generování stručného shrnutí dropu. Tato funkce pomáhá uživateli rychle pochopit, čím je daný drop zajímavý a zda má smysl ho sledovat. AI model neběží lokálně v aplikaci, ale na samostatném zařízení nebo serveru, odkud je volán po síti.

Discord bot

Součástí projektu je také Discord bot napsaný v Node.js. Bot je napojený na stejnou databázi jako webová aplikace a slouží jako doplňkový nástroj. Umožňuje pracovat s watchlistem, testovat price tracking a posílat upozornění na změny ceny nebo splnění podmínek sledování produktu. Díky tomu projekt nefunguje jen jako web, ale i jako rozšířený systém s notifikacemi přes Discord.

Spuštění projektu

naklonovat repozitář z GitHubu
v kořeni projektu mít soubor compose.yml
nastavit připojení k databázi
nastavit připojení k AI službě
spustit kontejnery
otevřít aplikaci v prohlížeči
případně spustit Discord bota

Cíl projektu

Cílem projektu je vytvořit funkční systém pro správu a sledování produktových dropů. Projekt propojuje webovou aplikaci, databázi, AI funkce a Discord bota. Výsledkem je praktická aplikace, na které je možné ukázat komunikaci s databází, využití jazykového modelu i rozšíření systému o automatizované notifikace.
