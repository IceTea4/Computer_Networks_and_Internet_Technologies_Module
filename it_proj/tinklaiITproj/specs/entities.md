# Duomenų bazės esybių aprašymas

## 1. Klausimas (klausimas)

**Paskirtis:** Saugo egzaminų klausimus su atsakymų variantais ir teisingu atsakymu.

### Atributai:

| Atributas | Tipas | Apribojimai | Aprašymas |
|-----------|-------|-------------|-----------|
| id | binary(16) | PRIMARY KEY, NOT NULL | Unikalus klausimo identifikatorius (UUID) |
| klausimas | varchar(500) | NOT NULL | Klausimo tekstas |
| atsakymai | varchar(500) | NOT NULL | Atsakymų variantai (JSON formatu) |
| tema | varchar(100) | NOT NULL, INDEX | Klausimo tema/kategorija |
| verte | int | NOT NULL | Klausimo vertė taškais |
| atsakymas | varchar(100) | NOT NULL | Teisingas atsakymas |

### Indeksai:
- `tema` - Pagreitina paiešką pagal temą

### Verslo taisyklės:
- Atsakymai saugomi JSON formatu kaip masyvas
- Verte turi būti teigiamas skaičius
- Klausimas gali būti naudojamas keliuose egzaminuose
- Klausimo ištrynimas nėra galimas, jei jis naudojamas bent viename egzamine

---

## 2. Egzaminas (egzaminas)

**Paskirtis:** Saugo egzaminų informaciją, įskaitant pavadinimą, datą ir trukmę.

### Atributai:

| Atributas | Tipas | Apribojimai | Aprašymas |
|-----------|-------|-------------|-----------|
| id | binary(16) | PRIMARY KEY, NOT NULL | Unikalus egzamino identifikatorius (UUID) |
| pavadinimas | varchar(100) | NOT NULL | Egzamino pavadinimas |
| data | timestamp | NULL, INDEX | Egzamino data ir laikas |
| trukme | int | NULL | Egzamino trukmė minutėmis |
| bandomasis | boolean | NOT NULL, DEFAULT 0 | Ar egzaminas yra bandomasis |
| perlaikomo_egzamino_id | binary(16) | NULL, FOREIGN KEY → egzaminas(id) | Nuoroda į pradinį egzaminą (jei tai perlaikymas) |

### Indeksai:
- `data` - Pagreitina paiešką pagal datą

### Ryšiai:
- **Rekursyvus ryšys (1:N):** Egzaminas gali būti susijęs su kitu egzaminu per `perlaikomo_egzamino_id`
- **Su egzamino_klausimas (1:N):** Vienas egzaminas turi daug klausimų
- **Su egzamino_rezultatas (1:N):** Vienas egzaminas turi daug rezultatų

### Verslo taisyklės:
- Bandomasis egzaminas leidžia studentams praktikuotis be rezultatų saugojimo
- Perlaikomo_egzamino_id naudojamas stebėti egzaminų perlaikymus
- Egzamino ištrynimas automatiškai ištrina susijusius įrašus egzamino_klausimas lentelėje (CASCADE DELETE)

---

## 3. Egzamino klausimas (egzamino_klausimas)

**Paskirtis:** Jungimo lentelė, siejanti egzaminus su klausimais (many-to-many ryšys).

### Atributai:

| Atributas | Tipas | Apribojimai | Aprašymas |
|-----------|-------|-------------|-----------|
| id | binary(16) | PRIMARY KEY, NOT NULL | Unikalus įrašo identifikatorius (UUID) |
| egzamino_id | binary(16) | NOT NULL, FOREIGN KEY → egzaminas(id) ON DELETE CASCADE | Nuoroda į egzaminą |
| klausimo_id | binary(16) | NOT NULL, FOREIGN KEY → klausimas(id) | Nuoroda į klausimą |

### Apribojimai:
- **UNIQUE KEY:** (egzamino_id, klausimo_id) - Užtikrina, kad tas pats klausimas negali būti pridėtas du kartus į tą patį egzaminą

### Ryšiai:
- **Su egzaminas (N:1):** Daug įrašų priklauso vienam egzaminui
- **Su klausimas (N:1):** Daug įrašų nurodo į vieną klausimą
- **Su egzamino_atsakymas (1:N):** Vienas įrašas turi daug atsakymų

### Verslo taisyklės:
- Veikia kaip jungimo lentelė tarp egzaminų ir klausimų
- CASCADE DELETE: Ištrynus egzaminą, ištrinami visi susiję įrašai
- Klausimų ištrynimas blokuojamas, jei jie naudojami egzamine
- Vartotojų atsakymai nurodo į šią lentelę, ne tiesiogiai į klausimą

