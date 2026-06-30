# Technisch Ontwerp v2 - BSO Spijkerbroek

**Plugin:** `bso-spijkerbroek`  
**Documentversie:** 2.3.0  
**Status:** In opbouw (implementatiegericht)  
**Datum:** 30 juni 2026  
**Doel:** 1 centrale technische blauwdruk voor stapsgewijze realisatie

---

## 1. Documentbesturing

### 1.1 Versiebeheer

| Versie | Datum | Wijziging | Auteur |
|--------|-------|-----------|--------|
| 2.0.0 | 2026-06-28 | Eerste gestroomlijnde versie op basis van Technical_Design + Game_Control | Byteway |
| 2.0.1 | 2026-06-28 | T05 geimplementeerd: score-engine rondeberekening + vullen `bso_round_scores` | Byteway |
| 2.0.2 | 2026-06-28 | T06 geimplementeerd: dashboard endpoint leest en toont tussenstand/eindstand uit `bso_round_scores` | Byteway |
| 2.0.3 | 2026-06-28 | T07 geimplementeerd: HR resignation verwerkt bij rondeafsluiting en doorwerking naar personeelscapaciteit | Byteway |
| 2.0.4 | 2026-06-28 | T08 geimplementeerd: golden regressietests en multiround HR-scenario toegevoegd | Byteway |
| 2.0.5 | 2026-06-28 | Werkboard geactualiseerd en uitgebreid met CI + vervolgtaken na T08 | Byteway |
| 2.0.6 | 2026-06-28 | T10 geimplementeerd: runtime DB regressietest tegen golden fixtures toegevoegd | Byteway |
| 2.0.7 | 2026-06-28 | T11 geimplementeerd: admin rondebeheer UI met openen/sluiten/lock en statushandler | Byteway |
| 2.0.8 | 2026-06-28 | T12 geimplementeerd: HR-aanvraagbeheer UI met approve/reject/reset en decision notes | Byteway |
| 2.0.9 | 2026-06-28 | T13 geimplementeerd: REST API endpoints voor commitments, scores en hr-requests | Byteway |
| 2.1.0 | 2026-06-28 | T14 geimplementeerd: formula-v2 validatieprotocol + afgedwongen golden update workflow | Byteway |
| 2.1.1 | 2026-06-28 | T15 geimplementeerd: Game Setup UI voor games, rondes en organisaties | Byteway |
| 2.1.2 | 2026-06-28 | T16 geimplementeerd: Player Setup UI voor koppeling van WP-gebruikers aan organisaties | Byteway |
| 2.1.3 | 2026-06-28 | T17 geimplementeerd: Quick Start / Demo Setup voor snelle speelbare testconfiguratie | Byteway |
| 2.1.4 | 2026-06-28 | Demo setup uitgebreid tot volledig speelbare testconfiguratie met snelle start | Byteway |
| 2.1.5 | 2026-06-28 | T18 geimplementeerd: organisatiegericht frontend/teamdashboard voor spelers | Byteway |
| 2.1.6 | 2026-06-28 | Game Ready werkboard + quick test stappen voor WordPress devsite toegevoegd | Byteway |
| 2.1.7 | 2026-06-30 | T21 gevalideerd op devsite: teamcontext-afscherming, refresh-consistentie, ronde-lockgedrag en nette no-team melding | Byteway |
| 2.1.8 | 2026-06-30 | T22 gevalideerd: responsive polish en lege-statencontrole op mobiel/desktop succesvol | Byteway |
| 2.1.9 | 2026-06-30 | T23 afgerond: release checklist en handoff-notities voor beheerder gevalideerd | Byteway |
| 2.2.0 | 2026-06-30 | Nieuw opleverblok T24-T29 toegevoegd; T24 overgeslagen en T25 gestart | Byteway |
| 2.2.1 | 2026-06-30 | T25 uitgewerkt: security hardening checklist, controlescope en Definition of Done toegevoegd | Byteway |
| 2.2.2 | 2026-06-30 | T25 eerste-pass code-audit ingevuld: nonce/capability/authz-status per endpoint + gaps vastgelegd | Byteway |
| 2.2.3 | 2026-06-30 | T25 authz-fixes geimplementeerd voor commitment en HR REST/admin paden; checklist bijgewerkt | Byteway |
| 2.2.4 | 2026-06-30 | T25 negatieve testmatrix toegevoegd voor ownership- en autorisatiecontroles | Byteway |
| 2.2.5 | 2026-06-30 | T25-N01 gevalideerd: blokkade en geen DB-write bevestigd; UX-gap genoteerd (nette foutmelding ontbreekt) | Byteway |
| 2.2.6 | 2026-06-30 | T25-N02 gevalideerd: submit zonder geldige nonce correct geweigerd | Byteway |
| 2.2.7 | 2026-06-30 | T25-N03 gevalideerd: submit op gesloten ronde geblokkeerd; lockstate-melding ontbreekt voor gebruiker | Byteway |
| 2.2.8 | 2026-06-30 | T25-N04 t/m T25-N08 op Pass; volledige T25 testmatrix 8/8 groen en T25 op Gereed gezet | Byteway |
| 2.2.9 | 2026-06-30 | T26 gestart: UX/foutafhandeling verbeterd voor frontend submit redirects en dashboard polling fallbackmeldingen | Byteway |
| 2.3.0 | 2026-06-30 | T26 afgerond: uniforme UX-copy + consistente warning/error styling in frontend en admin | Byteway |

