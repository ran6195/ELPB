<template>
  <div class="quick-contact-block">
    <!-- Placeholder nell'editor se nessun pulsante è configurato -->
    <div
      v-if="editable && !hasAnyButton"
      class="placeholder-message"
    >
      <div class="placeholder-content">
        <svg class="placeholder-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
        </svg>
        <h3>Contatti Rapidi</h3>
        <p>Configura i numeri WhatsApp e/o Telefono nelle Impostazioni Pagina (⚙️ in alto a destra)</p>
      </div>
    </div>

    <!-- WhatsApp Button - Basso Destra -->
    <a
      v-if="block.content.whatsapp?.enabled && block.content.whatsapp?.number"
      :href="whatsappLink"
      target="_blank"
      rel="noopener noreferrer"
      class="quick-contact-btn whatsapp-btn"
      :style="whatsappStyle"
      :title="block.content.whatsapp?.tooltip || 'Contattaci su WhatsApp'"
    >
      <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
      </svg>
      <span v-if="block.content.whatsapp?.showText" class="btn-text">{{ block.content.whatsapp?.text || 'WhatsApp' }}</span>
    </a>

    <!-- Phone Button - Basso Sinistra -->
    <a
      v-if="block.content.phone?.enabled && block.content.phone?.number"
      :href="phoneLink"
      class="quick-contact-btn phone-btn"
      :style="phoneStyle"
      :title="block.content.phone?.tooltip || 'Chiamaci'"
    >
      <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
        <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
      </svg>
      <span v-if="block.content.phone?.showText" class="btn-text">{{ block.content.phone?.text || 'Chiama' }}</span>
    </a>
  </div>
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
  }
})

// WhatsApp link
const whatsappLink = computed(() => {
  if (!props.block.content.whatsapp?.number) return '#'

  const number = props.block.content.whatsapp.number.replace(/\s+/g, '')
  const message = props.block.content.whatsapp.message || ''
  const encodedMessage = encodeURIComponent(message)

  return `https://wa.me/${number}${message ? '?text=' + encodedMessage : ''}`
})

// Phone link
const phoneLink = computed(() => {
  if (!props.block.content.phone?.number) return '#'
  return `tel:${props.block.content.phone.number}`
})

// WhatsApp button style
const whatsappStyle = computed(() => {
  const style = props.block.content.whatsapp?.style || {}
  const showText = props.block.content.whatsapp?.showText ?? false

  return {
    backgroundColor: style.backgroundColor || '#25D366',
    color: style.color || '#FFFFFF',
    bottom: style.bottom || '20px',
    right: style.right || '20px',
    width: showText ? 'auto' : (style.width || '60px'),
    height: style.height || '60px',
    borderRadius: showText ? '30px' : (style.borderRadius || '50%'),
    fontSize: style.fontSize || '24px',
    paddingLeft: showText ? '16px' : '0',
    paddingRight: showText ? '20px' : '0'
  }
})

// Phone button style
const phoneStyle = computed(() => {
  const style = props.block.content.phone?.style || {}
  const showText = props.block.content.phone?.showText ?? false

  return {
    backgroundColor: style.backgroundColor || '#007BFF',
    color: style.color || '#FFFFFF',
    bottom: style.bottom || '20px',
    left: style.left || '20px',
    width: showText ? 'auto' : (style.width || '60px'),
    height: style.height || '60px',
    borderRadius: showText ? '30px' : (style.borderRadius || '50%'),
    fontSize: style.fontSize || '24px',
    paddingLeft: showText ? '16px' : '0',
    paddingRight: showText ? '20px' : '0'
  }
})

// Check if at least one button is configured
const hasAnyButton = computed(() => {
  const hasWhatsApp = !!(props.block.content?.whatsapp?.enabled && props.block.content?.whatsapp?.number)
  const hasPhone = !!(props.block.content?.phone?.enabled && props.block.content?.phone?.number)
  return hasWhatsApp || hasPhone
})
</script>

<style>
.quick-contact-block {
  /* Aggiungi altezza minima per debug */
  min-height: 50px;
  position: relative;
}

.quick-contact-btn {
  position: fixed;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 999999 !important;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
  overflow: hidden;
}

.quick-contact-btn:hover {
  transform: scale(1.1) translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
}

.quick-contact-btn:active {
  transform: scale(1.05);
}

.quick-contact-btn .icon {
  width: 1em;
  height: 1em;
  flex-shrink: 0;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
}

.quick-contact-btn .btn-text {
  font-weight: 600;
  font-size: 14px;
  white-space: nowrap;
  padding-right: 12px;
}

/* Animazione pulse per WhatsApp */
.whatsapp-btn {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
  }
  50% {
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4), 0 0 0 10px rgba(37, 211, 102, 0);
  }
  100% {
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
  }
}

/* Animazione pulse per Phone */
.phone-btn {
  animation: pulse-phone 2s infinite;
}

@keyframes pulse-phone {
  0% {
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
  }
  50% {
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4), 0 0 0 10px rgba(0, 123, 255, 0);
  }
  100% {
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
  }
}

/* Responsive - su mobile ridimensiona */
@media (max-width: 768px) {
  .quick-contact-btn {
    width: 50px !important;
    height: 50px !important;
    font-size: 20px !important;
  }

  .quick-contact-btn .btn-text {
    display: none;
  }

  .whatsapp-btn {
    right: 15px !important;
    bottom: 15px !important;
  }

  .phone-btn {
    left: 15px !important;
    bottom: 15px !important;
  }
}

/* Placeholder per editor */
.placeholder-message {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 48px 32px;
  text-align: center;
  color: white;
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-content {
  max-width: 400px;
}

.placeholder-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 16px;
  opacity: 0.9;
}

.placeholder-message h3 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 12px;
  color: white;
}

.placeholder-message p {
  font-size: 16px;
  opacity: 0.9;
  line-height: 1.5;
  color: white;
}
</style>