---

## 4. Vartotojas (vartotojas)

**Paskirtis:** Saugo vartotojų paskyras su autentifikacijos informacija ir rolėmis.

### Atributai:

| Atributas | Tipas | Apribojimai | Aprašymas |
|-----------|-------|-------------|-----------|
| id | binary(16) | PRIMARY KEY, NOT NULL | Unikalus vartotojo identifikatorius (UUID) |
| vardas | varchar(100) | NOT NULL, UNIQUE, INDEX | Vartotojo vardas (login) |
| slaptazodis | varchar(100) | NOT NULL | Hash'uotas slaptažodis (SHA-256 + salt) |
| role | enum | NOT NULL, INDEX | Vartotojo rolė: 'administratorius', 'destytojas', 'vartotojas' |

### Indeksai:
- `vardas` - Pagreitina autentifikaciją
- `role` - Pagreitina filtravimą pagal roles

### Ryšiai:
- **Su egzamino_atsakymas (1:N):** Vienas vartotojas turi daug atsakymų
- **Su egzamino_rezultatas (1:N):** Vienas vartotojas turi daug rezultatų

### Verslo taisyklės:
- **Slaptažodžio hash'avimas:** SHA-256(vardas + slaptazodis + salt), base64 encoded
- **Rolės:**
  - `administratorius` - Pilna prieiga, gali valdyti vartotojus
  - `destytojas` - Gali kurti/redaguoti klausimus ir egzaminus
  - `vartotojas` - Gali laikyti egzaminus (default rolė registracijai)
- Vardo unikalumas užtikrina, kad negali būti dviejų vartotojų su tuo pačiu vardu
- Slaptažodžio reikalavimai: 5+ simboliai, mažosios, didžiosios raidės, skaičius, specialus simbolis

---

## 5. Egzamino atsakymas (egzamino_atsakymas)

**Paskirtis:** Saugo vartotojų atsakymus į egzaminų klausimus.

### Atributai:

| Atributas | Tipas | Apribojimai | Aprašymas |
|-----------|-------|-------------|-----------|
| id | binary(16) | PRIMARY KEY, NOT NULL | Unikalus atsakymo identifikatorius (UUID) |
| vartotojo_id | binary(16) | NOT NULL, FOREIGN KEY → vartotojas(id) | Nuoroda į vartotoją |
| egzamino_klausimo_id | binary(16) | NOT NULL, FOREIGN KEY → egzamino_klausimas(id) | Nuoroda į klausimą egzamine |
| atsakymas | varchar(100) | NOT NULL | Vartotojo pasirinktas atsakymas |

### Apribojimai:
- **UNIQUE KEY:** (vartotojo_id, egzamino_klausimo_id) - Vienas atsakymas per vartotoją per klausimą
- **INDEX:** (vartotojo_id, egzamino_klausimo_id) - Pagreitina paiešką

### Ryšiai:
- **Su vartotojas (N:1):** Daug atsakymų priklauso vienam vartotojui
- **Su egzamino_klausimas (N:1):** Daug atsakymų nurodo į vieną klausimą egzamine

### Verslo taisyklės:
- Atsakymas nurodo į egzamino_klausimas.id, ne tiesiogiai į klausimas.id
- Vartotojas gali išsaugoti atsakymus nebaigęs egzamino
- Išsaugojus atsakymus, senieji atsakymai ištrinami ir įrašomi nauji
- Unikalus constraint užtikrina, kad vartotojas negali pateikti dviejų atsakymų tam pačiam klausimui

---

## 6. Egzamino rezultatas (egzamino_rezultatas)

**Paskirtis:** Saugo baigtų egzaminų rezultatus ir statistiką.

### Atributai:

| Atributas | Tipas | Apribojimai | Aprašymas |
|-----------|-------|-------------|-----------|
| id | binary(16) | PRIMARY KEY, NOT NULL | Unikalus rezultato identifikatorius (UUID) |
| vartotojo_id | binary(16) | NOT NULL, FOREIGN KEY → vartotojas(id) | Nuoroda į vartotoją |
| egzamino_id | binary(16) | NOT NULL, FOREIGN KEY → egzaminas(id) | Nuoroda į egzaminą |
| verte | int | NOT NULL | Surinkti taškai |
| data | timestamp | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Egzamino baigimo data ir laikas |
| perlaikamas | boolean | NOT NULL | Ar tai perlaikymas |

### Indeksai:
- **(vartotojo_id, egzamino_id)** - Pagreitina rezultatų paiešką

