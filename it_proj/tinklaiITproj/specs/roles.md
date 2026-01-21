# Lietuviškos Egzaminų Valdymo Sistemos Dokumentacija

## Sistemos Naudotojų Kategorijos ir Jų Funkcijos

---

### 1. SVEČIAS (Neprisijungęs vartotojas)

**Aprašymas:** Bet kuris asmuo, lankantis sistemą be registracijos ar prisijungimo. Turi ribotas peržiūros teises, skirtas susipažinti su sistema ir egzaminų turiniu.

#### Galimos funkcijos:

##### 1.1. Egzaminų sąrašo peržiūra
- Peržiūrėti visų sistemoje esamų egzaminų sąrašą
- Matyti egzamino pavadinimą ir datą
- Filtruoti egzaminus pagal datų intervalą (nuo-iki)
- Naršyti puslapiais (10 egzaminų viename puslapyje)
- Matyti bendras egzaminų statistikas

##### 1.2. Klausimų bazės naršymas
- Naršyti visus sistemoje esančius klausimus
- Peržiūrėti klausimų tekstus ir galimus atsakymus
- Filtruoti klausimus pagal temą
- Matyti klausimo temą ir vertę (taškus)
- Naršyti puslapiais (10 klausimų viename puslapyje)

##### 1.3. Registracija ir prisijungimas
- Užsiregistruoti naujam vartotojui sistemoje
- Prisijungti su esamomis kredencialais
- Gauti prieigą prie saugomų funkcijų po prisijungimo

#### Apribojimai:
- Negali laikyti egzaminų
- Negali matyti egzaminų rezultatų
- Negali kurti ar redaguoti turinio
- Negali matyti bandomųjų egzaminų atsakymų
- Negali pasiekti jokių valdymo funkcijų

---

### 2. VARTOTOJAS (Studentas)

**Aprašymas:** Registruotas sistemos naudotojas, kurio pagrindinis tikslas - laikyti egzaminus ir stebėti savo rezultatus. Ši rolė automatiškai suteikiama visiems naujiems vartotojams po registracijos.

#### Galimos funkcijos:

**Visos SVEČIO teisės PLIUS:**

##### 2.1. Egzaminų laikymas
- Dalyvauti egzaminuose, kurie dar nevyksta arba vyksta šiuo metu
- Peržiūrėti egzamino klausimus, sugrupuotus pagal temas
- Atsakinėti į klausimus renkantis iš pateiktų variantų
- Išsaugoti atsakymus ir grįžti prie egzamino vėliau
- Užbaigti egzaminą ir iškart gauti rezultatą procentais
- Sistema automatiškai įvertina atsakymus pagal teisingą klausimų versiją

##### 2.2. Bandomųjų egzaminų peržiūra
- Peržiūrėti bandomųjų egzaminų klausimus ir teisingus atsakymus
- Pasimokyti iš teisingų atsakymų be laiko apribojimų
- Užbaigti bandomojo egzamino peržiūrą ir gauti automatinį rezultatą

##### 2.3. Egzaminų informacijos peržiūra
- Matyti, ar egzaminas yra bandomasis ar oficialus
- Matyti, ar egzaminas yra ankstesnio egzamino perlaikymas
- Stebėti savo egzaminų rezultatus
- Matyti egzamino laikymo progresą

#### Apribojimai:
- Negali kurti naujų klausimų ar egzaminų
- Negali redaguoti klausimų bazės
- Negali matyti kitų studentų rezultatų
- Negali keisti egzaminų parametrų
- Negali valdyti kitų vartotojų

---

### 3. DĖSTYTOJAS (Mokytojas)

**Aprašymas:** Pedagogas, atsakingas už mokomojo turinio kūrimą ir studentų vertinimą. Turi teises valdyti klausimų bazę, kurti egzaminus ir analizuoti studentų pasiekimus.

#### Galimos funkcijos:

**Visos VARTOTOJO teisės PLIUS:**

##### 3.1. Klausimų bazės valdymas
- Kurti naujus klausimus egzaminams
- Apibrėžti klausimo tekstą ir galimus atsakymus
- Nurodyti teisingą atsakymo variantą
- Priskirti klausimą tematinei kategorijai
- Nustatyti klausimo vertę taškais
- Naudoti esamas temas arba kurti naujas
- Užtikrinti klausimų bazės kokybę ir įvairovę

##### 3.2. Egzaminų kūrimas
- Sudaryti naujus egzaminus iš klausimų bazės
- Pasirinkti atskirus klausimus rankiniu būdu
- Automatiškai parinkti atsitiktinius klausimus pagal temas ir kiekį
- Valdyti egzamino klausimų sąrašą (pridėti, pašalinti, išvalyti)
- Nustatyti egzamino pavadinimą, datą ir trukmę
- Kurti bandomuosius egzaminus be laiko apribojimų
- Sukurti perlaikymo egzaminus, susietus su ankstesniais egzaminais
- Matyti egzamino statistiką (klausimų skaičius, bendra vertė)

