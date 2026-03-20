<template>
  <div class="rich-text-editor">
    <QuillEditor
      v-model:content="content"
      content-type="html"
      :toolbar="toolbarOptions"
      theme="snow"
      @update:content="handleUpdate"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const content = ref(props.modelValue)

// Toolbar configuration - include tutte le funzionalità necessarie
const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'], // text formatting
  [{ 'header': [1, 2, 3, false] }], // headings dropdown
  [{ 'list': 'ordered'}, { 'list': 'bullet' }], // lists
  [{ 'align': ['', 'center', 'right', 'justify'] }], // text align ('' = left)
  [{ 'color': [] }, { 'background': [] }], // colore testo e sfondo
  ['blockquote', 'code-block'], // quote e code
  ['link'], // link
  ['clean'] // remove formatting button
]

// Update parent when content changes
const handleUpdate = (newContent) => {
  console.log('📝 VueQuill HTML generato:', newContent)
  emit('update:modelValue', newContent)
}

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  if (newValue !== content.value) {
    content.value = newValue
  }
})
</script>

<style scoped>
.rich-text-editor {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  overflow: hidden;
}

/* Personalizzazione stili Quill */
:deep(.ql-toolbar) {
  background: #f9fafb;
  border: none;
  border-bottom: 1px solid #d1d5db;
  padding: 8px;
}

:deep(.ql-container) {
  border: none;
  font-size: 14px;
  min-height: 200px;
  max-height: 400px;
  overflow-y: auto;
}

:deep(.ql-editor) {
  padding: 16px;
  min-height: 200px;
}

:deep(.ql-editor.ql-blank::before) {
  color: #9ca3af;
  font-style: normal;
}

/* Stili per il contenuto */
:deep(.ql-editor p) {
  margin-bottom: 0.5em;
}

:deep(.ql-editor h1) {
  font-size: 2em;
  font-weight: 600;
  margin-bottom: 0.5em;
  margin-top: 0.5em;
}

:deep(.ql-editor h2) {
  font-size: 1.5em;
  font-weight: 600;
  margin-bottom: 0.5em;
  margin-top: 0.5em;
}

:deep(.ql-editor h3) {
  font-size: 1.25em;
  font-weight: 600;
  margin-bottom: 0.5em;
  margin-top: 0.5em;
}

/* Grassetto normale per paragrafi */
:deep(.ql-editor p strong),
:deep(.ql-editor p b) {
  font-weight: 700 !important;
}

/* Grassetto extra per heading */
:deep(.ql-editor h1 strong),
:deep(.ql-editor h1 b) {
  font-weight: 900 !important;
}

:deep(.ql-editor h2 strong),
:deep(.ql-editor h2 b) {
  font-weight: 900 !important;
}

:deep(.ql-editor h3 strong),
:deep(.ql-editor h3 b) {
  font-weight: 900 !important;
}

:deep(.ql-editor ul),
:deep(.ql-editor ol) {
  padding-left: 1.5em;
  margin-bottom: 0.5em;
}

:deep(.ql-editor li) {
  margin-bottom: 0.25em;
}

:deep(.ql-editor li strong),
:deep(.ql-editor li b) {
  font-weight: 700 !important;
}

:deep(.ql-editor a) {
  color: #3b82f6;
  text-decoration: underline;
}

:deep(.ql-editor a:hover) {
  color: #2563eb;
}

:deep(.ql-editor strong) {
  font-weight: 700 !important;
}

:deep(.ql-editor em) {
  font-style: italic !important;
}

:deep(.ql-editor u) {
  text-decoration: underline !important;
}

:deep(.ql-editor s),
:deep(.ql-editor del) {
  text-decoration: line-through !important;
}

:deep(.ql-editor blockquote) {
  border-left: 4px solid #d1d5db;
  padding-left: 1em;
  margin-left: 0;
  margin-bottom: 1em;
  font-style: italic;
  color: #6b7280;
}

:deep(.ql-editor pre.ql-syntax) {
  background: #1e293b;
  color: #e2e8f0;
  padding: 1em;
  border-radius: 0.375rem;
  overflow-x: auto;
  margin-bottom: 1em;
  font-family: 'Courier New', monospace;
}

/* Allineamento testo */
:deep(.ql-editor .ql-align-center) {
  text-align: center;
}

:deep(.ql-editor .ql-align-right) {
  text-align: right;
}

:deep(.ql-editor .ql-align-justify) {
  text-align: justify;
}

/* Stili bottoni toolbar */
:deep(.ql-toolbar button) {
  width: auto !important;
  padding: 4px 8px;
}

:deep(.ql-toolbar button:hover) {
  background: #e5e7eb;
}

:deep(.ql-toolbar button.ql-active) {
  background: #3b82f6;
  color: white;
}

:deep(.ql-toolbar .ql-stroke) {
  stroke: #374151;
}

:deep(.ql-toolbar button.ql-active .ql-stroke) {
  stroke: white;
}

:deep(.ql-toolbar .ql-fill) {
  fill: #374151;
}

:deep(.ql-toolbar button.ql-active .ql-fill) {
  fill: white;
}
</style>
