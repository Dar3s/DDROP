# DDrop

DDrop je webová aplikace zaměřená na sledování produktových dropů. Projekt běží v Dockeru, používá PostgreSQL databázi a obsahuje AI shrnutí dropů, které se volá přes vzdálený server.

## Funkce
- seznam upcoming dropů
- detail dropu
- AI shrnutí na tlačítko
- PostgreSQL databáze
- jednoduchý prototyp loginu a účtu

## Technologie
- PHP
- HTML
- CSS
- PostgreSQL
- Docker
- compose.yml

## Spuštění
Aplikace se po nasazení sama připojí do PostgreSQL, vytvoří potřebné tabulky a vloží základní seed data.

## AI
AI shrnutí se nevolá přímo lokálně, ale přes vzdálený endpoint na serveru ddrop.net, kde už je napojená Ollama.

## Autor
Daniel Zoubek