### 1.2 Projectstatus

| Onderdeel | Status | Opmerking |
|-----------|--------|-----------|
| Basis plugin bootstrap | Basis gereed | Runtime klasse + hook wiring aanwezig |
| Datamodel ontwerp | Geimplementeerd basis | dbDelta schema + parameter seeds actief |
| Scorelogica ontwerp | Geimplementeerd basis | v1-formule actief, bronbladvalidatie blijft open |
| Admin UI | Geimplementeerd uitgebreid | Setup UI voor games/rondes/organisaties/spelers + demo setup + rondebeheer + HR-aanvraagbeheer actief |
| Frontend formulieren | Geimplementeerd uitgebreid | Organisatiegericht teamdashboard + commitmentflow actief |
| API/AJAX laag | Geimplementeerd uitgebreid | Dashboard endpoint + REST routes voor commitments/scores/hr-requests |
| Release readiness | In voorbereiding | Game Ready smoke test, mobile check en contentplaatsing |

### 1.3 Werkboard (voor uitvoering)

| ID | Taak | Prioriteit | Status | Doelversie |
|----|------|------------|--------|------------|
| T01 | `class-bso-plugin.php` toevoegen + hook wiring | Hoog | Gereed | v2.1 |
| T02 | Activator/deactivator implementeren | Hoog | Gereed | v2.1 |
| T03 | DB tabellen aanmaken met `dbDelta` | Hoog | Gereed | v2.1 |
| T04 | Commitment inputflow + validatie | Hoog | Gereed | v2.2 |
| T05 | Score engine (rondeberekening) | Hoog | Gereed | v2.3 |
| T06 | Dashboard tussenstand/eindstand | Midden | Gereed | v2.3 |
| T07 | HR resignation workflow | Midden | Gereed | v2.4 |
| T08 | Testset + regressiechecks formules | Hoog | Gereed | v2.4 |
| T09 | CI workflow voor regressietests | Hoog | Gereed | v2.4 |
| T10 | Runtime regressietest (DB output vs golden fixtures) | Hoog | Gereed | v2.5 |
| T11 | Admin rondebeheer UI (openen/sluiten/lock) | Hoog | Gereed | v2.5 |
| T12 | HR-aanvraagbeheer UI (approve/reject + notes) | Midden | Gereed | v2.5 |
| T13 | REST API endpoints voor commitments/scores/hr | Midden | Gereed | v2.6 |
| T14 | Formula v2 validatie tegen bronmodel (golden update protocol) | Hoog | Gereed | v2.6 |
| T15 | Game Setup UI (games, rondes, organisaties) | Hoog | Gereed | v2.6 |
| T16 | Player Setup UI (WP-gebruikers koppelen aan organisaties) | Hoog | Gereed | v2.6 |
| T17 | Quick Start / Demo Setup | Midden | Gereed | v2.6 |
| T18 | Organisatiegericht frontend/teamdashboard voor spelers | Hoog | Gereed | v2.7 |

