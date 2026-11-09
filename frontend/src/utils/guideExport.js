/**
 * Esportazione della guida alla pubblicazione in HTML autonomo o Markdown,
 * partendo dal contenuto già mostrato nella pagina (una sola fonte da mantenere).
 *
 * Attributi usati nel template:
 *   data-export-skip      elemento escluso (navigazione, pulsanti)
 *   data-export="code"    blocco da trattare come codice
 *   data-tone="…"         riquadro di nota (citazione in Markdown)
 *   data-step="n"         titolo di un passo numerato
 *   data-check            casella di controllo di una lista di verifica
 */

/** Copia del contenuto pronta per l'esportazione: link assoluti, niente icone né parti escluse */
function prepare(root) {
  const clone = root.cloneNode(true)
  clone.querySelectorAll('[data-export-skip], svg, script, style').forEach(el => el.remove())
  clone.querySelectorAll('a[href]').forEach(a => {
    const href = a.getAttribute('href')
    // Le ancore interne restano tali; il resto diventa assoluto (es. /admin, /downloads/…)
    if (!href.startsWith('#')) a.setAttribute('href', new URL(href, window.location.href).href)
  })
  return clone
}

// ---------------------------------------------------------------- HTML

const HTML_STYLE = `
  :root { color-scheme: light; }
  body { margin: 0; background: #f9fafb; color: #1f2937; font: 15px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
  main { max-width: 820px; margin: 0 auto; padding: 32px 16px 64px; }
  header { margin-bottom: 24px; }
  h1 { font-size: 1.6rem; margin: 0 0 4px; }
  .meta { color: #6b7280; font-size: .85rem; margin: 0; }
  section { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 24px; margin: 0 0 20px; }
  h2 { font-size: 1.15rem; margin: 0 0 12px; display: flex; align-items: center; gap: 10px; }
  h3 { font-size: .95rem; margin: 20px 0 8px; }
  [data-step] > span:first-child { flex: none; width: 26px; height: 26px; border-radius: 50%; background: #0284c7; color: #fff; font-size: .8rem; display: inline-flex; align-items: center; justify-content: center; }
  code, kbd, pre, [data-export="code"] { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .85em; }
  pre { background: #111827; color: #f3f4f6; padding: 14px 16px; border-radius: 8px; overflow-x: auto; line-height: 1.5; }
  [data-export="code"] { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; word-break: break-all; }
  [data-tone] { border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 8px; padding: 10px 14px; margin: 14px 0; }
  [data-tone="amber"] { background: #fffbeb; border-color: #fde68a; color: #78350f; }
  [data-tone="red"] { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
  [data-tone="blue"] { background: #eff6ff; border-color: #bfdbfe; color: #1e3a8a; }
  table { border-collapse: collapse; width: 100%; font-size: .9rem; }
  th, td { border: 1px solid #e5e7eb; padding: 6px 10px; text-align: left; }
  th { background: #f9fafb; font-size: .75rem; text-transform: uppercase; color: #6b7280; }
  ul, ol { padding-left: 22px; }
  li { margin: 4px 0; }
  ul:has([data-check]) { list-style: none; padding-left: 0; }
  [data-check]::before { content: "\\2610"; margin-right: 6px; }
  dl > div { display: grid; grid-template-columns: 1fr 2fr; gap: 12px; padding: 8px 0; border-top: 1px solid #f3f4f6; }
  dt { font-weight: 600; }
  dd { margin: 0; }
  a { color: #0369a1; }
  @media (max-width: 600px) { dl > div { grid-template-columns: 1fr; gap: 2px; } }
`

function escapeHtml(text) {
  return text.replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c])
}

export function toHtmlDocument(root, { title, meta }) {
  const clone = prepare(root)
  // Le classi Tailwind non servono fuori dall'app: lo stile è nel documento
  clone.querySelectorAll('[class]').forEach(el => el.removeAttribute('class'))
  clone.querySelectorAll('*').forEach(el => {
    for (const attr of [...el.attributes]) {
      if (attr.name.startsWith('data-v-') || attr.name === 'aria-current') el.removeAttribute(attr.name)
    }
  })
  return `<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${escapeHtml(title)}</title>
<style>${HTML_STYLE}</style>
</head>
<body>
<main>
<header>
<h1>${escapeHtml(title)}</h1>
<p class="meta">${escapeHtml(meta)}</p>
</header>
${clone.innerHTML.trim()}
</main>
</body>
</html>
`
}

