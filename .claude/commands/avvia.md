Avvia l'ambiente di sviluppo locale per il Landing Page Builder.

Esegui questi passi in sequenza:

**Passo 1** — Avvia il backend PHP in background e salva il PID:
```bash
cd /Users/francesco/Code/landing-page-builder/backend && php -S localhost:8000 -t public > /tmp/elpb-backend.log 2>&1 &
echo $! > /Users/francesco/Code/landing-page-builder/.claude/.dev-pids-backend
```

**Passo 2** — Avvia il frontend Vue in background e salva il PID:
```bash
cd /Users/francesco/Code/landing-page-builder/frontend && npm run dev > /tmp/elpb-frontend.log 2>&1 &
echo $! > /Users/francesco/Code/landing-page-builder/.claude/.dev-pids-frontend
```

**Passo 3** — Attendi 2 secondi per permettere ai processi di avviarsi, poi leggi i PID dai file salvati e mostra all'utente:
- Backend PHP (PID letto da `.claude/.dev-pids-backend`) → http://localhost:8000
- Frontend Vue (PID letto da `.claude/.dev-pids-frontend`) → http://localhost:3000
- Log backend: /tmp/elpb-backend.log
- Log frontend: /tmp/elpb-frontend.log
- Per fermare tutto: usa `/stop`