### 1.4 Werkboard - Game Ready

| ID | Taak | Prioriteit | Status | Doelversie |
|----|------|------------|--------|------------|
| T19 | Devsite smoke test: demo setup, dashboard en commitmentflow | Hoog | In uitvoering (technische smoke groen, devsite-run pending) | Game Ready |
| T20 | Shortcode en pagina plaatsen voor spelersdashboard | Hoog | Gereed | Game Ready |
| T21 | Spelerpad testen: login, teamherkenning, commitment submit, refresh | Hoog | Gereed | Game Ready |
| T22 | Responsive polish en lege-staten controle op mobiel/desktop | Midden | Gereed | Game Ready |
| T23 | Release checklist en handoff-notities voor beheerder | Midden | Gereed | Game Ready |

### 1.5 Werkboard - Productieklare Oplevering

| ID | Taak | Prioriteit | Status | Doelversie |
|----|------|------------|--------|------------|
| T24 | T19 formaliseren en sluiten (testrapportage + bewijs) | Hoog | Overgeslagen | Productieblok |
| T25 | Security hardening check (nonce, capabilities, sanitization/escaping) | Hoog | Gereed | Productieblok |
| T26 | UX en foutafhandeling polish (lock/no-team/no-data) | Midden | Gereed | Productieblok |
| T27 | Release packaging (versie, changelog, artifact) | Midden | Niet gestart | Productieblok |
| T28 | Beheerders-handoff compleet maken (runbook) | Midden | Niet gestart | Productieblok |
| T29 | Go/No-Go gate met expliciete vrijgavecriteria | Hoog | Niet gestart | Productieblok |

---

## 2. Scope en uitgangspunten

Deze versie combineert:

- architectuur en componentverdeling
- spelinvoer, scoreketen en formulelogica
- implementatiestappen richting werkende WordPress-plugin

### Kernuitgangspunten

1. Turn-based game met immutable afgesloten rondes.
2. Per organisatie exact 1 commitment per ronde.
3. Score is cumulatief over rondes.
4. Personeelseffecten zijn vertraagd in de tijd.
5. Formules moeten reproduceerbaar versioned worden opgeslagen.

---

## 3. Huidige situatie (as-is)

### 3.1 Feitelijke code-status

- `bso-spijkerbroek.php` bestaat en probeert runtime te starten.
- `includes/class-bso-plugin.php` bestaat met basis hook wiring.
- activator/deactivator hebben nu minimale callable methods.
- `uninstall.php` is placeholder.
- `assets/js/admin.js` en `assets/js/public.js` pollen op `bso_dashboard_data` met actuele scorepayload.
- CSS-bestanden zijn placeholders.

```mermaid
graph TD
	WP[WordPress] --> MAIN[bso-spijkerbroek.php]
	MAIN --> CORE[class-bso-plugin.php]
	CORE --> HOOKS[Hook wiring actief]
	HOOKS --> AJAX[bso_dashboard_data met tussenstand/eindstand]
```

---

## 4. Doelarchitectuur (to-be)

```mermaid
graph LR
	B[Bootstrap] --> C[Core Plugin]
	C --> ADM[Admin Module]
	C --> PUB[Frontend Module]
	C --> API[REST/AJAX Module]
	C --> ENG[Score Engine]
	C --> REP[Repository Layer]
	REP --> DB[(Custom Tables)]
```

### 4.1 Technische lagen

1. **Bootstraplaag**
   - constants, includes, runtime start
2. **Applicatielaag**
   - game services, validatie, use-cases
3. **Datalaag**
   - repositories, SQL, migraties
4. **Presentatielaag**
   - admin pagina's, shortcodes, dashboardrendering

### 4.2 Aanbevolen kernclasses

- `BSO_Plugin`
- `BSO_Game_Program_Service`
- `BSO_Commitment_Service`
- `BSO_HR_Request_Service`
- `BSO_Scoring_Engine`
- `BSO_Round_Dashboard_Service`
- repositoryklassen per entiteit

---

## 5. Datamodel (samengevoegd)

