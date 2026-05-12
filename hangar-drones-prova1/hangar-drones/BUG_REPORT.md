# Bug Report #001 — Minuti di volo non accumulati tra voli

**Stato:** aperto
**Priorità:** media
**Segnalato da:** cliente QA

## Sintomo

Il cliente segnala che il contatore `flightMinutes()` di un drone non rispetta l'invariante
attesa: i minuti di ogni volo **non** si sommano al totale precedente.

## Passi per riprodurre

Con un hangar vuoto di capacità almeno 1, ripetere su uno stesso drone:

1. `addDrone(new Drone('D1'))`
2. `launchDrone()` — ritorna `D1`, stato `in_flight`
3. `landDrone($d1, 240)` — `D1` ora è in maintenance con (ci si aspetta) 240 minuti
4. `releaseFromMaintenance('D1')` — `D1` torna in docking
5. `launchDrone()` — `D1` parte nuovamente
6. `landDrone($d1, 150)` — `D1` rientra

## Risultato osservato

Al termine, `$d1->flightMinutes()` restituisce `150`.

## Risultato atteso

`$d1->flightMinutes()` deve restituire `390` (ovvero `240 + 150`).

## Nota

Lo script `bin/hangar.php` fornito non espone questo difetto, perché ogni drone
effettua un solo volo. La riproduzione richiede uno scenario con voli ripetuti
sullo stesso drone.
