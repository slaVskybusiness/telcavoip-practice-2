# TelcaVoIP - Panel zarzadzania klientami i uslugami VoIP

Projekt praktyk - wewnetrzna aplikacja webowa do zarzadzania klientami,
kontami VoIP, uslugami, odnowieniami i interwencjami technicznymi.

## Technologie

- **HTML / CSS / JavaScript** - frontend
- **PHP** - backend (bez frameworkow)
- **MySQL** - baza danych (obsluga przez phpMyAdmin)

## Jak uruchomic (OSPanel / XAMPP)

1. Skopiuj projekt do folderu serwera (np. domena w OSPanel).
2. Uruchom **phpMyAdmin** i zaimportuj plik **`database.sql`**
   (utworzy baze `telcavoip` z tabelami i kontem admina).
3. W razie potrzeby popraw dane logowania do bazy w pliku **`config.php`**
   (domyslnie host `127.0.0.1`, uzytkownik `root`, haslo puste).
4. Otworz strone w przegladarce, np. `http://localhost/telcavoip/login.php`.

## Konto startowe

- e-mail: `admin@telcavoip.eu`
- haslo: `admin123`

## Role i uprawnienia (RBAC)

- **Administrator** - wszystko + zarzadzanie uzytkownikami i dziennik audytu.
- **Operator** - klienci, konta VoIP, uslugi, odnowienia.
- **Technik** - interwencje techniczne (otwiera, zamyka, dodaje pliki).

Kazda akcja sprawdza role (domyslnie odmawiamy - deny by default).

## Baza danych (tabele)

`users`, `customers`, `voip_accounts`, `services`, `renewals`,
`interventions`, `attachments`, `audit_logs`.

## Wazne decyzje

- Hasla trzymane jako hash (`password_hash`), nigdy jawnie.
- Logowanie na sesjach PHP.
- Zapytania przygotowane (PDO) - ochrona przed SQL injection.
- Status uslugi liczony z daty waznosci - usluga po terminie nie jest aktywna.
- Duplikaty klientow (NIP / email / telefon) blokowane przy dodawaniu i edycji.
- Dziennik audytu tylko do dopisywania - brak edycji i usuwania w kodzie.
- Zalaczniki sprawdzane pod katem typu (PDF/PNG/JPG) i rozmiaru (max 5 MB).

## Wspolpraca (dla drugiej osoby)

Baze przekazujemy jako plik `database.sql` (eksport z phpMyAdmin).
Kazdy importuje go u siebie lokalnie przez phpMyAdmin i pracuje na swojej kopii.

## Zrobione (obowiazkowe wymagania)

- [x] Logowanie i role (RBAC)
- [x] CRUD klientow, kont VoIP, uslug i interwencji
- [x] Wykrywanie duplikatow klientow
- [x] Konto VoIP zawsze przypiete do istniejacego klienta
- [x] Obsluga wygasania i odnowien uslug
- [x] Dziennik audytu (niezmienialny)
- [x] Walidacja zalacznikow
