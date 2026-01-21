# Sistemos Testavimo Procedūra

## Turinys
1. [Testavimo Aplinka](#testavimo-aplinka)
2. [Prieš Pradedant](#prieš-pradedant)
3. [Funkciniai Testai](#funkciniai-testai)
4. [Saugumo Testai](#saugumo-testai)
5. [Našumo Testai](#našumo-testai)
6. [Suderinamumo Testai](#suderinamumo-testai)
7. [Žinomų Problemų Sąrašas](#žinomų-problemų-sąrašas)

---

## Testavimo Aplinka

### Reikalavimai
- Docker Desktop 4.0+
- PHP 8.1+
- MySQL 8.0+
- Šiuolaikinė naršyklė (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)

### Sistemos Paleidimas
```bash
# Paleisti sistemą
docker-compose up -d

# Patikrinti statusą
docker-compose ps

# Prieiti prie sistemos
open http://localhost:8000
```

### Testinius Duomenis Atkurti
```bash
# Ištrinti esamą duomenų bazę ir sukurti naują su pradiniais duomenimis
docker-compose down -v
docker-compose up -d
```

---

## Prieš Pradedant

### Pradiniai Prisijungimo Duomenys

Po naujo sistemos paleidimo yra šie vartotojai:

| Vartotojo vardas | Slaptažodis | Rolė |
|------------------|-------------|------|
| admin | Admin123! | administratorius |
| destytojas1 | Destytojas1! | destytojas |
| studentas1 | Studentas1! | vartotojas |

### Patikrinti Sistemos Būseną

1. **Duomenų bazės ryšys:**
```bash
docker exec -it mysql mysql -uroot -prootpassword aistis_jakutonis -e "SELECT COUNT(*) as vartotojai FROM vartotojas;"
```

2. **PHP konteinerio būsena:**
```bash
docker-compose logs php | tail -20
```

3. **UTF-8 palaikymas:**
```bash
docker exec -it mysql mysql -uroot -prootpassword -e "SHOW VARIABLES LIKE 'character_set%';"
```

---

## Funkciniai Testai

### 1. Autentifikacija ir Autorizacija

#### TEST-AUTH-001: Registracija
**Tikslas:** Patikrinti vartotojo registracijos procesą

**Žingsniai:**
1. Eiti į http://localhost:8000/registracija.php
2. Įvesti naują vartotojo vardą (pvz., `testuotojas1`)
3. Įvesti galiojantį slaptažodį (pvz., `Test123!@`)
4. Pakartoti tą patį slaptažodį
5. Paspausti "Registruotis"

**Tikėtinas rezultatas:**
- ✓ Sėkmingo registracijos pranešimas
- ✓ Automatinis nukreipimas į prisijungimo puslapį
- ✓ Naujas vartotojas sukurtas su role `vartotojas`

**Neigiami testai:**
- Slaptažodis be didžiosios raidės → klaidos pranešimas
- Slaptažodis be skaičiaus → klaidos pranešimas
- Slaptažodis be spec. simbolio → klaidos pranešimas
- Slaptažodžiai nesutampa → klaidos pranešimas
- Vardas jau egzistuoja → klaidos pranešimas

#### TEST-AUTH-002: Prisijungimas
**Tikslas:** Patikrinti prisijungimo funkcionalumą

**Žingsniai:**
1. Eiti į http://localhost:8000/prisijungimas.php
2. Įvesti `admin` / `Admin123!`
3. Paspausti "Prisijungti"

**Tikėtinas rezultatas:**
- ✓ Sėkmingas prisijungimas
- ✓ Cookie `user_role` nustatytas į `administratorius`
- ✓ Nukreipimas į pagrindinį puslapį
- ✓ Navigacijoje rodomi atitinkamos rolės meniu punktai

**Testuoti su visomis rolėmis:**
- `admin` → administratorius
- `destytojas1` → destytojas
- `studentas1` → vartotojas

#### TEST-AUTH-003: Atsijungimas
**Tikslas:** Patikrinti atsijungimo funkcionalumą

**Žingsniai:**
1. Prisijungti kaip bet kuris vartotojas
2. Paspausti "Atsijungti" navigacijoje
3. Bandyti prieiti prie apriboto puslapio

**Tikėtinas rezultatas:**
- ✓ Cookie išvalomi
- ✓ Nukreipimas į prisijungimo puslapį
- ✓ Nebegalima prieiti prie apribotų puslapių

#### TEST-AUTH-004: Rolių Prieigos Kontrolė
**Tikslas:** Patikrinti, kad rolės apriboja prieigą

| Puslapis | vartotojas | destytojas | administratorius |
|----------|------------|------------|------------------|
| egzaminai.php | ✓ | ✓ | ✓ |
| klausimai.php | ✓ | ✓ | ✓ |
| atsakymai.php | ✓ (gali spręsti) | ✗ | ✗ |
| egzaminas.php | ✗ | ✓ | ✓ |
| klausimas.php | ✗ | ✓ | ✓ |
| vartotojai.php | ✗ | ✗ | ✓ |

**Žingsniai:**
1. Prisijungti kaip `studentas1`
2. Bandyti tiesioginį URL prieigą: http://localhost:8000/vartotojai.php

**Tikėtinas rezultatas:**
- ✗ Prieiga uždrausta arba nukreipimas

---

### 2. Klausimų Valdymas

#### TEST-QUEST-001: Klausimo Sukūrimas
**Tikslas:** Patikrinti klausimo kūrimo funkcionalumą

**Prieš testuojant:** Prisijungti kaip `destytojas1`

**Žingsniai:**
1. Eiti į http://localhost:8000/klausimas.php
2. Užpildyti formą:
   - Klausimas: `Koks yra PHP pilnas pavadinimas?`
   - Tema: `PHP Pagrindai`
   - Atsakymai: `PHP Hypertext Preprocessor`, `Personal Home Page`, `Private Hosting Protocol`, `Public Hypertext Parser`
   - Teisingas atsakymas: pasirinkti `A`
   - Vertė: `2`
3. Paspausti "Išsaugoti"

**Tikėtinas rezultatas:**
- ✓ Klausimas išsaugotas duomenų bazėje
- ✓ Nukreipimas į klausimų sąrašą
- ✓ Naujas klausimas matomas sąraše
- ✓ Lietuviški simboliai (ą, ė, į, ų, ū) rodomi teisingai

**Patikrinti duomenų bazėje:**
```bash
docker exec -it mysql mysql -uroot -prootpassword aistis_jakutonis -e "SELECT tema, atsakymas, verte FROM klausimas ORDER BY data DESC LIMIT 1;"
```

#### TEST-QUEST-002: Klausimo Redagavimas
**Žingsniai:**
1. Klausimų sąraše pasirinkti bet kurį klausimą
2. Paspausti "Redaguoti"
3. Pakeisti temą į `PHP Teorija`
4. Pakeisti vertę į `3`
5. Išsaugoti

**Tikėtinas rezultatas:**
- ✓ Pakeitimai išsaugoti
- ✓ Atnaujinta versija rodoma sąraše
- ✓ UUID nepasikeitė

#### TEST-QUEST-003: Klausimo Trynimas
**Žingsniai:**
1. Sukurti naują klausimą
2. Paspausti "Ištrinti"
3. Patvirtinti trynimą

**Tikėtinas rezultatas:**
- ✓ Klausimas ištrintas iš `klausimas` lentelės
- ✓ Jei klausimas nebuvo naudojamas egzaminuose, ištrynimas sėkmingas
- ✓ Jei klausimas naudojamas egzamine, apsaugota nuo trynimo (FOREIGN KEY)

#### TEST-QUEST-004: Klausimų Filtravimas pagal Temą
**Žingsniai:**
1. Eiti į http://localhost:8000/klausimai.php
2. Filtruoti pagal temą, pvz., `SQL`
3. Tikėtinas rezultatas: rodomi tik SQL temos klausimai

#### TEST-QUEST-005: Klausimų Paginacija
**Žingsniai:**
1. Užtikrinti, kad sistemoje yra >10 klausimų
2. Eiti į klausimų sąrašą
3. Paspausti kito puslapio numerį

**Tikėtinas rezultatas:**
- ✓ Rodoma 10 klausimų per puslapį
- ✓ Paginacija veikia teisingai
- ✓ URL parametras `page` atnaujinamas

---

### 3. Egzaminų Valdymas

#### TEST-EXAM-001: Egzamino Kūrimas
**Tikslas:** Patikrinti egzamino kūrimo procesą

**Prieš testuojant:** Prisijungti kaip `destytojas1`

**Žingsniai:**
1. Eiti į http://localhost:8000/egzaminas.php
2. Pridėti kelis klausimus:
   - **Individualiai:** Pasirinkti konkretų klausimą ir paspausti "Pridėti"
   - **Atsitiktinai:** Pasirinkti temas (pvz., SQL, PHP), įvesti kiekį (5), paspausti "Pridėti atsitiktinius"
3. Peržiūrėti pasirinktų klausimų sąrašą
4. Paspausti "Išsaugoti egzaminą"

**Tikėtinas rezultatas:**
- ✓ Naujas įrašas `egzaminas` lentelėje su UUID
- ✓ Klausimai susieti per `egzamino_klausimas` lentelę
- ✓ `data` laukas nustatytas į dabartinį timestamp
- ✓ Sesija išvalyta (`$_SESSION['selected_klausimai']`)

**Patikrinti duomenų bazėje:**
```bash
docker exec -it mysql mysql -uroot -prootpassword aistis_jakutonis -e "
SELECT e.id, COUNT(ek.klausimo_id) as klausimų_kiekis
FROM egzaminas e
LEFT JOIN egzamino_klausimas ek ON e.id = ek.egzamino_id
GROUP BY e.id
ORDER BY e.data DESC
LIMIT 1;
"
```

#### TEST-EXAM-002: Atsitiktinių Klausimų Dublikatų Prevencija
**Žingsniai:**
1. Egzamino kūrimo lange pridėti 3 atsitiktinius PHP klausimus
2. Vėl pridėti 3 atsitiktinius PHP klausimus
3. Patikrinti pasirinktų klausimų sąrašą

**Tikėtinas rezultatas:**
- ✓ Nėra dublikatų
- ✓ Iš viso 6 unikalūs klausimai

#### TEST-EXAM-003: Egzamino Peržiūra
**Žingsniai:**
1. Eiti į http://localhost:8000/egzaminai.php
2. Pasirinkti bet kurį egzaminą
3. Paspausti "Peržiūrėti"

**Tikėtinas rezultatas:**
- ✓ Rodomi visi egzamino klausimai
- ✓ Rodoma bendra vertė (suma klausimų `verte`)
- ✓ Kiekvienas klausimas rodo temą, atsakymus, teisingą atsakymą

#### TEST-EXAM-004: Egzamino Trynimas
**Žingsniai:**
1. Sukurti naują egzaminą su klausimais
2. Paspausti "Ištrinti"
3. Patvirtinti trynimą

**Tikėtinas rezultatas:**
- ✓ Įrašas ištrintas iš `egzaminas` lentelės
- ✓ Susiję įrašai ištrinami iš `egzamino_klausimas` (CASCADE)
- ✓ Susiję įrašai ištrinami iš `egzamino_atsakymas` (CASCADE)
- ✓ Originalūs klausimai išlieka `klausimas` lentelėje

#### TEST-EXAM-005: Egzaminų Filtravimas pagal Datą
**Žingsniai:**
1. Eiti į http://localhost:8000/egzaminai.php
2. Nustatyti "Nuo" datą: `2025-01-01`
3. Nustatyti "Iki" datą: `2025-12-31`
4. Paspausti "Filtruoti"

**Tikėtinas rezultatas:**
- ✓ Rodomi tik egzaminai nurodytu laikotarpiu
- ✓ URL parametrai `date_from` ir `date_to` išsaugoti
- ✓ Paginacija veikia su filtrais

#### TEST-EXAM-006: Egzamino Statistika
**Žingsniai:**
1. Egzaminų sąraše patikrinti statistikos skiltis

**Tikėtinas rezultatas:**
- ✓ Rodomas klausimų kiekis
- ✓ Rodoma bendra vertė (suma)
- ✓ Rodoma laikančių skaičius (iš `egzamino_rezultatas`)

---

### 4. Egzamino Laikymas

#### TEST-TAKE-001: Egzamino Sprendimas (Išsaugojimas)
**Tikslas:** Patikrinti studentų galimybę spręsti egzaminą ir išsaugoti atsakymus

**Prieš testuojant:**
- Prisijungti kaip `studentas1`
- Užtikrinti, kad yra sukurtas egzaminas su klausimais

**Žingsniai:**
1. Eiti į http://localhost:8000/egzaminai.php
2. Pasirinkti egzaminą
3. Paspausti "Laikyti egzaminą"
4. Atsakyti į kelis klausimus (ne visus)
5. Paspausti "Išsaugoti atsakymus"

**Tikėtinas rezultatas:**
- ✓ Atsakymai išsaugoti `egzamino_atsakymas` lentelėje
- ✓ `perlaikomas` flag = false
- ✓ Galima grįžti ir tęsti vėliau
- ✓ Seniau išsaugoti atsakymai pakeičiami naujais

**Patikrinti duomenų bazėje:**
```bash
docker exec -it mysql mysql -uroot -prootpassword aistis_jakutonis -e "
SELECT ea.atsakymas, k.tema
FROM egzamino_atsakymas ea
INNER JOIN egzamino_klausimas ek ON ea.egzamino_klausimo_id = ek.id
INNER JOIN klausimas k ON ek.klausimo_id = k.id
WHERE ea.vartotojo_id = (SELECT id FROM vartotojas WHERE vardas = 'studentas1')
ORDER BY ea.data DESC
LIMIT 5;
"
```

#### TEST-TAKE-002: Egzamino Užbaigimas
**Žingsniai:**
1. Tęsti egzaminą iš TEST-TAKE-001
2. Atsakyti į visus klausimus
3. Paspausti "Baigti egzaminą"

**Tikėtinas rezultatas:**
- ✓ Atsakymai išsaugoti
- ✓ Rezultatas apskaičiuotas ir įrašytas į `egzamino_rezultatas`
- ✓ `verte` laukas = teisingų atsakymų suma
- ✓ Rodomas rezultatas procentais
- ✓ Nukreipimas į rezultatų puslapį su pranešimu

**Apskaičiavimo formulė:**
```
Procentai = (Uždirbta vertė / Bendra vertė) * 100
```

**Patikrinti duomenų bazėje:**
```bash
docker exec -it mysql mysql -uroot -prootpassword aistis_jakutonis -e "
SELECT er.verte, er.perlaikamas,
       (SELECT SUM(k.verte) FROM egzamino_klausimas ek
        INNER JOIN klausimas k ON ek.klausimo_id = k.id
        WHERE ek.egzamino_id = er.egzamino_id) as max_verte
FROM egzamino_rezultatas er
WHERE er.vartotojo_id = (SELECT id FROM vartotojas WHERE vardas = 'studentas1')
ORDER BY er.data DESC
LIMIT 1;
"
```

#### TEST-TAKE-003: Negalima Perlaikyti
**Žingsniai:**
1. Užbaigti egzaminą kaip `studentas1`
2. Bandyti laikyti tą patį egzaminą dar kartą

**Tikėtinas rezultatas:**
- ✗ Sistema neleidžia laikyti jau laikyto egzamino
- ✗ Rodomas pranešimas: "Šį egzaminą jau išlaikėte"

#### TEST-TAKE-004: Laiko Apribojimai
**Žingsniai:**
1. Redaguoti egzaminą duomenų bazėje ir nustatyti `data` į praeitį:
```bash
docker exec -it mysql mysql -uroot -prootpassword aistis_jakutonis -e "
UPDATE egzaminas SET data = DATE_SUB(NOW(), INTERVAL 2 HOUR) WHERE id = UNHEX('...egzamino_uuid...');
"
```
2. Bandyti laikyti šį egzaminą kaip `studentas1`

**Tikėtinas rezultatas:**
- ✗ Sistema neleidžia laikyti pasibaigusio egzamino
- ✗ Rodomas pranešimas apie pasibaigusį laiką

---

### 5. Vartotojų Valdymas

#### TEST-USER-001: Vartotojų Sąrašas
**Prieš testuojant:** Prisijungti kaip `admin`

**Žingsniai:**
1. Eiti į http://localhost:8000/vartotojai.php

**Tikėtinas rezultatas:**
- ✓ Rodomi visi vartotojai
- ✓ Rodoma rolė kiekvienam vartotojui
- ✓ Yra mygtukai redaguoti/ištrinti

#### TEST-USER-002: Rolės Keitimas
**Žingsniai:**
1. Vartotojų sąraše pasirinkti `studentas1`
2. Pakeisti rolę į `destytojas`
3. Išsaugoti

**Tikėtinas rezultatas:**
- ✓ Rolė atnaujinta duomenų bazėje
- ✓ Atsijungus ir prisijungus kaip `studentas1`, turima destytojas privilegijos

#### TEST-USER-003: Vartotojo Trynimas
**Žingsniai:**
1. Sukurti naują testinį vartotoją
2. Paspausti "Ištrinti"
3. Patvirtinti trynimą

**Tikėtinas rezultatas:**
- ✓ Vartotojas ištrintas iš `vartotojas` lentelės
- ✗ Jei vartotojas turi egzamino rezultatų, gali būti FOREIGN KEY klaida

#### TEST-USER-004: Prieigos Kontrolė
**Žingsniai:**
1. Atsijungti kaip `admin`
2. Prisijungti kaip `destytojas1`
3. Bandyti prieiti: http://localhost:8000/vartotojai.php

**Tikėtinas rezultatas:**
- ✗ Prieiga uždrausta (tik administratorius gali valdyti vartotojus)

---

## Saugumo Testai

### SEC-001: SQL Injection Prevencija
**Tikslas:** Užtikrinti, kad sistema apsaugota nuo SQL injection

**Testiniai atvejai:**

1. **Prisijungimo forma:**
```
Vardas: admin' OR '1'='1
Slaptažodis: bet kas
```
**Tikėtinas rezultatas:** ✗ Prisijungimas nepavyksta

2. **Klausimų paieška:**
```
Tema: SQL'; DROP TABLE klausimas; --
```
**Tikėtinas rezultatas:** ✓ Prepared statements apsaugo, jokių lentelių neištrina

3. **Patikrinti kode:** Visi SQL užklausos naudoja PDO prepared statements su named parameters

### SEC-002: XSS Prevencija
**Tikslas:** Užtikrinti, kad vartotojo įvestis neperleidžia JavaScript kodo

**Testiniai atvejai:**

1. **Klausimo tekste:**
```
Klausimas: <script>alert('XSS')</script>
```
**Tikėtinas rezultatas:** ✓ Tekstas ekranuojamas su `htmlspecialchars()`, alert nevykdomas

2. **Vartotojo varde:**
```
Vardas: <img src=x onerror=alert('XSS')>
```
**Tikėtinas rezultatas:** ✓ Registracija gali nepavykti arba tekstas ekranuojamas

### SEC-003: CSRF Apsauga
**Problema:** Sistema **neturi** CSRF token mechanizmo

**Rekomenduojama:**
1. Įdiegti CSRF token generavimą kiekvienai formai
2. Validuoti token serverio pusėje

**Testavimas:**
```html
<!-- Išorinis puslapis gali siųsti POST užklausas -->
<form action="http://localhost:8000/klausimas.php" method="POST">
  <input name="tema" value="Hack">
  <input type="submit">
</form>
```

### SEC-004: Slaptažodžių Saugumas

**Patikrinti:**
1. ✓ Slaptažodžiai hashed su SHA-256
2. ✓ Naudojamas salt (`PASSWORD_SALT` aplinkos kintamasis)
3. ✓ Slaptažodžiai niekada nelogginami ar nerodomi

**Gerinimo rekomendacijos:**
- Naudoti `password_hash()` su bcrypt/argon2 vietoj SHA-256
- Salt turėtų būti unikalus kiekvienam vartotojui, ne globalus

### SEC-005: Cookie Saugumas

**Patikrinti cookie nustatymus:**
```php
setcookie('user_role', $user['role'], [
    'expires' => time() + 86400,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

**Testas:**
1. Prisijungti
2. Atidaryti Developer Tools → Application → Cookies
3. Patikrinti flags:
   - ✓ HttpOnly = true
   - ✓ SameSite = Strict
   - ✗ Secure = false (nes HTTP, ne HTTPS)

**Produkcinėje aplinkoje:** Pridėti `'secure' => true` HTTPS aplinkoje

---

## Našumo Testai

### PERF-001: Puslapio Įkėlimo Laikas

**Įrankiai:** Chrome DevTools → Network tab

**Testuoti puslapiai:**
- egzaminai.php (su >100 egzaminų)
- klausimai.php (su >100 klausimų)

**Tikėtinas rezultatas:**
- ✓ Puslapio įkėlimas < 2 sekundės
- ✓ SQL užklausos < 500ms

### PERF-002: Duomenų Bazės Indeksai

**Patikrinti indeksus:**
```sql
SHOW INDEX FROM klausimas;
SHOW INDEX FROM egzaminas;
SHOW INDEX FROM egzamino_klausimas;
```

**Rekomenduojami indeksai:**
- `klausimas.tema` (dažna paieška)
- `egzaminas.data` (filtravimas pagal datą)
- `egzamino_klausimas.egzamino_id` (JOIN)
- `egzamino_atsakymas.vartotojo_id` (JOIN)

### PERF-003: N+1 Query Problema

**Patikrinti egzamino peržiūroje:**
- ✗ Jei kiekvienam klausimui daroma atskira užklausa
- ✓ Turėtų būti viena JOIN užklausa

**Debugginti:**
```php
// Prieš SQL užklausas
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
```

---

## Suderinamumo Testai

### COMPAT-001: Naršyklės

**Testuoti šiose naršyklėse:**

| Naršyklė | Versija | Išvaizda | Funkcionalumas |
|----------|---------|----------|----------------|
| Chrome | 120+ | ✓ | ✓ |
| Firefox | 115+ | ✓ | ✓ |
| Safari | 16+ | ? | ? |
| Edge | 120+ | ✓ | ✓ |

**Pagrindiniai testai:**
- Registracija/prisijungimas
- Egzamino kūrimas
- Egzamino laikymas
- CSS išdėstymas

### COMPAT-002: Mobilūs Įrenginiai

**Testuoti (Chrome DevTools → Device Mode):**
- iPhone 12/13/14 (390x844)
- Samsung Galaxy S21 (360x800)
- iPad (810x1080)

**Patikrinti:**
- ✓ Responsive dizainas
- ✓ Formos lengvai pildomi
- ✓ Mygtukai pakankamai dideli

### COMPAT-003: UTF-8 Palaikymas

**Testuoti lietuviškas raides:** ą č ę ė į š ų ū ž

**Testiniai scenarijai:**
1. Klausimas su lietuvišku tekstu
2. Atsakymai su lietuviškomis raidėmis
3. Vartotojo vardas su lietuviškomis raidėmis

**Patikrinti visuose lygiuose:**
- ✓ HTML formose (input value)
- ✓ Duomenų bazėje (SELECT)
- ✓ Ekrane (rendering)

---

## Žinomų Problemų Sąrašas

### Kritinės Problemos

**ISSUE-001: Trūksta CSRF apsaugos**
- **Aprašymas:** Formos neturi CSRF token validacijos
- **Poveikis:** Galimi CSRF išpuoliai
- **Prioritetas:** Aukštas
- **Sprendimas:** Įdiegti CSRF token mechanizmą

**ISSUE-002: Slaptažodžio hash algoritmas**
- **Aprašymas:** Naudojamas SHA-256 su globaliu salt vietoj bcrypt/argon2
- **Poveikis:** Rainbow table atakos galimos
- **Prioritetas:** Vidutinis
- **Sprendimas:** Pereiti prie `password_hash()` su bcrypt

### Vidutinės Problemos

**ISSUE-003: Session hijacking**
- **Aprašymas:** Cookie neturi `Secure` flag (HTTP aplinkoje)
- **Poveikis:** Cookie gali būti perimtas per HTTP
- **Prioritetas:** Vidutinis
- **Sprendimas:** Įdiegti HTTPS produkcinėje aplinkoje

**ISSUE-004: Nevaliduojami file uploads**
- **Aprašymas:** Jei būtų įdiegtas failų įkėlimas, trūksta validacijos
- **Poveikis:** Galima įkelti kenkėjiškas failus
- **Prioritetas:** Žemas (funkcija dar neegzistuoja)

### Mažos Problemos

**ISSUE-005: Klaidos pranešimai per detalūs**
- **Aprašymas:** Kai kurie PDO exception pranešimai rodo DB struktūrą
- **Poveikis:** Information disclosure
- **Prioritetas:** Žemas
- **Sprendimas:** Įdiegti custom error handling

**ISSUE-006: Trūksta rate limiting**
- **Aprašymas:** Prisijungimo forma neturi rate limiting
- **Poveikis:** Galimi brute-force išpuoliai
- **Prioritetas:** Vidutinis
- **Sprendimas:** Įdiegti rate limiting (pvz., 5 bandymai per minutę)

---

## Automatizuotas Testavimas (Būsimi Darbai)

### PHPUnit Testai

**Planuojami testai:**
- `tests/Auth/RegistrationTest.php`
- `tests/Auth/LoginTest.php`
- `tests/Exam/ExamCreationTest.php`
- `tests/Question/QuestionCRUDTest.php`

**Pavyzdys:**
```php
<?php
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase {
    public function testValidLogin() {
        $pdo = new PDO(/* ... */);
        $result = login($pdo, 'admin', 'Admin123!');
        $this->assertTrue($result['success']);
        $this->assertEquals('administratorius', $result['role']);
    }
}
```

### Selenium/Cypress E2E Testai

**Planuojami scenarijai:**
- Pilnas egzamino kūrimo flow
- Pilnas egzamino laikymo flow
- Multi-user scenarijai

---

## Testavimo Ataskaita (Šablonas)

```markdown
# Testavimo Ataskaita

**Data:** 2025-XX-XX
**Testuotojas:** [Vardas]
**Aplinka:** Docker (PHP 8.1, MySQL 8.0)

## Praeiti Testai
- ✓ TEST-AUTH-001: Registracija
- ✓ TEST-AUTH-002: Prisijungimas
- ...

## Nepraėję Testai
- ✗ TEST-AUTH-004: Rolių kontrolė (vartotojas gali prieiti prie vartotojai.php)

## Rasti Defektai
1. **BUG-001: Lietuviški simboliai neatvaizduojami**
   - Žingsniai atkurti: ...
   - Tikėtinas rezultatas: ...
   - Faktinis rezultatas: ...
   - Screenshot: ...

## Rekomendacijos
- Įdiegti CSRF apsaugą
- Pereiti prie bcrypt slaptažodžiams
- Pridėti rate limiting prisijungimui

## Išvada
Sistema veikia stabiliai, bet reikia pataisyti saugumo spragas prieš produkciją.
```

---

## Kontaktai ir Pagalba

Problemos atveju:
- Tikrinti Docker logus: `docker-compose logs -f`
- Tikrinti MySQL: `docker exec -it mysql mysql -uroot -p`
- Perkurti aplinką: `docker-compose down -v && docker-compose up -d`

**Testavimo procesas turi būti atliekamas reguliariai po kiekvieno feature įdiegimo!**
