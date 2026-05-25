# 📄 Functionele beschrijving – WordPress plugin *BSO Spijkerbroek*

## 1. Doel en context
De plugin **“bso-spijkerbroek”** ondersteunt het registratieproces van leerlingen die zich willen aanmelden als *spijkerbroek leverancier* via een WordPress website.

De plugin biedt:
- Een **speciaal registratieformulier** voor leerlingen
- Automatische toekenning van de **rol “leverancier”**
- Een gecontroleerde en uniforme onboarding van gebruikers

---

## 2. Scope van de functionaliteit
De plugin richt zich op:

✅ Registratie van nieuwe gebruikers  
✅ Vastleggen van aanvullende leerlinggegevens  
✅ Automatisch toekennen van een specifieke rol  
✅ Integratie met WordPress user management  

Niet in scope (optioneel uitbreidbaar):
- Goedkeuringsflows
- Betalingen of membership
- Complexe autorisatie buiten rollen  

---

## 3. Gebruikersrollen en typen

### 3.1 Gebruikerstypes
| Type gebruiker | Omschrijving |
|----------------|-------------|
| Leerling | Nieuwe gebruiker die zich registreert |
| Leverancier | Leerling met specifieke rol na registratie |
| Beheerder | WordPress admin |

### 3.2 Rolgebruik
- Nieuwe gebruiker krijgt **automatisch rol: `leverancier`**
- Deze rol bepaalt:
  - Toegang tot bepaalde onderdelen
  - Mogelijke acties (bijv. content toevoegen of profiel beheren)

---

## 4. Hoofdproces: Registratieflow

### 4.1 Processtappen
1. Leerling opent registratiepagina  
2. Leerling vult speciaal formulier in  
3. Validatie van invoer  
4. Gebruiker wordt aangemaakt in WordPress  
5. Rol *leverancier* wordt automatisch toegekend  
6. Gebruiker ontvangt bevestiging (optioneel e-mail)  

### 4.2 Procesdiagram (logisch)
[Bezoek registratiepagina]
↓
[Invullen formulier]
↓
[Validatie gegevens]
↓
[Gebruiker aanmaken]
↓
[Rol = leverancier toekennen]
↓
[Account actief]

```markdown
# 📄 Functionele beschrijving – WordPress plugin *BSO Spijkerbroek*

## 1. Doel en context
De plugin **“bso-spijkerbroek”** ondersteunt het registratieproces van leerlingen die zich willen aanmelden als *spijkerbroek leverancier* via een WordPress website.

De plugin biedt:
- Een **speciaal registratieformulier** voor leerlingen
- Automatische toekenning van de **rol “leverancier”**
- Een gecontroleerde en uniforme onboarding van gebruikers

---

## 2. Scope van de functionaliteit
De plugin richt zich op:

✅ Registratie van nieuwe gebruikers  
✅ Vastleggen van aanvullende leerlinggegevens  
✅ Automatisch toekennen van een specifieke rol  
✅ Integratie met WordPress user management  

Niet in scope (optioneel uitbreidbaar):
- Goedkeuringsflows
- Betalingen of membership
- Complexe autorisatie buiten rollen  

---

## 3. Gebruikersrollen en typen

### 3.1 Gebruikerstypes
| Type gebruiker | Omschrijving |
|----------------|-------------|
| Leerling | Nieuwe gebruiker die zich registreert |
| Leverancier | Leerling met specifieke rol na registratie |
| Beheerder | WordPress admin |

### 3.2 Rolgebruik
- Nieuwe gebruiker krijgt **automatisch rol: `leverancier`**
- Deze rol bepaalt:
  - Toegang tot bepaalde onderdelen
  - Mogelijke acties (bijv. content toevoegen of profiel beheren)

---

## 4. Hoofdproces: Registratieflow

### 4.1 Processtappen
1. Leerling opent registratiepagina  
2. Leerling vult speciaal formulier in  
3. Validatie van invoer  
4. Gebruiker wordt aangemaakt in WordPress  
5. Rol *leverancier* wordt automatisch toegekend  
6. Gebruiker ontvangt bevestiging (optioneel e-mail)  

### 4.2 Procesdiagram (logisch)


\[Bezoek registratiepagina]
↓
\[Invullen formulier]
↓
\[Validatie gegevens]
↓
\[Gebruiker aanmaken]
↓
\[Rol = leverancier toekennen]
↓
\[Account actief]


## 5. Functionele eisen

### 5.1 Registratieformulier

**Verplichte velden**
- Voornaam  
- Achternaam  
- E-mailadres  
- Wachtwoord  

**Specifieke leerlingvelden (voorbeeld)**
- School / groep  
- Leeftijd  
- Toestemming (checkbox)  

**Validaties**
- E-mail uniek  
- Wachtwoord voldoet aan eisen  
- Verplichte velden niet leeg  

---

### 5.2 Gebruiker aanmaken
Bij succesvolle registratie:

- WordPress user wordt aangemaakt  
- Username = e-mail (of gegenereerd)  
- Status = actief  

---

### 5.3 Roltoekenning

Na registratie wordt automatisch:

- Rol = `leverancier`

**Functioneel gedrag:**
- Gebruiker hoeft GEEN rol te kiezen  
- Rol is hardcoded/configureerbaar in plugin  
- Eén gebruiker = één primaire rol  

---

### 5.4 Beheerfunctionaliteit

Voor beheerder:

- Plugin actief/inactief zetten  
- Registratiepagina beheren (shortcode of blok)  
- Optioneel:
  - Rol aanpassen  
  - Velden configureren (toekomstige uitbreiding)  

---

## 6. Gebruikersinteractie (UI)

### 6.1 Registratiepagina
- Heldere titel: “Aanmelden als spijkerbroek leverancier”  
- Formulier met duidelijke instructies  
- Foutmeldingen per veld  

### 6.2 Feedback
Bij succesvolle registratie:
- Meldtekst:  
  - “Je account is aangemaakt als leverancier”  
- Optioneel:
  - automatische login  
  - redirect naar dashboard  

---

## 7. Technische randvoorwaarden (functioneel benoemd)

- Plugin integreert met **WordPress user systeem**  
- Gebruik van:
  - WordPress registratieproces  
  - Roles & capabilities model  
- Formulier beschikbaar via:
  - shortcode (bijv. `[bso_spijkerbroek_register]`)  
  - of Gutenberg blok (optioneel)  

---

## 8. Beveiliging en validatie

Minimale eisen:

- Input validatie en sanitization  
- CSRF bescherming (nonce)  
- Geen directe toekenning admin-rechten  
- Rate limiting (optioneel)  

---

## 9. Uitbreidbaarheid

De plugin is ontworpen om uit te breiden met:

### Mogelijke uitbreidingen
- ✅ Meerdere groepen (teams/clans)  
- ✅ Approval flow (docent moet goedkeuren)  
- ✅ Custom dashboard voor leveranciers  
- ✅ API / Dataverse koppeling  
- ✅ Groepsindeling voor games of projecten  

---

## 10. Acceptatiecriteria

De plugin is functioneel gereed als:

- [ ] Gebruiker kan zich registreren via formulier  
- [ ] Gebruiker wordt correct aangemaakt  
- [ ] Rol “leverancier” wordt automatisch toegekend  
- [ ] Validaties werken correct  
- [ ] Beheerder ziet gebruiker in WordPress admin  

