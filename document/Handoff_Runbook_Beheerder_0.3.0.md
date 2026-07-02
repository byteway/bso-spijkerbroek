# Beheerders Handoff Runbook 0.3.0 - BSO Spijkerbroek

## Doel

Dit runbook helpt beheerders bij dagelijkse bediening, incidentafhandeling en veilige release/rollback van de plugin.

## 1. Omvang en versie

- Plugin: bso-spijkerbroek
- Versie: 0.3.0
- Datum: 2026-06-30

## 2. Beheerdersrechten

Minimaal vereist:

- WordPress capability: `manage_options`

Toegangspad:

- WordPress admin -> Spijkerbroekenspel

## 3. Eerste ingebruikname (Quick Start)

1. Activeer de plugin in WordPress.
2. Open adminmenu `Spijkerbroekenspel`.
3. Klik `Quick Start / Demo Setup`.
4. Maak of controleer een pagina met shortcode `[bso_team_dashboard]`.
5. Log in als gekoppelde speler en controleer teamdashboard + commitmentflow.

## 4. Dagelijkse beheertaken

1. Controleer ronde-status in `Rondebeheer` (open/closed/locked).
2. Verwerk HR-aanvragen in `HR-aanvraagbeheer` (approve/reject/reset).
3. Controleer scoreweergave en tussenstand na commit-updates.
4. Controleer op foutmeldingen in browserconsole en serverlog.

## 5. Veelvoorkomende operationele handelingen

### 5.1 Ronde openen/sluiten/locken

1. Ga naar `Rondebeheer`.
2. Kies game en ronde.
3. Gebruik actieknoppen `Open`, `Sluit`, `Lock`.
4. Verifieer dat frontend commitmentformulier read-only wordt bij gesloten ronde.

### 5.2 Speler koppelen/ontkoppelen

1. Ga naar `Player Setup`.
2. Selecteer game, organisatie en WordPress-gebruiker.
3. Koppel speler of gebruik `Ontkoppel`.
4. Verifieer in teamdashboard dat teamcontext correct wordt getoond.

### 5.3 HR-aanvraag verwerken

1. Open `HR-aanvraagbeheer`.
2. Kies actie `Approve`, `Reject` of `Reset`.
3. Vul `decision_note` en indien nodig `effective_round`.
4. Controleer dat status en scoredoorwerking correct zijn.

## 6. Incident runbook

### 6.1 Teamdashboard toont geen data

1. Controleer of speler gekoppeld is aan organisatie in actieve game.
2. Controleer of rondes bestaan voor gekozen game.
3. Controleer of scoredata beschikbaar is in huidige ronde.
4. Herlaad pagina en controleer netwerkrequests naar `bso_dashboard_data`.

### 6.2 Commitment kan niet worden opgeslagen

1. Controleer of ronde status `open` is.
2. Controleer of gebruiker is gekoppeld aan juiste organisatie.
3. Controleer nonce- en autorisatiefout in melding/log.
4. Herhaal submit na refresh.

### 6.3 Scoreweergave blijft leeg

1. Controleer of commitments zijn opgeslagen voor de ronde.
2. Trigger herberekening via ronde-afsluiting/opening workflow.
3. Controleer serverlog op PHP warnings/notices.

## 7. Release en rollback

### 7.1 Release

Gebruik als leidraad:

- `document/Release_Checklist_GameReady.md`
- `document/Release_Package_0.3.0.md`

Kernstappen:

1. Build artifact `bso-spijkerbroek-0.3.0.zip`.
2. Upload/activeer op doelomgeving.
3. Draai smoke test admin + spelerflow.

### 7.2 Rollback

1. Deactiveer huidige pluginversie.
2. Herinstalleer vorige bekende werkende zip.
3. Activeer opnieuw.
4. Draai korte smoke test (dashboard + commitment + lockstate).
5. Leg oorzaak en besluit vast in release notities.

## 8. Bekende aandachtspunten

1. T19 staat nog als in uitvoering in technisch werkboard.
2. Monitoring op JS/PHP fouten blijft aanbevolen bij intensief gebruik.
3. T29 Go/No-Go gate moet nog formeel worden uitgevoerd.

## 9. Overdrachtscheck (aftekenen)

- [ ] Plugin actief en versie gecontroleerd
- [ ] Adminmenu en kernpanelen werken
- [ ] Teamdashboard en commitmentflow werken
- [ ] Ronde-lockgedrag gevalideerd
- [ ] HR-aanvraagbeheer gevalideerd
- [ ] Release/rollback stappen gedeeld met beheerder
- [ ] Bekende aandachtspunten besproken
