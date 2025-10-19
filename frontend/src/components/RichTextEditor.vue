<template>
  <div class="rich-text-editor">
    <!-- Toolbar -->
    <div v-if="editor" class="toolbar">
      <!-- Bold -->
      <button
        @click="editor.chain().focus().toggleBold().run()"
        :class="{ 'is-active': editor.isActive('bold') }"
        type="button"
        title="Grassetto"
      >
        <strong>B</strong>
      </button>

      <!-- Italic -->
      <button
        @click="editor.chain().focus().toggleItalic().run()"
        :class="{ 'is-active': editor.isActive('italic') }"
        type="button"
        title="Corsivo"
      >
        <em>I</em>
      </button>

      <!-- Underline -->
      <button
        @click="editor.chain().focus().toggleUnderline().run()"
        :class="{ 'is-active': editor.isActive('underline') }"
        type="button"
        title="Sottolineato"
      >
        <u>U</u>
      </button>

      <span class="divider"></span>

      <!-- Headings -->
      <button
        @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
        :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
        type="button"
        title="Titolo H1"
      >
        H1
      </button>
      <button
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
        type="button"
        title="Titolo H2"
      >
        H2
      </button>
      <button
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
        type="button"
        title="Titolo H3"
      >
        H3
      </button>
      <button
        @click="editor.chain().focus().setParagraph().run()"
        :class="{ 'is-active': editor.isActive('paragraph') }"
        type="button"
        title="Paragrafo"
      >
        P
      </button>

      <span class="divider"></span>

      <!-- Lists -->
      <button
        @click="editor.chain().focus().toggleBulletList().run()"
        :class="{ 'is-active': editor.isActive('bulletList') }"
        type="button"
        title="Lista puntata"
      >
        •&nbsp;Lista
      </button>
      <button
        @click="editor.chain().focus().toggleOrderedList().run()"
        :class="{ 'is-active': editor.isActive('orderedList') }"
        type="button"
        title="Lista numerata"
      >
        1.&nbsp;Lista
      </button>

      <span class="divider"></span>

      <!-- Alignment -->
      <button
        @click="editor.chain().focus().setTextAlign('left').run()"
        :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }"
        type="button"
        title="Allinea a sinistra"
      >
        ⬅
      </button>
      <button
        @click="editor.chain().focus().setTextAlign('center').run()"
        :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }"
        type="button"
        title="Allinea al centro"
      >
        ↔
      </button>
      <button
        @click="editor.chain().focus().setTextAlign('right').run()"
        :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }"
        type="button"
        title="Allinea a destra"
      >
        ➡
      </button>

      <span class="divider"></span>

      <!-- Link -->
      <button
        @click="setLink"
        :class="{ 'is-active': editor.isActive('link') }"
        type="button"
        title="Aggiungi link"
      >
        🔗
      </button>
      <button
        v-if="editor.isActive('link')"
        @click="editor.chain().focus().unsetLink().run()"
        type="button"
        title="Rimuovi link"
      >
        ⛔
      </button>
    </div>

    <!-- Editor Content -->
    <editor-content :editor="editor" class="editor-content" />
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
import Underline from '@tiptap/extension-underline'
import { watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Underline,
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        target: '_blank',
        rel: 'noopener noreferrer'
      }
    }),
    TextAlign.configure({
      types: ['heading', 'paragraph']
    })
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  }
})

// Update editor content when modelValue changes externally
watch(() => props.modelValue, (newValue) => {
  const isSame = editor.value.getHTML() === newValue
  if (!isSame && editor.value) {
    editor.value.commands.setContent(newValue, false)
  }
})

const setLink = () => {
  const previousUrl = editor.value.getAttributes('link').href
  const url = window.prompt('URL:', previousUrl)

  if (url === null) return

  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }

  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}
</script>

<style scoped>
.rich-text-editor {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  overflow: hidden;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 8px;
  background: #f9fafb;
  border-bottom: 1px solid #d1d5db;
}

.toolbar button {
  padding: 6px 10px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s;
  color: #374151;
  font-weight: 500;
}

.toolbar button:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
}

.toolbar button.is-active {
  background: #3b82f6;
  color: white;
  border-color: #2563eb;
}

.divider {
  width: 1px;
  background: #d1d5db;
  margin: 0 4px;
}

.editor-content {
  padding: 16px;
  min-height: 200px;
  max-height: 400px;
  overflow-y: auto;
}

/* Stili per il contenuto dell'editor */
:deep(.ProseMirror) {
  outline: none;
  min-height: 150px;
}

:deep(.ProseMirror p) {
  margin-bottom: 0.5em;
}

:deep(.ProseMirror h1) {
  font-size: 2em;
  font-weight: bold;
  margin-bottom: 0.5em;
}

:deep(.ProseMirror h2) {
  font-size: 1.5em;
  font-weight: bold;
  margin-bottom: 0.5em;
}

:deep(.ProseMirror h3) {
  font-size: 1.25em;
  font-weight: bold;
  margin-bottom: 0.5em;
}

:deep(.ProseMirror ul),
:deep(.ProseMirror ol) {
  padding-left: 1.5em;
  margin-bottom: 0.5em;
}

:deep(.ProseMirror a) {
  color: #3b82f6;
  text-decoration: underline;
}

:deep(.ProseMirror a:hover) {
  color: #2563eb;
}

:deep(.ProseMirror strong) {
  font-weight: bold;
}

:deep(.ProseMirror em) {
  font-style: italic;
}

:deep(.ProseMirror u) {
  text-decoration: underline;
}
</style>