### Ryšiai:
- **Su vartotojas (N:1):** Daug rezultatų priklauso vienam vartotojui
- **Su egzaminas (N:1):** Daug rezultatų priklauso vienam egzaminui

### Verslo taisyklės:
- Rezultatas sukuriamas tik kai vartotojas baigia egzaminą (ne tik išsaugo atsakymus)
- **Rezultato skaičiavimas:**
  ```
  total_points = SUM(klausimas.verte) visų klausimų
  earned_points = SUM(klausimas.verte) už teisingus atsakymus
  percentage = ROUND((earned_points / total_points) * 100)
  verte = earned_points
  ```
- Perlaikamas flag nurodo, ar egzaminas buvo laikomas antrą kartą
- Vienas vartotojas gali turėti tik vieną rezultatą per egzaminą (nebent tai perlaikymas)
- Data automatiškai užpildoma dabartine laiko žyma

---

## Esybių santykių diagrama (ER Diagram)

```
klausimas 1---N egzamino_klausimas N---1 egzaminas
                      |                      |
                      |                      | (rekursyvus)
                      |                      |
                    1 |                      1
                      |                      |
                      N                      N
              egzamino_atsakymas      egzamino_rezultatas
                      |                      |
                      N                      N
                      |                      |
                      +----------+-----------+
                                 |
                                 1
                            vartotojas
```

## Pagrindiniai constraint'ai ir indeksai

### Primary Keys:
- Visos lentelės naudoja binary(16) UUID tipo primary key

### Foreign Keys:
- egzaminas.perlaikomo_egzamino_id → egzaminas.id
- egzamino_klausimas.egzamino_id → egzaminas.id (ON DELETE CASCADE)
- egzamino_klausimas.klausimo_id → klausimas.id
- egzamino_atsakymas.vartotojo_id → vartotojas.id
- egzamino_atsakymas.egzamino_klausimo_id → egzamino_klausimas.id
- egzamino_rezultatas.vartotojo_id → vartotojas.id
- egzamino_rezultatas.egzamino_id → egzaminas.id

### Unique Constraints:
- vartotojas.vardas (UNIQUE)
- egzamino_klausimas(egzamino_id, klausimo_id) - Apsaugo nuo dublikatų
- egzamino_atsakymas(vartotoju_id, egzamino_klausimo_id) - Vienas atsakymas per klausimą

### Indexes:
- klausimas.tema - Paieška pagal temą
- egzaminas.data - Filtravimas pagal datą
- vartotojas.vardas - Autentifikacija
- vartotojas.role - Prieigos kontrolė
- egzamino_atsakymas(vartotoju_id, egzamino_klausimo_id) - Atsakymų užklausos
- egzamino_rezultatas(vartotojo_id, egzamino_id) - Rezultatų statistika

## Duomenų tipai ir kodavimas

### UUID formato naudojimas:
Visos primary key reikšmės yra **binary(16)** formato:
- PHP pusėje: `hex2bin(str_replace('-', '', $uuid))` - konvertavimas į binary
- SQL pusėje: `bin2hex($binary_id)` - konvertavimas atgal į string

### UTF-8 (utf8mb4) palaikymas:
Visos lentelės ir stulpeliai naudoja:
- `CHARACTER SET utf8mb4`
- `COLLATE utf8mb4_unicode_ci`

Tai užtikrina lietuviškų simbolių (ą, č, ė, ę, į, š, ų, ū, ž) korektiš ką veikimą.

## CASCADE DELETE hierarchija

```
egzaminas (DELETE)
    ↓ CASCADE
egzamino_klausimas (DELETE)
    ↓ PRESERVE
klausimas (PRESERVED)
```

**Svarbu:** Ištrinus egzaminą:
- ✅ Ištrinama: egzamino_klausimas įrašai
- ✅ Ištrinama: egzamino_atsakymas įrašai (per foreign key)
- ❌ Neištrinama: klausimas įrašai (išsaugomi būsimiems egzaminams)

## Saugumo aspektai

### Slaptažodžių saugojimas:
```php
hash = base64_encode(
    hash('sha256', vardas + slaptazodis + PASSWORD_SALT, true)
)
```
- Salt saugomas environment kintamajame
- SHA-256 hash algoritmas
- Base64 encoding saugojimui

### Role-based Access Control (RBAC):
- Administratorius: Pilna prieiga
- Destytojas: Klausimai + egzaminai (CRUD)
- Vartotojas: Tik egzaminų laikymas

### SQL Injection apsauga:
- Visos užklausos naudoja PDO prepared statements
- Named parameters (`:param`) vietoj pozicijos (`?`)
- Tipo validacija su `PDO::PARAM_INT` kur reikia