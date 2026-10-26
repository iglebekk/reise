# Laravel Instructions

Dette dokumentet inneholder generelle mønstre, pakker og beste praksis for Laravel-utvikling basert på Laravel Boost Guidelines. Disse prinsippene kan gjenbrukes på tvers av prosjekter.

## Arbeidsregler for AI-assistenter

Disse reglene skal vektlegges høyt når AI-en analyserer, implementerer og verifiserer endringer.

### 1. Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

Før implementering:

- Si antakelser eksplisitt.
- Hvis noe er usikkert eller tvetydig: spør brukeren i stedet for å velge stille.
- Hvis flere tolkninger er mulige: presenter dem kort.
- Hvis en enklere løsning finnes: si det tydelig.
- Hvis noe er uklart: stopp, forklar hva som er uklart, og be om avklaring.

### 2. Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- Ikke bygg mer enn det som ble bedt om.
- Ikke innfør abstraksjoner for kode som bare brukes ett sted.
- Ikke legg til fleksibilitet, konfigurasjon eller utvidbarhet som ikke er etterspurt.
- Ikke skriv error handling for scenarier som i praksis ikke kan skje.
- Hvis løsningen blir unødvendig stor: forenkle den.

Spør alltid: "Ville en senior engineer sagt at dette er overkomplisert?" Hvis ja, gjør det enklere.

### 3. Surgical Changes

**Touch only what you must. Clean up only your own mess.**

Når eksisterende kode endres:

- Endre bare det som er nødvendig for oppgaven.
- Ikke forbedre nærliggende kode, kommentarer eller formatering uten at det er en del av oppgaven.
- Ikke refaktorer kode som ikke er direkte relevant.
- Match eksisterende stil i prosjektet.
- Fjern imports, variabler eller funksjoner som blir overflødige som følge av egne endringer.
- Hvis du oppdager unrelated dead code: nevn det, men ikke slett det uten å bli bedt om det.

Testen er enkel: Hver endret linje skal kunne spores direkte til brukerens behov.

### 4. Goal-Driven Execution

**Define success criteria. Loop until verified.**

Gjør oppgaver om til verifiserbare mål:

- "Add validation" -> skriv tester for ugyldige inputs, og få dem til å passere.
- "Fix the bug" -> skriv en test som reproduserer feilen, og få den til å passere.
- "Refactor X" -> sørg for at tester passerer både før og etter.

Ved oppgaver med flere steg skal planen være kort og målbar:

1. [Steg] -> verifiser: [sjekk]
2. [Steg] -> verifiser: [sjekk]
3. [Steg] -> verifiser: [sjekk]

Sterke suksesskriterier gjør at AI-en kan jobbe selvstendig uten å gjette. Svake mål som "få det til å virke" krever mer avklaring.

## Språk

- Alle kommentarer, variabelnavn, funksjonsnavn og tekst i koden skal være på engelsk.
- UI-tekster skal lokaliseres ved hjelp av Laravels localization system (`lang`-filer).
- Unngå hardkodet tekst i views, controllers, og andre steder i koden.
- Bruk alltid engelsk som grunnspråk i applikasjonen, selv om sluttbrukerne er norske. Dette sikrer konsistens i koden og gjør det enklere å legge til flere språk senere.
- Ikke legg rå PHP-logikk i Blade. Flytt logikk til controllers, actions, services eller view data.
- Enkle Blade-uttrykk som `__()`, `route()` og `old()` er greit; beregninger, spørringer og beslutningslogikk hører ikke hjemme i Blade.

## Laravel-first og Spatie-first (obligatorisk)

