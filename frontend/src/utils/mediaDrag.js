// Trascinamento di file e cartelle della libreria media verso una cartella

const MIME = 'application/x-media-library'

/** @param {DragEvent} e @param {{kind: 'media'|'folder', id: number}} payload */
export function writeDragPayload(e, payload) {
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData(MIME, JSON.stringify(payload))
}

/** Ritorna il payload, oppure null se si sta trascinando altro (es. file dal computer) */
export function readDragPayload(e) {
  try {
    const raw = e.dataTransfer.getData(MIME)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}
