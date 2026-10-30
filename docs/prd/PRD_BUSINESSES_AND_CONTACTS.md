# PRD: Moje firmy a kontakty

Datum: 2026-10-26. Verze: 1.2. Stav: schválená produktová specifikace.
Dokumentační revize: 2026-10-30, bez změny produktových rozhodnutí.
Technický návrh: [DESIGN_BUSINESSES_AND_CONTACTS.md](../design/DESIGN_BUSINESSES_AND_CONTACTS.md).
Rozhodnutí: [D-045](../adr/D-045-businesses-and-contacts.md).
Stav implementace a ověření: [VERIFICATION_BUSINESSES_AND_CONTACTS.md](VERIFICATION_BUSINESSES_AND_CONTACTS.md).

Verze 1.1 (2026-10-26): oddíl 4 upřesněn podle technického návrhu —
adresy firem i kontaktů sdílejí tabulku `addresses`, bankovní účty
tabulku `bank_accounts`. Produktová rozhodnutí se nemění.

Verze 1.2 (2026-10-26): oddíl 7.1 doplňuje pravidla formulářů dokladů
odsouhlasená při technickém návrhu; odstraněny věty platné jen pro
přípravu specifikace.

Produktová rozhodnutí byla postupně odsouhlasena uživatelem. Závěrečné
schválení zahrnuje také okrajová pravidla oddílu 10 a minimální úpravu
současných výdajů v oddílu 11. Otevřená produktová rozhodnutí pro tento
rozsah nezůstávají; navazující funkcionality jsou výslovně oddělené.
Dokument určuje požadované chování a hranice kroku 7. Budoucí čas v požadavcích
a postup v oddílu 14 nejsou seznamem dosud chybějící implementace. Skutečný
implementovaný rozsah a mapa ověření A01–A29 jsou v doprovodné mapě VERIFICATION;
schválená specifikace a záznam skutečného stavu mají odlišnou úlohu.

## 1. Problém a cíl

Výchozí model před krokem 7 ukládal do `suppliers` vlastní firmy uživatele,
do `clients` jeho odběratele. Vazba `expenses.supplier_id` ale také odkazovala
na vlastní firmy, přestože na přijatém dokladu potřebujeme externího dodavatele.
Dodavatel a odběratel jsou role na dokladu. Stejná protistrana musí být
použitelná pro prodej i nákup bez kopírování kontaktu.

Cílem je rozlišit vlastní podnikatelskou identitu a obchodní protistranu,
sjednotit jejich sdílené chování a připravit údaje stran pro navazující
neměnnost vystavených dokladů. Součástí je adresář, adresy, bankovní účty,
archivace, výchozí volby, limity a integrace do stávající aplikace.

Billex zůstává modulárním monolitem podle D-042 a D-043: Laravel 12,
PHP 8.3, MySQL, Backpack, Blade/Livewire/Alpine/Tailwind. Party zůstává
jedním doménovým kontextem. Nové externí služby ani broker nevznikají.
Pro změnu modelu bylo podle D-045 schváleno přestavění vývojové databáze
bez převodu obchodních dat, protože projekt není v produkci. Jde o výchozí
implementační postup, nikoli o pokyn znovu přestavět existující lokální data.

## 2. Pojmy a vlastnictví

| Pojem | Význam | Uživatelský název |
|---|---|---|
| Business | Vlastní podnikatelská identita uživatele | Moje firmy |
| Contact | Firma nebo člověk, se kterým uživatel obchoduje | Kontakty |
| Supplier / customer | Role strany konkrétního dokladu | Dodavatel / odběratel |

- Účet má společný adresář kontaktů pro všechny své vlastní firmy.
- Business i Contact patří účtu přes `user_id`; kontakt se nepřiřazuje firmě.
- Kontakt nemá ruční příznaky odběratel/dodavatel. Role vyplývá z dokladu.
- Vystavený doklad: Business je dodavatel, protistrana je odběratel.
- Přijatý doklad: Business je odběratel, protistrana je dodavatel.
- Protistrana může pocházet z Contact, nebo být zadána pouze na dokladu.
- Doklad používá `business_id` a volitelné `contact_id`; skutečné údaje
  stran ukládá také přímo s dokladem. Odkazy určují zdroj a vazbu do adresáře.
- Vlastní firmy se automaticky nenabízejí jako kontakty ani se do nich nekopírují.
- Vyhledávání protistrany zahrnuje všechny aktivní kontakty účtu,
  včetně dosud nepoužitých. Budoucí filtry podle role mohou vycházet z dokladů.

## 3. Údaje kontaktu a vlastní firmy

### 3.1 Kontakt

K založení kontaktu stačí název/jméno. E-mail a telefon jsou nepovinné.
Pravidla registrace a adresy se uplatní, pokud jsou příslušné údaje vyplněné.
Neúplný kontakt lze doplnit později. Při odesílání dokladu e-mailem je
nutná platná adresa příjemce; její absence neblokuje založení kontaktu.

