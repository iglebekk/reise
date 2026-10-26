# Gjennomføringsplan: reiseapp med pakkelister

## Mål og rammer

Lever en liten, fungerende Laravel-app der en bruker kan administrere turer og pakkelister via nettside, API og en ekstern AI-agent koblet til MCP. Agenten lager forslagene; appen lagrer data, håndhever tilgang og tilbyr trygge eksempler fra tidligere turer. Førsteversjonen har ingen avkryssing, pakkestatus, ansvarstildeling eller innebygd AI-assistent.

Prosjektet er nå et Laravel 13-skjelett med PHP 8.5, Pest 5, Vite og SQLite. Det finnes ingen turmodeller, autentisering eller API. Følg `AGENTS.md` og `docs/laravel-instructions.md`. Bruk Ponytail aktivt: velg den minste komplette løsningen, gjenbruk Laravel og fjern planlagt kode som ikke trengs for brukerreisen. `generelle-laravel-prinsipper.md` finnes ikke i prosjektet; avklar dette før PHP-implementasjon hvis filen fortsatt er påkrevd.

**Avklaringer fra planleggingen:** En tur er tilgangsgrensen (tenant). Inspirasjon fra andre brukere består bare av punktnavn fra en godkjent standardordliste og grove metadata. Nettsiden bruker Bootstrap, ikke Tailwind eller Flux. Bruk [Bootstrap 6.0.0-alpha.1](https://getbootstrap.com/docs/getting-started/), som prosjektets Laravel-instruksjoner angir. Versjonen krever moderne nettlesere; kontroller støtten mot målgruppen før lansering.

## 1. Grunnlag og avhengigheter

- Få særskilt godkjenning før nye avhengigheter eller nye basemapper opprettes. Nødvendige pakker er `laravel/fortify`, `laravel/passport`, `laravel/mcp` som direkte produksjonsavhengighet, og `bootstrap@6.0.0-alpha.1`. `laravel/mcp` finnes i dag bare transitivt via Boost. Ikke installer en rollepakke: turroller er to verdier på turmedlemskapet, og en global rolletabell ville ikke forenkle dette.
- Bruk Fortify til registrering, innlogging, passordtilbakestilling og verifisering av e-post. Lag egne Blade-sider for disse flytene. Invitasjoner kan først aksepteres av en innlogget bruker med verifisert e-post som samsvarer med invitasjonen.
- Erstatt Tailwind-importen og de unødvendige Tailwind-pakkene med Bootstrap-CSS i `resources/css/app.css`. Behold eksisterende Vite-oppsett. Legg bare nødvendig Bootstrap-JavaScript i `resources/js/app.js`; bruk ikke inline script i Blade. Bruk Bootstrap-komponenter og mobiltilpasset grid gjennom egne `x-app.*` og `x-form.*` Blade-komponenter.
- Les Boost `application-info` og `search-docs` for installerte versjoner og aktuelle Laravel-API-er før koding. Bruk Artisan med `--no-interaction` for generatorer. Opprett ikke en alternativ frontend, et nytt designbibliotek eller dokumentasjon ut over denne planen, API-referansen og korte bruksinstrukser som oppgaven ber om.

**Ferdig når:** Bruker kan registrere seg, verifisere e-post, logge inn og se en tom turoversikt på mobil. Det finnes ingen direkte bruk av Bootstrap-markup i sidevisninger.

## 2. Datamodell og turregler

Opprett bare tabellene som trengs for kjerneflyten:

| Data | Innhold og regel |
| --- | --- |
| `trips` | Reisemål, startdato, sluttdato, type, transport, overnatting, aktiviteter og valgfri beskrivelse. Varighet beregnes fra datoene; samme dato er en dagstur. Turen er tenant-roten. |
| `trip_user` | Medlemskap med `trip_id`, `user_id` og `role` (`admin` eller `member`). Unik bruker per tur. Oppretteren får adminrollen i samme transaksjon som turen opprettes. |
| `trip_participants` | Navn, valgfri `user_id` og valgfri alder i hele år ved turstart. En person uten konto har `user_id = null`. Deltakeren og listen består selv om et medlemskap senere fjernes. |
| `trip_invitations` | Tenant, normalisert e-post, ønsket rolle, hash av engangstoken, utløp og aksepttid. Bare admin kan opprette eller trekke tilbake invitasjoner. |
| `packing_items` | Tenant, valgfri `participant_id`, obligatorisk navn, positivt heltallsantall (standard 1) og valgfri kommentar. `participant_id = null` er felleslisten; ellers tilhører punktet deltakerens liste. Tomme lister finnes som visninger av turen/deltakeren uten en egen listetabell. |

Bruk factories og en liten demo-seeder. Modeller som eies av turen bruker `BelongsToTenant`. La `User::currentTenant` peke til turen som er valgt **for den aktuelle forespørselen**: slå først opp turen gjennom brukerens medlemskapsrelasjon, sett `current_tenant_id` og relasjonen på brukermodellen i minnet, og lagre ikke valget globalt. Turoversikten leses gjennom brukerens turrelasjon. Dette hindrer at samtidige API-, MCP- og nettleserforespørsler bytter aktiv tur for hverandre.

Les turens deltakere, invitasjoner og punkter via `currentTenant`-relasjoner med `findOrFail()`. Invitasjonsaksept er et avgrenset unntak før medlemskap finnes: finn invitasjonen med hash av tokenet, kontroller utløp og verifisert e-post, og opprett medlemskap i en transaksjon. Policies krever både riktig `current_tenant_id` og relevant rolle ved skriving. Oppdatering eller sletting av siste admin avvises i samme transaksjon som rolleendringen, med låsing der databasen støtter det. En fjernet bruker mister tilgang ved neste forespørsel selv om OAuth-tokenet fortsatt er gyldig.

**Ferdig når:** Admin kan opprette tur, invitere og forfremme et medlem; flere adminer støttes; siste admin bevares; barn uten konto har egne lister; ingen lesing eller skriving krysser turgrenser.

## 3. Én domeneflyt, tre grensesnitt

Hold HTTP-controllere og MCP-verktøy tynne. Bruk Form Requests for nett/API-validering og autorisasjon, policies for turroller og små Actions for handlinger som faktisk deles, særlig opprettelse av tur med admin, invitasjonsaksept, rolleendring og pakkepunktendring. Felleslisten finnes implisitt fra turen, også når den er tom. MCP validerer argumenter med Laravel MCP og kaller samme Actions og policies. Ingen grensesnitt skal sende HTTP-kall til et annet grensesnitt i samme app.

**Nettside:** Lag innlogging, turoversikt og én turside. Tursiden viser og redigerer metadata, deltakere, felleslisten og alle deltakerlistene. Alle med medlemskap kan endre disse dataene. Bare admin ser kontroller for invitasjoner, roller, fjerning av tilgang og sletting av tur. Bruk named routes, lokaliserte tekster med engelsk grunnspråk, egne Blade-wrappere, synlige valideringsfeil og mobiltilpassede Bootstrap-skjemaer. Ikke legg PHP-logikk eller rå HTML i sidevisninger.

**API:** Lag `/api/v1` med Passport bearer-autentisering og JSON API Resources. Tilby liste/opprett/vis/oppdater/slett for egne turer der rollen tillater det; les/opprett/oppdater/slett for deltakere og pakkepunkter; samt invitasjon, aksept og rolle-/tilgangsendring. Bruk ressursruter der handlingene passer, og egne ressurscontrollere for invitasjoner og medlemskap. Returner `404` for turressurser uten medlemskap, `403` for en kjent tur der rollen ikke tillater handlingen, og `422` ved valideringsfeil. Publiser en kort referanse med autentisering, endepunkter, felt, eksempelrespons og feil.

**MCP:** Undersøk først MCP-serveren i `/Users/andersiglebekk/Code/claudemcpserver`: se særlig på ruteregistrering, autentisering, verktøystruktur, validering og tester. Gjenbruk relevante mønstre som passer denne appen, og kontroller dem mot installerte pakkeversjoner og Laravel-dokumentasjonen. Registrer deretter HTTP-server i `routes/ai.php`, OAuth discovery med `Mcp::oauthRoutes()` og `auth:api` på MCP-ruten. Bruk Passport authorization code med PKCE, slik at hver ekstern agent handler som brukeren som godkjente tilkoblingen. Gi verktøy for å opprette/oppdatere tur, liste/lese egne turer og pakkelister, søke etter lignende turer, samt opprette/oppdatere/slette enkeltpunkter. Hvert verktøy tar tur-ID der det trengs, setter turkontekst gjennom medlemskap og gjør policy-sjekk. Presise punktoperasjoner bevarer alt innhold som ikke er omfattet av brukerens endring. Dokumenter MCP-URL, OAuth-tilkobling og verktøyene. [Laravel MCP-dokumentasjon](https://laravel.com/framework/docs/13.x/mcp)

**Ferdig når:** Den samme autoriserte brukeren kan utføre kjerneoppgaver via nettside, API og MCP, mens en invitert bruker først får tilgang etter aksept. API og MCP bruker samme validerings- og domeneregler.

## 4. Lignende turer uten personvernlekkasje

Søk returnerer høyst ti resultater med `source` (`own` eller `anonymous`), sammenlignbare metadata og pakkepunkter som agenten kan bruke. Egne tidligere turer kan vise full turinformasjon og lister. Ranger dem enkelt etter treff på type, aktiviteter, varighetsgruppe, transport og overnatting; reisemål brukes bare for egne turer. Turer med sluttdato før dagens dato regnes som tidligere. Agenten gjør selve vurderingen og lager forslagene.

For andre brukere brukes en **egen, begrenset inspirasjonsprojeksjon**, aldri rå turer i søkeresponsen. Normaliser bare eksakte, godkjente punktnavn til kanoniske standardpunkter. Avvis alle øvrige navn; kopier aldri kommentarer, deltakere, e-post, fritekstbeskrivelse eller eksakt reisemål. Ta bare med forhåndsdefinerte verdier for type, transport, overnatting og varighetsgruppe. Vis en aggregert eksempelgruppe først når minst tre ulike tureiere bidrar. Oppdater projeksjonen når relevant turmetadata eller punkt endres eller slettes. Test at navn som inneholder personopplysninger og sjeldne fritekstverdier aldri kommer ut. Hvis ingen eksempler kvalifiserer, returner tomme resultater; agenten kan fortsatt skrive lister fra brukerens beskrivelse.

Dette er et bevisst, smalt unntak fra vanlig tenant-scopet lesing: kun den godkjente projeksjonen kan aggregeres på tvers av turer. Ingen API- eller MCP-rute gir direkte tilgang til en annen brukers tur eller til projeksjonens interne kilde-ID-er.

**Ferdig når:** Søket skiller egne turer fra anonyme eksempler, viser nok ikke-personlige metadata til sammenligning og eksponerer ingen rå data fra andre turer.

## 5. Tester, leveranse og kontroll

Skriv små Pest feature-tester med factories for hver ny beslutning: vellykket flyt, ugyldige felt og manglende rettigheter. Prioriter disse scenariene:

1. Admin oppretter tur, inviterer en bruker, gir adminrolle og kan ikke fjerne siste admin.
2. Verifisert invitert bruker aksepterer, redigerer alle lister i web og MCP, og mister tilgang i alle grensesnitt etter fjerning.
3. Deltaker uten konto får egen tom liste og kan ikke logge inn; dagstur får varighet én dag.
4. Uvedkommende får ikke lese eller skrive turdata; feil tur-ID eller punkt-ID lekker ikke eksistensen av andre turer.
5. Egne og anonyme søkeresultater er tydelig merket; fritekst og identitet fra andre brukere lekker ikke; punktendring bevarer urørte punkter.

Kjør først de smaleste relevante testene etter hver del. Kjør så `vendor/bin/pint --dirty --format agent`, `npm run build` og `php artisan test --compact`. Kontroller nettgrensesnittet på mobilbredde og kjør MCP Inspector mot en autentisert og en uautentisert klient. Lever korte instrukser for lokal oppstart, API-token og MCP-OAuth. Rapporter hva som fungerer, hva som faktisk er testet og kjente begrensninger.

**Risiko og avgrensning:** Bootstrap 6 er alpha. Den konservative ordlisten og kravet om minst tre bidragsytere kan gi få anonyme eksempler i starten. Det er akseptabelt i førsteversjonen; ingen automatisk AI-anonymisering eller bredere deling legges til uten nytt behov.
