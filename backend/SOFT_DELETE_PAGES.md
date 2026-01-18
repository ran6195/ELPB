# Soft Delete per Pagine - Archiviazione

Il sistema implementa il **soft delete** (eliminazione logica) per le pagine. Questo significa che quando una pagina viene eliminata, non viene rimossa fisicamente dal database, ma viene semplicemente archiviata.

## Come Funziona

### Database
- La tabella `pages` ha un campo `deleted_at` (timestamp nullable)
- Quando una pagina viene eliminata, il campo `deleted_at` viene impostato alla data/ora corrente
- Le pagine con `deleted_at != null` sono considerate "archiviate"

### Model Eloquent
Il model `Page` usa il trait `SoftDeletes` di Laravel/Eloquent:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;
    // ...
}
```

### Comportamento Automatico

**Query Normali** (escludono automaticamente pagine archiviate):
```php
Page::all()                    // Solo pagine NON archiviate
Page::find($id)                // Solo se NON archiviata
Page::where('slug', $slug)->get()  // Solo NON archiviate
```

**Eliminazione**:
```php
$page->delete()  // Imposta deleted_at, NON elimina dal database
```

**Include Pagine Archiviate**:
```php
Page::withTrashed()->get()     // Include anche archiviate
Page::onlyTrashed()->get()     // Solo archiviate
```

**Ripristino**:
```php
$page->restore()  // Rimuove deleted_at, ripristina la pagina
```

**Eliminazione Definitiva**:
```php
$page->forceDelete()  // Elimina FISICAMENTE dal database
```

## Endpoint API (Opzionali)

Se vuoi aggiungere gestione dell'archivio, puoi implementare questi endpoint:

### 1. Vedere Pagine Archiviate
```php
// In PageController.php
public function archived(Request $request, Response $response)
{
    $user = $request->getAttribute('user');

    $query = Page::onlyTrashed()->with(['blocks', 'company', 'user']);

    // Filtra per permessi (stesso logic di index())
    if ($user->isUser()) {
        $query->where('user_id', $user->id);
    } elseif ($user->isCompany()) {
        $query->where('company_id', $user->company_id);
    }

    $pages = $query->orderBy('deleted_at', 'desc')->get();

    $response->getBody()->write(json_encode($pages));
    return $response->withHeader('Content-Type', 'application/json');
}
```

**Route**: `GET /api/pages/archived`

### 2. Ripristinare Pagina Archiviata
```php
// In PageController.php
public function restore(Request $request, Response $response, array $args)
{
    $user = $request->getAttribute('user');
    $page = Page::onlyTrashed()->find($args['id']);

    if (!$page) {
        $response->getBody()->write(json_encode(['error' => 'Archived page not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Verifica permessi
    if (!$user || !$user->canEditPage($page)) {
        $response->getBody()->write(json_encode(['error' => 'Forbidden']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    $page->restore();

    $response->getBody()->write(json_encode(['message' => 'Page restored successfully']));
    return $response->withHeader('Content-Type', 'application/json');
}
```

**Route**: `POST /api/pages/{id}/restore`

### 3. Eliminare Definitivamente
```php
// In PageController.php
public function forceDelete(Request $request, Response $response, array $args)
{
    $user = $request->getAttribute('user');
    $page = Page::onlyTrashed()->find($args['id']);

    if (!$page) {
        $response->getBody()->write(json_encode(['error' => 'Archived page not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Solo admin può eliminare definitivamente
    if (!$user || !$user->isAdmin()) {
        $response->getBody()->write(json_encode(['error' => 'Only admins can permanently delete pages']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    $page->forceDelete();  // Elimina FISICAMENTE

    $response->getBody()->write(json_encode(['message' => 'Page permanently deleted']));
    return $response->withHeader('Content-Type', 'application/json');
}
```

**Route**: `DELETE /api/pages/{id}/force`

## Frontend (Opzionale)

Se vuoi aggiungere sezione Archivio nel frontend:

### PageStore.js
```javascript
// Ottieni pagine archiviate
async getArchivedPages() {
  this.loading = true
  try {
    const response = await apiClient.get('/pages/archived')
    this.archivedPages = response.data
  } catch (error) {
    console.error('Error fetching archived pages:', error)
  } finally {
    this.loading = false
  }
}

// Ripristina pagina
async restorePage(id) {
  try {
    await apiClient.post(`/pages/${id}/restore`)
    await this.getArchivedPages()
    await this.fetchPages()
  } catch (error) {
    throw error
  }
}
```

### Vue Component (ArchivedPages.vue)
```vue
<template>
  <div>
    <h2>Pagine Archiviate</h2>
    <div v-for="page in archivedPages" :key="page.id">
      <h3>{{ page.title }}</h3>
      <p>Archiviata il: {{ formatDate(page.deleted_at) }}</p>
      <button @click="restorePage(page.id)">Ripristina</button>
    </div>
  </div>
</template>
```

## Vantaggi del Soft Delete

1. **Storico**: Mantieni uno storico completo di tutte le pagine create
2. **Recupero**: Possibilità di ripristinare pagine eliminate per errore
3. **Audit**: Tracciabilità di chi ha eliminato cosa e quando
4. **Relazioni**: Lead e altri dati collegati alla pagina rimangono accessibili
5. **Sicurezza**: Riduce il rischio di perdita dati accidentale

## Pulizia Periodica (Opzionale)

Se vuoi eliminare definitivamente pagine archiviate dopo un certo periodo:

```php
// Script di pulizia (eseguire con cron job)
// Elimina pagine archiviate da più di 90 giorni
$cutoffDate = now()->subDays(90);
Page::onlyTrashed()
    ->where('deleted_at', '<', $cutoffDate)
    ->forceDelete();
```

## Migrazione Eseguita

La migration è già stata eseguita:
- File: `backend/database/migrations/add_soft_delete_to_pages.php`
- Comando: `php database/migrations/add_soft_delete_to_pages.php`
- Campo aggiunto: `deleted_at TIMESTAMP NULL`

## Stato Attuale

✅ **Implementato**:
- Campo `deleted_at` nel database
- Trait `SoftDeletes` nel model Page
- Eliminazione automaticamente archivia (non elimina)
- Query normali escludono automaticamente pagine archiviate

❌ **Non Implementato** (opzionale):
- Endpoint per vedere pagine archiviate
- Endpoint per ripristinare pagine
- Endpoint per eliminare definitivamente
- UI frontend per gestire archivio

Il soft delete funziona già automaticamente. Le pagine eliminate vengono archiviate e non appaiono più nelle liste normali, ma rimangono nel database.