| Pole | Povinnost a význam |
|---|---|
| Název / jméno | Povinné; označení skutečné protistrany pro doklady |
| Interní zkratka | Nepovinná; vyhledávání a rozlišení, netiskne se místo názvu |
| Registrační číslo | Nepovinné; technicky `company_number` |
| Země registrace | Povinná při zadaném registračním čísle |
| DIČ | Nepovinné; technicky `vat_number`, nezávislé na registračním čísle |
| Fakturační adresa | Samostatný záznam podle oddílu 4 |
| E-mail pro doklady | Nepovinný; výchozí příjemce při odesílání |
| Telefon | Nepovinný, mezinárodní zápis jako text |
| Kontaktní osoba | Nepovinná; nezaměňuje se s názvem fakturované firmy |
| Interní poznámka | Nepovinná; nepřenáší se na doklady |

Nepřítomnost registračního čísla neurčuje, zda jde o firmu nebo soukromou osobu.
Povinný typ firma/osoba ani globální výchozí kontakt se nezavádějí.

### 3.2 Vlastní firma

Business má vlastní název, registrační číslo, zemi registrace, DIČ,
fakturační adresu, e-mail, telefon, interní označení a poznámku,
volitelné logo a bankovní účty. Název je povinný, komunikační údaje
jsou nepovinné. Pravidla země registrace odpovídají kontaktům.

Přihlašovací e-mail a jméno uživatele jsou oddělené od údajů firmy.
Registrace může hodnoty předvyplnit, ale dovolí jiný název firmy a obchodní
e-mail. Pozdější změny účtu a firmy se navzájem automaticky nepřenášejí.
První firma vznikne při registraci a stane se výchozí.

Daňový profil a budoucí stav plátce patří Business, protože jeden účet
může spravovat několik podnikatelských identit. Zavedení plátcovství
a jeho vlivu na výpočty a doklady zůstává navazujícímu kroku 8.

### 3.3 Společná pravidla

- Vyprázdnění nepovinného pole skutečně uloží prázdnou hodnotu.
- Aktualizace úplného formuláře rozlišuje prázdnou hodnotu a samostatné
  operace typu změna výchozího záznamu; ty ostatní pole nepřepisují.
- Načtení z ARES je pomocné předvyplnění ke kontrole. Nenahrazuje ruční
  zadání, nepřepisuje samo uložený záznam a nevyrábí DIČ jako `CZ + IČO`.
- Registrační číslo a DIČ se neodvozují jedno z druhého; vyplněné DIČ
  samo nenastavuje budoucí příznak plátce.
- Úplnost údajů pro operaci s konkrétním dokladem se kontroluje na skutečných
  údajích dokladu stejným způsobem při výběru kontaktu i jednorázovém zadání.
  Samotné `contact_id` nebo `business_id` není důkazem úplnosti údajů.
- Tato funkcionalita nezavádí úplnou sadu daňových náležitostí nových typů
  dokladů ani nový životní cyklus draft/issued; ty patří kroku 8.

## 4. Fakturační adresy

První verze spravuje jednu fakturační adresu kontaktu a jednu vlastní firmy.
Adresa může chybět u neúplného záznamu; nevytváří se prázdný adresní řádek
jen kvůli existenci kontaktu nebo firmy.

| Pole | Pravidlo |
|---|---|
| Ulice a číslo / adresní řádek | Text; žádné povinné rozdělení podle českých čísel domů |
| Město / obec | Text |
| PSČ | Text, zachovává nuly i písmena; bez globálního českého formátu |
| Země adresy | Výběr země, povinná při zadání jiné části adresy |

Země registrace je samostatný údaj. Při zadávání registračního čísla se
může předvyplnit ze známé země adresy a uživatel ji může změnit.
Pozdější změna adresy již uloženou zemi registrace sama nezmění.

Adresy se od začátku ukládají samostatně v jedné tabulce `addresses`
se skutečnými FK `business_id` a `contact_id`; každá adresa patří právě
jednomu vlastníkovi, což hlídá i databáze (CHECK). Bankovní účty mají
stejné uspořádání v tabulce `bank_accounts`.
Model připouští více adres; první verze aplikačně umožní nejvýše jednu.
Formulář zatím zobrazuje běžná adresní pole, nikoli správu seznamu adres.
Kontakt a jeho adresa se ukládají konzistentně v jedné transakci.

Budoucí rozšíření přidá pojmenování adres, výběr na dokladu, označení
výchozí adresy a případnou archivaci jednotlivé adresy. Nevytvářejí se
kopie kontaktu kvůli jiné adrese téže firmy. Doručovací adresy nejsou
součástí první verze. Jiná právní identita má vlastní kontakt.

Důvod odděleného uložení: budoucí potřeba výběru adres je potvrzená;
rozšíření nebude vyžadovat přesun adresních polí ze záznamu kontaktu.
Sdílí se hodnota adresy a její validace, vlastnictví záznamů je jednoznačné.