// ------------------------------------------------------------ Markdown

const BLOCK_TAGS = new Set(['SECTION', 'DIV', 'P', 'H1', 'H2', 'H3', 'H4', 'UL', 'OL', 'PRE', 'TABLE', 'DL', 'ARTICLE'])

/** Testo inline con enfasi, codice e link */
function inline(node) {
  if (node.nodeType === Node.TEXT_NODE) return node.textContent.replace(/\s+/g, ' ')
  if (node.nodeType !== Node.ELEMENT_NODE || node.hasAttribute('data-check')) return ''
  const content = () => [...node.childNodes].map(inline).join('')
  switch (node.tagName) {
    case 'STRONG': case 'B': return wrap(content(), '**')
    case 'EM': case 'I': return wrap(content(), '*')
    case 'CODE': case 'KBD': return '`' + node.textContent.trim() + '`'
    case 'BR': return '  \n'
    case 'A': {
      const text = content().trim()
      const href = node.getAttribute('href')
      return href ? `[${text}](${href})` : text
    }
    default: return content()
  }
}

function wrap(text, mark) {
  const trimmed = text.trim()
  if (!trimmed) return text
  // Gli spazi restano fuori dai segni, altrimenti il Markdown non li riconosce
  return text.match(/^\s*/)[0] + mark + trimmed + mark + text.match(/\s*$/)[0]
}

const clean = (text) => text.replace(/[ \t]+/g, ' ').replace(/ *\n */g, '\n').trim()

function blocks(node) {
  const out = []
  let pending = ''
  const flush = () => {
    if (clean(pending)) out.push(clean(pending))
    pending = ''
  }
  for (const child of node.childNodes) {
    if (child.nodeType === Node.ELEMENT_NODE && (BLOCK_TAGS.has(child.tagName) || child.hasAttribute('data-tone'))) {
      flush()
      const block = block_(child)
      if (block) out.push(block)
    } else {
      pending += inline(child)
    }
  }
  flush()
  return out.join('\n\n')
}

function list(node, ordered) {
  return [...node.children].filter(li => li.tagName === 'LI').map((li, i) => {
    const marker = li.querySelector('[data-check]') ? '- [ ]' : ordered ? `${i + 1}.` : '-'
    const text = clean(blocks(li)).replace(/\n/g, '\n   ')
    return `${marker} ${text}`
  }).join('\n')
}

function table(node) {
  const rows = [...node.querySelectorAll('tr')].map(tr =>
    [...tr.children].map(cell => clean([...cell.childNodes].map(inline).join('')).replace(/\|/g, '\\|')))
  if (!rows.length) return ''
  const [head, ...body] = rows
  return [head, head.map(() => '---'), ...body].map(r => `| ${r.join(' | ')} |`).join('\n')
}

function block_(node) {
  if (node.hasAttribute('data-tone')) {
    return blocks(node).split('\n').map(line => (line ? `> ${line}` : '>')).join('\n')
  }
  if (node.getAttribute('data-export') === 'code') {
    return '```\n' + node.textContent.trim() + '\n```'
  }
  switch (node.tagName) {
    case 'H1': return '# ' + clean(inline(node))
    case 'H2': case 'H3': case 'H4': {
      const level = '#'.repeat(Number(node.tagName[1]))
      const step = node.getAttribute('data-step')
      if (step) {
        const title = clean([...node.childNodes].slice(1).map(inline).join(''))
        return `${level} ${step}. ${title}`
      }
      return `${level} ${clean(inline(node))}`
    }
    case 'P': return clean(inline(node))
    case 'UL': return list(node, false)
    case 'OL': return list(node, true)
    case 'PRE': return '```\n' + node.textContent.replace(/\n+$/, '') + '\n```'
    case 'TABLE': return table(node)
    case 'DL': return [...node.querySelectorAll('dt')].map(dt =>
      `- **${clean(inline(dt))}** — ${clean(inline(dt.nextElementSibling || document.createElement('dd')))}`).join('\n')
    default: return blocks(node)
  }
}

export function toMarkdown(root, { title, meta }) {
  const body = blocks(prepare(root)).replace(/\n{3,}/g, '\n\n')
  return `# ${title}\n\n_${meta}_\n\n${body}\n`
}

/** Scarica un testo come file */
export function downloadText(content, filename, type) {
  const url = URL.createObjectURL(new Blob([content], { type: `${type};charset=utf-8` }))
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
