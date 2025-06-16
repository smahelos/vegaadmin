# Prompts Summary - Podrobný průvodce použitím

Tento dokument poskytuje kompletní přehled všech prompt souborů v projektu a jejich použití pro efektivní development workflow.

## 📁 Struktura Prompt Systému

```
.github/prompts/
├── project.prompt.md              # Hlavní prompt - importuje všechny instrukce
├── instructions/                  # Modulární instrukce pro různé oblasti
│   ├── laravel.md                # Laravel 12 standardy a konvence
│   ├── backpack.md               # Backpack 6.8 admin panel
│   ├── tailwind.md               # Tailwind CSS 4.1.3 styling
│   ├── testing.md                # Testing standardy a best practices
│   ├── architecture.md           # Architektura a design patterns
│   ├── api.md                    # API development standardy
│   ├── deployment.md             # Deployment a produkční prostředí
│   ├── dockerContainer.md        # Docker container commands
│   └── databaseConnection.md     # Database connection settings
├── create-tests.prompt.md         # Workflow pro vytváření testů
├── create-crud.prompt.md          # Workflow pro CRUD operace
├── refactor-code.prompt.md        # Workflow pro refactoring kódu
├── debug-troubleshoot.prompt.md   # Debugging a troubleshooting
└── clean-build.prompt.md          # Cache clearing a build
```

## 🚀 Hlavní Prompts (Workflow Prompts)

### 1. `@project.prompt.md` - Hlavní Project Prompt
**Kdy použít**: Při všech úkolech v projektu jako základní setup
**Co dělá**: 
- Importuje všechny modulární instrukce
- Nastavuje základní standardy pro Laravel 12, Backpack 6.8, Tailwind CSS 4.1.3
- Definuje coding standards a project conventions

**Použití**:
```
@project.prompt.md
Potřebuji vytvořit nový model Client s validací
```

### 2. `@create-tests.prompt.md` - Vytváření Testů
**Kdy použít**: Při vytváření Unit a Feature testů pro jakoukoliv komponentu
**Co dělá**:
- Rozhoduje mezi Unit a Feature testy
- Poskytuje templates pro různé typy testů
- Definuje test patterns pro Models, Requests, Controllers, Services

**Použití**:
```
@create-tests.prompt.md
Vytvořit kompletní testy pro ProductRequest včetně Unit a Feature testů
```

**Test Patterns**:
- **Models**: Unit (structure, traits) + Feature (relationships, DB)
- **Request Classes**: Unit (rules, messages) + Feature (HTTP validation)
- **Controllers**: Feature tests only (HTTP workflows)
- **Services**: Unit tests with mocked dependencies

### 3. `@create-crud.prompt.md` - CRUD Operace
**Kdy použít**: Při vytváření nových CRUD operací s Backpack admin panelem
**Co dělá**: 
- Poskytuje kompletní checklist pro CRUD vytvoření
- Definuje požadované soubory a jejich strukturu
- Zajišťuje konzistenci napříč všemi CRUD operacemi

**Použití**:
```
@create-crud.prompt.md
Vytvořit kompletní CRUD pro Products včetně admin panelu
```

**Checklist zahrnuje**:
- [ ] Model s relationships
- [ ] Migration s proper structure
- [ ] Admin CRUD Controller
- [ ] Request classes s validací
- [ ] Routes (admin + frontend)
- [ ] Translations (všechny locales)
- [ ] Navigation menu entry
- [ ] Tests (Unit + Feature)

### 4. `@refactor-code.prompt.md` - Refactoring Kódu
**Kdy použít**: Při zlepšování existujícího kódu, optimalizaci a čištění
**Co dělá**:
- Systematický přístup k refactoringu
- Identifikuje code smells a poskytuje řešení
- Aplikuje design patterns a best practices

**Použití**:
```
@refactor-code.prompt.md
Refaktorovat ClientController - je příliš dlouhý a má duplicitní kód
```

**Refactoring Priority**:
1. Extract methods z dlouhých funkcí
2. Remove duplicated code
3. Improve naming
4. Apply design patterns
5. Add type hints
6. Improve error handling

### 5. `@debug-troubleshoot.prompt.md` - Debugging a Řešení Problémů
**Kdy použít**: Při řešení bugů, chyb a problémů v aplikaci
**Co dělá**:
- Poskytuje strukturovaný přístup k debuggingu
- Obsahuje common issues a jejich řešení
- Specific pro Laravel/Backpack environment

**Použití**:
```
@debug-troubleshoot.prompt.md
Testy selháváją s chybou "Class not found" - potřebuji najít a opravit problém
```

**Common Issues**:
- Database issues (connection, migrations, permissions)
- Cache issues (config, route, view cache)
- Permission issues (Backpack roles/permissions)
- Validation issues (Form Requests, translations)
- Frontend issues (assets, JS/CSS compilation)

### 6. `@clean-build.prompt.md` - Čištění Cache a Build
**Kdy použít**: Při problémech s cache nebo potřebě fresh build
**Co dělá**: 
- Spustí sekvenci příkazů pro vyčištění všech cache
- Optimalizuje aplikaci
- Rebuilds assets

**Použití**:
```
@clean-build.prompt.md
Aplikace se chová podivně, potřebuji vyčistit všechny cache
```

## 📋 Instruction Prompts (Modulární Instrukce)

### Framework & Technology Prompts