## 5. Identita a duplicity

Zahraniční firmy lze evidovat od první verze. Globální katalog registrů
ani integrace zahraničních rejstříků se nezavádějí.

| Situace v rámci jednoho účtu | Chování |
|---|---|
| Shoda českého IČO | Zablokovat založení kopie a nabídnout existující kontakt |
| Shoda českého IČO s archivovaným kontaktem | Nabídnout výslovné obnovení; identita zůstává rezervovaná |
| Zahraniční číslo: stejná země registrace a číslo | Upozornit, umožnit pokračovat |
| Stejný název nebo e-mail bez jisté shody identity | Upozornit, umožnit pokračovat |
| Kontakt bez registračního čísla | Povolit; neslučovat automaticky podle jména či e-mailu |

Při shodě se záznam neobnovuje ani nepřepisuje automaticky. Stejná firma
v obou dokladových rolích zůstává jedním kontaktem.

České IČO se ukládá jako text včetně úvodních nul. Technické doplnění:
oříznout krajní mezery, vyžadovat osm číslic a ověřit kontrolní součet;
chybějící nuly se automaticky nedoplňují. Prázdné číslo se ukládá jako NULL.
U ostatních čísel se oříznou krajní mezery a zachová se jejich zápis;
český formát se na ně neuplatňuje. Návrh přesné normalizace je technickým
doplněním pro implementaci, ne požadavkem na online existenci subjektu.

Databáze musí zabránit i souběžnému založení dvou kontaktů se stejným
českým IČO v účtu, včetně archivovaných. Unikátní omezení se uplatní jen
na českou registrační identitu; zahraniční čísla mají vyhledávací index.
Konkrétní provedení podmíněného klíče je detail MySQL migrace.

Samotná dvojice země a místního čísla není univerzální identifikátor všech
registrů: EUID rozlišuje také registr, obdobně GLEIF registrační autoritu.
Další přísně kontrolované identifikační systémy se přidají s definicí
jejich registračního prostoru a normalizace. Podklady jsou v oddílu 15.

## 6. Bankovní účty a výchozí volby

### 6.1 Účty

Business i Contact mohou mít žádný, jeden nebo více účtů již v první verzi.
U každého vlastníka je nejvýše jeden aktivní výchozí účet, společný pro
všechny měny dokladu. Automatické směrování podle měny se nezavádí.
Účet nemá povinnou vazbu na jedinou měnu.

| Pole | Pravidlo |
|---|---|
| Označení | Nepovinné, například „Hlavní“ nebo „EUR“ |
| IBAN | Volitelný při úplných místních údajích |
| Místní číslo účtu | Volitelné při IBAN; včetně případného předčíslí |
| Kód / identifikace banky | Při použití místního čísla účtu |
| Země účtu | Kontext místních údajů; lze odvodit z platného IBAN |
| SWIFT/BIC | Nepovinný, při vyplnění validovaný |
| Název banky | Nepovinný informativní údaj |
| Výchozí | Jedna společná volba na vlastníka |

Účet vyžaduje IBAN nebo úplné místní údaje. Samotné označení nestačí.
IBAN se ověří na formát a kontrolní součet. Pokud jsou zadány oba zápisy
a umíme ověřit jejich vzájemný převod, nesmějí si odporovat.
Validace zápisu nepotvrzuje existenci účtu ani jeho vlastníka.

První účet se automaticky stane výchozím. Označení jiného účtu nahradí
předchozí volbu atomicky, i při souběžných požadavcích. Účet lze archivovat;
archivace výchozího vyžádá náhradu nebo výslovný stav bez aktivního účtu.
Archivované účty zmizí z nových výběrů. Jejich údaje zůstávají na dokladech.

Vystavený doklad používá konkrétní účet vlastní firmy. U přijatého dokladu
je rozhodující účet dodavatele zachycený na tomto dokladu; účet kontaktu
slouží k předvyplnění. Jednorázový účet se do adresáře ukládá výslovně.
Změna výchozího ani úprava účtu nepřepíše existující doklad nebo otevřený formulář.

Technický návrh popisuje podporované místní formáty; implementace zachová
stávající rozsah QR poskytovatelů. Uložení zahraničního účtu samo nerozšiřuje
podporu QR na všechny země. Bankovní převody, napojení bank a opravy ostatních částí
QR plateb, zejména číslování a variabilního symbolu, nejsou touto funkcionalitou.

### 6.2 Výchozí vlastní firma

První firma vytvořená při registraci je výchozí. Uživatel ji může změnit
na jinou aktivní firmu účtu. Nový doklad ji předvyplní a umožní změnit.
Protistranu určí uživatel, jednorázové zadání nebo akce z detailu kontaktu.
Globální výchozí kontakt neexistuje; `clients.is_default` se odstraní.

## 7. Doklad a adresář

Uživatel vybere existující kontakt nebo zadá jednorázovou protistranu.
Nová protistrana se uloží do adresáře jen výslovnou volbou
„Uložit také do kontaktů“. Bez ní se Contact nevytvoří, `contact_id`
zůstane NULL a počet kontaktů se nezmění.

