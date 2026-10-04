import { ref, computed } from 'vue'
import { useMediaStore } from '../stores/mediaStore'

/**
 * Albero delle cartelle della libreria media, costruito dall'elenco piatto dell'API.
 * @param {import('vue').Ref<string|number>} companyId filtro azienda (solo admin)
 */
export function useMediaFolders(companyId = ref('')) {
  const mediaStore = useMediaStore()
  const folders = ref([])
  const loading = ref(false)

  const byId = computed(() => new Map(folders.value.map((f) => [f.id, f])))

  async function load() {
    loading.value = true
    try {
      folders.value = await mediaStore.fetchFolders({ company_id: companyId.value })
    } finally {
      loading.value = false
    }
  }

  /** Sottocartelle dirette (null = radice), in ordine alfabetico */
  function childrenOf(parentId) {
    return folders.value
      .filter((f) => (f.parent_id ?? null) === (parentId ?? null))
      .sort((a, b) => a.name.localeCompare(b.name, 'it', { sensitivity: 'base' }))
  }

  /** Percorso dalla radice alla cartella (inclusa) */
  function pathTo(folderId) {
    const path = []
    let current = folderId ? byId.value.get(folderId) : null
    let guard = 0
    while (current && guard++ < 100) {
      path.unshift(current)
      current = current.parent_id ? byId.value.get(current.parent_id) : null
    }
    return path
  }

  /** Id della cartella e di tutte le discendenti */
  function descendantIds(folderId) {
    const ids = new Set([folderId])
    let added = true
    while (added) {
      added = false
      for (const f of folders.value) {
        if (f.parent_id && ids.has(f.parent_id) && !ids.has(f.id)) {
          ids.add(f.id)
          added = true
        }
      }
    }
    return ids
  }

  /** Stesso proprietario (stessa azienda, oppure stesso utente se senza azienda) */
  function sameOwner(a, b) {
    if (a.company_id != null || b.company_id != null) return Number(a.company_id) === Number(b.company_id)
    return Number(a.user_id) === Number(b.user_id)
  }

  /**
   * Opzioni indentate per una <select>, in ordine d'albero.
   * @param {(folder) => boolean} filter es. solo cartelle dello stesso proprietario
   */
  function treeOptions(filter = () => true) {
    const out = []
    const walk = (parentId, depth) => {
      for (const f of childrenOf(parentId)) {
        if (filter(f)) out.push({ id: f.id, label: `${'   '.repeat(depth)}${f.name}`, folder: f })
        walk(f.id, depth + 1)
      }
    }
    walk(null, 0)
    return out
  }

  /** Numero di elementi contenuti direttamente (file + sottocartelle) */
  function itemCount(folder) {
    return (folder.media_count || 0) + childrenOf(folder.id).length
  }

  return { folders, loading, byId, load, childrenOf, pathTo, descendantIds, sameOwner, treeOptions, itemCount }
}
