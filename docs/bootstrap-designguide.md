# Designguide: Typiske Bootstrap 5.3-sider

Referanse: https://getbootstrap.com/docs/5.3/examples/

## Overordnet uttrykk

Bootstrap 5.3 har et rent, strukturert og funksjonelt designspråk. Sidene fremstår som profesjonelle, forutsigbare og minimalistiske. Prioriter lesbarhet, konsistens og brukervennlighet fremfor visuelle effekter.

## Visuell stil

### Farger

- Primærfarge: `#0d6efd` (blå).
- Hovedbakgrunn: `#ffffff` (hvit).
- Alternativ bakgrunn: `#f8f9fa` (svært lys grå).
- Primærtekst: `#212529` (mørk grå).
- Sekundærtekst: `#6c757d` (dempet grå).
- Kantlinjer: `#dee2e6` (lys grå).
- Bruk semantiske Bootstrap-farger for suksess, advarsler og feil.
- Støtt lys og mørk modus når det er hensiktsmessig.

### Typografi

- Bruk systemfont (`system-ui`) som standard.
- Normal brødtekst: omtrent 16 px.
- Ha et tydelig hierarki mellom overskrifter, underoverskrifter og brødtekst.
- Bruk fet skrift kontrollert.
- Vis sekundærinformasjon med mindre eller svakere tekst.
- Bruk god linjeavstand og begrenset tekstbredde.

### Avstand og former

- Bruk Bootstraps standard spacing-system konsekvent.
- La elementer som hører sammen stå tettere enn separate seksjoner.
- Bruk moderat avrundede hjørner og tynne kantlinjer.
- Foretrekk lite eller ingen skygge.
- Unngå både overfylte grensesnitt og unødvendig store tomrom.

## Typiske sideoppsett

### Dashboard og administrasjon

Fast toppnavigasjon, vertikal sidemeny og et stort innholdsområde med tabeller, skjemaer og handlinger. Vanlig for CRM, administrasjon og interne systemer.

### Markedsføringsside

Toppnavigasjon, stor hovedoverskrift, kort beskrivelse og en tydelig primærknapp. Deretter logiske innholdsseksjoner og footer.

### Kortbasert oversikt

Innhold ordnes i like kort med tittel, beskrivelse, metadata og handlinger. Typisk for produkter, prosjekter, prissider og gallerier.

### Skjema og innlogging

Smal, sentrert kolonne med få elementer, tydelige feltnavn, lik høyde på feltene og en dominerende handlingsknapp.

## Komponenter

| Komponent | Typisk utseende |
|---|---|
| Navigasjon | Horisontal toppmeny eller vertikal sidemeny |
| Knapper | Blå primærknapp, dempet eller kantlinjebasert sekundærknapp |
| Kort | Hvite flater, tynn ramme og moderat avrunding |
| Tabeller | Tydelige kolonner, diskrete skiller og valgfri striping |
| Skjemaer | Rektangulære felter, lyse kanter og blå fokusmarkering |
| Modaler | Sentrerte dialogbokser over mørklagt bakgrunn |
| Varsler | Lyse bakgrunnsfarger med tydelig statusfarge |
| Nedtrekksmenyer | Hvite paneler, svak skygge og enkel listevisning |

## Layout og responsivitet

- Bygg mobile-first.
- Bruk responsivt grid med opptil 12 kolonner.
- Bruk sentrerte containere og konsistente marger.
- Stable kolonner vertikalt på små skjermer.
- Tilpass navigasjonen til mobil.
- Sørg for at knapper og skjemafelt fungerer godt med berøring.
- Unngå horisontal scrolling, unntatt for tabeller ved behov.
- Ikke bruk flere kort eller paneler enn innholdet krever.

## Designinstruks for AI-agent

Lag et moderne, rent og funksjonelt brukergrensesnitt inspirert av de offisielle Bootstrap 5.3-eksemplene.

1. **Konsistens fremfor originalitet.** Følg etablerte Bootstrap-mønstre.
2. **Funksjonalitet fremfor effekter.** Unngå unødvendige animasjoner, gradienter og dekorasjoner.
3. **Standardkomponenter først.** Bruk Bootstrap-komponenter og designvariabler før tilpasset CSS.
4. **Tydelig hierarki.** Gjør primærhandlinger synlige og sekundærhandlinger dempede.
5. **Enkle skjemaer.** Bruk synlige etiketter og tydelig validering.
6. **Lesbare tabeller.** Hold dem kompakte og oversiktlige.
7. **Bevisst bruk av modaler og menyer.** Bruk dem bare når de forenkler oppgaven.
8. **Innholdet styrer layouten.** Ikke omvendt.
9. **Konsekvent luft.** Følg standard spacing-klasser.
10. **Responsiv oppførsel.** Alle visninger skal fungere på mobil, nettbrett og desktop.

## Ønsket resultat

Et grensesnitt som umiddelbart oppleves som en moderne Bootstrap 5.3-applikasjon: ryddig, raskt, konsekvent, responsivt og enkelt å bruke.

> **Avgrensning:** Dette beskriver det typiske uttrykket i Bootstrap-eksemplene, ikke alle mulighetene i rammeverket. For et gjenkjennelig Bootstrap-utseende bør standardkomponenter beholdes og egen CSS begrenses.