Při výběru vlastní firmy, kontaktu, adresy a platebního účtu se potřebné
údaje předvyplní do dokladu. Doklad ukládá vlastní údaje stran a účtu
i v případě použití adresáře. Následné otevření a tisk používají uložené
údaje, nikoli aktuální adresář. Vztah platí také pro jednorázovou protistranu.

Změna údajů na rozpracovaném dokladu automaticky nemění adresář.
Načtení aktuálních údajů z adresáře nebo uložení změny do adresáře
je samostatná výslovná akce. Uživatel musí vědět, kterého záznamu se týká.
Interní poznámka a zkratka se nestávají údaji tištěné strany.

Například změna sídla kontaktu Brno → Praha předvyplní Prahu na novém
dokladu. Dříve uložený doklad zachová Brno. Na rozpracovaném dokladu
uživatel může výslovně načíst aktuální údaje.

Uložení stran a účtu patří již do této dodávky. Úplný životní cyklus,
uzamčení vystaveného dokladu, číslování při vystavení, opravné doklady
a uchování výsledného PDF včetně šablony a loga patří navazujícímu kroku 8.
Uložené údaje stran samy nejsou úplnou neměnností celého PDF.

### 7.1 Formuláře dokladů

- Povinné údaje stran faktury jsou v této dodávce stejné jako dnes:
  název, ulice, město, PSČ a země dodavatele i odběratele. Platí stejně
  pro vybraný kontakt, jednorázovou protistranu, frontend, administraci
  i fakturu nepřihlášeného. IČO a DIČ se při vyplnění ukládají již nyní;
  jejich povinnost podle daňového režimu a další náležitosti doplní krok 8.
- Vlastní firma se na faktuře jen vybírá, údaje dodavatele se ručně
  nepřepisují. Firma bez úplné adresy vyvolá validační chybu s odkazem
  na její úpravu.
- Účet vystavené faktury se vybírá jen z aktivních účtů vybrané firmy;
  jednorázový účet ji nemá. Účet je povinný jen u platby bankovním
  převodem, výchozí účet firmy se předvyplní.
- Faktura bez kontaktu nabízí na detailu „Uložit do kontaktů“; při shodě
  českého IČO „Propojit s existujícím kontaktem“. Obě akce nastaví jen
  vazbu na kontakt, údaje dokladu nemění. Vystavená faktura se po kroku 8
  nemění vůbec, ani vazbou na kontakt; akce pak zůstanou jen u nové
  faktury a konceptu.
- Odesílání dokladu e-mailem v aplikaci zatím není; pravidlo 10.3 platí,
  až vznikne. Upomínky platby používají e-maily uložené na dokladu.
- Faktura nepřihlášeného uživatele zadává dodavatele, odběratele i účet
  jednorázově, bez výběru z adresáře a bez ukládání do kontaktů.
- Administrace faktury a výdaje: uživatel, jeho aktivní firma a volitelně
  jeho aktivní kontakt. Výběr nebo změna kontaktu převezme jeho údaje,
  bez kontaktu se použijí zadané údaje protistrany; úprava beze změny
  kontaktu je opravou údajů dokladu. Účet výdaje pochází z účtů kontaktu
  nebo je jednorázový. „Uložit do kontaktů“ administrace nenabízí.

## 8. Archivace a obnova

Kontakty i vlastní firmy se v běžném rozhraní archivují, i když ještě
nejsou použité na dokladech. Fyzické odstranění není běžnou uživatelskou akcí.
Archivace a obnova obou entit patří do této dodávky, nikoli až do kroku 8.

- Archivovaný záznam se nenabízí v nových výběrech.
- Zůstává dostupný v přehledu archivovaných s možností výslovné obnovy.
- Vazby a uložené údaje dosavadních dokladů se archivací nezmění.
- Archivovaný kontakt si rezervuje případnou jednoznačnou českou identitu.
- Archivace uvolní místo v limitu, obnova vyžaduje volnou kapacitu.
- Archivace výchozí vlastní firmy s jinými aktivními firmami vyžádá
  výslovný výběr nové výchozí firmy.
- Poslední aktivní vlastní firmu lze archivovat. Účet pak nemá aktivní
  ani výchozí Business; historie zůstává dostupná a vystavení dalšího
  dokladu vyžaduje založení nebo obnovení aktivní vlastní firmy.

Archivace produktů zůstává v navazujícím kroku 8. Zrušení účtu a fyzické
odstranění jeho dat jsou samostatným případem užití mimo tento PRD.

## 9. Katalog produktů a tarifní limity

### 9.1 Produkty

Katalog produktů a služeb je společný pro uživatele přes `user_id`.
Každá jeho vlastní firma může použít stejnou položku; prodávajícího
určuje doklad. `products.supplier_id` se odstraní bez náhrady vazbou
na Business nebo Contact.

