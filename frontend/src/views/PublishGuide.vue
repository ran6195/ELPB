<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-4xl mx-auto px-4 sm:px-8 py-6 flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 mb-1">Pubblicare una pagina su un nuovo sito</h1>
          <p class="text-gray-500 text-sm">Dominio, cartella sul sito, file del renderer e configurazione</p>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-8 py-8 space-y-8">
      <!-- Come funziona -->
      <section class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Come funziona</h2>
        <p class="text-sm text-gray-700 leading-relaxed">
          Le landing page si creano qui, ma si mostrano sul sito del cliente. Sul sito si installa una volta
          il <strong>renderer</strong>, una piccola cartella di file PHP (il sito deve quindi girare in PHP, vedi i
          <a href="#download" class="text-primary-600 hover:underline">requisiti</a>): a ogni visita chiede la pagina all'app e la mostra
          con il dominio del cliente. Una sola installazione serve tutte le pagine di quel sito:
        </p>
        <div class="mt-3 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 break-all">
          https://www.sito-cliente.it/lp/<span class="text-primary-600">slug-della-pagina</span>
        </div>
        <p class="mt-2 text-xs text-gray-500"><code class="font-mono">lp</code> è il nome della cartella del renderer sul sito: puoi sceglierne uno qualsiasi.</p>
        <ol class="mt-4 grid sm:grid-cols-5 gap-2 text-xs">
          <li v-for="(step, i) in steps" :key="step.id">
            <a :href="'#' + step.id" class="block h-full p-3 rounded-lg border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors">
              <span class="block text-primary-600 font-semibold mb-0.5">Passo {{ i + 1 }}</span>
              <span class="text-gray-700">{{ step.title }}</span>
            </a>
          </li>
        </ol>
      </section>

      <!-- Download -->
      <section id="download" class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-baseline justify-between gap-4 mb-1 flex-wrap">
          <h2 class="text-lg font-semibold text-gray-900">Scarica il renderer</h2>
          <span v-if="info" class="text-xs text-gray-500">
            Versione <strong class="text-gray-800">{{ info.version }}</strong> · pacchetto del {{ formatDate(info.built_at) }}
          </span>
        </div>
        <p class="text-sm text-gray-600 mb-4">
          Il pacchetto contiene la cartella <code class="font-mono">lp/</code> e un file <code class="font-mono">LEGGIMI.txt</code>
          con le stesse istruzioni in breve. Le pagine legali non sono incluse: il Footer Legale delle landing page
          punta alle pagine Privacy, Condizioni d'uso e Cookie Policy già presenti sul sito.
        </p>

        <p v-if="infoError" class="text-sm text-red-600 mb-3">Impossibile leggere le informazioni sul pacchetto. Riprova più tardi.</p>
        <a
          :href="pkg.file ? downloadUrl(pkg.file) : undefined"
          :download="pkg.file || undefined"
          @click="logDownload"
          :class="pkg.file ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-300 pointer-events-none'"
          class="inline-flex items-center justify-center gap-2 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors max-w-full"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
          </svg>
          <span class="truncate">{{ pkg.file || 'Scarica il renderer' }}</span>
          <span v-if="pkg.size" class="text-white/70 font-normal shrink-0">· {{ formatSize(pkg.size) }}</span>
        </a>

        <h3 class="text-sm font-semibold text-gray-900 mt-6 mb-2">Requisiti del sito</h3>
        <ul class="text-sm text-gray-700 space-y-1 list-disc pl-5">
          <li>PHP 8.0 o superiore con l'estensione cURL</li>
          <li>Apache con mod_rewrite (per gli indirizzi puliti)</li>
          <li>Connessioni HTTPS in uscita verso <code class="font-mono">elpb.edysma.com</code></li>
        </ul>
        <Note tone="red">
          <strong>Il renderer è scritto in PHP.</strong> Se il sito del cliente non gira in PHP (sito statico, Node.js, .NET,
          piattaforme come Wix, Shopify o Squarespace) questo pacchetto non si può usare: serve uno
          <strong>sviluppo ad hoc</strong>, da valutare prima di promettere la pubblicazione. WordPress e Joomla sono in PHP e vanno bene.
        </Note>
      </section>

      <!-- Passo 1: dominio -->
      <section :id="steps[0].id" class="bg-white rounded-lg border border-gray-200 p-6">
        <StepTitle :n="1" :title="steps[0].title" />
        <p class="text-sm text-gray-700 leading-relaxed">
          Il dominio è il sito del cliente su cui comparirà la pagina. Va registrato una volta per azienda e poi
          si può associare a tutte le sue pagine.
        </p>
        <ul class="mt-3 text-sm text-gray-700 space-y-2 list-disc pl-5">
          <li>
            <strong>Dall'editor della pagina:</strong> <em>⚙️ Impostazioni</em> → sezione <em>Domini</em> → scrivi il dominio
            e premi <em>Aggiungi</em>. Il dominio viene creato e associato subito alla pagina.
          </li>
          <li>
            <strong>Dall'anagrafica:</strong> scheda <em>Domini</em> del pannello
            <router-link v-if="authStore.isAdmin" to="/admin" class="text-primary-600 hover:underline">Admin</router-link>
            <router-link v-else-if="authStore.isCompany" to="/company" class="text-primary-600 hover:underline">Gestione</router-link>
            <span v-else>Admin o Gestione</span>.
          </li>
        </ul>
        <p class="mt-3 text-sm text-gray-700">
          Scrivi solo il nome del sito, senza <code class="font-mono">https://</code> né percorsi:
          <code class="font-mono">sito-cliente.it</code>. Il <code class="font-mono">www.</code> si toglie da solo,
          quindi vale per entrambe le forme.
        </p>
        <Note v-if="!canManageDomains" tone="amber">
          Nuovi domini possono crearli solo l'amministratore e il responsabile aziendale: chiedi a loro di registrarlo,
          poi lo trovi nell'elenco della sezione Domini della pagina.
        </Note>
      </section>

      <!-- Passo 2: associazione e pubblicazione -->
      <section :id="steps[1].id" class="bg-white rounded-lg border border-gray-200 p-6">
        <StepTitle :n="2" :title="steps[1].title" />
        <ol class="text-sm text-gray-700 space-y-2 list-decimal pl-5">
          <li>Apri la pagina nell'editor e premi <em>⚙️ Impostazioni</em>.</li>
          <li>Nella sezione <em>Domini</em> spunta il dominio del sito (puoi associarne più di uno).</li>
          <li>In <em>Pubblicazione</em> attiva l'interruttore.</li>
        </ol>
        <Note tone="amber">
          Senza almeno un dominio associato la pagina non si può pubblicare, e una pagina pubblicata non può perdere
          l'ultimo dominio. Le pagine pubblicate in passato senza dominio verranno ritirate dalla pubblicazione
          con le prossime versioni dell'app.
        </Note>
      </section>

      <!-- Passo 3: cartella -->
      <section :id="steps[2].id" class="bg-white rounded-lg border border-gray-200 p-6">
        <StepTitle :n="3" :title="steps[2].title" />
        <p class="text-sm text-gray-700 leading-relaxed">
          Se il renderer è già installato sul sito, salta al <a :href="'#' + steps[4].id" class="text-primary-600 hover:underline">passo 5</a>:
          la nuova pagina è già raggiungibile al suo slug. Altrimenti estrai lo zip e carica via FTP la cartella
          <code class="font-mono">lp/</code> nella cartella pubblica del sito (di solito <code class="font-mono">public_html/</code>
          o <code class="font-mono">httpdocs/</code>).
        </p>
        <Note tone="blue">
          <strong>La cartella può avere un nome qualsiasi.</strong> <code class="font-mono">lp</code> è solo il nome predefinito:
          puoi rinominarla, per esempio, <code class="font-mono">promo</code>, <code class="font-mono">offerte</code> o
          <code class="font-mono">landing</code>, oppure mettere i file direttamente nella radice del sito. Il nome diventa parte
          dell'indirizzo delle pagine (<code class="font-mono">https://www.sito-cliente.it/<strong>promo</strong>/slug-della-pagina</code>)
          e va riportato nel <code class="font-mono">RewriteBase</code> dell'<code class="font-mono">.htaccess</code>
          (<a :href="'#' + steps[3].id" class="underline">passo 4</a>).
        </Note>
        <pre class="code mt-4">public_html/
