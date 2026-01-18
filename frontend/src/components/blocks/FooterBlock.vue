<template>
  <footer :class="footerWrapperClass" :style="footerWrapperStyle">
    <div :class="['px-6 py-12', containerClass, roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <div class="grid md:grid-cols-3 gap-8">
        <!-- Colonna 1: Info azienda -->
        <div>
          <!-- Titolo sezione (opzionale) -->
          <h4
            v-if="block.content.companyTitle"
            :contenteditable="editable"
            @blur="updateContent('companyTitle', $event.target.innerText)"
            class="text-lg font-semibold mb-4 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ block.content.companyTitle }}
          </h4>
          <!-- Nome azienda -->
          <h3
            :contenteditable="editable"
            @blur="updateContent('companyName', $event.target.innerText)"
            class="text-xl font-bold mb-4 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ block.content.companyName }}
          </h3>
          <!-- Descrizione -->
          <p
            :contenteditable="editable"
            @blur="updateContent('companyDescription', $event.target.innerText)"
            class="text-gray-400 leading-relaxed outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ block.content.companyDescription }}
          </p>
        </div>

        <!-- Colonna 2: Link utili -->
        <div>
          <h4
            :contenteditable="editable"
            @blur="updateContent('linksTitle', $event.target.innerText)"
            class="text-lg font-semibold mb-4 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ block.content.linksTitle }}
          </h4>
          <ul class="space-y-2">
            <li
              v-for="(link, index) in block.content.links"
              :key="index"
            >
              <a
                :href="link.url"
                class="text-gray-400 hover:text-white transition-colors"
                :contenteditable="editable"
                @blur="updateLink(index, 'text', $event.target.innerText)"
              >
                {{ link.text }}
              </a>
            </li>
          </ul>
        </div>

        <!-- Colonna 3: Contatti -->
        <div>
          <h4
            :contenteditable="editable"
            @blur="updateContent('contactTitle', $event.target.innerText)"
            class="text-lg font-semibold mb-4 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ block.content.contactTitle }}
          </h4>
          <!-- Rich text per contatti -->
          <div
            class="text-gray-400 prose prose-sm prose-invert max-w-none"
            v-html="contactContent"
          ></div>
        </div>
      </div>

      <!-- Copyright -->
      <div class="border-t border-gray-800 mt-8 pt-8 text-center">
        <p
          :contenteditable="editable"
          @blur="updateContent('copyright', $event.target.innerText)"
          class="text-gray-400 text-sm outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
        >
          {{ block.content.copyright }}
        </p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  block: {
    type: Object,
    required: true
  },
  editable: {
    type: Boolean,
    default: false
  },
  roundedCorners: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update'])

// Gestisci fullWidth (default true per retrocompatibilità)
const fullWidth = computed(() => {
  return props.block.content.fullWidth !== false // true se undefined o true
})

// Classi per il wrapper esterno (footer tag)
const footerWrapperClass = computed(() => {
  if (!fullWidth.value) {
    // Se NON fullWidth, applica larghezza limitata al wrapper
    return 'max-w-7xl mx-auto'
  }
  return '' // FullWidth: nessuna classe di limitazione
})

// Stili per il wrapper esterno (solo se fullWidth)
const footerWrapperStyle = computed(() => {
  if (fullWidth.value) {
    const styles = props.block.styles || {}
    return {
      backgroundColor: styles.backgroundColor || '#1F2937',
      color: styles.textColor || '#FFFFFF'
    }
  }
  return {} // Se non fullWidth, gli stili sono sul div interno
})

// Classi per il container interno
const containerClass = computed(() => {
  if (fullWidth.value) {
    // Se fullWidth, il contenuto è limitato ma lo sfondo si estende
    return 'max-w-7xl mx-auto'
  }
  return '' // Se non fullWidth, la larghezza è già limitata dal wrapper
})

// Stili per il div interno
const blockStyles = computed(() => {
  const styles = props.block.styles || {}

  if (!fullWidth.value) {
    // Se NON fullWidth, applica gli stili al div interno
    return {
      backgroundColor: styles.backgroundColor || '#1F2937',
      color: styles.textColor || '#FFFFFF',
      padding: styles.padding || undefined,
      fontFamily: styles.fontFamily || undefined
    }
  }

  // Se fullWidth, solo padding e font (colori sono sul wrapper)
  return {
    padding: styles.padding || undefined,
    fontFamily: styles.fontFamily || undefined
  }
})

// Computed per gestire retrocompatibilità
const contactContent = computed(() => {
  // Se esiste contactText, usalo
  if (props.block.content.contactText) {
    return props.block.content.contactText
  }

  // Altrimenti genera HTML dai vecchi campi email/phone/address
  let parts = []
  if (props.block.content.email) {
    parts.push(`<p><strong>Email:</strong> <a href="mailto:${props.block.content.email}">${props.block.content.email}</a></p>`)
  }
  if (props.block.content.phone) {
    parts.push(`<p><strong>Telefono:</strong> <a href="tel:${props.block.content.phone.replace(/\s/g, '')}">${props.block.content.phone}</a></p>`)
  }
  if (props.block.content.address) {
    parts.push(`<p><strong>Indirizzo:</strong> ${props.block.content.address}</p>`)
  }
  return parts.join('')
})

const updateContent = (field, value) => {
  const updatedBlock = {
    ...props.block,
    content: {
      ...props.block.content,
      [field]: value
    }
  }
  emit('update', updatedBlock)
}

const updateLink = (index, field, value) => {
  const updatedLinks = [...props.block.content.links]
  updatedLinks[index] = {
    ...updatedLinks[index],
    [field]: value
  }

  const updatedBlock = {
    ...props.block,
    content: {
      ...props.block.content,
      links: updatedLinks
    }
  }
  emit('update', updatedBlock)
}
</script>