Budoucí přiřazení produktů firmám je směr rozvoje s dosud neznámým
způsobem použití. Nyní nevzniká nepoužívaný sloupec ani spojovací tabulka.
Budoucí migrace doplní vazbu podle toho, zda půjde o jednu nebo více firem.
Identita produktu zůstane zachována. Katalog neurčuje daňový profil firmy.

### 9.2 Limity

Tarif odděleně omezuje počet aktivních Business a aktivních Contact účtu.
Jde o aktuální kapacitu, nikoli počet vytvoření za rok.

| Operace | Dopad |
|---|---|
| Založení | Obsazení jednoho místa |
| Archivace | Uvolnění jednoho místa |
| Obnovení | Opětovné obsazení místa, kontrola dostupné kapacity |
| Použití existujícího kontaktu | Bez změny |
| Jednorázová protistrana bez uložení do adresáře | Bez změny počtu kontaktů |
| Přidání adresy nebo bankovního účtu | Není další kontakt či firma |

První firma z registrace se započítává; konfigurace registračního tarifu
musí její vytvoření umožnit. Konkrétní tarifní hodnoty tento PRD neurčuje.
Chybějící konfigurace nepovoluje založení ani obnovu podle pravidel UELS.
Kapacitní limity firem a kontaktů nemají výjimku pro neomezené vytváření,
ani v administraci; uplatní se limit vlastníka. Výjimky pro ostatní
periodické limity zůstávají podle pravidel UELS. Toto upřesnění odpovídá
odsouhlasenému technickému návrhu Q8–Q10.

Při snížení tarifu se nic automaticky nemaže ani nearchivuje. Čtení,
úpravy, archivace a používání existujících záznamů na dokladech pokračují.
Nové založení a obnova vyžadují kapacitu podle nového limitu.
Ostatní limity dokladů a předplatného se tím neruší.

Kontrola a změna aktivního počtu musí být konzistentní se zápisem,
i při souběhu. Dvě souběžná vytvoření nesmějí obsadit jediné volné místo.
Staré roční čítače nelze jen přejmenovat a ponechat se stejným významem.

## 10. Pravidla okrajových situací

Následující pravidla byla odsouhlasena při závěrečném uzavření specifikace.

1. **Vytvoření kontaktu z dokladu:** kontakt s volbou uložení do adresáře
   vznikne až s úspěšným uložením dokladu, ve stejné transakci. Chyba
   validace, duplicity či limitu nezanechá částečný kontakt, adresu,
   doklad nebo čítač. Při vyčerpaném limitu uživatel může volbu uložení
   kontaktu zrušit a uložit jednorázovou protistranu; aplikace to neudělá potichu.
2. **Nalezený existující kontakt:** uživatel ho může výslovně použít.
   Ručně zadané údaje dokladu se tím samovolně nepřepíší a kontakt
   se neaktualizuje; načtení jeho hodnot je samostatná výslovná volba.
   Jednorázovou protistranu lze do adresáře uložit také později z detailu
   dokladu se stejnými pravidly identity, oprávnění a limitu.
3. **Příjemce e-mailu:** doplnění adresy při odesílání ji samo neukládá
   do kontaktu. Lze výslovně zvolit její uložení, má-li uživatel oprávnění
   k úpravě kontaktu. Stejné pravidlo platí pro jednorázový účet protistrany.
4. **Archivace během práce:** rozpracovaný doklad uchová své údaje
   i vazbu na později archivovaný kontakt nebo účet a označí zdroj jako
   archivovaný. Nevynutí jeho obnovení a nezmění ho automaticky. Vlastní
   firma musí být při novém vystavení aktivní. Nový výběr archivované
   reference je odmítnut i při přímém zaslání ID do formuláře/API.
5. **Obnova firmy a výchozí volba:** pokud účet nemá žádnou aktivní firmu,
   první založená nebo obnovená se stane výchozí. Jinak obnova výchozí
   firmu nezmění. Archivace vlastníka nesmí zpřístupnit jeho účty v cizím
   nebo novém výběru; samostatně archivované účty zůstávají archivované
   i po obnovení vlastníka.
6. **Změna identity a vlastní firmy:** změna registrační identity přímo
   u protistrany dokladu vyžádá odpovídající kontakt nebo přechod na
   jednorázovou protistranu. Vlastní firmu určuje výběr Business.
   Oprava údajů v adresáři kontroluje nové kolize a nemění dřívější
   údaje dokladů. Změna vlastní firmy v rozpracovaném dokladu zachová
   položky společného katalogu a vyžádá výběr účtu nové firmy; účet původní
   firmy se nesmí ponechat. Daňové přepočty patří příslušným pravidlům dokladu.
7. **Business a Contact se stejnou identitou:** jsou možné jako dvě
   odlišné evidence v účtu, například při obchodování mezi vlastními
   firmami. Nevzniká automatická kopie ani synchronizace; unikátnost
   se kontroluje uvnitř příslušné entity. Stejná pravidla duplicit
   jako u Contact platí také pro vlastní firmy.

