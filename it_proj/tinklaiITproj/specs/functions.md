# Funkcijų specifikacija (verslo lygis)

## Turinys
1. [Autentifikacija](#autentifikacija)
2. [Egzaminų valdymas](#egzaminų-valdymas)
3. [Klausimų valdymas](#klausimų-valdymas)
4. [Egzaminų laikymas](#egzaminų-laikymas)
5. [Rezultatų peržiūra](#rezultatų-peržiūra)
6. [Vartotojų administravimas](#vartotojų-administravimas)

---

## Autentifikacija

### 1. Registracija

**Prieiga:** Visi (neprisijungę vartotojai)

**Aprašymas:** Naujo vartotojo paskyros sukūrimas sistemoje.

**Įvedami duomenys:**
- Vartotojo vardas
- Slaptažodis
- Slaptažodžio patvirtinimas

**Rezultatas:**
- Sukuriama nauja vartotojo paskyra su role "Vartotojas"
- Rodomas pranešimas "Registracija sėkminga!"
- Galima prisijungti su sukurtu vardu ir slaptažodžiu

**Validacija:**
- Vartotojo vardas turi būti unikalus
- Slaptažodžiai turi sutapti
- Slaptažodis turi atitikti saugumo reikalavimus:
  - Bent 5 simboliai
  - Turi būti mažųjų ir didžiųjų raidžių
  - Turi būti skaičių
  - Turi būti specialių simbolių

---

### 2. Prisijungimas

**Prieiga:** Visi (neprisijungę vartotojai)

**Aprašymas:** Prisijungimas prie sistemos su vardu ir slaptažodžiu.

**Įvedami duomenys:**
- Vartotojo vardas
- Slaptažodis

**Rezultatas:**
- Sėkmingo prisijungimo atveju nukreipiama į egzaminų sąrašą
- Įsimenama vartotojo sesija (24 valandoms)
- Rodomas turinys pagal vartotojo rolę

**Klaidos:**
- "Neteisingas vartotojo vardas arba slaptažodis" - jei duomenys neteisingi

---

### 3. Atsijungimas

**Prieiga:** Prisijungę vartotojai

**Aprašymas:** Atsijungimas nuo sistemos.

**Įvedami duomenys:**
- Nėra (tik mygtuko paspaudimas)

**Rezultatas:**
- Baigiama vartotojo sesija
- Nukreipiama į prisijungimo puslapį

---

## Egzaminų valdymas

### 4. Egzaminų sąrašo peržiūra

**Prieiga:** Visi (įskaitant neprisijungusius)

**Aprašymas:** Peržiūrėti visų egzaminų sąrašą su galimybe filtruoti pagal datą.

**Įvedami duomenys:**
- Data nuo (neprivaloma)
- Data iki (neprivaloma)
- Puslapio numeris (automatinis)

**Rezultatas:**
- Rodomas egzaminų sąrašas lentelėje su informacija:
  - Egzamino pavadinimas
  - Data ir laikas
  - Trukmė minutėmis
  - Klausimų skaičius
  - Bendras galimų taškų skaičius
  - Temos (atskirtos kableliais)

**Papildoma informacija pagal rolę:**

**Vartotojui (prisijungusiam):**
- Egzamino statusas:
  - "Nebaigta (X%)" - jei egzaminas pradėtas bet nebaigtas
  - "Baigta (X%)" - jei egzaminas baigtas su rezultatu
- Mygtukai:
  - "Vykdyti" - jei egzaminas šiuo metu vyksta
  - "Peržiūrėti" - jei tai bandomasis egzaminas

**Dėstytojui/Administratoriui:**
- Mygtukai:
  - "Trinti" - ištrinti egzaminą
  - "Rezultatai" - peržiūrėti egzamino rezultatus (jei egzaminas pasibaigęs)

**Filtravimas:**
- Galima filtruoti pagal datos intervalą
- Filtrai išsaugomi naršant tarp puslapių
- Mygtukas "Valyti filtrus" grąžina į pradinę būseną

**Puslapiavimas:**
- Rodoma po 10 egzaminų per puslapį
- Navigacija: Pirmas | Ankstesnis | Puslapis X iš Y | Kitas | Paskutinis

---

### 5. Naujo egzamino kūrimas

**Prieiga:** Dėstytojas, Administratorius

**Aprašymas:** Sukurti naują egzaminą pasirenkant klausimus iš klausimų banko.

**Žingsniai:**

#### 5.1. Klausimų pasirinkimas

**Įvedami duomenys:**
- Klausimų sąraše:
  - Galima filtruoti pagal temą
  - Rodomi klausimai po 10 per puslapį

**Veiksmai:**
- **"Pridėti" mygtukas** - pridėti konkretų klausimą į egzaminą
- **"Pašalinti" mygtukas** - pašalinti klausimą iš egzamino
- **"Išvalyti visus" mygtukas** - pašalinti visus pasirinktus klausimus

**Atsitiktinių klausimų pridėjimas:**
- Pasirinkti viena ar kelias temas (checkbox)
- Įvesti klausimų kiekį
- **"Pridėti atsitiktinius" mygtukas** - sistema automatiškai pasirenka nurodytą kiekį atsitiktinių klausimų iš pasirinktų temų
- Sistema automatiškai išvengia dublikatų

**Pasirinkimo lentelė:**
- Rodomi visi pasirinkti klausimai
- Matomas klausimo tekstas, tema ir vertė taškais
- Bendras pasirinktų klausimų taškų suma

#### 5.2. Egzamino parametrų nustatymas

**Įvedami duomenys:**
- **Egzamino pavadinimas** (privalomas)
- **Bandomasis egzaminas** (checkbox):
  - Jei pažymėta - data ir trukmė neprivalomos (egzaminas prieinamas bet kada)
  - Jei nepažymėta - data ir trukmė privalomos
- **Data ir laikas** (jei ne bandomasis)
- **Trukmė minutėmis** (jei ne bandomasis)
- **Perlaikomo egzamino pasirinkimas** (neprivalomas):
  - Dropdown sąrašas pasibaigusių egzaminų
  - Naudojama statistikai sekti

**Validacija:**
- Turi būti pasirinktas bent vienas klausimas
- Pavadinimas privalomas
- Ne bandomajam egzaminui data turi būti ateityje

**Rezultatas:**
- Sukuriamas naujas egzaminas
- Nukreipiama į egzaminų sąrašą su pranešimu "Egzaminas sukurtas!"
- Išvalomi visi sesijos duomenys (pasirinkti klausimai, filtrai)

---

### 6. Egzamino trynimas

**Prieiga:** Dėstytojas, Administratorius

**Aprašymas:** Ištrinti egzaminą iš sistemos.

**Įvedami duomenys:**
- Egzamino ID (automatiškai perduodamas per "Trinti" mygtuką)

**Rezultatas:**
- Egzaminas ištrinamas iš sistemos
- Automatiškai ištrinami visi susiję duomenys (klausimų priskyrimas egzaminui)
- Klausimai išlieka sistemoje (gali būti naudojami kituose egzaminuose)
- Nukreipiama atgal į egzaminų sąrašą su pranešimu "Egzaminas ištrintas!"

---

## Klausimų valdymas

### 7. Klausimų sąrašo peržiūra

**Prieiga:** Dėstytojas, Administratorius

**Aprašymas:** Peržiūrėti visų klausimų sąrašą su galimybe filtruoti pagal temą.

**Įvedami duomenys:**
- Tema (dropdown, neprivalomas)
- Puslapio numeris (automatinis)

**Rezultatas:**
- Rodomas klausimų sąrašas lentelėje:
  - Klausimo tekstas
  - Tema
  - Vertė taškais
  - Teisingas atsakymas
  - "Trinti" mygtukas

**Filtravimas:**
- Dropdown su visomis temomis
- Filtras išsaugomas naršant tarp puslapių
- Mygtukas "Valyti filtrus" grąžina į pradinę būseną

**Puslapiavimas:**
- Po 10 klausimų per puslapį
- Navigacija tarp puslapių

**Papildomi veiksmai:**
- "Kurti naują klausimą" mygtukas viršuje
- "Trinti" mygtukas prie kiekvieno klausimo

---

### 8. Naujo klausimo kūrimas

**Prieiga:** Dėstytojas, Administratorius

**Aprašymas:** Sukurti naują klausimą su atsakymų variantais.

**Įvedami duomenys:**
- **Klausimo tekstas** (privalomas, iki 500 simbolių)
- **Atsakymų variantai** (privalomi):
  - Kiekvienas atsakymas naujoje eilutėje
  - Tekstinė sritis (textarea)
- **Tema** (privaloma):
  - Galima pasirinkti iš egzistuojančių temų (dropdown)
  - Arba įvesti naują temą
- **Vertė taškais** (privalomas, teigiamas skaičius)
- **Teisingas atsakymas** (privalomas):
  - Turi būti vienas iš pateiktų atsakymų variantų

**Validacija:**
- Visi laukai privalomi
- Vertė turi būti teigiamas skaičius
- Teisingas atsakymas turi būti vienas iš pateiktų atsakymų

**Rezultatas:**
- Sukuriamas naujas klausimas
- Nukreipiama atgal į klausimų sąrašą su pranešimu "Klausimas sukurtas!"
- Išsaugomi ankstesni filtrai (tema, puslapis)

**Pavyzdys:**
```
Klausimas: Kuri programavimo kalba yra interpretuojama?
Atsakymai:
Python
Java
C++
C#

Tema: Programavimo kalbos
Vertė: 10
Teisingas atsakymas: Python
```

---

### 9. Klausimo trynimas

**Prieiga:** Dėstytojas, Administratorius

**Aprašymas:** Ištrinti klausimą iš sistemos.

**Įvedami duomenys:**
- Klausimo ID (automatiškai perduodamas per "Trinti" mygtuką)

**Rezultatas:**
- Sėkmės atveju:
  - Klausimas ištrinamas
  - Nukreipiama atgal į klausimų sąrašą su pranešimu "Klausimas ištrintas!"
- Klaidos atveju:
  - Jei klausimas naudojamas bent viename egzamine, trinimas nepavyksta
  - Rodomas klaidos pranešimas

---

## Egzaminų laikymas

### 10. Egzamino laikymas

**Prieiga:** Visi prisijungę vartotojai

**Aprašymas:** Laikyti egzaminą atsakant į klausimus nustatytą laiko intervalu.

**Priėjimas:**
- Per egzaminų sąrašą paspaudus "Vykdyti" mygtuką
- Galima tik jei egzaminas šiuo metu vyksta (data <= NOW <= data + trukmė)

**Rodomos informacija:**
- Egzamino pavadinimas
- Likusio laiko skaitiklis (minutės:sekundės)
- Klausimų sąrašas sugrupuotas pagal temą

**Kiekvienam klausimui:**
- Klausimo tekstas
- Vertė taškais
- Atsakymų variantai (radio buttons)
- Galima pasirinkti tik vieną atsakymą

**Jei yra išsaugoti ankstesni atsakymai:**
- Automatiškai pažymimi anksčiau pasirinkti atsakymai
- Galima tęsti nuo ten, kur buvo sustota

**Veiksmai:**

#### 10.1. Išsaugoti atsakymus

**Mygtukas:** "Išsaugoti atsakymus"

**Įvedami duomenys:**
- Visi pasirinkti atsakymai (radio buttons)

**Rezultatas:**
- Atsakymai išsaugomi sistemoje
- Nukreipiama į egzaminų sąrašą su pranešimu "Atsakymai išsaugoti!"
- Galima grįžti ir tęsti egzaminą vėliau (kol nepasibaigė laikas)
- Egzamino statusas: "Nebaigta"

#### 10.2. Baigti egzaminą

**Mygtukas:** "Baigti egzaminą"

**Įvedami duomenys:**
- Visi pasirinkti atsakymai

**Rezultatas:**
- Atsakymai išsaugomi
- Automatiškai apskaičiuojamas rezultatas:
  - Kiekvienam klausimui tikrinama ar atsakymas teisingas
  - Susumuojami teisingų atsakymų taškai
  - Apskaičiuojamas procentas: (surinkti taškai / bendri taškai) × 100
- Rezultatas įrašomas į sistemą
- Nukreipiama į egzaminų sąrašą su pranešimu "Egzaminas baigtas! Jūsų rezultatas: X%"
- Egzamino statusas: "Baigta (X%)"
- Negalima daugiau laikyti šio egzamino

**Ribojimai:**
- Negalima laikyti jei laikas pasibaigė
- Negalima laikyti pakartotinai jei jau yra rezultatas (tik vartotojas role)
- Dėstytojai/administratoriai gali laikyti bet kada (testavimo tikslais)

**Skaičiavimo pavyzdys:**
```
Klausimas 1: 10 taškų - atsakyta teisingai → +10
Klausimas 2: 15 taškų - atsakyta neteisingai → +0
Klausimas 3: 10 taškų - atsakyta teisingai → +10
Klausimas 4: 15 taškų - neatsakyta → +0

Bendrai: 50 taškų
Surinkta: 20 taškų
Rezultatas: (20 / 50) × 100 = 40%
```

---

### 11. Bandomojo egzamino peržiūra

**Prieiga:** Visi vartotojai (įskaitant neprisijungusius)

**Aprašymas:** Peržiūrėti bandomojo egzamino klausimus ir teisingus atsakymus mokymosi tikslais.

**Priėjimas:**
- Per egzaminų sąrašą paspaudus "Peržiūrėti" mygtuką prie bandomojo egzamino

**Rodoma informacija:**
- Egzamino pavadinimas
- Klausimų sąrašas su visais atsakymų variantais
- Radio buttons klausimams (galima pasirinkti bet netikrinama)

**Veiksmai:**

**Mygtukas:** "Baigti"

**Veikimas:**
- JavaScript automatiškai pažymi:
  - Teisingus atsakymus **žaliai**
  - Neteisingus atsakymus (jei pasirinkti) **raudonai**
- Išjungiami visi radio buttons (negalima keisti)
- Galima peržiūrėti klausimus ir teisingus atsakymus

**Prisijungusiems vartotojams (role: vartotojas):**
- Automatiškai įrašomas 100% rezultatas
- Nukreipiama į egzaminų sąrašą
- Egzamino statusas: "Baigta (100%)"

**Tikslas:**
- Mokymasis ir pasirengimas realiems egzaminams
- Teisingų atsakymų peržiūra
- Be laiko limito ir spaudimo

---

## Rezultatų peržiūra

### 12. Egzamino rezultatų peržiūra

**Prieiga:** Dėstytojas, Administratorius

**Aprašymas:** Peržiūrėti egzamino rezultatus su galimybe pasirinkti skaičiavimo metodą.

**Priėjimas:**
- Per egzaminų sąrašą paspaudus "Rezultatai" mygtuką prie pasibaigusio egzamino

**Rodoma informacija:**
- Egzamino pavadinimas
- Data ir laikas
- Trukmė

**Skaičiavimo taisyklės (skirtukų pavidalu):**

#### 12.1. Geriausias rezultatas (numatytasis)

**Aprašymas:** Rodomas geriausias kiekvieno studento rezultatas iš visų bandymų (įskaitant perlaikymus).

**Tinka:** Kai svarbu įvertinti ar studentas galų gale įsisavino medžiagą, nepriklausomai nuo bandymų skaičiaus.

#### 12.2. Paskutinis rezultatas

**Aprašymas:** Rodomas paskutinis (naujausias) kiekvieno studento rezultatas.

**Tinka:** Kai svarbu įvertinti dabartinį žinių lygį, ne praeitį.

#### 12.3. Vidurkis

**Aprašymas:** Rodomas vidutinis visų bandymų rezultatas kiekvienam studentui.

**Tinka:** Kai svarbu įvertinti bendrą studento pastovumą ir mokymosi progresą.

**Rezultatų lentelė:**
- Studentų sąrašas abėcėlės tvarka
- Kiekvieno studento vardas
- Rezultatas procentais (pagal pasirinktą taisyklę)
- Spalvinis kodavimas:
  - **Žalia** (≥ 45%) - Išlaikė
  - **Raudona** (< 45%) - Neišlaikė

**Perlaikymų grandinė:**
- Sistema automatiškai suranda visus susijusius egzaminus (pradinį + perlaikymus)
- Naudoja `perlaikomo_egzamino_id` ryšį
- Rezultatai skaičiuojami per visą grandinę

**Pavyzdys:**
```
Studentas: Jonas Jonaitis

Egzaminas: "PHP pagrindai" (2024-01-15)
1-as bandymas: 30%

Perlaikymas: "PHP pagrindai (Perlaikymas)" (2024-02-15)
2-as bandymas: 60%

Perlaikymas: "PHP pagrindai (Perlaikymas 2)" (2024-03-15)
3-as bandymas: 50%

Rodomi rezultatai:
- Geriausias: 60% (žalia)
- Paskutinis: 50% (žalia)
- Vidurkis: 47% (žalia)
```

---

## Vartotojų administravimas

### 13. Vartotojų sąrašo peržiūra

**Prieiga:** Administratorius

**Aprašymas:** Peržiūrėti ir valdyti visus vartotojus sistemoje.

**Įvedami duomenys:**
- Rolės filtras (dropdown: Visi / Dėstytojas / Vartotojas)
- Puslapio numeris (automatinis)

**Rezultatas:**
- Rodomas vartotojų sąrašas lentelėje:
  - Vartotojo vardas
  - Dabartinė rolė
  - Rolės keitimo forma (dropdown + "Keisti" mygtukas)
  - "Trinti" mygtukas

**Ribojimai:**
- Administratoriai nerodomi sąraše (apsaugoti nuo atsitiktinio trynimo)
- Negalima keisti administratoriaus rolės
- Negalima trinti administratoriaus

**Filtravimas:**
- Galima filtruoti pagal rolę
- Filtras išsaugomas naršant tarp puslapių

**Puslapiavimas:**
- Po 10 vartotojų per puslapį

---

### 14. Vartotojo rolės keitimas

**Prieiga:** Administratorius

**Aprašymas:** Pakeisti vartotojo rolę sistemoje.

**Įvedami duomenys:**
- Vartotojo ID (automatinis, iš lentelės)
- Nauja rolė (dropdown):
  - Dėstytojas
  - Vartotojas

**Rezultatas:**
- Vartotojo rolė pakeičiama
- Puslapis perkraunamas su pranešimu "Rolė pakeista!"
- Vartotojas gaus naujas teises pagal naują rolę

**Rolių aprašymas:**
- **Vartotojas:**
  - Gali laikyti egzaminus
  - Gali matyti savo rezultatus
  - Negali kurti klausimų ar egzaminų

- **Dėstytojas:**
  - Visos vartotojo teisės +
  - Gali kurti ir redaguoti klausimus
  - Gali kurti ir trinti egzaminus
  - Gali peržiūrėti studentų rezultatus

- **Administratorius:**
  - Visos dėstytojo teisės +
  - Gali valdyti vartotojus
  - Gali keisti vartotojų roles
  - Gali trinti vartotojus

**Ribojimai:**
- Negalima priskirti administratoriaus rolės (saugumo sumetimais)
- Negalima keisti administratoriaus rolės

---

### 15. Vartotojo trynimas

**Prieiga:** Administratorius

**Aprašymas:** Ištrinti vartotojo paskyrą iš sistemos.

**Įvedami duomenys:**
- Vartotojo ID (automatinis, per "Trinti" mygtuką)

**Rezultatas:**
- Vartotojas ištrinamas iš sistemos
- Automatiškai ištrinami visi susiję duomenys:
  - Vartotojo atsakymai
  - Vartotojo rezultatai
- Nukreipiama atgal į vartotojų sąrašą su pranešimu "Vartotojas ištrintas!"

**Ribojimai:**
- Negalima trinti administratoriaus
- Jei bandoma trinti administratorių, rodomas klaidos pranešimas

**Perspėjimas:**
- Trynimas negrįžtamas
- Prarandama visa vartotojo istorija
- Rekomenduojama naudoti tik kraštutiniais atvejais

---

## Papildomos funkcijos

### 16. Laiko juostos valdymas

**Prieiga:** Automatinis (visiems vartotojams)

**Aprašymas:** Sistema automatiškai nustato vartotojo laiko juostą ir rodo visas datas vietiniame laike.

**Veikimas:**
- JavaScript automatiškai nustato naršyklės laiko juostą
- Visos datos rodomos vartotojo vietiniame laike
- Duomenų bazėje datos saugomos UTC formatu
- Konvertavimas vyksta automatiškai

**Pavyzdys:**
```
Egzamino data duomenų bazėje: 2024-01-15 10:00:00 UTC
Vartotojui Lietuvoje rodoma: 2024-01-15 12:00:00 (EET, UTC+2)
Vartotojui Londone rodoma: 2024-01-15 10:00:00 (GMT, UTC+0)
```

---

### 17. Sesijos valdymas

**Prieiga:** Automatinis

**Aprašymas:** Sistema automatiškai išsaugo vartotojo būseną naršant puslapius.

**Išsaugoma informacija:**
- Dabartiniai filtrai (tema, data, rolė)
- Dabartinis puslapis
- Pasirinkti klausimai (kuriant egzaminą)

**Veikimas:**
- Uždarius naršyklę informacija ištrinama
- Atsijungus informacija ištrinama
- Perkrovus puslapį informacija išlieka

**Tikslas:**
- Patogus navigavimas
- Nereikia iš naujo įvesti filtrų
- Neprarandamas darbas (pvz., klausimų pasirinkimas)

---

## Pranešimų sistema

**Aprašymas:** Sistema rodo informacinius pranešimus po sėkmingų ar nesėkmingų veiksmų.

**Pranešimų tipai:**

### Sėkmės pranešimai (žali):
- "Registracija sėkminga!"
- "Egzaminas sukurtas!"
- "Egzaminas ištrintas!"
- "Klausimas sukurtas!"
- "Klausimas ištrintas!"
- "Atsakymai išsaugoti!"
- "Egzaminas baigtas! Jūsų rezultatas: X%"
- "Rolė pakeista!"
- "Vartotojas ištrintas!"

### Klaidos pranešimai (raudoni):
- "Neteisingas vartotojo vardas arba slaptažodis"
- "Vartotojo vardas jau užimtas"
- "Slaptažodžiai nesutampa"
- "Slaptažodis per silpnas"
- "Neturite prieigos prie šio puslapio"
- "Negalima trinti klausimo, nes jis naudojamas egzamine"
- "Negalima trinti administratoriaus"
- "Egzamino laikas pasibaigė"

---

## Navigacija

### Pagrindinė navigacija (visuose puslapiuose):

**Neprisijungusiems:**
- Egzaminai
- Klausimai (tik peržiūra)
- Prisijungti
- Registruotis

**Prisijungusiems (Vartotojas):**
- Egzaminai
- Atsijungti

**Prisijungusiems (Dėstytojas):**
- Egzaminai
- Klausimai
- Kurti egzaminą
- Atsijungti

**Prisijungusiems (Administratorius):**
- Egzaminai
- Klausimai
- Kurti egzaminą
- Vartotojai
- Atsijungti

---

## Santrauka

### Pagrindinės funkcijos pagal vartotojo tipą:

**Neprisijungęs:**
- ✅ Registruotis
- ✅ Prisijungti
- ✅ Peržiūrėti egzaminų sąrašą
- ✅ Peržiūrėti bandomuosius egzaminus

**Vartotojas:**
- ✅ Visos neprisijungusio funkcijos +
- ✅ Laikyti egzaminus
- ✅ Matyti savo rezultatus
- ✅ Išsaugoti atsakymus
- ✅ Atsijungti

**Dėstytojas:**
- ✅ Visos vartotojo funkcijos +
- ✅ Kurti klausimus
- ✅ Trinti klausimus
- ✅ Kurti egzaminus
- ✅ Trinti egzaminus
- ✅ Peržiūrėti studentų rezultatus

**Administratorius:**
- ✅ Visos dėstytojo funkcijos +
- ✅ Valdyti vartotojus
- ✅ Keisti vartotojų roles
- ✅ Trinti vartotojus

### Iš viso funkcijų: 17
- Autentifikacija: 3
- Egzaminų valdymas: 3
- Klausimų valdymas: 3
- Egzaminų laikymas: 3
- Rezultatų peržiūra: 1
- Vartotojų administravimas: 3
- Papildomos: 2 (laiko juostos, sesijos)
