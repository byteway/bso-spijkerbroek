# 🧱 Datamodel op basis van XML-analyse

Dit hoofdstuk beschrijft het datamodel van de plugin *BSO Spijkerbroek*, gebaseerd op analyse van de bestaande XML-bestanden.  
Het model vormt de basis voor het genereren van database-tabellen, admin formulieren en frontend functionaliteit.

---

## 📌 Overzicht architectuur

De plugin is gebaseerd op de volgende kernentiteiten:

1. Game  
2. Game Round  
3. Team  
4. Player  
5. Game Commitment (turn-based invoer + resultaat)  
6. Game Parameters  

Deze structuur is direct afgeleid van de XML-tabellen:
- `bso_team`
- `bso_gameround`
- `bso_investment`
- `bso_gameparameters`

---

## 📊 Entiteiten en tabellen

## 1. Game

Definieert een spelinstantie.

### Tabel: `wp_bso_games`

**Velden:**
- `id` (PK)
- `name`
- `description`
- `start_datetime`
- `end_datetime`
- `status` (draft / active / finished)

---

## 2. Game Round

Bevat de spelrondes (turn-based structuur).

### Tabel: `wp_bso_game_rounds`

**Velden:**
- `id` (PK)
- `game_id` (FK → wp_bso_games.id)
- `turn_number`
- `start_datetime`
- `end_datetime`
- `status` (open / closed / calculated)

---

## 3. Team (Leverancier)

Groepen spelers die samen een leverancier vormen.

### Tabel: `wp_bso_teams`

**Velden:**
- `id` (PK)
- `name`
- `description`
- `status` (active / inactive)

---

## 4. Player

Koppeling tussen WordPress users en teams.

### Tabel: `wp_bso_players`

**Velden:**
- `id` (PK)
- `wp_user_id` (FK → wp_users.ID)
- `team_id` (FK → wp_bso_teams.id)
- `email`
- `display_name`
- `role_in_team` (CEO / Marketing / etc.)

---

## 5. Game Commitment (kern van het spel)

Bevat alle keuzes én resultaten per team per beurt.

Gebaseerd op `bso_investment` XML.

### Tabel: `wp_bso_commitments`

---

### 🔹 Basisvelden

- `id` (PK)
- `game_id` (FK)
- `round_id` (FK)
- `team_id` (FK)

---

### 🔹 Inputvelden (door spelers)

#### Marketing
- `advertisement_tv`
- `advertisement_newspaper`
- `advertisement_family_weekly`
- `advertisement_luxury_weekly`
- `marketing_research`

#### Prijs
- `price_jeans`
- `price_factor`

#### Productie
- `production_segment_1`
- `production_segment_2`
- `production_segment_3`

#### Distributie / segment
- `distribution_form`
- `theme`

#### Personeel
- `hiring_staff`
- `layoff_staff`

---

### 🔹 Berekende velden (door systeem)

- `total_amount` (totale investering)
- `turnover` (omzet)
- `profit` (winst)
- `sale` (verkoop)
- `market_index` (marktaandeel)
- `total_employees`
- `media_total`
- `advertisement_factor`

---

## 6. Game Parameters

Configuratie van spelregels en rekenwaarden.

Gebaseerd op `bso_gameparameters`.

### Tabel: `wp_bso_game_parameters`

**Velden:**
- `id` (PK)
- `variable_name`
- `numeric_value`

---

### Voorbeelden van parameters
- max_players_per_team
- default_price_factor
- marketing_effect_factor
- production_cost
- number_of_turns

---

## 🔁 Relatiemodel (logisch)

```mermaid
erDiagram

    GAME ||--o{ GAME_ROUND : has
    GAME ||--o{ TEAM : contains
    TEAM ||--o{ PLAYER : has
    GAME_ROUND ||--o{ COMMITMENT : contains
    TEAM ||--o{ COMMITMENT : submits

    GAME {
        int id
        string name
    }

    GAME_ROUND {
        int id
        int turn_number
    }

    TEAM {
        int id
        string name
    }

    PLAYER {
        int id
        int wp_user_id
    }

    COMMITMENT {
        int id
        int profit
        int turnover
    }