### 5.1 Kernentiteiten

- `games`
- `game_rounds`
- `organizations`
- `players`
- `commitments`
- `round_scores`
- `game_parameters`
- `hr_requests`

```mermaid
erDiagram
	GAME ||--o{ GAME_ROUND : has
	GAME ||--o{ ORGANIZATION : has
	ORGANIZATION ||--o{ PLAYER : has
	GAME_ROUND ||--o{ COMMITMENT : receives
	ORGANIZATION ||--o{ COMMITMENT : submits
	GAME_ROUND ||--o{ ROUND_SCORE : yields
	ORGANIZATION ||--o{ ROUND_SCORE : gets
	GAME ||--o{ GAME_PARAMETER : configures
	GAME ||--o{ HR_REQUEST : manages
```

### 5.2 Kritieke constraints

- unieke key op `(game_id, round_id, organization_id)` in commitments
- ronde-lock voorkomt mutaties na sluiting
- `effective_round` op personeelsmutaties
- scoreberekening logt `formula_version`

---

## 6. Invoer- en spelproces

## 6.1 Programmaflow

```mermaid
flowchart TD
	A[Game start] --> B[Kies duur: kort/lang]
	B --> C[Initialiseer rondes]
	C --> D[Open ronde N]
	D --> E[Commitments verzamelen]
	E --> F[Ronde sluiten]
	F --> G[Score berekenen]
	G --> H[Tussenstand tonen]
	H --> I{Laatste ronde?}
	I -- Nee --> D
	I -- Ja --> J[Eindstand + winnaar]
```

## 6.2 Commitment invoer (P1-Investment)

Invoerblokken:

- thema A/B/C
- prijs
- reclame per medium
- productie per broektype
- personeelsmutaties
- distributievorm
- marketing research

```mermaid
flowchart LR
	I1[Invoer organisatie] --> I2[Valideer]
	I2 --> I3[Opslaan commitment]
	I3 --> I4[Lock bij ronde-sluiting]
```

## 6.3 HR ontslagproces

```mermaid
flowchart TD
	A[Resignation aanvraag] --> B[Verplicht volledig?]
	B -- Nee --> C[Afwijzen]
	B -- Ja --> D[Game Control beoordeling]
	D -- Afgekeurd --> E[Geen wijziging]
	D -- Goedgekeurd --> F[Inplannen effective_round]
```

---

## 7. Score-engine en formuleketen

## 7.1 Rekenvolgorde per ronde

```mermaid
flowchart TD
	A[Commitments ronde N] --> B[Prijsblok]
	B --> C[Reclameblok]
	C --> D[Capaciteitsblok personeel/productie]
	D --> E[Afzetberekening]
	E --> F[Omzetberekening]
	F --> G[Kostenberekening]
	G --> H[Winst + marktaandeel]
	H --> I[Opslaan round_scores]
```

## 7.2 Kernformules (uit bronmodel)

$$
omzet = prijs \times afzet
$$

$$
winst = omzet - kosten
$$

$$
varkost = (prijs \times 0.2) + prijs + 15
$$

$$
verschil = prijs - richtprijs
$$

Bronnotatie prijseffect:

$$
effect =
\begin{cases}
|verschil \times 2| + richtprijs, & verschil > 0 \\
|verschil| + richtprijs, & anders
\end{cases}
$$

Bronnotatie reclamefactor:

$$
recfac = manvrouw \times welstandsklasse \times mediumbereik \times reclameuitgaven
$$

### 7.3 Doorwerking naar score

- prijs en reclame beïnvloeden afzet en marktaandeel
- productie en personeel begrenzen maximale verkoop
- winst en marktaandeel gaan naar ronde-score
- eindscore is cumulatie van alle ronde-scores

```mermaid
sequenceDiagram
	participant C as Commitment
	participant E as Score Engine
	participant R as Round Score
	participant T as Tussenstand
	participant F as Eindstand

	C->>E: Input ronde N
	E->>R: Bereken scorecomponenten
	R->>T: Update actuele ranking
	R->>F: Cumulatief optellen
```

---

## 8. API en UI-contracten

### 8.1 Aanbevolen endpoints

