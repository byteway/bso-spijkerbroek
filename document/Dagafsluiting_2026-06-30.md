# Dagsluiting - BSO Spijkerbroek (2026-06-30)

## Samenvatting vandaag (T20)

- T20 afgerond: spelersdashboardpagina op de devsite gepubliceerd met shortcode `[bso_team_dashboard]`.
- Devsiteweergave bevestigd op `192.168.2.165:8092`.
- Werkboardstatus in technisch ontwerp bijgewerkt: T20 staat nu op **Gereed**.

## Gevalideerde observaties op devsite

- De dashboardpagina rendert zichtbaar zonder directe frontend-fout.
- Teamcontext wordt herkend voor de ingelogde gebruiker.
- In de getoonde sessie zijn onder meer zichtbaar:
  - game: `Demo Spijkerbroekenspel`
  - team: `Team Alpha`
  - speler: `byteway`
  - ronde: `Ronde 8`
  - rondestatus: `closed`
  - commitmentstatus: `Opgeslagen`
- De tussenstand wordt geladen in het dashboard.

## Statusinterpretatie

- T20 is functioneel afgerond: pagina bestaat, shortcode staat live en de gepubliceerde dashboardweergave is bevestigd.
- T19 blijft nog open als aparte smoke test taak, omdat de volledige quick-step validatie en runtime `wp-load.php` test nog niet formeel zijn afgerond.
- T21 is nu het logische vervolg: spelerpad end-to-end testen met minimaal 2 rollen/gebruikers.

## Gewijzigde bestanden vandaag

- `document/Technical_Design_v2.md`
- `document/Dagafsluiting_2026-06-30.md`

## T21 - Testopzet spelerpad

- T21 gestart als vervolgstap na afgeronde publicatie van het spelersdashboard.
- Doel: end-to-end bevestigen dat het dashboard correct reageert voor minimaal 2 gebruikersscenario's.

### Testmatrix

1. Gekoppelde speler in actief team
  - login met gekoppelde gebruiker
  - open dashboardpagina met `[bso_team_dashboard]`
  - controleer teamnaam, spelernaam, ronde en tussenstand
  - controleer status `Opgeslagen` of `Nog niet ingediend`
  - indien ronde open is: wijzig commitment, sla op, ververs en controleer dat waarden terugkomen
  - indien ronde gesloten is: bevestig read-only gedrag en laatst opgeslagen versie

2. Niet-gekoppelde of verkeerde gebruiker
  - login met gebruiker zonder actieve koppeling
  - open dezelfde dashboardpagina
  - verwacht nette lege toestand: `Je bent nog niet gekoppeld aan een organisatie in een actieve game.`
  - bevestig dat geen data van een ander team zichtbaar is

### Verwachte uitkomst T21

- gekoppelde speler ziet alleen eigen teamcontext
- commitmentflow blijft consistent na refresh
- gesloten ronde toont geen bewerkbare submitflow
- niet-gekoppelde gebruiker ziet geen teamdata maar wel een nette melding

### Nog vast te leggen na uitvoering

- gebruikte testgebruikers
- PASS/FAIL per scenario
- eventuele regressies of UX-gaten