##### 3.3. Rezultatų analizė
- Peržiūrėti visų studentų rezultatus konkrečiam egzaminui
- Analizuoti rezultatus pagal skirtingas metodikas:
  - **Geriausias rezultatas:** Aukščiausias pasiektas rezultatas iš visų bandymų
  - **Paskutinis rezultatas:** Naujausias egzamino laikymo rezultatas
  - **Vidutinis rezultatas:** Visų bandymų vidurkis
- Stebėti studentų progresą ir pasiekimus
- Identifikuoti studentus, kuriems reikalinga pagalba
- Analizuoti egzamino perlaikymų rezultatus

##### 3.4. Egzaminų administravimas
- Matyti visų egzaminų sąrašą su išplėstine informacija
- Pasiekti egzaminų rezultatų peržiūros įrankius
- Valdyti bandomuosius ir oficialius egzaminus
- Kurti egzaminų perlaikymus

#### Apribojimai:
- Negali valdyti kitų vartotojų paskyrų
- Negali keisti vartotojų rolių
- Negali trinti kitų vartotojų
- Negali pasiekti sistemos administravimo funkcijų

---

### 4. ADMINISTRATORIUS

**Aprašymas:** Sistemos administratorius su visomis valdymo teisėmis. Atsakingas už vartotojų valdymą, sistemos priežiūrą ir bendrą funkcionalumo užtikrinimą.

#### Galimos funkcijos:

**Visos DĖSTYTOJO teisės PLIUS:**

##### 4.1. Vartotojų administravimas
- Peržiūrėti visų sistemos vartotojų sąrašą
- Filtruoti vartotojus pagal jų rolę
- Keisti vartotojų roles tarp "Dėstytojas" ir "Vartotojas"
- Šalinti vartotojų paskyras iš sistemos
- Stebėti vartotojų veiklą ir registracijas

##### 4.2. Rolių valdymas
- Suteikti dėstytojo teises vartotojams
- Pažeminti dėstytojus į vartotojų lygį
- Užtikrinti tinkamą teisių paskirstymą
- Valdyti prieigos lygius sistemoje

##### 4.3. Sistemos priežiūra
- Prieiga prie visų sistemos funkcijų
- Galimybė valdyti visą mokomąjį turinį
- Galimybė peržiūrėti visus rezultatus ir statistikas
- Užtikrinti sistemos veikimo kokybę
- Administruoti visus egzaminus ir klausimus

##### 4.4. Saugumo užtikrinimas
- Kontroliuoti vartotojų prieigą
- Tvarkyti netinkamas paskyras
- Užkirsti kelią piktnaudžiavimui sistema
- Užtikrinti duomenų vientisumą

#### Apribojimai:
- Negali ištrinti kitų administratorių paskyrų
- Negali pakeisti kitų administratorių rolių
- Negali matyti vartotojų slaptažodžių (saugomi užkoduoti)

---

## Prieigos teisių suvestinė

| Funkcija | Svečias | Vartotojas | Dėstytojas | Administratorius |
|----------|---------|------------|-----------|-----------------|
| **Peržiūra** | | | | |
| Egzaminų sąrašas | ✓ | ✓ | ✓ | ✓ |
| Klausimų naršymas | ✓ | ✓ | ✓ | ✓ |
| Egzaminų statistika | ✓ | ✓ | ✓ | ✓ |
| **Egzaminai** | | | | |
| Egzaminų laikymas | ✗ | ✓ | ✓ | ✓ |
| Bandomųjų peržiūra | ✗ | ✓ | ✓ | ✓ |
| Rezultatų peržiūra (savų) | ✗ | ✓ | ✓ | ✓ |
| **Turinio kūrimas** | | | | |
| Klausimų kūrimas | ✗ | ✗ | ✓ | ✓ |
| Egzaminų kūrimas | ✗ | ✗ | ✓ | ✓ |
| **Analizė** | | | | |
| Studentų rezultatai | ✗ | ✗ | ✓ | ✓ |
| Rezultatų statistika | ✗ | ✗ | ✓ | ✓ |
| **Administravimas** | | | | |
| Vartotojų valdymas | ✗ | ✗ | ✗ | ✓ |
| Rolių keitimas | ✗ | ✗ | ✗ | ✓ |
| Vartotojų šalinimas | ✗ | ✗ | ✗ | ✓ |

---