- Default: Velg innebygd Laravel/Eloquent før custom kode.
- Default: Velg Spatie-pakker før andre tredjepartspakker når behovet dekkes.
- Default: Bruk Bootstrap 6 alpha for styling og innebygde UI-komponenter, innkapslet i app-spesifikke Blade-komponenter.
- Default: Installer alltid Laravel Boost MCP Tools for rask innsikt i codebase og debugging (https://laravel.com/ai/boost)

## CRUDdy by Design (obligatorisk standard)

- KI-agenter skal alltid bruke CRUDdy by Design når de designer eller implementerer Laravel-funksjonalitet, med mindre brukeren, en godkjent spesifikasjon eller prosjektets eksplisitte regler sier noe annet.
- Controllere skal som standard bare bruke Laravels resource-actions: `index`, `create`, `store`, `show`, `edit`, `update` og `destroy`.
- Bruk `Route::resource()` eller `Route::apiResource()` med named routes, og begrens rutene med `only()` eller `except()` når ressursen ikke trenger alle actions.
- En handling som ikke passer i en resource-action skal som standard modelleres som en egen ressurs med egen controller. En ressurs trenger ikke å være en Eloquent-modell; den kan representere et domenekonsept, en relasjon, en tilstandsendring eller en prosess.
- Gi nestede ressurser, pivot-relasjoner og domenekonsepter egne resource-controllere når det holder controllerne enkle og forutsigbare.
- Ikke legg custom actions som `publish`, `archive`, `approve` eller `cancel` på en eksisterende resource-controller når handlingen naturlig kan uttrykkes som `store`, `update` eller `destroy` på en egen ressurs.
- Ikke bruk invokable controllere som erstatning for resource-controllere uten at avviket er eksplisitt spesifisert.
- Et avvik skal være eksplisitt, ikke antatt. Ved avvik skal valgt controller- og routestruktur beskrives kort før implementasjon.

## Delegering til underagenter (obligatorisk standard)

- KI-agenter skal alltid bruke enklere underagenter til å gjennomføre koding og filendringer, med mindre brukeren, en godkjent spesifikasjon eller prosjektets eksplisitte regler sier noe annet.
- Velg den enkleste tilgjengelige agenten eller modellen som har tilstrekkelig kapasitet til den avgrensede oppgaven.
- Hovedagenten skal bryte arbeidet ned i små, tydelige oppgaver med relevant kontekst og konkrete akseptansekriterier før delegering.
- Hovedagenten beholder ansvaret for arkitektur, sikkerhet, koordinering og endelig kvalitet. All delegert kode skal gjennomgås, integreres og verifiseres med relevante tester av hovedagenten.
- Underagenten skal få beskjed om å følge dette dokumentet og prosjektets lokale instruksjoner før den gjør endringer.
- Hvis underagenter ikke er tilgjengelige i agentmiljøet, kan hovedagenten utføre endringen direkte og skal opplyse kort om dette.

### Prioritetsrekkefølge for valg av løsning

1. Innebygd Laravel/Eloquent
2. Spatie-pakke
3. Annen etablert pakke
4. Egen implementasjon (kun når 1-3 ikke dekker behovet)

### Ikke lag custom hvis Laravel allerede har dette

- Collections: bruk Collection API (`wrap`, `map`, `filter`, `pluck`, `keyBy`, `groupBy`).
- Querying: bruk Eloquent/query builder (`when`, `whereRelation`, `withCount`, `withExists`, `exists`, `firstOrFail`, `paginate`).
- Validation/auth: Form Requests, Policies, Gates, middleware.
- Cache/queues/events/notifications/files: bruk Laravel facades og contracts.
- Routing/URLs: named routes + `route()`.
- Model behavior: bruk relationships, scopes, casts, accessors/mutators, observers.
- API output: API Resources før manuell array-bygging.

### Spatie-regel

- Sjekk alltid først om Spatie har en moden pakke for behovet.
- `spatie/laravel-permission` skal være standardvalg og installeres som default i alle prosjekter.
- Eksempler:
  - permissions/roles -> `spatie/laravel-permission`
  - media/files -> `spatie/laravel-medialibrary`
  - activity/audit logs -> `spatie/laravel-activitylog`
  - settings/data objects -> relevante Spatie-pakker
- Ved valg av annen pakke enn Spatie skal det begrunnes kort.

### Controller/Service-regel

- Controller kan inneholde enkel, effektiv og tydelig Laravel-orkestrering.
- Flytt logikk ut når den blir gjenbrukt, får tydelig domeneansvar eller gjør controlleren tung å lese.
- Bruk Actions/ for enkeltansvar og Services/ for gjenbrukbar domenelogikk.
- Unngå abstraksjoner uten tydelig verdi.

### Forbud mot unødvendige wrappers

- Ikke lag egne helper-metoder hvis Laravel har en direkte metode.
- Ikke lag egne datastrukturer når Collection/Eloquent dekker behovet.
- Ikke bruk rå SQL hvis samme kan uttrykkes tydelig med Eloquent.

### Kvalitetskrav i PR/commit

- Ved custom løsning: skriv kort hvorfor innebygd Laravel/Spatie ikke var nok.
- All ny adferd skal ha test (happy path + validering + authorization der relevant).

## Før du implementerer

- Finnes dette i Laravel core?
- Finnes dette i Eloquent API?
- Finnes dette i Spatie?
- Dekker Laravel, Eloquent, Spatie eller Bootstrap behovet?
- Hvis nei: kan enkel custom kode forsvares?

### Ved valg av autentisering

- Spør alltid brukeren om OAuth, SSO eller social login er viktig før du velger auth-pakke.
- Bruk Laravel Fortify som standard for autentisering.
- Fortify er frontend-agnostisk og skal pares med prosjektets egne Blade-komponenter.
- Hvis OAuth/SSO er viktig, velg en løsning som dekker behovet naturlig og begrunn valget kort.

## 📦 Anbefalt Teknologi-stack

### Backend

- **Laravel siste versjon** - Moderne Laravel-struktur
- **PHP 8.3+** - Constructor property promotion, type hints
- **Laravel Fortify** - Standardvalg for autentisering uten å låse frontend
- **spatie/laravel-permission** - Installeres som standard i alle prosjekter for roller og rettigheter
- **SQLite** (utvikling) / PostgreSQL/MySQL (produksjon)

### Lokal utvikling

- Prosjektene har som regel Laravel Valet installert lokalt, slik at nettsiden er tilgjengelig på mappenavn + `.test`.
- Hvis prosjektmappen heter `nettsiden`, er testdomenet vanligvis `nettsiden.test`.

### Frontend

- **Blade** templates med komponentbasert arkitektur
- **Bootstrap 6.0.0-alpha.1** for styling og UI-komponenter (alpha; følg offisiell dokumentasjon for gjeldende status og [nettleserstøtte](https://getbootstrap.com/docs/6.0/getting-started/browsers-devices/))
- **Vite** for asset bundling
- Frontend-design skal alltid tenkes og bygges mobile first.
- Egenprodusert frontend bygges med app-spesifikke Blade-komponenter og lokaliserte tekster. Sidevisninger komponerer komponenter i stedet for å inneholde rå HTML-markup.

Installer Bootstrap i et Laravel-prosjekt med npm og bruk prosjektets eksisterende Vite-oppsett:

```bash
npm install bootstrap@6.0.0-alpha.1
```

Importer Bootstrap-stilarket fra `resources/css/app.css` og la layout-komponenten laste Vite-filene med `@vite(['resources/css/app.css', 'resources/js/app.js'])`:

```css
@import 'bootstrap/dist/css/bootstrap.min.css';
```

Importer JavaScript fra `bootstrap` i `resources/js/app.js` bare når interaktive Bootstrap-komponenter trengs. Ikke legg JavaScript i Blade-visninger. Se [Bootstrap install](https://getbootstrap.com/docs/6.0/getting-started/install/) og [Bootstrap med Vite](https://getbootstrap.com/docs/6.0/guides/vite/) for oppsett og oppdaterte imports.

### Testing & Kvalitet

- **Pest 4** - Modern PHP testing framework
- **Laravel Pint** - Code formatting (Laravel's opinionated PHP-CS-Fixer)
- Feature og unit tests med factories

### Komponentbibliotek

- **Bootstrap 6 alpha** - Standard for komponenter, grid og utilities i egenprodusert frontend.
- **Spatie-pakker** - Foretrukket tredjeparts-leverandør (Media Library, Permissions, etc.)

## 🏗️ Arkitekturprinsipper

### Komponent-wrapper Mønster

**VIKTIG**: Bootstrap-komponentmarkup skal alltid kapsles i app-spesifikke Blade-komponenter før den brukes i views. Views skal komponere disse komponentene, ikke inneholde rå HTML-markup.

```
resources/views/components/
├── app/              # App-specific wrapper components
│   ├── card/         # App-specific card component styled with Bootstrap
│   ├── button/       # App-specific button component styled with Bootstrap
│   └── ...
├── form/             # Form components
├── layouts/          # Layout components
└── [domain]/         # Domain-specific components
```

**Fordeler**:

- Sentral kontroll over styling (f.eks. avrundede hjørner som standard)
- Enkel endring av standardverdier uten å berøre vendor-kode
- Mulighet til å bytte ut underliggende bibliotek
- Konsistens på tvers av applikasjonen
- Views holdes tynne, lesbare og fri for rå PHP-logikk

### Laravel 12 Struktur

- Middleware i `bootstrap/app.php`, ikke `app/Http/Kernel.php`
- Service providers i `bootstrap/providers.php`
- Console commands auto-registreres fra `app/Console/Commands/`
- Ingen `app/Console/Kernel.php`

## 🏢 Multi-Tenancy

### Data-tilgang via tenant (obligatorisk)

Data skal **alltid** hentes via relasjonen til tenant. Direkte spørringer på modellen er **ikke tillatt** i en multi-tenant løsning, da dette kan eksponere data på tvers av tenants.

```php
// ✅ Always read tenant data through the tenant relationship
$stages = $tenant->pipelineStages()->orderBy('order')->get();
$company = $tenant->companies()->findOrFail($id);
$users = $tenant->users()->where('is_active', true)->get();

// ❌ Never read tenant data directly from the model
$stages = PipelineStage::all(); // Wrong: returns data from all tenants
$stages = PipelineStage::where('tenant_id', $tenant->id)->get(); // Wrong: use the relationship instead
$company = Company::find($id); // Wrong: no tenant isolation
```

Dette sikrer at:
- Data er alltid isolert per tenant
- Det ikke er mulig å ved en feil eksponere en tenants data til en annen
- Koden er konsistent og forutsigbar

## 📝 Kodestandarder

### PHP Generelt

```php
// ✅ Use constructor property promotion
public function __construct(
    public GitHub $github,
    private string $apiKey,
) {}

// ✅ Always use explicit return types
public function isAccessible(User $user, ?string $path = null): bool
{
    // ...
}

// ✅ Use curly braces even for single-line statements
if ($condition) {
    return true;
}

// ✅ Use PHPDoc blocks for complex types
/**
 * @param array{name: string, email: string} $data
 * @return Collection<int, User>
 */
public function createUsers(array $data): Collection
{
    // ...
}
```

### Laravel Best Practices

```php
// ✅ Use Eloquent relationships, not raw queries
$company->pipelineStage()->first();
$tenant->pipelineStages()->orderBy('order')->get();

// ✅ Eager loading for N+1 prevention
$stages = $tenant->pipelineStages()
    ->withCount('companies')
    ->orderBy('order')
    ->get();

// ✅ Named routes
return redirect()->route('settings.stages.index');

// ✅ Use config() instead of env()
$apiKey = config('services.github.token');

// ❌ Never use env() outside config files
$apiKey = env('GITHUB_TOKEN'); // Wrong
```

### Controllers

- Controllers skal være enkle og effektive. Litt Eloquent-orkestrering er greit når det gir mer lesbar kode enn en ekstra abstraksjon.
- Controllers skal følge CRUDdy by Design og bare bruke standard resource-actions, med mindre et avvik er eksplisitt spesifisert.
- Bruk Form Requests for validering og write-authorization der det passer.
- Bruk Policies for domeneautorisasjon. Kall policy fra controller eller Form Request ut fra hva som gir tydeligst flyt.

```php
// ✅ Tenant-scoped read and clear response handling
public function index(Request $request): View
{
    $stages = $request->user()
        ->currentTenant
        ->pipelineStages()
        ->withCount('companies')
        ->orderBy('order')
        ->get();

    return view('settings.stages.index', [
        'stages' => $stages,
    ]);
}

// ✅ Form Request handles validation and authorization when that is clearest
public function store(PipelineStageRequest $request): RedirectResponse
{
    $request->user()
        ->currentTenant
        ->pipelineStages()
        ->create($request->validated());

    return redirect()
        ->route('settings.stages.index')
        ->with('status', __('pipeline_stages.messages.created'));
}
```

```php
// ✅ Avoid invokable controllers as the default
// Prefer standard controller methods: index/show/create/store/edit/update/destroy
// Use --invokable only when explicitly agreed for the task
```

### Form Requests

```php
class PipelineStageRequest extends FormRequest
{
    // ✅ Use policy checks here when request-level authorization is clearest
    public function authorize(): bool
    {
        return $this->user()?->can('create', PipelineStage::class) ?? false;
    }

    // ✅ Use array syntax
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('table')->ignore($this->route('model')),
            ],
            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ];
    }

    // ✅ Custom error messages
    public function messages(): array
    {
        return [
            'name.required' => __('validation.name_required'),
        ];
    }
}
```

### Policies

```php
class ResourcePolicy
{
    // ✅ Keep authorization logic simple and explicit
    public function viewAny(User $user): bool
    {
        return $user->current_tenant_id !== null
            && $user->isAdminOfCurrentTenant();
    }

    public function update(User $user, Resource $resource): bool
    {
        return $user->current_tenant_id === $resource->tenant_id
            && $user->isAdminOfCurrentTenant();
    }
}
```

### Models

```php
class PipelineStage extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'order',
        'is_active',
    ];

    // ✅ Use the casts() method, not the $casts property
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ✅ Type-hinted relationships
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
```

## 🎨 Frontend Mønstre

### Blade Komponent Struktur

```blade
{{-- ✅ Page views compose components and keep logic out of Blade --}}
<x-layouts.app>
    <x-app.page>
        <x-app.header
            :title="__('pipeline_stages.title')"
            :description="__('pipeline_stages.description')"
        />

        <x-app.split-layout>
            <x-slot:sidebar>
                <x-settings.sidebar />
            </x-slot:sidebar>

            <x-app.card>
                <x-pipeline-stages.table :stages="$stages" />
            </x-app.card>
        </x-app.split-layout>
    </x-app.page>
</x-layouts.app>
```

Selve Bootstrap-markupen hører hjemme i komponenten, for eksempel `resources/views/components/app/card.blade.php`:

```blade
<div {{ $attributes->class(['card']) }}>
    <div class="card-body">{{ $slot }}</div>
</div>
```

### Form Pattern

```blade
<x-form :action="route('pipeline-stages.store')" method="POST">
    <x-form.input
        name="name"
        :label="__('pipeline_stages.form.name')"
        required
    />

    <x-form.checkbox
        name="is_active"
        :label="__('pipeline_stages.form.is_active')"
        :checked="old('is_active', true)"
    />

    <x-form.actions>
        <x-app.button type="submit">
            {{ __('pipeline_stages.form.save') }}
        </x-app.button>

        <x-app.button
            :href="route('pipeline-stages.index')"
            variant="secondary"
        >
            {{ __('pipeline_stages.form.cancel') }}
        </x-app.button>
    </x-form.actions>
</x-form>
```

### Bootstrap 6 Mønstre

- Bruk Bootstrap-komponenter for vanlige UI-mønstre og Bootstrap grid for responsive sideoppsett.
- Bruk Bootstrap utilities for avgrensede spacing-, flex-, display- og typografibehov; legg gjentatte mønstre i app-spesifikke Blade-komponenter.
- Bygg mobile first med Bootstrap-breakpoints og grid. Sjekk Bootstrap 6-dokumentasjonen før du velger komponentklasser, siden alpha-versjonen kan endres.
- Bruk Bootstrap CSS-variabler for temaverdier der de dekker behovet. Bruk Sass-variabler og Sass-kildeimport når prosjektet trenger å tilpasse Bootstrap før bygging.
- Se [komponenter](https://getbootstrap.com/docs/6.0/components/), [grid](https://getbootstrap.com/docs/6.0/layout/grid/), [utilities](https://getbootstrap.com/docs/6.0/utilities/api/) og [CSS-variabler](https://getbootstrap.com/docs/6.0/getting-started/css-variables/) i den offisielle dokumentasjonen.

## 🧪 Testing med Pest

### Test Struktur

```php
<?php

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can perform action', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['current_tenant_id' => $tenant->id]);
    $tenant->users()->attach($admin->id, ['role' => 'admin']);

    // Act
    $response = $this->actingAs($admin)
        ->post(route('resources.store'), ['name' => 'Test']);

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('resources', ['name' => 'Test']);
});

test('non-admin cannot perform action', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['current_tenant_id' => $tenant->id]);
    $tenant->users()->attach($user->id, ['role' => 'user']);

    $this->actingAs($user)
        ->post(route('resources.store'), ['name' => 'Test'])
        ->assertForbidden();
});
```

### Test Best Practices

- ✅ Bruk `RefreshDatabase` trait
- ✅ Test både happy path og edge cases
- ✅ Test authorization (admin vs user)
- ✅ Test validation (required fields, unique constraints)
- ✅ Use factories for test data
- ✅ Kjør kun relevante tester: `php artisan test --filter=TestName`
- ✅ Bruk `--compact` for rask feedback

## 📚 Lokalisering

### Språkfil Struktur

```php
// lang/en/resource.php
return [
    'title' => 'Resources',
    'subtitle' => 'Manage your resources.',
    'create_new' => 'Create New Resource',
    'empty' => 'No resources yet.',

    'form' => [
        'name' => 'Name',
        'color' => 'Color',
        'save' => 'Save Changes',
        'cancel' => 'Cancel',
    ],

    'validation' => [
        'name_required' => 'Name is required.',
        'name_unique' => 'A resource with this name already exists.',
    ],

    'messages' => [
        'created' => 'Resource created successfully.',
        'updated' => 'Resource updated successfully.',
        'deleted' => 'Resource deleted successfully.',
    ],
];
```

### Bruk i Views

```blade
{{ __('resource.title') }}
{{ __('resource.form.name') }}
{{ __('resource.messages.created') }}

{{-- With parameters --}}
{{ __('resource.count', ['count' => $items->count()]) }}
```

## 🛠️ Artisan Commands

### Bruk alltid Artisan når kommandoen finnes (obligatorisk)

- Bruk **alltid** `php artisan`-kommandoer for oppgaver som Laravel har en kommando for, når kommandoen er tilgjengelig.
- Dette gjelder spesielt oppretting av nye filer: bruk `php artisan make:*` i stedet for å opprette controllers, models, migrations, requests, policies, tester osv. manuelt.
- Artisan genererer riktig namespace, stub, plassering og boilerplate, og holder koden konsistent med Laravels konvensjoner og gjeldende versjon.
- Sjekk `php artisan list` hvis du er usikker på om en passende kommando finnes.
- Manuell oppretting er kun aktuelt når ingen Artisan-kommando dekker behovet.

### Opprett Nye Filer

```bash
# Controller
php artisan make:controller ResourceController --resource --no-interaction

# Model with factory and migration
php artisan make:model Resource -mf --no-interaction

# Form Request
php artisan make:request ResourceRequest --no-interaction

# Policy
php artisan make:policy ResourcePolicy --model=Resource --no-interaction

# Test
php artisan make:test ResourceTest --pest --no-interaction

# Generic PHP class
php artisan make:class Actions/DoSomethingAction --no-interaction
```

### Alltid Bruk --no-interaction

Dette sikrer at kommandoen kjører uten brukerinput, viktig for automatisering og CI/CD.

## 🎯 Workflow

### Utviklingsprosess

1. **Analyser** - Forstå eksisterende mønstre i codebase
2. **Plan** - Lag strukturert plan før implementering
3. **Implementer** - Backend først (routes, request, policy, controller, actions/services ved behov)
4. **Views** - Bygg med app-spesifikke Blade-komponenter, Bootstrap 6 og mobile first layout
5. **Lokaliser** - Legg til translation keys
6. **Test** - Skriv dekkende feature tests
7. **Format** - Kjør `vendor/bin/pint --dirty --format agent`
8. **Verifiser** - Kjør alle tester: `php artisan test --compact`

### Database Migrations

```php
Schema::create('resources', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('color');
    $table->integer('order');
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['tenant_id', 'order']);
});
```

### Route Organisering

```php
// Group routes with middleware
Route::middleware(['auth', 'tenant'])->group(function () {
    // Settings routes
    Route::prefix('settings')->group(function () {
        Route::get('/stages', [PipelineStageController::class, 'index'])
            ->name('settings.stages.index');
        Route::get('/stages/create', [PipelineStageController::class, 'create'])
            ->name('settings.stages.create');
        // etc...
    });

    // Resource routes
    Route::resource('resources', ResourceController::class);
});
```

## 📋 Sjekkliste for Nye Features

- [ ] CRUDdy by Design er fulgt, eller avvik er eksplisitt spesifisert og begrunnet
- [ ] Koding og filendringer er delegert til enkleste egnede underagent, og resultatet er gjennomgått
- [ ] Controller er enkel, tydelig og tenant-scoped der relevant
- [ ] Form Request med validation, messages og authorization der det gir mening
- [ ] Policy for domeneautorisasjon der relevant
- [ ] Routes med korrekt naming convention
- [ ] Views komponerer app-spesifikke Blade-komponenter
- [ ] Bootstrap-komponenter brukes gjennom app-spesifikke Blade-komponenter
- [ ] Mobile first layout er ivaretatt
- [ ] Translation keys for alle UI-tekster
- [ ] Feature tests (happy path + edge cases)
- [ ] Kjør Pint for formatering
- [ ] Kjør alle tester for å sikre ingen regresjoner
- [ ] Test manuelt i browser

## 🚀 Laravel Boost MCP Tools

Bruk Laravel Boost MCP Tools for Laravel- og økosystemkode før implementasjon:

```bash
# Search documentation (important)
laravel-boost-mcp-search-docs --queries=["rate limiting", "validation"]

# Database queries
laravel-boost-mcp-database-query --query="SELECT * FROM users LIMIT 5"

# Tinker execution
laravel-boost-mcp-tinker --code="User::count()"

# List routes
laravel-boost-mcp-list-routes --path="settings"

# Application info
laravel-boost-mcp-application-info

# Get absolute URL
laravel-boost-mcp-get-absolute-url --path="/dashboard"
```

### Bootstrap-dokumentasjon for AI-agenter

Før Bootstrap-frontend implementeres, bruk Bootstraps [maskinlesbare dokumentasjonsindeks (`llms.txt`)](https://getbootstrap.com/llms.txt) og [relevante offisielle skills](https://getbootstrap.com/docs/6.0/getting-started/ai/), for eksempel `bootstrap-v6-install`, `bootstrap-vite` og `bootstrap-component`. Velg skill for oppgaven, og kontroller endringene mot Bootstrap-dokumentasjonen. Denne pakken installerer ikke disse skills.

## 🔐 Laravel MCP Server Best Practices

Når en MCP-server eksponeres over HTTP, skal den behandles som en produksjons-APIflate med tilgang til brukerdata, interne handlinger og tredjepartsintegrasjoner. Lokale STDIO-servere kan ha implicit trust, men alle HTTP-tilgjengelige MCP-servere skal sikres eksplisitt.

### Auth-valg

- Bruk `laravel/mcp` som standardpakke for MCP-servere i Laravel.
- Bruk Laravel Passport og OAuth 2.1 som standard for produksjon og eksterne MCP-klienter.
- Registrer OAuth discovery, metadata og dynamic client registration med `Mcp::oauthRoutes()` i `routes/ai.php`.
- Beskytt HTTP-servere med Passport middleware, normalt `->middleware('auth:api')`.
- Bruk Laravel Sanctum bare for kontrollerte miljøer der både klient og server eies av samme system/team.
- Bruk custom middleware bare når prosjektet allerede har en etablert token/JWT/API-key-løsning, og middleware må alltid resolve `$request->user()`.
- Ikke bruk session-basert auth for HTTP MCP-servere. Valider bearer token per request.

```php
use App\Mcp\Servers\AppServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp/app', AppServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
```

### Authorization og tenant-scope

- Bruk `Laravel\Mcp\Request`, ikke `Illuminate\Http\Request`, i tools, prompts og resources.
- Hent alltid bruker via `$request->user()` inne i MCP primitives.
- Skjul tools, prompts og resources for brukere uten tilgang med `shouldRegister(Request $request): bool`.
- `shouldRegister` er første authorization-lag, men hvert tool må fortsatt gjøre egne Policy/Gate-sjekker før data leses eller endres.
- I multi-tenant-kode skal all lesing gå via `$request->user()->currentTenant` og tenant-relasjoner med `findOrFail()`.
- Skriveoperasjoner skal sjekkes med Policies som sammenligner `current_tenant_id` mot ressursens `tenant_id` og relevant rolle.
- Returner MCP-feilrespons ved manglende tilgang. Ikke lek detaljer om eksisterende ressurser på tvers av tenants.

```php
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ShowProjectTool extends Tool
{
    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->can('viewAny', Project::class) ?? false;
    }

    public function handle(Request $request): Response
    {
        $tenant = $request->user()->currentTenant;
        $project = $tenant->projects()->findOrFail($request->integer('project_id'));

        if ($request->user()->cannot('view', $project)) {
            return Response::error('Permission denied.');
        }

        return Response::json([
            'id' => $project->id,
            'name' => $project->name,
        ]);
    }
}
```

### Tool-design og sikkerhet

- Gi tools presise navn og smale ansvarsområder. Ett tool skal gjøre én tydelig handling.
- Valider alle tool-argumenter med Laravel MCPs argument-validering før handlinger utføres.
- Marker tools med riktige annotations: `#[IsReadOnly]`, `#[IsDestructive]`, `#[IsIdempotent]` og `#[IsOpenWorld]`.
- Bruk read-only tools som default. Destructive tools skal være eksplisitte, policy-beskyttet og audit-logget.
- Ikke forward MCP access tokens til nedstrøms API-er. Lagre tredjepartscredentials separat og bruk dem server-side.
- Bruk minimale scopes. Ikke bruk wildcard scopes for MCP.
- Legg rate limiting på alle HTTP MCP-servere, helst per bruker/token.
- Logg authorization, destructive operations og eksterne kall med nok metadata til audit, men aldri secrets eller tokens.
- Bruk queues for tunge eller langsomme handlinger, og returner tydelig status i stedet for å blokkere MCP-kallet unødvendig lenge.
- Eksponer aldri interne stack traces, SQL-feil, tokens, secrets eller tenant-identifikatorer i MCP-responser.

### Consent og token-administrasjon

- Publiser MCP authorization view med `php artisan vendor:publish --tag=mcp-views --no-interaction` når Passport brukes.
- Tilpass consent-skjermen slik at brukeren ser klientnavn, redirect URI og hvilke capabilities klienten ber om.
- Krev MFA før godkjenning når MCP-serveren gir tilgang til sensitive data eller skriveoperasjoner.
- Gi brukeren mulighet til å se og revoke MCP-tokens fra konto-/innstillingssider.
- Varsle bruker eller admin når en ny MCP-klient får tilgang til produksjonsdata.

### Testing og verifisering

- Skriv Pest-tester for hver MCP primitive: happy path, validering og authorization.
- Test at uautentiserte kall avvises for HTTP-servere.
- Test at `shouldRegister` skjuler capabilities for brukere uten tilgang.
- Test tenant-isolasjon eksplisitt: bruker i tenant A skal aldri kunne lese eller endre tenant B-data.
- Test destructive tools med både autorisert og uautorisert bruker.
- Bruk `php artisan mcp:inspector <server> --no-interaction` for manuell verifisering av auth, headers, tools, prompts og resources.

## 💡 Viktige Prinsipper

1. **CRUDdy by Design** - KI-agenter bruker alltid resource-controllere og standard CRUD-actions med mindre annet er eksplisitt spesifisert
2. **Deleger implementasjon** - KI-agenter bruker alltid enkleste egnede underagent til koding og filendringer
3. **Følg Laravel Conventions** - Bruk Laravels innebygde løsninger først
4. **Bootstrap via App-komponenter** - Bruk Bootstrap gjennom egne Blade-komponenter
5. **Test Everything** - Feature tests er påkrevd
6. **Type Hints Everywhere** - PHP 8.3+ features
7. **Lokalisering fra Start** - Ingen hardkodet tekst
8. **Keep Controllers Effective** - Enkel orkestrering kan ligge i controller; tung eller gjenbrukt logikk flyttes til Actions/Services
9. **Authorization i Policies** - Ikke spredt rundt i koden
10. **Eager Loading** - Unngå N+1 queries
11. **Named Routes** - Aldri hardkodede URLs
12. **Format med Pint** - Konsistent kodestil
13. **Bruk MCP Tools** - For rask innsikt i codebase og debugging
14. **DRY med måte** - Lag komponenter og tjenester når de gir reell gjenbruk eller lesbarhet
15. **Sikkerhet Først** - Alltid tenk på authorization og data validation
16. **Ytelse** - Optimaliser database queries og unngå unødvendige operasjoner
17. **Cache Strategisk** - Bruk caching for å forbedre ytelsen der det gir mening
18. **Multi-Tenant Isolasjon** - Hent alltid data via tenant-relasjonen, aldri direkte på modellen
19. **Bruk Artisan-kommandoer** - Bruk alltid `php artisan`-kommandoer når de finnes, f.eks. `make:*` for å opprette nye filer

## 🔗 Nyttige Ressurser

- Laravel Documentation: https://laravel.com/docs
- Pest Documentation: https://pestphp.com
- Bootstrap 6 Documentation: https://getbootstrap.com/docs/6.0/
- Bootstrap 6 AI documentation and skills: https://getbootstrap.com/docs/6.0/getting-started/ai/
- Laravel Best Practices: https://github.com/alexeymezenin/laravel-best-practices
- Spatie Packages: https://spatie.be/open-source
