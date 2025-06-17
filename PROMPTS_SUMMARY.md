# Prompts Summary - Kompletní průvodce prompt systémem

Tento dokument poskytuje úplný přehled všech prompt souborů v projektu, jejich účelu a způsobu použití pro efektivní development workflow.

## 📁 Skutečná struktura Prompt Systému

```
.github/
├── copilot-instructions.md         # 🤖 Automatické instrukce pro GitHub Copilot
└── prompts/
    ├── project.prompt.md           # 🏠 Hlavní prompt - importuje všechny instrukce
    ├── create-tests.prompt.md      # 🧪 Komplexní guide pro vytváření testů
    ├── test-authentication.prompt.md  # 🔐 Specializovaný guide pro autentizaci v testech
    ├── test-auth-quickref.md       # ⚡ Rychlá reference pro copy-paste
    ├── create-crud.prompt.md       # 📋 Workflow pro CRUD operace
    ├── code-quality.prompt.md      # 💎 Aplikace coding standards a type safety
    ├── refactor-code.prompt.md     # 🔄 Workflow pro refactoring kódu
    ├── debug-troubleshoot.prompt.md   # 🐛 Debugging a troubleshooting
    ├── clean-build.prompt.md       # 🧹 Cache clearing a build
    └── instructions/               # 📚 Modulární instrukce pro různé oblasti
        ├── laravel.md              # 🅰️ Laravel 12 standardy a konvence
        ├── backpack.md             # 🎛️ Backpack 6.8 admin panel
        ├── tailwind.md             # 🎨 Tailwind CSS 4.1.3 styling
        ├── testing.md              # 🧪 Testing standardy a best practices
        ├── architecture.md         # 🏗️ Architektura a design patterns
        ├── api.md                  # 🌐 API development standardy
        ├── coding-standards.md     # 📝 Coding standards a konvence
        ├── deployment.md           # 🚀 Deployment a produkční prostředí
        ├── dockerContainer.md      # 🐳 Docker container commands
        └── databaseConnection.md   # 🗄️ Database connection settings
```

## 🎯 Typy prompts a jejich použití

### 🤖 Automatické instrukce

#### `.github/copilot-instructions.md`
**Typ**: Automatické načítání  
**Kdy se používá**: Automaticky při každé interakci s GitHub Copilot  
**Není nutné explicitně načítat**: GitHub Copilot ho načte automaticky

**Co obsahuje**:
- Základní pravidla pro celý projekt
- Laravel 12, Backpack 6.8, Tailwind CSS 4.1.3 instrukce
- Základní testing guidelines pro Backpack admin testy
- PHPUnit atributový styl (#[Test])
- Coding standards (angličtina, clean code)

**Použití**: Automatické, není potřeba volat

---

### 🏠 Hlavní workflow prompts

#### `@project.prompt.md` - Hlavní Project Prompt
**Typ**: Importující prompt  
**Kdy použít**: Při všech úkolech v projektu jako základní setup

**Co dělá**: 
- Importuje VŠECHNY modulární instrukce ze složky `/instructions/`
- Nastavuje kompletní kontext pro projekt
- Poskytuje všechny standardy najednou

**Jak načítat**: 
```
@project.prompt.md
Potřebuji vytvořit nový model Client s validací a admin CRUD
```

**Výhoda**: Jeden prompt načte vše potřebné  
**Nevýhoda**: Velký kontext - může být pomalý

---

### 🧪 Specialized workflow prompts

#### `@create-tests.prompt.md` - Vytváření Testů
**Typ**: Specialized workflow prompt  
**Kdy použít**: Při vytváření nebo úpravě testů

**Co dělá**:
- Rozhoduje mezi Unit a Feature testy
- Poskytuje templates pro různé typy testů
- Obsahuje best practices pro testování
- Pokrývá PHPUnit atributový styl
- Code quality first approach

**Jak načítat**:
```
@create-tests.prompt.md
Vytvořit kompletní testy pro ProductRequest včetně Unit a Feature testů
```

**Kdy NUTNĚ kombinovat s project.prompt.md**:
```
@project.prompt.md @create-tests.prompt.md
Vytvořit nový UserService s testy
```

#### `@test-authentication.prompt.md` - Autentizace v testech
**Typ**: Specialized troubleshooting prompt  
**Kdy použít**: Při problémech s autentizací v admin testech

**Co dělá**:
- Detailní guide pro Backpack authentication
- Troubleshooting 401/403/404 chyb
- Permission dependencies mapping
- Status code reference

**Jak načítat**:
```
@test-authentication.prompt.md
Mám 403 chybu v admin testu StatusRequestTest - pomoč s autentizací
```

#### `test-auth-quickref.md` - Rychlá reference
**Typ**: Reference file (není prompt)  
**Kdy použít**: Pro rychlé copy-paste řešení

**Co obsahuje**:
- Ready-to-copy imports
- Template setUp() metod
- Permissions lists
- Common mistakes checklist

**Jak použít**: Otevřít soubor a kopírovat kód, nebo:
```
#file:.github/prompts/test-auth-quickref.md
Potřebuji rychle opravit autentizaci v testu
```

#### `@create-crud.prompt.md` - CRUD Operace
**Typ**: Workflow prompt  
**Kdy použít**: Při vytváření nových CRUD operací

**Co dělá**:
- Poskytuje checklist pro CRUD vytvoření
- Definuje požadované soubory a strukturu
- Backpack-specific patterns

**Jak načítat**:
```
@project.prompt.md @create-crud.prompt.md
Vytvořit kompletní CRUD pro Products včetně admin panelu
```

#### `@code-quality.prompt.md` - Coding Standards
**Typ**: Code improvement prompt  
**Kdy použít**: Pro zlepšení kvality kódu a type safety

**Co dělá**:
- Aplikuje strict typing
- Modern PHP 8.2+ features
- Laravel best practices
- IDE support improvement

**Jak načítat**:
```
@code-quality.prompt.md
Přidat return types a zlepšit type safety v ClientController
```

#### `@refactor-code.prompt.md` - Refactoring
**Typ**: Code improvement prompt  
**Kdy použít**: Při refactoringu existujícího kódu

**Jak načítat**:
```
@refactor-code.prompt.md
Refaktorovat ClientController - je příliš dlouhý a má duplicitní kód
```

#### `@debug-troubleshoot.prompt.md` - Debugging
**Typ**: Troubleshooting prompt  
**Kdy použít**: Při řešení bugů a problémů

**Jak načítat**:
```
@debug-troubleshoot.prompt.md
Testy selháváją s chybou "Class not found" - najít a opravit problém
```

#### `@clean-build.prompt.md` - Cache Management
**Typ**: Utility prompt  
**Kdy použít**: Při problémech s cache

**Jak načítat**:
```
@clean-build.prompt.md
Aplikace se chová podivně, vyčistit všechny cache
```

---

## 📚 Modulární instrukce (Instructions)

### ❗ DŮLEŽITÉ: Instrukce se načítají automaticky přes project.prompt.md

**Instrukce NEJSOU prompts** - jsou to modulární kousky dokumentace, které se importují do prompts.

### Framework & Technology Instructions

#### `instructions/laravel.md` - Laravel 12 Standards
**Co obsahuje**:
- Laravel 12 specific features a syntax
- File organization standards
- Translation system usage
- Code standards (English, type hints, PSR-4)

**Načítání**: Automaticky přes `@project.prompt.md` nebo explicitně:
```
#file:.github/prompts/instructions/laravel.md
Potřebuji Laravel-specific řešení
```

#### `instructions/backpack.md` - Backpack 6.8 Admin Panel
**Co obsahuje**:
- CRUD Controllers struktura
- Permissions & Authentication (PermissionManager)
- Field types a operations
- Admin routes conventions

#### `instructions/tailwind.md` - Tailwind CSS 4.1.3
**Co obsahuje**:
- Utility-first approach
- Configuration standards
- Integration s Backpack
- Responsive design patterns

#### `instructions/testing.md` - Testing Standards
**Co obsahuje**:
- Unit vs Feature test guidelines
- Test organization struktura
- Database testing s RefreshDatabase
- Permissions testing patterns
- PHPUnit modern features

### Development & Architecture Instructions

#### `instructions/architecture.md` - Project Architecture
**Co obsahuje**:
- Clean Architecture principles
- Service Layer pattern
- Repository pattern usage
- Trait organization

#### `instructions/api.md` - API Development
**Co obsahuje**:
- RESTful conventions
- Response format standards
- Authentication s Laravel Sanctum
- API Resources usage

#### `instructions/coding-standards.md` - Coding Standards
**Co obsahuje**:
- Code style guidelines
- Naming conventions
- Documentation standards
- Best practices

### Infrastructure Instructions

#### `instructions/deployment.md` - Production Deployment
**Co obsahuje**:
- Pre-deployment checklist
- Production environment setup
- Performance optimization
- Security measures

#### `instructions/dockerContainer.md` - Docker Commands
**Co obsahuje**:
- Artisan commands přes Docker
- Container-specific instructions
- Proper command formatting

#### `instructions/databaseConnection.md` - Database Setup
**Co obsahuje**:
- MCP connection settings
- Database configuration reference

## 🎯 Praktické použití scenarios

### Scenario 1: Vytváření nového testu od základu
```
# Použij create-tests.prompt.md s project kontextem
@project.prompt.md @create-tests.prompt.md
"Vytvořit Feature test pro ProductController s kompletní autentizací"
```
**Výsledek**: Kompletní test class s setUp(), permissions, #[Test] atributy

### Scenario 2: Debugging authentication problémů
```
# Použij specialized authentication prompt
@test-authentication.prompt.md
"Mám 404 chybu v admin testu, pomoč s authentication"
```
**Výsledek**: Detailní diagnosis a fix

### Scenario 3: Rychlé opravy (copy-paste)
```
# Otevři quickref jako referenci
#file:.github/prompts/test-auth-quickref.md
"Potřebuji rychle přidat missing permissions"
```

### Scenario 4: Komplexní nový feature
```
# Kombinace prompts pro kompletní workflow
@project.prompt.md @create-crud.prompt.md
"Vytvořit kompletní CRUD pro Orders včetně admin panelu"

# Pak přidat testy
@create-tests.prompt.md
"Vytvořit testy pro OrderRequest a Order model"
```

### Scenario 5: Code quality improvement
```
# Zlepšení kvality kódu
@code-quality.prompt.md
"Přidat return types a type safety do ClientController"
```

### Scenario 6: Převod testů na PHPUnit atributový styl
```
@create-tests.prompt.md
"Převést testovací metody na PHPUnit atributový styl - přidat #[Test] a odstranit test_ prefix"
```

## 🔧 Strategie kombinování prompts

### Pro nové komponenty:
```
@project.prompt.md @create-crud.prompt.md @create-tests.prompt.md
"Vytvořit kompletní Product management s admin panelem a testy"
```

### Pro refactoring:
```
@project.prompt.md @refactor-code.prompt.md @code-quality.prompt.md
"Refaktorovat a zlepšit ClientController"
```

### Pro troubleshooting:
```
@debug-troubleshoot.prompt.md @test-authentication.prompt.md
"Opravit failing testy s authentication issues"
```

### Pro reference v vlastních promptech:
```
#file:.github/prompts/instructions/testing.md
#file:.github/prompts/test-auth-quickref.md

Vytvořit custom test pro specifickou business logiku...
```

## 📋 Decision Tree: Který prompt použít?

### 🤔 Co chci dělat?

#### ✨ **Vytvářím něco nového:**
- **Model/Controller/Service** → `@project.prompt.md @create-crud.prompt.md`
- **Testy** → `@create-tests.prompt.md` (+ project.prompt.md pro kontext)
- **API endpoint** → `@project.prompt.md` + reference na `instructions/api.md`

#### 🔧 **Opravuji/zlepšuji:**
- **Refactoring** → `@refactor-code.prompt.md`
- **Code quality** → `@code-quality.prompt.md`
- **Bug fixing** → `@debug-troubleshoot.prompt.md`
- **Auth problémy v testech** → `@test-authentication.prompt.md`

#### 🧹 **Maintenance:**
- **Cache issues** → `@clean-build.prompt.md`
- **Deployment** → reference na `instructions/deployment.md`
- **Docker commands** → reference na `instructions/dockerContainer.md`

#### 📚 **Potřebuji reference:**
- **Quick copy-paste** → `test-auth-quickref.md`
- **Framework specific** → `instructions/laravel.md`, `instructions/backpack.md`
- **Standards** → `instructions/coding-standards.md`

## ⚡ Quick Reference pro časté úkoly

### 1. Nový CRUD s testy
```bash
@project.prompt.md @create-crud.prompt.md
"Vytvořit CRUD pro Invoice management"

@create-tests.prompt.md  
"Přidat testy pro Invoice CRUD"
```

### 2. Test authentication fix
```bash
@test-authentication.prompt.md
"Opravit 403 error v StatusRequestTest"
```

### 3. Code quality upgrade
```bash
@code-quality.prompt.md
"Přidat return types do všech metod v ClientService"
```

### 4. Complete new feature
```bash
@project.prompt.md @create-crud.prompt.md @create-tests.prompt.md
"Vytvořit kompletní Report management s admin panelem, API a testy"
```

## 📈 Best Practices

### ✅ DO:

1. **Vždy začni s kontextem:**
   ```
   @project.prompt.md @specific-workflow.prompt.md
   ```

2. **Kombinuj related prompts:**
   ```
   @create-crud.prompt.md @create-tests.prompt.md
   ```

3. **Použij specific prompts pro specific problémy:**
   ```
   @test-authentication.prompt.md pro auth issues
   @code-quality.prompt.md pro type safety
   ```

4. **Reference instrukce pro detaily:**
   ```
   #file:.github/prompts/instructions/backpack.md
   ```

### ❌ DON'T:

1. **Nepřeskakuj project.prompt.md** u komplexních úkolů
2. **Nemíchej workflow prompts bez kontextu**
3. **Nepoužívej instructions samostatně** - jsou to reference materials
4. **Nezapomínaj na testing** při vytváření nových features

## 🔄 Typical Development Workflows

### Workflow 1: Nová komponenta (Model + CRUD + Tests)
```bash
# 1. Vytvoření základu
@project.prompt.md @create-crud.prompt.md
"Vytvořit kompletní CRUD pro Product management"

# 2. Přidání testů
@create-tests.prompt.md
"Vytvořit Unit a Feature testy pro ProductRequest a Product model"

# 3. Debugging (pokud nutné)
@test-authentication.prompt.md
"Opravit authentication v Product admin testech"
```

### Workflow 2: Code Quality Improvement
```bash
# 1. Analýza a zlepšení
@code-quality.prompt.md
"Analyzovat a zlepšit type safety v ClientController"

# 2. Refactoring
@refactor-code.prompt.md
"Refaktorovat dlouhé metody v ClientController"

# 3. Test update
@create-tests.prompt.md
"Aktualizovat testy po refactoringu"
```

### Workflow 3: Bug Fixing
```bash
# 1. Debugging
@debug-troubleshoot.prompt.md
"Testy selháváją s Foreign key constraint - najít problém"

# 2. Authentication issues (pokud relevant)
@test-authentication.prompt.md
"Opravit Backpack authentication v failing testech"

# 3. Verification
@clean-build.prompt.md
"Vyčistit cache a ověřit fix"
```

## 📊 Výhody tohoto Prompt Systému

### 🎯 **Modularita**
- Každý prompt má specifický účel
- Kombinovatelné pro komplexní úkoly
- Snadné udržování a rozšiřování

### ⚡ **Efektivita**
- Rychlý přístup k specific řešením
- Předpřipravené templates a patterns
- Copy-paste ready solutions

### 📚 **Dokumentace**
- Prompts slouží jako living documentation
- Best practices zabudované do workflow
- Konzistentní standards napříč projektem

### 🔍 **Troubleshooting**
- Specialized prompts pro common issues
- Step-by-step debugging guides
- Quick reference materials

## 🔧 Maintenance tohoto systému

### Přidání nového promptu:
1. Vytvoř soubor v odpovídající složce
2. Přidej metadata (mode, description)
3. Aktualizuj tento návod
4. Test s real-world scenarios

### Update existujících prompts:
1. Udržuj aktuální s project evolution
2. Přidávej nové patterns a solutions
3. Remove deprecated approaches
4. Update tohoto průvodce

Tento prompt systém poskytuje flexibilní a efektivní foundation pro development workflow v Laravel/Backpack projektu s kompletní podporou pro moderní testing practices.
