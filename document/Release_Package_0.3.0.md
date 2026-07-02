# Release Package 0.3.0 - BSO Spijkerbroek

## Scope

Deze release bundelt T25 (security hardening) en T26 (UX/foutafhandeling polish).

## Versiegegevens

- Plugin: bso-spijkerbroek
- Releaseversie: 0.3.0
- Datum: 2026-06-30

## Build-artifact

Doelbestand:

- bso-spijkerbroek-0.3.0.zip

Aanmaakcommando vanuit pluginroot:

```bash
rm -f bso-spijkerbroek-0.3.0.zip && \
zip -r bso-spijkerbroek-0.3.0.zip . \
  -x "./.git/*" \
  -x "./.github/*" \
  -x "./@eaDir/*" \
  -x "./tests/*" \
  -x "*.DS_Store"
```

## Snelle validatie na build

```bash
unzip -l bso-spijkerbroek-0.3.0.zip | head -n 40
```

Controlepunten:

1. `bso-spijkerbroek.php` is aanwezig in root van de zip.
2. `includes/`, `assets/`, `document/` en `uninstall.php` zitten in de zip.
3. `.git`, `.github`, `@eaDir` en `tests` ontbreken.

## Installatie op dev/staging

1. Upload `bso-spijkerbroek-0.3.0.zip` via WordPress pluginscherm.
2. Activeer de plugin.
3. Controleer in pluginlijst versie 0.3.0.
4. Voer Quick Start / Demo Setup uit en controleer teamdashboard.

## Release notes (kort)

- T25 afgerond: security hardening check + 8/8 negatieve tests geslaagd.
- T26 afgerond: consistente UX-foutmeldingen en fallback-states in frontend/admin.
- Verbeterde redirectflow na commitment submit met expliciete return-url.

## Rollback

1. Deactiveer 0.3.0.
2. Herinstalleer vorige bekende werkende zip.
3. Draai korte smoke test (dashboard laden + commitment opslaan + lockgedrag).
