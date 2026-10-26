# Reise

En liten reiseapp for turer og pakkelister. Appen styres først og fremst av eksterne AI-agenter gjennom MCP, men har også et vanlig REST-API og en enkel, mobilvennlig nettside.

Agenten lager forslagene. Appen lagrer data, håndhever tilgang og tilbyr anonymiserte eksempler fra tidligere turer. Det finnes ingen innebygd AI-assistent.

## Innhold

- [Kom i gang](#kom-i-gang)
- [Arkitektur](#arkitektur)
- [For utviklere](#for-utviklere)
- [For AI-agenter](#for-ai-agenter)
- [Status: hva er gjort](#status-hva-er-gjort)
- [Hva mangler](#hva-mangler)
- [Dokumentasjon](#dokumentasjon)

## Kom i gang

Krav: PHP 8.5, Composer, Node/npm. Lokalt brukes Laravel Valet (`http://reise.test`) og SQLite.

```bash
composer run setup                          # .env, nøkkel, migrering, npm install, bygg
php artisan passport:keys --no-interaction  # OAuth-nøkler for API og MCP
php artisan db:seed                         # valgfritt: demobruker demo@example.com
composer run dev                            # server, kø, logger og Vite
```

E-post skrives til loggen lokalt (`MAIL_MAILER=log`). Invitasjonslenker og verifiseringslenker finnes derfor i `storage/logs/laravel.log`.

## Arkitektur

**Domene.** En tur (`Trip`) er tilgangsgrensen (tenant). Alt annet hører til en tur:

| Tabell | Innhold |
| --- | --- |
| `trips` | Reisemål, datoer, type, transport, overnatting, aktiviteter, beskrivelse. Varighet beregnes fra datoene. |
| `trip_user` | Medlemskap med rolle `admin` eller `member`. Turen har alltid minst én admin. |
| `trip_participants` | Deltakere, med eller uten konto (f.eks. barn), valgfri alder. |
| `trip_invitations` | E-postinvitasjoner med hashet engangstoken og utløp. |
| `packing_items` | Navn, antall, kommentar. `participant_id = null` betyr felleslisten. |
| `trip_inspiration_*` | Anonymisert projeksjon for inspirasjon på tvers av brukere. |

**Én domeneflyt, tre grensesnitt.** Nettside, API og MCP deler samme regler:

```
Web (Blade)  ─┐
API (/api/v1) ├─► Form Request / MCP-validering ─► TripPolicy ─► Actions ─► Modeller
MCP (/mcp)   ─┘
```

- `app/Actions/` – delt forretningslogikk (opprette tur med admin, invitasjon, aksept, rolleendring, pakkepunkter).
- `app/Policies/TripPolicy.php` – rolle- og tilgangsregler.
- `app/Http/Middleware/SelectTripTenant.php` – velger aktiv tur per forespørsel via brukerens medlemskap. Valget lagres ikke globalt.
- `app/Services/SearchSimilarTrips.php` og `InspirationCatalog.php` – søk etter lignende turer og godkjent ordliste for anonym inspirasjon.
- `app/Mcp/` – MCP-server (`TripServer`) og ni tynne verktøy.

**Teknologi:** Laravel 13, Fortify (innlogging, registrering, e-postverifisering), Passport (OAuth 2.0 med PKCE), Laravel MCP, Pest 5, Bootstrap 5.3 via Vite.

## For utviklere

### Regler

1. **Følg [`docs/laravel-instructions.md`](docs/laravel-instructions.md) og `CLAUDE.md`/`AGENTS.md`.** De er kilden; README gjentar dem ikke.
2. **Jobb på en feature-branch.** Aldri direkte på `main`.
3. **Én regel, ett sted.** Ny funksjonalitet går i en Action og/eller policy, og kalles fra web, API og MCP. Ingen grensesnitt kaller et annet over HTTP.
4. **Tenant-scoping.** Les turdata gjennom `currentTenant`-relasjoner med `findOrFail()`. Tur uten medlemskap gir `404`, manglende rolle gir `403`.
5. **Personvern i inspirasjon.** Data fra andre brukere går bare gjennom inspirasjonsprojeksjonen. Aldri kommentarer, deltakere, e-post, fritekst eller eksakt reisemål.
6. **Minste komplette løsning.** Ikke legg til funksjoner, avhengigheter eller nye basemapper uten godkjenning.
7. **Frontend.** Bootstrap-markup bare i egne `x-app.*`/`x-form.*` Blade-komponenter, ikke direkte i sidevisninger. Tekster lokaliseres med engelsk som grunnspråk.

### Før du leverer

```bash
vendor/bin/pint --dirty --format agent
npm run build                       # ved endringer i CSS/JS/Blade
php artisan test --compact          # eller smalere: --filter=... / filsti
```

Tester ligger i `tests/Feature/{Api,Auth,Domain,Mcp,Web}`. Lag nye med `php artisan make:test --pest NavnTest` og bruk factories.

### Planlagte jobber

`inspiration:projections-refresh` kjører daglig via scheduleren. I produksjon må `php artisan schedule:run` kjøres hvert minutt.

## For AI-agenter

### Som utviklingsagent i kodebasen

1. Les `CLAUDE.md` (Claude) eller `AGENTS.md`/`GEMINI.md` først. De inneholder Laravel Boost-reglene for prosjektet.
2. Bruk Laravel Boost MCP-verktøyene: `search-docs` før du bruker Laravel-API-er, `database-schema` før migreringer, `application-info` for versjoner.
3. Bruk `php artisan make:*` med `--no-interaction` for nye filer.
4. Kjør smaleste relevante test etter hver endring, og Pint før levering.
5. Spør før du endrer avhengigheter, oppretter dokumentasjonsfiler eller nye basemapper.
6. Rapporter ærlig hva som er testet, og hva som ikke er det.

### Som brukeragent via MCP

- **URL:** `http://reise.test/mcp/trips` (lokalt), Streamable HTTP.
- **Autentisering:** OAuth 2.0 authorization code med PKCE, scope `mcp:use`. Agenten handler med rettighetene til brukeren som godkjente tilkoblingen.
- **Verktøy:** `list_trips`, `show_trip`, `create_trip`, `update_trip`, `show_packing_lists`, `search_similar_trips`, `create_packing_item`, `update_packing_item`, `delete_packing_item`.

Anbefalt flyt:

1. `create_trip` ut fra brukerens beskrivelse. Supplér metadata med `update_trip` etter hvert.
2. `search_similar_trips` for å finne egne tidligere turer (`source: own`) og anonyme eksempler (`source: anonymous`).
3. Vekt egne lister høyest. Bruk anonyme eksempler som supplement.
4. Skriv forslag med `create_packing_item`, ett punkt om gangen, til felleslisten eller en deltakers liste.
5. Ved endringer: bruk `update_packing_item`/`delete_packing_item` på enkeltpunkter. Ikke slett og gjenskap hele lister – urørt innhold skal bevares.

Finnes ingen lignende turer, lag listene direkte fra brukerens beskrivelse.

## Status: hva er gjort

Førsteversjonen fra [`docs/init-instructions.md`](docs/init-instructions.md) er implementert:

- ✅ Registrering, innlogging, passordtilbakestilling og e-postverifisering (Fortify).
- ✅ Turer med metadata, dagsturer, deltakere med og uten konto, alder.
- ✅ Felles pakkeliste og én liste per deltaker; legg til, endre og slett punkter.
- ✅ Roller: admin/medlem, flere admins, siste admin kan ikke fjernes.
- ✅ Invitasjon via e-post, aksept krever innlogget bruker med verifisert, samsvarende e-post.
- ✅ Nettside: turoversikt og turside med admin-kontroller. Automatisk mørk modus etter systeminnstilling.
- ✅ REST-API `/api/v1` med Passport og JSON-ressurser ([referanse](docs/api-reference.md)).
- ✅ MCP-server med OAuth og ni verktøy.
- ✅ Søk etter lignende turer med anonymisert inspirasjon (godkjent ordliste, minst tre ulike tureiere).
- ✅ 202 Pest-tester, 902 assertions, alle grønne (26.10.2026).

## Hva mangler

**Må gjøres før produksjon**

1. **Ekte e-postutsending.** `MAIL_MAILER=log` lokalt; SMTP/tjeneste for invitasjoner og verifisering er ikke satt opp.
2. **Deploy-oppsett.** Ingen produksjonsmiljø, database, kø-worker eller scheduler er konfigurert.
3. **Manuell kontroll.** Nettsiden er ikke visuelt kontrollert på mobilbredde etter Bootstrap-byttet, og MCP er ikke kjørt mot MCP Inspector med autentisert og uautentisert klient.

**Kjent avvik og opprydding**

4. **Bootstrap-versjon.** Prosjektet bruker Bootstrap 5.3, men `docs/laravel-instructions.md` og `docs/implementation-plan.md` sier 6.0.0-alpha.1. Dokumentene bør oppdateres – eller koden flyttes til 6 når den er stabil.
5. **Skjelettrester.** `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` og `"name": "laravel/laravel"` i `composer.json`.

**Kjente begrensninger (bevisst i v1)**

- Anonym inspirasjon er tom til minst tre ulike tureiere bidrar med godkjente punktnavn.
- Ingen avkrysning, pakkestatus eller ansvarstildeling.
- Ingen innebygd AI; forslag kommer fra den eksterne agenten.

## Dokumentasjon

| Fil | Innhold |
| --- | --- |
| [`docs/init-instructions.md`](docs/init-instructions.md) | Opprinnelig oppgave og krav. |
| [`docs/implementation-plan.md`](docs/implementation-plan.md) | Gjennomføringsplan og designbeslutninger. |
| [`docs/api-reference.md`](docs/api-reference.md) | API- og MCP-referanse. |
| [`docs/laravel-instructions.md`](docs/laravel-instructions.md) | Kodekonvensjoner (fra `iglebekk/laravel-instructions`). |
| [`docs/bootstrap-designguide.md`](docs/bootstrap-designguide.md) | Designguide for Bootstrap. |