## 11. Hranice první dodávky

### Součást dodávky

- Business a Contact napříč frontendem, administrací a existujícími API.
- Společný adresář, formuláře a vyhledávání; pole podle oddílů 3–6.
- Samostatné adresy, více bankovních účtů a výchozí volby.
- Jednorázová protistrana, výslovné uložení do adresáře a údaje stran
  a účtu uložené s fakturou; `PartyPrintDTO` a související tiskové DTO.
- Archivace a obnova firem, kontaktů a účtů; aktivní tarifní počty.
- Oprava vyprazdňování polí a oddělení účtu od podnikatelské identity.
- Přejmenování `ico` / `dic` v celé dotčené cestě na `company_number`
  / `vat_number`, PHP vlastnosti `companyNumber` / `vatNumber`.
- Odstranění `products.supplier_id`, společný katalog podle uživatele.
- Úprava registrace, oprávnění, seedů, analytických dotazů a cache,
  které používají přejmenované entity. Statistiky zachovají své obchodní
  významy a zahrnou i doklady bez `contact_id` a s archivovanou stranou.
- Úprava PDF šablon a snapshot testů bez neplánované změny obsahu.

### Současné výdaje

V této dodávce se současné `expenses` upraví na vlastní firmu přes
`business_id` a externího dodavatele přes volitelné `contact_id`, případně
jednorázové údaje. Zruší se dnešní nejednoznačné `expenses.supplier_id`,
upraví se formuláře, filtry, validace vlastnictví a uloží se údaje stran
a platebního účtu podle stejného kontraktu.
Výdaj vyžaduje úplnou adresu externího dodavatele; u vlastní firmy v roli
odběratele stačí název (DESIGN Q47). Úplná adresa vlastní firmy je povinná
na vystavené faktuře podle oddílu 7.1.
Současné schvalovací stavy, výpočty DPH a ostatní chování výdajů se zachovají.
Nové dokladové typy, platební stavy a daňová pravidla se zavedou až při
navazující přestavbě na přijaté doklady.

Důvod: odstranění Supplier nesmí ponechat výdaj bez jasné protistrany
ani zaměnit vlastní firmu za externího dodavatele. Jde o konzistentní
adaptaci současných výdajů, ze které může vycházet jejich pozdější
přestavba na přijaté doklady.

### Navazující práce

- Úplná neměnnost vystavených dokladů, uchování PDF a souborů, opravné
  doklady, číslování při vystavení a pravidla variabilního symbolu.
- Přestavba výdajů na přijaté doklady podle D-044, nové dokladové typy,
  VAT řádky, režimy a daňové chování Business.
- Více fakturačních adres a výběr výchozí adresy v UI.
- Produktové vazby na firmy, výchozí účty podle měny, víceuživatelské
  organizace, oddělené adresáře, importy, hromadné slučování kontaktů
  a integrace zahraničních registrů.
- Konkrétní čísla tarifů, úplná přestavba předplatného a globální
  přejmenování oprávnění mimo dotčené entity.
- Archivace produktů a úplná konverze ostatních kontextů do standardu.

## 12. Architektura a bezpečnost

Business, Contact a jejich adresy mají jednoznačné vlastnictví a skutečné
FK. Každý bankovní účet patří právě jedné vlastní firmě nebo kontaktu.
Konkrétní fyzické uspořádání účtů a údajů stran je technický návrh;
nesmí spoléhat na polymorfní ID bez referenční integrity ani duplikovat
bankovní validaci podle typu vlastníka.

Sdílené chování adres, registračních identifikátorů, bankovních údajů,
archivace a předvyplnění se implementuje jednou. Rozdílné entity nemají
tvořit téměř totožné páry služeb. Nevzniká obecný CRM framework.

Nový a dotčený kód používá cílový standard: Domain v čistém PHP,
aplikační případy užití, DTO/WriteData, repozitářové porty a mapování
v Infrastructure. PartyServiceProvider zůstává composition root.
Frontendové formuláře a výběry používají Livewire/Alpine podle standardu;
převod celé fakturace nebo celé aplikace do Livewire není samostatným
požadavkem této změny. Endpointy pouze pro nahrazené JS předvyplnění
se odstraní, skuteční API konzumenti dostanou explicitní resources.

Frontend spravuje data aktuálního účtu. Administrace vyžaduje výslovná
oprávnění pro zobrazení, založení, úpravu, archivaci, obnovu a výchozí
volby příslušné entity. Oprávnění k vytvoření faktury samo nepovoluje
vytvořit či upravit kontakt. Účty a adresy podléhají oprávnění svého vlastníka.
Granulární katalog se zavede pro dotčené entity; ostatní zůstávají
v samostatném kroku přejmenování oprávnění.

