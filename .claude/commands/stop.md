Ferma l'ambiente di sviluppo locale del Landing Page Builder.

Esegui questi passi in sequenza:

**Passo 1** — Leggi il PID del backend da `.claude/.dev-pids-backend` e terminalo:
```bash
kill $(cat /Users/francesco/Code/landing-page-builder/.claude/.dev-pids-backend) 2>/dev/null
rm -f /Users/francesco/Code/landing-page-builder/.claude/.dev-pids-backend
```

**Passo 2** — Leggi il PID del frontend da `.claude/.dev-pids-frontend` e terminalo:
```bash
kill $(cat /Users/francesco/Code/landing-page-builder/.claude/.dev-pids-frontend) 2>/dev/null
rm -f /Users/francesco/Code/landing-page-builder/.claude/.dev-pids-frontend
```

**Passo 3** — Comunica all'utente quali processi sono stati terminati. Se un file PID non esisteva, segnalalo (il processo era già fermo o non era mai stato avviato con `/avvia`).
