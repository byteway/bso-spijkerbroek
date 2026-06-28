# Release Checklist - Game Ready (BSO Spijkerbroek)

## Doel

Deze checklist ondersteunt T23: gecontroleerd releasen op WordPress dev/staging met snelle rollback-optie.

## 1. Voor release

1. Werkboom is schoon of alle wijzigingen zijn bewust gecommit.
2. Plugin activeert zonder PHP fouten.
3. Database migraties/activator draaien zonder foutmeldingen.
4. Laatste regressietests zijn groen:
   - `php tests/regression/score_engine_golden_test.php --formula-version=v1`
   - `php tests/regression/runtime_score_engine_db_test.php --wp-load=/absolute/path/to/wp-load.php`
5. `document/Technical_Design_v2.md` en dagsluiting zijn bijgewerkt.

## 2. Installatie / Deploy

1. Plaats plugin in `wp-content/plugins/bso-spijkerbroek` (symlink of copy).
2. Activeer plugin in WordPress admin.
3. Controleer dat adminmenu `BSO Spijkerbroek` zichtbaar is.
4. Draai `Quick Start / Demo Setup` (indien testomgeving).
5. Publiceer of controleer pagina met shortcode `[bso_team_dashboard]`.

## 3. Functionele verificatie (smoke)

1. Admin flow:
   - game bestaat
   - rondes bestaan
   - organisaties bestaan
   - spelers zijn gekoppeld
2. Speler flow:
   - inloggen als gekoppelde gebruiker
   - teamdashboard toont juiste organisatie en ronde
   - commitment opslaan lukt
   - opgeslagen waarden komen terug na refresh
3. Rondeflow:
   - ronde sluiten of locken in admin
   - frontend wordt read-only voor commitment
4. Scoreflow:
   - tussenstand zichtbaar
   - eigen organisatie is gemarkeerd

## 4. Non-functioneel

1. Desktop en mobiel gecontroleerd op teamdashboard.
2. Geen zichtbare JS errors in browser console tijdens hoofdflow.
3. Geen nieuwe PHP warnings/notices in serverlogs tijdens smoke test.

## 5. Rollback plan

1. Deactiveer nieuwe pluginversie in WordPress.
2. Herstel vorige bekende werkende pluginmap/commit.
3. Activeer opnieuw en voer korte smoke test uit:
   - adminmenu zichtbaar
   - scoredashboard laadt
   - commitment kan opgeslagen worden
4. Leg rollback-reden vast in release notities.

## 6. Acceptatiecriteria

Release is Game Ready als:

1. Alle smoke checks in sectie 3 geslaagd zijn.
2. Geen blocker-severity issues openstaan.
3. Rollback-pad is getest of aantoonbaar uitvoerbaar.
4. Teamdashboard en commitmentflow bruikbaar zijn zonder handmatige ID-invoer.

## 7. Release notities (invullen)

- Datum:
- Omgeving:
- Versie/commit:
- Uitvoerder:
- Resultaat smoke test:
- Bekende beperkingen:
- Go/No-Go besluit:
