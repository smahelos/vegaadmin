# Domain Events Migration Strategy

## 🎯 Postupný přechod z Observer Pattern na Domain Events

Tento dokument popisuje strategii migrace z Observer patternu na Domain Events systém.

### ✅ Co už máme hotové

**Domain Events systém:**
- ✅ Base `DomainEvent` abstract class
- ✅ Kompletní Party Events (ClientCreated, ClientUpdated, ClientDeleted, SupplierCreated, atd.)
- ✅ `DomainEventDispatcher` s handler managementem
- ✅ `DomainEvents` facade pro snadné dispatchování
- ✅ `UsageRecordingHandler` pro UELS integraci
- ✅ `DomainEventsServiceProvider` registrovaný v Laravel
- ✅ Funkční testy potvrzující správnost systému

### 📋 Aktuální stav Observerů

**Zachováváme funkčnost:**
- `ClientObserver` - dispatch `UserDataChanged` pro cache invalidation
- `SupplierObserver` - dispatch `UserDataChanged` pro cache invalidation
- Observery **nezajišťují** UELS usage recording (to nyní řeší Domain Events)

### 🔄 Hybridní přístup (doporučený)

**Krátkodobě:**
1. **Zachovat** stávající Observer funkčnost pro cache invalidation
2. **Přidat** Domain Events dispatch do Controller akcí
3. **Využívat** Domain Events pro nové business logiky

**Dlouhodobě:**
1. **Přesunout** cache invalidation logiku do Domain Event Handlerů  
2. **Odstranit** Observery úplně
3. **Centralizovat** veškerou business logiku do Domain Events

### 🚀 Implementace v Controllerech

**Kde přidat Domain Events dispatch:**

```php
// V ClientController::store()
public function store(StoreClientRequest $request)
{
    $client = Client::create($request->validated());
    
    // Domain Events dispatch
    $clientDTO = ClientDTO::fromArray($client->toArray());
    DomainEvents::dispatch(new ClientCreated($clientDTO, $client->user_id));
    
    return redirect()->route('client.index')->with('success', 'Client created');
}

// V ClientController::update()  
public function update(UpdateClientRequest $request, Client $client)
{
    $oldData = $client->toArray();
    $client->update($request->validated());
    
    // Domain Events dispatch
    $clientDTO = ClientDTO::fromArray($client->fresh()->toArray());
    DomainEvents::dispatch(new ClientUpdated($clientDTO, $client->user_id, $client->getChanges()));
    
    return redirect()->route('client.show', $client);
}
```

### ⚠️ Důležité poznámky

**Co funguje hned:**
- UELS usage recording přes `UsageRecordingHandler` 
- Cache invalidation přes stávající Observery
- Domain Events systém je production ready

**Co chce rozšíření:**
- Integrace do všech CRUD Controller akcí
- Vytvoření dalších Event Handlerů dle potřeby
- Postupné nahrazení Observer logiky

### 🎯 Příští kroky

1. **Integrovat** Domain Events do Party Controllerů (Client, Supplier)
2. **Vytvořit** cache invalidation handler pro Domain Events
3. **Testovat** paralelní běh Observerů a Domain Events
4. **Postupně** migrovat logiku z Observerů do Domain Event Handlerů

### 📊 Výhody tohoto přístupu

✅ **Zero downtime migration** - stávající funkčnost zůstává  
✅ **Postupná migrace** - můžeme testovat kousek po kousku  
✅ **Lepší separace** - business logika odděleně od infrastruktury  
✅ **Scalability** - Domain Events připravené pro všechny domény  
✅ **Maintainability** - jasná struktura a zodpovědnost

Tato strategie zajišťuje plynulý přechod na modernější architekturu bez rizika narušení stávající funkčnosti.