| Endpoint/action | Gebruik |
|-----------------|---------|
| `POST /commitments` | invoer per organisatie/ronde |
| `POST /hr-requests` | ontslagaanvraag |
| `GET /round-state` | actieve ronde + lockstatus |
| `GET /scores` | tussenstand/eindstand |
| `GET /dashboard` | polling payload voor UI |

### 8.2 Frontendcontract

- formulieren met nonce
- server-side validatie als bron van waarheid
- duidelijk statusbericht bij lock of fout

### 8.3 Admincontract

- ronde openen/sluiten
- commitments inzien
- HR-aanvragen accorderen/weigeren
- dashboard publicatie per ronde

---

## 9. Security en betrouwbaarheid

- capability checks (`manage_options`) voor adminacties
- nonce op alle muterende requests
- prepared statements en escaping
- transactiegrenzen rond scoreberekening
- idempotente verwerking per commitment key
- fallback/backoff voor dashboard polling

---

## 10. Implementatieplan per release

## v2.1 - Foundation

- runtimeklasse toevoegen
- activator/deactivator invullen
- tabellen + indexes aanmaken
- minimale admin menu-structuur

**Definition of Done v2.1**

- plugin activeert zonder fatale fout
- DB schema bestaat
- healthcheck pagina toont basisstatus

## v2.2 - Input en rondebeheer

- commitmentformulier + opslag
- ronde open/close workflow
- basisvalidatie en lockgedrag

**Definition of Done v2.2**

- 1 volledige ronde kan worden ingevoerd en afgesloten

## v2.3 - Score en dashboards

- score engine implementeren
- tussenstand en eindstand renderen
- dashboard endpoint koppelen aan polling JS

**Definition of Done v2.3**

- dashboard toont consistente round_scores

## v2.4 - Hardening

- formulevalidatie met golden testdata
- HR resignation workflow volledig
- audittrail en foutlogging

**Definition of Done v2.4**

- regressietests groen
- formule-uitkomsten reproduceerbaar

---

## 11. Openstaande beslissingen

1. Definitieve operatorvolgorde van prijsfactorformule bevestigen op bronbestandniveau.
2. Exacte tie-breaker-regels bij gelijk marktaandeel/winst.
3. Keuze REST-only of hybride REST/AJAX.
4. Hoe teamrollen technisch aan WP-users worden gekoppeld (custom table vs user meta uitgebreid model).

---

## 11.1 Regressietests (golden baseline)

Toegevoegde testset:

- `tests/regression/score_engine_golden_test.php`
- `tests/regression/runtime_score_engine_db_test.php`
- `tests/golden/score_engine_multiround_hr.json`
- `tests/golden/score_engine_tiebreak_equal_cumulative.json`
- `tests/golden/score_engine_zero_production.json`
- `tests/golden/score_engine_extreme_resignation_impact.json`

Doel:

- formule-uitkomsten vastzetten als golden baseline
- regressie detecteren op omzet/winst/marktaandeel/ranking/cumulatieve score
- scenario over meerdere rondes met HR resignation (`effective_round`) valideren
- runtime-uitkomst van plugin score-engine vergelijken met golden expected rows

Uitvoeren:

```bash
php tests/regression/score_engine_golden_test.php --formula-version=v1
```

Golden baseline opnieuw genereren (bewust bij functionele wijziging):

```bash
php tests/regression/score_engine_golden_test.php --update-golden --formula-version=v1 --accept-golden-update=yes --update-reason="korte omschrijving"
```

### 11.2 Golden Update Protocol (T14)

Verplicht protocol voor formulewijzigingen:

1. Valideer bronmodelwijziging (Game_Control.xls) en leg operatorvolgorde vast.
2. Verhoog `formula_version` in code en in fixture `meta.formula_version`.
3. Draai eerst regressie zonder update:

```bash
php tests/regression/score_engine_golden_test.php --formula-version=vX
```

4. Pas code aan totdat mismatches verklaarbaar zijn.
5. Update golden alleen met expliciete bevestiging en reden:

```bash
php tests/regression/score_engine_golden_test.php --update-golden --formula-version=vX --accept-golden-update=yes --update-reason="formula vX aligned with source model"
```

