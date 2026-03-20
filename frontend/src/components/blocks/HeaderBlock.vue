<template>
  <nav :style="{ marginTop: block.content.marginTop || '0px' }">
    <div :class="['max-w-7xl mx-auto px-6 py-4', roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <div class="flex items-center justify-between">
        <!-- Logo -->
        <a
          :href="block.content.logoLink || '#'"
          class="flex items-center"
        >
          <img
            v-if="block.content.logoUrl"
            :src="block.content.logoUrl"
            :alt="block.content.logoAlt || 'Logo'"
            class="h-12 w-auto"
            :style="{ height: block.content.logoHeight || '50px', width: 'auto' }"
          />
          <div
            v-else
            class="h-12 w-48 bg-gray-300 rounded flex items-center justify-center text-sm text-gray-600"
          >
            Logo
          </div>
        </a>

        <!-- Right side: Menu + Social Buttons -->
        <div class="flex items-center gap-6">
          <!-- Menu links opzionali (per il futuro) -->
          <div v-if="block.content.showMenu" class="hidden md:flex items-center space-x-6">
            <a
              v-for="(link, index) in block.content.menuLinks"
              :key="index"
              :href="link.url"
              class="text-sm font-medium hover:opacity-80 transition-opacity"
            >
              {{ link.text }}
            </a>
          </div>

          <!-- Social Buttons -->
          <div v-if="hasSocialLinks" class="flex items-center gap-3">
            <!-- Facebook -->
            <a
              v-if="block.content.socialLinks?.facebook"
              :href="block.content.socialLinks.facebook"
              target="_blank"
              rel="noopener noreferrer"
              :style="socialButtonStyle"
              class="social-button transition-all hover:opacity-80"
            >
              <svg v-if="iconStyle === 'colored'" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/>
              </svg>
              <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>

            <!-- Instagram -->
            <a
              v-if="block.content.socialLinks?.instagram"
              :href="block.content.socialLinks.instagram"
              target="_blank"
              rel="noopener noreferrer"
              :style="socialButtonStyle"
              class="social-button transition-all hover:opacity-80"
            >
              <svg v-if="iconStyle === 'colored'" class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <defs>
                  <linearGradient id="instagram-gradient-header" x1="0%" y1="100%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:#FD5949;stop-opacity:1" />
                    <stop offset="50%" style="stop-color:#D6249F;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#285AEB;stop-opacity:1" />
                  </linearGradient>
                </defs>
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" fill="url(#instagram-gradient-header)"/>
              </svg>
              <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
            </a>

            <!-- X (Twitter) -->
            <a
              v-if="block.content.socialLinks?.twitter"
              :href="block.content.socialLinks.twitter"
              target="_blank"
              rel="noopener noreferrer"
              :style="socialButtonStyle"
              class="social-button transition-all hover:opacity-80"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" :fill="iconStyle === 'colored' ? '#000000' : 'currentColor'">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>
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

const blockStyles = computed(() => {
  const styles = props.block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || '#343a40', // bg-dark di Bootstrap
    color: styles.textColor || '#FFFFFF',
    padding: styles.padding || undefined,
    fontFamily: styles.fontFamily || undefined
  }
})

const hasSocialLinks = computed(() => {
  const links = props.block.content.socialLinks || {}
  return links.facebook || links.instagram || links.twitter
})

const socialButtonStyle = computed(() => {
  const socialStyle = props.block.content.socialButtonStyle || {}
  return {
    backgroundColor: socialStyle.backgroundColor || 'transparent',
    color: socialStyle.color || (props.block.styles?.textColor || '#FFFFFF'),
    padding: socialStyle.padding || '8px',
    borderRadius: socialStyle.borderRadius || '8px',
    border: socialStyle.borderWidth ? `${socialStyle.borderWidth} solid ${socialStyle.borderColor || '#FFFFFF'}` : 'none',
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center'
  }
})

const iconStyle = computed(() => {
  return props.block.content.iconStyle || 'monochrome'
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
</script>

<style scoped>
[contenteditable="true"]:focus {
  outline: 2px solid #0ea5e9;
  outline-offset: 2px;
  border-radius: 4px;
}

[contenteditable="true"]:hover {
  background: rgba(14, 165, 233, 0.05);
}
</style>