Vlastnictví se ověřuje při každém čtení a zápisu, nejen u nabídky položek.
ID cizí firmy, kontaktu, adresy, účtu nebo produktu se nedá přiřadit.
Admin při práci s cizím účtem používá výslovnou oprávněnou cestu;
všechny vzájemné vazby stále patří vlastníkovi záznamu, ne náhodně aktérovi.
Cache uživatelských dat vždy zahrnuje vlastníka a po změně se invaliduje.
Jména, e-maily, telefony a interní poznámky nejsou automaticky součástí
logů ani veřejných API odpovědí.

Zápisy kontaktu s adresou, registrace s první firmou, změny výchozích voleb
a vytváření/obnova s limity jsou atomické. Odvozené čítače se mění spolu
s daty; cache až po commitu. HTTP GET nemění stav.

UI a hlášení jsou dostupná v cs/de/en/sk. Uživatelské názvy jsou
„Moje firmy“, „Kontakty“, „Dodavatel“ a „Odběratel“ podle kontextu.
Nové zdrojové entity a veřejné kontrakty nepoužívají Client/Supplier jako
alternativní názvy Business/Contact. Tiskové role zůstávají supplier/customer.

## 13. Akceptační scénáře

| ID | Scénář a očekávaný výsledek |
|---|---|
| A01 | Dvě vlastní firmy účtu použijí tentýž kontakt a produkt bez kopie. |
| A02 | Kontakt lze uložit jen se jménem; bez e-mailu a telefonu. Zadané nepovinné údaje lze následně vyprázdnit. |
| A03 | Registrační číslo vyžaduje zemi registrace; změna země adresy ji sama nezmění. |
| A04 | Shoda českého IČO blokuje kopii včetně souběžného zápisu a archivovaného záznamu. Zahraniční shoda, jméno či e-mail pouze upozorní. |
| A05 | Kontakt z výběru nemusí mít předchozí doklad ani ručně zadanou roli. |
| A06 | Jednorázová protistrana přežije uložení, načtení a tisk bez kontaktu a bez spotřeby kontaktového limitu. |
| A07 | „Uložit také do kontaktů“ respektuje duplicity, oprávnění a kapacitu; selhání neukládá částečný kontakt ani doklad. |
| A08 | Změna adresáře neovlivní uložené strany ani účet dokladu při načtení a tisku. Opačná aktualizace vyžaduje výslovnou akci. |
| A09 | Firma i kontakt mají nejvýše jednu spravovanou adresu v první verzi; ukládá se samostatně a konzistentně s vlastníkem. |
| A10 | Lze přidat více účtů, první je výchozí. Souběžná změna nezanechá dva výchozí; měna dokladu výběr sama nepřesměruje. |
| A11 | Neúplný účet a chybný IBAN se odmítnou. Archivace výchozího vyžádá náhradu či výslovný stav bez účtu; historie se nezmění. |
| A12 | Vystavená faktura tiskne Business jako dodavatele a účet z jeho vybrané platby; Contact nebo jednorázová protistrana je odběratel. |
| A13 | Adaptovaný výdaj používá Business jako odběratele a Contact nebo jednorázovou protistranu jako dodavatele; uchová účet externího dodavatele a zachová dosavadní stavy a výpočty. |
| A14 | Archivovaná firma a kontakt zmizí z nových nabídek, zůstanou v archivu i historii. Obnova neproběhne automaticky podle zadaného IČO. |
| A15 | Archivace výchozí firmy vyžádá náhradu. Poslední firmu lze archivovat; historie zůstane přístupná a další vystavení vyžádá aktivní firmu. |
| A16 | Archivace uvolní místo, obnova jej vyžaduje. Dva požadavky na poslední místo povolí nejvýše jedno vytvoření nebo obnovu. |
| A17 | Snížení tarifu zachová data, úpravy i použití; nad kapacitou odmítne vytvoření a obnovu. |
| A18 | Jiný účet nezíská cizí údaje přes seznam, hledání, detail, cache, předvyplnění, přímý zápis ID ani vložené adresy/účty. |
| A19 | Administrace a API vynucují stejné doménové invarianty, vlastnictví, limity a oprávnění jako frontend. |
| A20 | Registrace vytvoří vlastní firmu atomicky a jako výchozí; pozdější změna přihlašovacího e-mailu nezmění firmu. |
| A21 | Výchozí vlastní firma se předvyplní na nový doklad; protistrana nemá globální výchozí volbu. |
| A22 | Analytika zahrne doklady bez kontaktu a s archivovanými stranami bez změny významu metrik. |
| A23 | Texty a validace existují ve všech čtyřech jazycích; PDF snapshoty zachovají obsah mimo výslovně schválené změny. |
| A24 | Použití nalezeného kontaktu samo nepřepíše ručně zadané údaje dokladu. Pozdější uložení jednorázové protistrany do adresáře respektuje oprávnění, duplicity a kapacitu. |
| A25 | Doplnění e-mailu při odesílání ani jednorázového účtu samo nemění adresář; výslovné uložení vyžaduje oprávnění k příslušné změně. |
| A26 | Archivace kontaktu nebo účtu během práce zachová údaje rozpracovaného dokladu, označí archivovaný zdroj a nevynutí obnovu. Nové vystavení vyžaduje aktivní vlastní firmu. |
| A27 | První založená či obnovená firma po archivaci všech firem je výchozí. Při existující aktivní firmě obnova výchozí volbu nezmění ani neobnoví samostatně archivované účty. |
| A28 | Změna vlastní firmy zachová položky a vyžádá účet nové firmy. Změna registrační identity protistrany vyžádá odpovídající kontakt nebo jednorázovou protistranu. |
| A29 | Stejná registrační identita může existovat jako Business i Contact bez automatické kopie a synchronizace; uvnitř každé entity platí vlastní kontrola duplicit. |