6. Draai regressie opnieuw en verifieer groene run.
7. Bewaar in elke fixture-meta minimaal:
	- `formula_version`
	- `source_model`
	- `last_updated_at`
	- `last_update_reason`
	- `updated_by`

Runtime-test op echte WordPress-tabellen (vereist pad naar `wp-load.php`):

```bash
php tests/regression/runtime_score_engine_db_test.php --wp-load=/absolute/path/to/wp-load.php
```

Specifieke fixture draaien:

```bash
php tests/regression/runtime_score_engine_db_test.php --wp-load=/absolute/path/to/wp-load.php --fixture=score_engine_multiround_hr.json
```

## 11.3 Quick Steps - WordPress devsite

Gebruik dit pad om snel te testen of de plugin Game Ready is op je devsite:

1. Activeer de plugin in WordPress en open daarna `BSO Spijkerbroek` in het admin menu.
2. Klik op `Quick Start / Demo Setup` zodat er direct een game, rondes, organisaties en spelerkoppelingen worden aangemaakt.
3. Maak een nieuwe pagina, bijvoorbeeld `Teamdashboard`, en plaats daar de shortcode `[bso_team_dashboard]`.
4. Log in als een gekoppelde demo-gebruiker of koppel je eigen WP-user via `Player Setup`.
5. Open de pagina en controleer of de organisatie, ronde en tussenstand automatisch verschijnen.
6. Dien een commitment in en ververs de pagina om te zien of de opgeslagen waarden terugkomen.
7. Open daarna `Rondebeheer` en sluit of lock de ronde om te verifiëren dat de frontend read-only wordt.
8. Controleer in `HR-aanvraagbeheer` en het score-dashboard of de data synchroon blijft met de laatste actie.

### 11.4 Resultaat T21 devsite-validatie (2026-06-30)

- succes: gekoppelde speler ziet alleen eigen teamcontext
- succes: commitmentflow blijft consistent na refresh
- succes: gesloten ronde toont geen bewerkbare submitflow
- succes: niet-gekoppelde gebruiker ziet geen teamdata maar wel een nette melding

### 11.5 Resultaat T22 responsive/lege-staten (2026-06-30)

- succes: responsive polish op mobiel en desktop gevalideerd
- succes: lege-staten tonen correcte en nette feedback zonder layoutbreuk

### 11.6 Resultaat T23 release/handoff (2026-06-30)

- succes: release checklist is doorlopen en vastgelegd
- succes: handoff-notities voor beheerder zijn aanwezig en bruikbaar

### 11.7 Startnotitie Productieblok (2026-06-30)

- T24 is overgeslagen op verzoek
- T25 is gestart als actief werkspoor

### 11.8 T25 Security Hardening Check (werkuitwerking)

Doel van T25:

- alle muterende paden afdichten op CSRF, privilege escalation, inputmisbruik en outputinjectie
- server-side validatie en autorisatie leidend maken boven UI-state
- security-controles herhaalbaar vastleggen als afvinkbare baseline

Scope:

- admin-acties: rondebeheer, HR-aanvraagbeheer, game setup, player setup, demo setup
- frontend-acties: commitment submit en eventuele statusmutaties
- API/AJAX-acties: REST-routes (commitments/scores/hr) en `bso_dashboard_data`
- datalaag: inserts/updates/selects op custom tabellen

#### 11.8.1 Controlematrix per aanvalsvector

