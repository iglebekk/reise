Bygg en superenkel reiseapp med pakkelister. Appen skal primært styres av eksterne AI-agenter gjennom MCP, men også ha et ordinært API og en enkel mobilvennlig nettside.

Bruk ponytail-skillen aktivt gjennom implementering og kontroll: bygg den minste komplette løsningen, gjenbruk eksisterende kode og unngå funksjoner eller abstraheringer «for senere». Undersøk prosjektet før du velger løsning. Følg eksisterende teknologi og konvensjoner.

**1. Turer og deltakere**

Brukeren skal kunne opprette og redigere en tur med reisemål, datoer, varighet, type tur, transport, overnatting, aktiviteter og deltakere. Metadata skal gi AI-agenten et godt grunnlag for pakkeforslag. En tur kan også være en dagstur.

Hver tur får én felles pakkeliste og én pakkeliste per deltaker. Deltakere kan være brukere med konto eller personer uten konto, eksempelvis barn. Alder på barn skal kunne beskrives som del av turinformasjonen.

Den som oppretter turen blir admin. Flere kan være admin på samme tur. Bare admin kan invitere via e-post, administrere tilgang og roller samt slette turen. Turen skal alltid ha minst én admin.

Inviterte brukere får tilgang når de aksepterer invitasjonen og logger inn. Alle med tilgang kan lese og redigere turinformasjonen og samtlige pakkelister. Personer uten konto får ingen egen innlogging.

**2. Pakkelister**

Hvert punkt har:

- Navn, obligatorisk.
- Antall, obligatorisk med standardverdi 1.
- Kommentar, valgfri.

Brukerne skal kunne legge til, endre og slette punkter på alle turens lister.

Det skal ikke være avkryssing, pakkestatus eller tildeling av ansvar. Felleslisten fungerer som de personlige listene.

**3. Pakkeforslag gjennom MCP**

AI-agenten skal kunne gjennomføre denne brukerreisen:

- Opprette en tur ut fra brukerens beskrivelse og supplere metadata etter hvert.
- Finne lignende tidligere turer ut fra metadata.
- Lese relevante pakkelister fra disse turene.
- Lage et tilpasset forslag og skrive det til den nye turens pakkelister.
- Oppdatere listene når brukeren ber om endringer.

Egne tidligere lister skal vektlegges høyest. Anonymiserte lister fra andre brukere skal supplere automatisk, uten en egen delingsprosess.

Søkeresultatene må gi agenten nok informasjon til å vurdere likheten, og skille mellom egne turer og anonymiserte eksempler. Brukeren skal bare få direkte tilgang til turer vedkommende har tilgang til. Inspirasjon fra andre skal ikke avsløre identitet eller personlige opplysninger gjennom turinformasjon, punktnavn eller kommentarer.

Agenten som bruker MCP står for vurderingene og forslagene. Det skal ikke bygges en separat AI-assistent inn i appen. Hvis ingen tidligere turer passer, skal agenten fortsatt kunne opprette pakkelister fra brukerens beskrivelse.

Endringer skal bevare eksisterende innhold som ikke omfattes av brukerens forespørsel.

**4. MCP, API og nettside**

MCP skal bruke OAuth 2.0. Hver bruker kobler til sin egen konto, og agenten handler med denne brukerens rettigheter.

Et ordinært, dokumentert API skal tilby funksjonene som trengs for en fremtidig app. MCP, API og nettsiden skal bruke samme regler for tilgang, validering og endringer. Unngå å implementere samme funksjonalitet flere ganger.

Nettsiden skal være superenkel: innlogging, turoversikt og en turside med turinformasjon, deltakere og pakkelister. Admin får nødvendige kontroller for invitasjoner og tilgang. Kjernefunksjonene skal også kunne brukes uten MCP.

**5. Ferdigstilling og kontroll**

Lever en fungerende førsteversjon med nødvendig oppsett og korte instrukser for å kjøre løsningen, bruke API-et og koble til MCP.

Kontroller særlig at:

- En admin kan opprette tur, invitere en bruker og gi vedkommende adminrollen.
- Inviterte brukere kan bidra på alle listene gjennom nettsiden og MCP.
- Deltakere uten konto får egne pakkelister.
- Uvedkommende ikke får tilgang, og fjernet tilgang gjelder i alle grensesnitt.
- Søk etter lignende turer og redigering av pakkelister fungerer uten å eksponere andres personopplysninger eller overskrive uvedkommende innhold.

Bruk små, målrettede tester for tilgangsregler og annen viktig logikk. Ikke bygg flere funksjoner enn beskrevet. Avslutt med hva som fungerer, hva som er kontrollert, og eventuelle kjente begrensninger.