└── lp/
    ├── .env
    ├── .htaccess
    ├── page.php
    ├── BlockRenderer.php
    ├── config.php
    └── thank-you.php</pre>
        <Note tone="amber">
          <code class="font-mono">.env</code> e <code class="font-mono">.htaccess</code> iniziano con un punto e su macOS e Linux
          sono nascosti: controlla che il client FTP li carichi (in Finder: <kbd>Cmd</kbd>+<kbd>Maiusc</kbd>+<kbd>.</kbd> li mostra).
          Permessi: cartelle 755, file 644.
        </Note>
        <Note tone="gray">
          Su un sito WordPress o Joomla tieni il renderer nella sua sottocartella con il suo <code class="font-mono">.htaccess</code>:
          così il CMS non intercetta gli indirizzi delle landing page.
        </Note>
      </section>

      <!-- Passo 4: configurazione -->
      <section :id="steps[3].id" class="bg-white rounded-lg border border-gray-200 p-6">
        <StepTitle :n="4" :title="steps[3].title" />

        <h3 class="text-sm font-semibold text-gray-900 mt-2 mb-2">.htaccess → RewriteBase</h3>
        <p class="text-sm text-gray-700 mb-3">
          Deve corrispondere al nome che hai dato alla cartella del renderer (è l'unica impostazione che dipende dal nome):
        </p>
        <div class="overflow-x-auto">
          <table class="w-full text-sm border border-gray-200 rounded-lg">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
              <tr>
                <th class="text-left px-3 py-2">Cartella</th>
                <th class="text-left px-3 py-2">RewriteBase</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-mono text-xs">
              <tr><td class="px-3 py-2">/lp/</td><td class="px-3 py-2">/lp/ <span class="font-sans text-gray-500">(già impostato)</span></td></tr>
              <tr><td class="px-3 py-2">/promo/ <span class="font-sans text-gray-500">(qualsiasi altro nome)</span></td><td class="px-3 py-2">/promo/</td></tr>
              <tr><td class="px-3 py-2 font-sans">radice del dominio</td><td class="px-3 py-2">/</td></tr>
            </tbody>
          </table>
        </div>

        <h3 class="text-sm font-semibold text-gray-900 mt-6 mb-2">.env</h3>
        <pre class="code">{{ envExample }}</pre>
        <ul class="mt-3 text-sm text-gray-700 space-y-1.5 list-disc pl-5">
          <li><code class="font-mono">API_BASE_URL</code> è già giusto: non modificarlo.</li>
          <li><code class="font-mono">HOME_URL</code> (facoltativo): link "Torna alla home" della pagina di ringraziamento.</li>
          <li><code class="font-mono">DEFAULT_SLUG</code> (facoltativo): pagina mostrata aprendo la cartella senza slug.</li>
          <li><code class="font-mono">DEBUG=true</code> solo per capire un errore durante l'installazione, poi di nuovo <code class="font-mono">false</code>.</li>
        </ul>

        <h3 class="text-sm font-semibold text-gray-900 mt-6 mb-2">Nell'app</h3>
        <ul class="text-sm text-gray-700 space-y-1.5 list-disc pl-5">
          <li>
            <strong>Footer Legale → pagine legali del sito:</strong> nel blocco <em>Footer Legale</em> → <em>Link Legali</em>
            disattiva <em>Genera automaticamente</em> e con <em>+ Aggiungi Link</em> inserisci Privacy, Condizioni d'uso e
            Cookie Policy con l'indirizzo completo delle pagine del sito, per esempio
            <code class="font-mono">https://www.sito-cliente.it/privacy-policy</code>. Con la generazione automatica i link andrebbero in 404.
          </li>
          <li>
            <strong>reCAPTCHA:</strong> se il modulo lo usa, aggiungi il dominio del sito alla chiave nella console
            Google reCAPTCHA.
          </li>
        </ul>
      </section>

      <!-- Passo 5: verifica -->
      <section :id="steps[4].id" class="bg-white rounded-lg border border-gray-200 p-6">
        <StepTitle :n="5" :title="steps[4].title" />
        <ul class="text-sm text-gray-700 space-y-2">
          <li v-for="check in checks" :key="check" class="flex gap-2">
            <span class="mt-0.5 w-4 h-4 shrink-0 rounded border border-gray-300"></span>
            <span v-html="check"></span>
          </li>
        </ul>
      </section>

      <!-- Aggiornamento -->
      <section id="aggiornare" class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Aggiornare un renderer già installato</h2>
        <p class="text-sm text-gray-700 leading-relaxed">
          Scarica il pacchetto e sostituisci via FTP solo i file PHP nella cartella del renderer:
          <code class="font-mono">page.php</code>, <code class="font-mono">BlockRenderer.php</code>, <code class="font-mono">config.php</code>,
          <code class="font-mono">thank-you.php</code>.
          <strong>Non sovrascrivere <code class="font-mono">.env</code> e <code class="font-mono">.htaccess</code></strong>: contengono le impostazioni del sito.
        </p>
        <p class="text-sm text-gray-700 mt-2">
          I siti con un renderer vecchio sono indicati come "da aggiornare" nella scheda <em>Renderer</em> del pannello admin.
        </p>
      </section>

      <!-- Problemi comuni -->
      <section id="problemi" class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Problemi comuni</h2>
        <dl class="divide-y divide-gray-100 text-sm">
          <div v-for="p in problems" :key="p.symptom" class="grid sm:grid-cols-3 gap-1 sm:gap-4 py-2.5">
            <dt class="font-medium text-gray-900">{{ p.symptom }}</dt>
            <dd class="sm:col-span-2 text-gray-700">{{ p.fix }}</dd>
          </div>
        </dl>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/authStore'
import apiClient from '../api/axios'

const authStore = useAuthStore()
const canManageDomains = computed(() => authStore.isAdmin || authStore.isCompany)

const steps = [
  { id: 'dominio', title: 'Registra il dominio' },
  { id: 'pubblica', title: 'Associa il dominio e pubblica' },
  { id: 'cartella', title: 'Prepara la cartella e copia i file' },
  { id: 'configura', title: 'Configura il renderer' },
  { id: 'verifica', title: 'Verifica' }
]

// Pacchetti generati da scripts/build-renderer-packages.sh in public/downloads/
const base = import.meta.env.BASE_URL
const downloadUrl = (file) => `${base}downloads/${file}`
const info = ref(null)
const infoError = ref(false)

// Registro attività: apertura della guida e download (i file sono statici, non passano dal backend)
const logActivity = (request) => request.catch(error => console.error('Attività non registrata:', error))

function logDownload() {
  logActivity(apiClient.post('/renderer/downloads', { file: pkg.value.file, version: info.value?.version ?? null }))
}

onMounted(async () => {
  logActivity(apiClient.get('/guides/publishing'))
  try {
    const response = await fetch(downloadUrl('renderer.json'), { cache: 'no-store' })
    if (!response.ok) throw new Error(response.statusText)
    info.value = await response.json()
  } catch (e) {
    infoError.value = true
  }
})

// Per ora solo la variante senza pagine legali (il Footer Legale linka quelle del sito)
const pkg = computed(() => info.value?.packages?.senza_legali || {})

const envExample = `# API del backend centrale (non modificare)
API_BASE_URL=https://elpb.edysma.com/backend/public/api

# Facoltativi
# DEFAULT_SLUG=nome-pagina
# HOME_URL=https://www.sito-cliente.it

DEBUG=false`

const checks = [
  'La pagina si apre a <code class="font-mono">https://www.sito-cliente.it/lp/{slug}</code> con immagini, font, slider e video.',
  '<code class="font-mono">https://www.sito-cliente.it/lp/.env</code> risponde 403 (file protetto).',
  'L\'invio del modulo porta alla pagina di ringraziamento e il contatto compare tra i lead.',
  'I link del Footer Legale aprono le pagine Privacy, Condizioni d\'uso e Cookie Policy del sito.',
  'In <em>⚙️ Impostazioni</em> della pagina, sezione <em>Dove viene mostrata</em>, compare il sito con la versione del renderer (dopo aver aperto la pagina almeno una volta).',
  '<code class="font-mono">DEBUG=false</code> nel file <code class="font-mono">.env</code>.'
]

const problems = [
  { symptom: '"Pagina non trovata"', fix: 'Pagina non pubblicata o slug sbagliato nell\'indirizzo.' },
  { symptom: '"Si è verificato un errore"', fix: 'cURL assente o connessioni in uscita bloccate dall\'hosting. Metti DEBUG=true per vedere il dettaglio.' },
  { symptom: 'Pagina bianca o errore 500', fix: 'Versione di PHP inferiore alla 8.0.' },
  { symptom: '404 su ogni indirizzo', fix: 'RewriteBase sbagliato o mod_rewrite non attivo. Prova page.php?slug={slug}: se funziona il problema è l\'.htaccess.' },
  { symptom: 'Link del footer in 404', fix: '"Genera automaticamente" ancora attivo nel Footer Legale: disattivalo e inserisci i link alle pagine del sito.' },
  { symptom: 'Il modulo non parte', fix: 'Dominio del sito non aggiunto alla chiave reCAPTCHA.' },
  { symptom: 'Il sito non è in PHP', fix: 'Il renderer non si può installare: serve uno sviluppo ad hoc.' },
  { symptom: 'Impossibile pubblicare', fix: 'La pagina non ha domini associati: vedi passo 2.' }
]

function formatSize(bytes) {
  return bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.round(bytes / 1024)} KB`
}

function formatDate(value) {
  const date = new Date(`${value}T00:00:00`)
  return isNaN(date) ? value : date.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' })
}

// Piccoli componenti locali
const StepTitle = (props) => h('h2', { class: 'flex items-center gap-3 text-lg font-semibold text-gray-900 mb-3' }, [
  h('span', { class: 'w-7 h-7 shrink-0 rounded-full bg-primary-600 text-white text-sm flex items-center justify-center' }, props.n),
  props.title
])
StepTitle.props = ['n', 'title']

const tones = {
  amber: 'bg-amber-50 border-amber-200 text-amber-900',
  red: 'bg-red-50 border-red-200 text-red-900',
  gray: 'bg-gray-50 border-gray-200 text-gray-700',
  blue: 'bg-blue-50 border-blue-200 text-blue-900'
}
const Note = (props, { slots }) => h('div', { class: `mt-4 p-3 rounded-lg border text-sm ${tones[props.tone] || tones.gray}` }, slots.default?.())
Note.props = ['tone']
</script>

<style scoped>
.code {
  @apply text-xs font-mono bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto leading-relaxed whitespace-pre;
}
section {
  scroll-margin-top: 1rem;
}
</style>