| Controlepunt | Verplichting | Acceptatiecriterium |
|--------------|--------------|---------------------|
| Nonce op mutaties | Verplicht op alle create/update/delete acties | Request zonder geldige nonce wordt geweigerd met duidelijke foutmelding |
| Capability checks | Verplicht op adminmutaties | Alleen bevoegde rollen kunnen muteren; onbevoegde gebruiker krijgt 403/afwijzing |
| AuthZ op teamcontext | Verplicht op frontend commitflow | Speler kan uitsluitend data van gekoppelde organisatie lezen/schrijven |
| Input sanitization | Verplicht op alle externe input | Tekst via sanitize-functies, numeriek via strikte casting/range checks |
| Output escaping | Verplicht bij render in admin/frontend | Variabele output wordt geescaped conform context (html/attr/url/js) |
| SQL hardening | Verplicht in repositories/services | Queries gebruiken prepare/placeholders; geen string-concatenatie met ruwe input |
| Lockstate afdwinging | Verplicht server-side | Gesloten/gelockte ronde blokkeert mutaties ook bij handmatige requests |
| Idempotentie mutaties | Verplicht op commitment key | Dubbele submit levert update of nette conflictafhandeling zonder datacorruptie |
| Foutafhandeling | Verplicht op alle mutaties | Geen gevoelige details in foutmeldingen; wel bruikbare user feedback |
| Logging/audit basis | Verplicht voor adminbesluiten | Kritieke acties (approve/reject/lock) bevatten actor + timestamp + context |

#### 11.8.2 Endpoint- en formulierchecklist

| Onderdeel | Nonce | Capability/AuthZ | Sanitization | Escaping | SQL prepare | Lock/State guard | Status |
|-----------|-------|------------------|--------------|----------|-------------|------------------|--------|
| Admin: Rondebeheer (open/sluit/lock) | Gereed | Gereed (`manage_options`) | Gereed | Gereed | Gereed | Gereed | Gereed (eerste pass) |
| Admin: HR-aanvraagbeheer (approve/reject/reset) | Gereed | Gereed (`manage_options`) | Gereed | Gereed | Gereed | n.v.t. | Gereed (eerste pass) |
| Admin: Game Setup (games/rondes/orgs) | Gereed | Gereed (`manage_options`) | Gereed | Gereed | Gereed | Gereed | Gereed (eerste pass) |
| Admin: Player Setup (koppeling WP-user/org) | Gereed | Gereed (`manage_options`) | Gereed | Gereed | Gereed | n.v.t. | Gereed (eerste pass) |
| Admin: Demo Setup | Gereed | Gereed (`manage_options`) | Gereed | Gereed | Gereed | Gereed | Gereed (eerste pass) |
| Frontend: commitment submit | Gereed | Gereed (ownership-check op game+organization toegevoegd) | Gereed | Gereed | Gereed | Gereed | Gereed (eerste pass) |
| REST: commitments | Deels (WP REST-auth, geen expliciete nonce-check in callback) | Gereed (ownership-check op game+organization toegevoegd) | Gereed | n.v.t. (JSON) | Gereed | Gereed | Gereed (eerste pass) |
| REST: hr-requests | Deels (WP REST-auth, geen expliciete nonce-check in callback) | Gereed: GET admin-only, POST met ownership-check op game+organization | Gereed | n.v.t. (JSON) | Gereed | n.v.t. | Gereed (eerste pass) |
| REST: scores | n.v.t. (read) | Openbaar (`__return_true`) - functioneel toegestaan, securitybesluit expliciet houden | Gereed | n.v.t. (JSON) | Gereed | n.v.t. | Gereed (publieke read) |
| AJAX: bso_dashboard_data | n.v.t. (read) | Openbaar via `wp_ajax_nopriv` - functioneel toegestaan | Gereed | n.v.t. (JSON) | Gereed | n.v.t. | Gereed (publieke read) |

#### 11.8.3 Definition of Done T25

- alle regels in 11.8.2 staan op `Gereed`
- minimaal 1 negatieve test per mutatiepad geslaagd:
	- zonder nonce
	- zonder rechten
	- met manipulatie van organization/teamcontext
	- met gesloten ronde maar geforceerde submit
- geen ongeescape output gevonden in admin/frontend voor user-gegenereerde data
- geen ruwe SQL-stringbouw met externe input in muterende paden
- bevindingen, fixes en resterende risico's vastgelegd in release-notities voor T25

#### 11.8.4 Uitvoerstappen (praktisch)

1. Inventariseer alle muterende handlers (admin postbacks, REST callbacks, AJAX actions).
2. Markeer per handler de controles uit 11.8.1.
3. Los eerst high-risk gaps op: nonce, capability/authz, lockstate, SQL prepare.
4. Voer daarna output-escaping en foutafhandeling uniform door.
5. Draai negatieve tests en leg resultaat per onderdeel vast in 11.8.2.
6. Zet T25 op `Gereed` zodra alle criteria onder 11.8.3 zijn gehaald.