#### `laravel.md` - Laravel 12 Standards
**Co obsahuje**:
- Laravel 12 specific features a syntax
- File organization standards
- Translation system usage
- Validation system conventions
- Code standards (English, type hints, PSR-4)

#### `backpack.md` - Backpack 6.8 Admin Panel
**Co obsahuje**:
- CRUD Controllers struktura
- Permissions & Authentication (PermissionManager)
- Field types a operations
- Admin routes conventions
- Views a templates

#### `tailwind.md` - Tailwind CSS 4.1.3 Styling
**Co obsahuje**:
- Utility-first approach
- Configuration standards
- Integration s Backpack
- File organization
- Best practices pro responsive design

### Development & Architecture Prompts

#### `testing.md` - Testing Standards
**Co obsahuje**:
- Unit vs Feature test guidelines
- Test organization struktura
- Database testing s RefreshDatabase
- Permissions testing patterns
- Request class testing patterns

#### `architecture.md` - Project Architecture
**Co obsahuje**:
- Clean Architecture principles
- Service Layer pattern
- Repository pattern usage
- Trait organization
- Controller responsibilities

#### `api.md` - API Development
**Co obsahuje**:
- RESTful conventions
- Response format standards
- Authentication s Laravel Sanctum
- API Resources usage
- Error handling patterns

### Infrastructure Prompts

#### `deployment.md` - Production Deployment
**Co obsahuje**:
- Pre-deployment checklist
- Production environment setup
- Performance optimization
- Security measures
- Monitoring a logging

#### `dockerContainer.md` - Docker Commands
**Co obsahuje**:
- Artisan commands přes Docker
- Container-specific instructions
- Proper command formatting

#### `databaseConnection.md` - Database Setup
**Co obsahuje**:
- MCP connection settings
- Database configuration reference

## 🎯 Jak Používat Prompts Efektivně

### 1. Kombinování Prompts
```
@project.prompt.md @create-tests.prompt.md
Vytvořit kompletní testy pro nový UserService včetně Unit a Feature testů
```

### 2. Workflow Examples

**Nový Feature Development**:
```
1. @project.prompt.md @create-crud.prompt.md
   - Vytvoř CRUD pro Orders

2. @create-tests.prompt.md  
   - Vytvoř testy pro OrderRequest a Order model

3. @debug-troubleshoot.prompt.md
   - Oprav problémy s validací
```

**Code Quality Improvement**:
```
1. @project.prompt.md @refactor-code.prompt.md
   - Refaktoruj OrderController

2. @create-tests.prompt.md
   - Přidej chybějící testy

3. @clean-build.prompt.md
   - Vyčisti cache po změnách
```

### 3. Best Practices

#### ✅ DO:
- Vždy používej `@project.prompt.md` jako základ
- Kombinuj workflow prompts s instruction prompts
- Specify konkrétní úkoly a očekávání
- Používej prompts iterativně pro complex tasks

#### ❌ DON'T:
- Nepoužívej prompts bez kontextu
- Neduplikuj instrukce z různých prompts
- Nezapomínaj na testing při development

## 🔄 Typical Development Workflows

### Workflow 1: Nová Komponenta
```bash
# 1. Vytvoření CRUD
@project.prompt.md @create-crud.prompt.md
Vytvořit kompletní CRUD pro Product management

# 2. Vytvoření testů
@create-tests.prompt.md
Vytvořit Unit a Feature testy pro ProductRequest a Product model

# 3. Debug případných problémů
@debug-troubleshoot.prompt.md
Opravit validation errors v ProductRequest testech
```

### Workflow 2: Refactoring Existujícího Kódu
```bash
# 1. Analýza a refactoring
@project.prompt.md @refactor-code.prompt.md
Refaktorovat ClientController - rozdělit do services a zlepšit structure

# 2. Update testů
@create-tests.prompt.md
Aktualizovat testy po refactoringu ClientController

# 3. Clean build
@clean-build.prompt.md
Vyčistit cache a rebuild po refactoringu
```

### Workflow 3: Bug Fixing
```bash
# 1. Debugging
@debug-troubleshoot.prompt.md
Testy selháváją s "Foreign key constraint" - najít a opravit problém

# 2. Fix a testy
@project.prompt.md @create-tests.prompt.md
Opravit factory definice a přidat regression testy

# 3. Verification
@clean-build.prompt.md
Vyčistit cache a ověřit fix
```

## 📈 Výhody Prompt Systému

1. **Konzistence**: Jednotné standardy napříč celým projektem
2. **Efektivita**: Rychlejší development díky předefinovaným workflows
3. **Kvalita**: Built-in best practices a testing standards
4. **Škálovatelnost**: Modulární struktura umožňuje snadné rozšíření
5. **Dokumentace**: Prompts slouží jako living documentation
6. **Onboarding**: Nové team members rychleji pochopí project standards

## 🔧 Maintenance Prompt Systému

### Přidání Nového Prompt
1. Vytvoř nový `.prompt.md` soubor v odpovídající složce
2. Přidej metadata (mode, description, tools)
3. Strukturuj content podle existujících patterns
4. Update `project.prompt.md` pokud je nutné importovat

### Update Existujících Prompts
1. Udržuj prompts aktuální s project evolution
2. Přidávej nové patterns a best practices
3. Remove deprecated approaches
4. Test prompts s real-world scenarios

Tento prompt systém poskytuje solidní foundation pro efektivní a konzistentní development workflow v Laravel/Backpack projektu.
