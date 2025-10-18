<template>
  <footer class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-6">
      <div class="grid md:grid-cols-3 gap-8">
        <!-- Colonna 1: Info azienda -->
        <div>
          <h3
            :contenteditable="editable"
            @blur="updateContent('companyName', $event.target.innerText)"
            class="text-xl font-bold mb-4 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ block.content.companyName }}
          </h3>
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
          <div class="space-y-2 text-gray-400">
            <p
              :contenteditable="editable"
              @blur="updateContent('email', $event.target.innerText)"
              class="outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
            >
              {{ block.content.email }}
            </p>
            <p
              :contenteditable="editable"
              @blur="updateContent('phone', $event.target.innerText)"
              class="outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
            >
              {{ block.content.phone }}
            </p>
            <p
              :contenteditable="editable"
              @blur="updateContent('address', $event.target.innerText)"
              class="outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
            >
              {{ block.content.address }}
            </p>
          </div>
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

const emit = defineEmits(['update'])

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