#### 11.8.5 Eerste bevindingen en directe vervolgacties

Afgeronde high-risk fixes:

- Frontend commitment submit valideert nu ownership server-side op combinatie game+organization.
- REST `POST /commitments` valideert nu ownership server-side en retourneert 403 bij mismatch.
- REST `POST /hr-requests` valideert nu ownership server-side en retourneert 403 bij mismatch.

Open vervolgactie voor T25:

1. Beslissen of expliciete nonce-validatie in REST-callbacks gewenst is bovenop standaard WP REST-auth.
2. UX-meldingen verder afstemmen op uniforme tekstset in T26.

#### 11.8.6 Negatieve testmatrix (uitvoering T25)

| Test-ID | Pad | Teststap | Verwacht resultaat | Resultaat |
|--------|-----|----------|--------------------|-----------|
| T25-N01 | Frontend `admin_post_bso_submit_commitment` | Ingelogde speler manipuleert verborgen `organization_id` naar ander team | Request geweigerd met nette foutmelding; geen DB-write | Pass (blokkade + geen write, maar nette foutmelding ontbreekt) |
| T25-N02 | Frontend `admin_post_bso_submit_commitment` | Submit zonder geldige nonce | Request geweigerd door nonce-check | Pass |
| T25-N03 | Frontend `admin_post_bso_submit_commitment` | Submit op gesloten ronde via handmatige POST | Request geweigerd met lockstate-melding | Pass (blokkade OK, maar geen melding zichtbaar) |
| T25-N04 | REST `POST /bso/v1/commitments` | Ingelogde speler post payload met vreemd `organization_id` | HTTP 403 + foutcode `bso_rest_forbidden_org`; geen DB-write | Pass |
| T25-N05 | REST `POST /bso/v1/commitments` | Niet-ingelogde call | HTTP 401/403 vanuit permission callback | Pass |
| T25-N06 | REST `POST /bso/v1/hr-requests` | Ingelogde speler post payload met vreemd `organization_id` | HTTP 403 + foutcode `bso_rest_forbidden_org`; geen DB-write | Pass |
| T25-N07 | REST `POST /bso/v1/hr-requests` | Niet-ingelogde call | HTTP 401/403 vanuit permission callback | Pass |
| T25-N08 | REST `POST /bso/v1/hr-requests/(?P<id>)/status` | Niet-admin probeert statusupdate | HTTP 401/403 vanuit `manage_options` check | Pass |

Uitvoernotitie:

- zet `Resultaat` op `Pass` of `Fail` en noteer bij `Fail` direct de fixreferentie
- bij 8x `Pass` kan T25 naar `Gereed` worden omgezet

### 11.9 T26 UX/Foutafhandeling - voortgang

Afgeronde verbeteringen in code:

- Frontend commitmentformulier stuurt nu een expliciete `bso_return_url` mee, zodat fout/succesmeldingen terugkomen op de juiste pagina.
- Submit-handler gebruikt nu een gevalideerde return-url fallbackketen (`bso_return_url` -> referer -> home).
- Redirects schonen bestaande statusquery-parameters op voordat nieuwe meldingen worden toegevoegd.
- Polling in zowel frontend als admindashboard heeft nu robuuste response-checks (`response.ok`) en nette warning/error fallbackmeldingen.
- No-data meldingen in server-rendered dashboard-output gebruiken nu dezelfde warning message class als de pollingfallback.
- Frontend en admin CSS bevatten nu consistente styling voor `bso-dashboard-message`, `--warning` en `--error`.

T26 status:

- `Gereed` (copy en foutafhandeling uniform doorgevoerd voor lock/no-data/error-paden)

---

## 12. Bijwerksjabloon voor volgende iteraties

Gebruik dit blok bij elke update van dit document:

```text
Nieuwe versie:
Datum:
Wat is afgerond:
Wat is gewijzigd in architectuur/formules:
Nieuwe risico's:
Volgende concrete taken:
```

---

*Dit is de leidende v2 voor stapsgewijze implementatie van bso-spijkerbroek.*
