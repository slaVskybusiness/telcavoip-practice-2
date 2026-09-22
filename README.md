# TelcaVoIP - Panel zarzadzania klientami i uslugami VoIP

Projekt praktyk (staz) - wewnetrzna aplikacja webowa do zarzadzania klientami,
kontami VoIP, uslugami, odnowieniami i interwencjami technicznymi.

## Stos technologiczny

| Warstwa | Technologia |
|---------|-------------|
| Frontend | React (Vite) |
| Backend | FastAPI (Python) |
| Baza danych | MongoDB |
| API | REST + dokumentacja Swagger/OpenAPI |
| Uruchomienie | Docker (docker-compose) |

## Jak uruchomic

### Wariant 1: Docker (zalecany)

```
docker compose up --build
```

- Frontend: http://localhost:5173
- Backend (API): http://localhost:8000
- Dokumentacja API (Swagger): http://localhost:8000/docs

### Wariant 2: recznie (do developmentu)

Backend (wymaga uruchomionego MongoDB na porcie 27017):

```
cd backend
python -m venv venv
venv\Scripts\activate        # Windows
pip install -r requirements.txt
uvicorn main:app --reload
```

Frontend:

```
cd frontend
npm install
npm run dev
```

## Konto startowe

Przy pierwszym uruchomieniu tworzy sie konto administratora:

- e-mail: `admin@telcavoip.eu`
- haslo: `admin123`

## Role i uprawnienia

- **Administrator** - pelny dostep, zarzadza uzytkownikami, widzi dziennik audytu.
- **Operator** - zarzadza klientami, kontami VoIP, uslugami i odnowieniami.
- **Technik** - obsluguje interwencje techniczne (otwiera, zamyka, dodaje pliki).

Kazdy endpoint sprawdza role uzytkownika (deny by default - domyslnie odmawiamy).

## Model danych (kolekcje MongoDB)

- **users** - uzytkownicy (name, email, password_hash, role, active)
- **customers** - klienci (name, vat, email, phone, address, active, created_at)
- **voip_accounts** - konta VoIP (customer_id, number, status)
- **services** - uslugi (customer_id, type, start_date, expiry_date)
- **renewals** - historia odnowien uslug (service_id, new_expiry_date, registered_by)
- **interventions** - interwencje (customer_id, type, status, opened_at, closed_at, notes)
- **attachments** - zalaczniki (entity_ref, filename, mime_type, size)
- **audit_logs** - dziennik zdarzen (actor_id, action, entity, entity_id, timestamp)

## Wazne decyzje projektowe

- **Hasla** trzymamy tylko jako hash (bcrypt), nigdy jako zwykly tekst.
- **Logowanie** oparte na tokenie JWT.
- **Status uslugi** liczymy zawsze z daty waznosci - usluga po terminie nigdy
  nie pokaze sie jako aktywna.
- **Duplikaty klientow** (ten sam NIP / email / telefon) sa wykrywane i blokowane
  przy dodawaniu i edycji.
- **Konto VoIP** zawsze przypiete do istniejacego klienta; przepiecie na innego
  klienta jest zapisywane w audycie.
- **Dziennik audytu** jest tylko do dopisywania - nie ma metody edycji ani
  usuwania wpisow, wiec operator i technik nie moga go zmienic.
- **Zalaczniki** sa sprawdzane pod katem typu i rozmiaru (max 5 MB) - zle pliki
  sa odrzucane.

## Co jest zrobione (obowiazkowe wymagania z ТЗ)

- [x] Logowanie i autoryzacja wg roli (RBAC)
- [x] CRUD klientow, kont VoIP, uslug i interwencji
- [x] Wykrywanie duplikatow klientow
- [x] Ochrona przed blednym powiazaniem konta VoIP
- [x] Poprawna obsluga wygasania i odnowien uslug
- [x] Dziennik audytu (niezmienialny)
- [x] Walidacja zalacznikow, brak wrazliwych danych w logach
- [x] Dokumentacja API (Swagger) i uruchomienie przez Docker

Rozszerzenia opcjonalne (email, eksport CSV/PDF, masowy import) - poza zakresem
tego etapu.