## 14. Postup implementace a dokončení

Následuje schválené pořadí a kritéria dokončení kroku 7. Postup zůstává
součástí specifikace; aktuální rozsah zachycuje mapa VERIFICATION.

1. Nové reverzibilní migrace a vývojové seedery; Business/Contact,
   adresy, účty, identifikátory, výchozí volby a aktivní limity.
2. Sdílené doménové hodnoty a validace, porty, mapování a transakční
   případy užití včetně registrace, archivace a obnovy.
3. Frontend, Backpack a používaná API; společné formuláře a výběry,
   přejmenované routes, překlady a oprávnění dotčených entit.
4. Údaje stran a účtu na faktuře, jednorázová protistrana, tisková DTO,
   PDF/QR adaptace, produkty a navazující analytika. Výdaje podle oddílu 11.
5. Chování akceptačních scénářů na MySQL, testy PDF, lokalizace,
   statická analýza a architektonické kontroly podle repozitáře.

Implementace je hotová až po napojení všech cest a odstranění starých
nepoužívaných párů, ne vytvořením nevyužitých nových tříd vedle původních.
Udělané položky TECH_DEBT se odškrtnou až po implementaci a ověření.
Technický návrh implementace je v `docs/design/DESIGN_BUSINESSES_AND_CONTACTS.md`.

Společný seznam faktur umožní filtrování podle vlastní firmy a kontaktu
podle technického návrhu, oddíl 4 „Frontend screens“. Historie zůstává
dostupná i pro archivované strany a každý filtr respektuje vlastníka dat.
Odstranění původních samostatných komponent samo tento požadavek nesplňuje.

Stav implementace, výsledky závěrečného ověření a mapa scénářů A01–A29 jsou
v [VERIFICATION_BUSINESSES_AND_CONTACTS.md](VERIFICATION_BUSINESSES_AND_CONTACTS.md).
Před dodáním změny se ověří aktuální rozsah celé větve podle pravidel
repozitáře. Mapa pokrytí nenahrazuje aktuální výsledky těchto kontrol.

## 15. Podklady a důvody rozhodnutí

- [TECH_DEBT](../TECH_DEBT.md): krok 7, neměnnost dokladů, přijaté doklady,
  archivace, prázdné hodnoty, bankovní validace, standard a oprávnění.
- [D-042](../adr/D-042-context-service-providers.md): composition roots.
- [D-043](../adr/D-043-modular-monolith.md): modulární monolit.
- [D-044](../adr/D-044-document-tables.md): navazující dokladový model,
  v době návrhu označen Proposed; nové typové tabulky nejsou tímto schváleny.
- [D-045](../adr/D-045-businesses-and-contacts.md): oddělení Business/Contact.
- `AGENTS.md` a `.claude/rules/`: společné AI pokyny, cílová architektura, persistence,
  HTTP, frontend, administrace, platby, překlady, oprávnění a testování.
- Historické podklady ověřené při návrhu 2026-10-26: migrace Party/Product/Invoice/Expense, RegisterController,
  InvoicePartyService, PartyApplicationService, ClientWriteData,
  SupplierWriteData, InvoicePrintDataBuilder, PrintableInvoiceData,
  ProductFormDataService, AresLookupController, ReferenceDataSeeder,
  PermissionCatalog a tehdejší QR providery. Některé uvedené třídy byly
  v kroku 7 odstraněny nebo nahrazeny; aktuální cesty a testy uvádí VERIFICATION.
- [Evropská komise: EPREL Supplier User Manual](https://ec.europa.eu/assets/move-ener/eprel/EPREL%20Compliance/User%20guides/Supplier/supplier-user-manual.html):
  EUID rozlišuje zemi, registr a číslo; podklad pro opatrnou mezinárodní identitu.
- [GLEIF: Registration Authorities List](https://www.gleif.org/en/lei-data/code-lists/gleif-registration-authorities-list):
  autorita a místní identifikátor; bez požadavku integrovat LEI/GLEIF.
- [MF: ARES, katalog veřejných služeb](https://www.mfcr.cz/assets/attachments/2023-08-01_ARES-Technicka-dokumentace-Katalog-verejnych-sluzeb_v07.pdf):
  české IČO jako osmimístný textový identifikátor.