## Autentifikavimas ir saugumas

### Registracija
- Nauji vartotojai registruojasi nurodydami vardą ir slaptažodį
- Sistema automatiškai priskiria rolę **"Vartotojas"**
- Slaptažodis turi atitikti saugumo reikalavimus:
  - Mažiausiai 5 simboliai
  - Įvairi simbolių kompozicija (didžiosios, mažosios, skaičiai, specialūs simboliai)
- Vardas turi būti unikalus sistemoje

### Prisijungimas
- Vartotojai prisijungia su vardu ir slaptažodžiu
- Sistema patikrina kredencialus ir suteikia prieigą
- Sesija galioja 24 valandas
- Saugus slaptažodžių šifravimas

### Atsijungimas
- Vartotojas gali bet kada atsijungti
- Sistema ištrina sesijos duomenis
- Saugoma informacija automatiškai apsaugoma

---

## Verslo procesai pagal roles

### Svečio kelias:
1. Aplanko sistemą
2. Peržiūri egzaminų ir klausimų sąrašus
3. Nusprendžia užsiregistruoti
4. Tampa vartotoju

### Vartotojo kelias:
1. Prisijungia prie sistemos
2. Randa jam skirtą egzaminą
3. Laiko egzaminą atsakydamas į klausimus
4. Gauna rezultatą
5. Gali perlaikyti, jei yra tokia galimybė

### Dėstytojo kelias:
1. Prisijungia su dėstytojo teisėmis
2. Sukuria klausimus pagal temas
3. Sudaro egzaminą iš klausimų bazės
4. Nustato egzamino parametrus (data, trukmė)
5. Laukia, kol studentai išlaiko egzaminą
6. Analizuoja rezultatus
7. Gali sukurti perlaikymo galimybę

### Administratoriaus kelias:
1. Prisijungia su administratoriaus teisėmis
2. Stebi naujus registruotus vartotojus
3. Priskiria dėstytojo roles pedagogams
4. Valdo vartotojų paskyras
5. Užtikrina sistemos veikimą
6. Šalina problemas ir netinkamas paskyras

---

## Hierarchinė rolių struktūra

Sistema naudoja hierarchinę rolių struktūrą, kur kiekviena aukštesnė rolė paveldi žemesnės rolės funkcionalumą ir prideda papildomų galimybių:

```
SVEČIAS (bazinė peržiūra)
    ↓
VARTOTOJAS (egzaminų laikymas)
    ↓
DĖSTYTOJAS (turinio kūrimas ir analizė)
    ↓
ADMINISTRATORIUS (vartotojų ir sistemos valdymas)
```

Ši struktūra užtikrina:
- Aiškų atsakomybės pasiskirstymą
- Paprastą teisių valdymą
- Saugų prieigos kontrolę
- Lankstų sistemos plėtimą

---

## Pagrindinės sistemos funkcijos

### 1. Klausimų bazė
- Centralizuota klausimų saugykla
- Organizuota pagal temas
- Įvertinta taškais
- Prieinama visoms rolėms (skirtingais lygiais)

### 2. Egzaminų sistema
- Dviejų tipų egzaminai: oficialūs ir bandomieji
- Laiko kontrolė oficialiems egzaminams
- Automatinis rezultatų skaičiavimas
- Perlaikymų funkcionalumas

### 3. Rezultatų valdymas
- Automatinis įvertinimas
- Rezultatų istorija
- Įvairios analizės metodikos
- Progreso stebėjimas

### 4. Vartotojų valdymas
- Rolių sistema
- Teisių kontrolė
- Paskyrų administravimas
- Saugus autentifikavimas

---

## Santrauka

Lietuviškos egzaminų valdymo sistema turi keturis aiškiai apibrėžtus vartotojų tipus:

1. **SVEČIAS** - Neregistruotas lankytojas su peržiūros teisėmis
2. **VARTOTOJAS** - Studentas, laikantis egzaminus
3. **DĖSTYTOJAS** - Pedagogas, kuriantis turinį ir vertinantis studentus
4. **ADMINISTRATORIUS** - Sistemos administratorius, valdantis vartotojus ir užtikrinantis sistemos veikimą

Kiekviena rolė turi aiškiai apibrėžtas atsakomybes ir galimybes, užtikrinančias efektyvų sistemos veikimą ir duomenų saugumą. Hierarchinė struktūra leidžia lengvai suprasti teisių paskirstymą ir užtikrina, kad kiekvienas vartotojas turėtų prieigą tik prie jam reikalingų funkcijų.

Sistema palaiko visą lietuviškų egzaminų ciklą: nuo klausimų kūrimo, per egzaminų organizavimą, iki rezultatų analizės ir vartotojų administravimo.