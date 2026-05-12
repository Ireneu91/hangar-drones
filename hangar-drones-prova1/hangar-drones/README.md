# Hangar &amp; Drones (CLI) — starter Prova 1

Mini-progetto didattico in PHP (solo CLI) che simula un piccolo **hangar** con un numero finito di **slot di docking (slots)** e dei **droni** che escono in volo e rientrano.

## Oggetti

- `Drone`: id, minuti di volo accumulati, stato (`docked`, `in_flight`, `maintenance`).
- `Hangar`: un numero fisso di slot, droni in docking, droni in manutenzione, droni in volo.

Regola di business semplificata: **ogni drone che rientra all'hangar entra sempre in `maintenance`** (ispezione post-volo/diagnostica), quindi non c'è alcuna logica random.

## Requisiti

- PHP 8.1+
- Composer

## Setup

```bash
composer install
```

## Esecuzione CLI

```bash
php bin/hangar.php
```

## Test

```bash
composer test
```

La cartella `tests/` è vuota: la scrittura dei test è oggetto della Prova 2.

## Lint (PSR-12)

```bash
composer lint
composer lint:fix
```

## File specifici di questa prova

- `BUG_REPORT.md` — descrizione di un difetto segnalato da QA.
- Il documento `PROVA_1.md` (fornito separatamente dal docente) descrive le attività da svolgere.
