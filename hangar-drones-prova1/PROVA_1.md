# Prova tecnico-pratica 1 — Feature + Bugfix + Git + Markdown

**Durata indicativa:** 4 ore.

Questa è la prima delle due prove pratiche. Leggi integralmente il presente documento prima di iniziare.

---

## 1. Materiale consegnato

Ti è stato consegnato un archivio `hangar-drones-prova1.zip` contenente un mini-progetto didattico PHP (CLI) che simula un piccolo **hangar** con un numero finito di **slot di docking (slots)** e dei **droni** che escono in volo, rientrano, e vengono gestiti in manutenzione.

```
hangar-drones/
├── bin/
│   └── hangar.php         # Script CLI dimostrativo
├── src/
│   ├── Drone.php          # Entità drone (stato + minuti di volo)
│   └── Hangar.php         # Hangar con capacità finita
├── tests/                 # Vuota
├── BUG_REPORT.md          # Descrizione del bug segnalato
├── composer.json
├── phpunit.xml
├── phpcs.xml              # PSR-12
└── README.md
```

Il dominio è volutamente semplice. Leggi e comprendi il codice sorgente esistente prima di iniziare: ti servirà per rispettare le convenzioni di stile, le firme dei metodi e la regola di business già implementata ("ogni drone che rientra all'hangar passa sempre dalla manutenzione").

---

## 2. Requisiti di ambiente

- PHP 8.1 o superiore (verifica con `php -v`).
- Composer installato e raggiungibile da shell (`composer --version`).
- Git installato.
- Tutto il lavoro deve essere svolto **da shell**. Il contenuto dei commit, i messaggi e la topologia dei branch devono essere visibili con `git log --graph --oneline --all`.

Il primo passo operativo è:

```bash
composer install
composer test   # deve girare senza test presenti, esito "no tests executed"
php bin/hangar.php   # deve produrre output coerente
```

Se uno di questi passaggi fallisce, risolvi il problema di ambiente prima di iniziare a scrivere i test.

---

## 3. Cosa ti viene richiesto

Questa prova comprende **tre attività**, tutte obbligatorie:

1. **Correggere il bug** descritto in `BUG_REPORT.md` (§4).
2. **Implementare una nuova funzionalità** (§5).
3. **Gestire l'intero lavoro con Git**, secondo le pratiche prudenziali descritte al §6, su **repository remoto**.

Ordine suggerito di lavoro:

1. Setup dell'ambiente e del repository.
2. Bugfix, su branch dedicato, con successivo merge su `main`.
3. Implementazione della feature, su branch dedicato, con successivo merge su `main`.

Tutte e tre le attività concorrono al giudizio finale (§8).

---

## 4. Bugfix

Il file `BUG_REPORT.md` consegnato documenta un difetto di comportamento relativo al conteggio dei minuti di volo.

Sei tenuto a:

1. **Riprodurre** il bug, preferibilmente tramite un piccolo script temporaneo (NON versionato) o una modifica temporanea al CLI; questo conferma che hai capito il problema prima di correggerlo.
2. **Localizzare** la riga responsabile nel codice.
3. **Applicare una correzione minimale**: non alterare firme di metodi né comportamenti non correlati.
4. **Verificare** che la correzione risolva il difetto e che `php bin/hangar.php` continui a produrre un output sensato.

Lo script di riproduzione non deve essere committato: le verifiche empiriche della Prova 1 restano locali. La suite di test arriverà nella Prova 2.

---

## 5. Funzionalità da implementare

**Dismissione di un drone (retiring).**

Allo stato attuale non esiste alcun modo per rimuovere definitivamente un drone dal parco mezzi: un drone può entrare (`addDrone`), essere lanciato in volo (`launchDrone`), atterrare (`landDrone`), essere mandato in maintenance (`sendToMaintenance`) e uscire dalla maintenance (`releaseFromMaintenance`), ma non può essere ritirato.

Devi implementare la dismissione, rispettando i seguenti requisiti funzionali.

### 5.1 Modifiche alla classe `Drone`

- Introduci una nuova costante `STATUS_RETIRED = 'retired'`.
- Introduci un metodo `isRetired()`.
- Introduci un metodo `retire()` che porta il drone nello stato `retired`. L'operazione è ammessa **solo** se il drone si trova in stato `maintenance`; in qualunque altro stato il metodo deve sollevare `\RuntimeException`, in modo analogo ai metodi di transizione già presenti.
- Una volta `retired`, il drone **non** può più cambiare stato: qualunque ulteriore `takeOff`, `markDocked`, `sendToMaintenance`, `returnFromMaintenance`, `addFlightMinutes`, `retire` deve sollevare `\RuntimeException`. Suggerimento: la struttura di validazione già presente nei metodi di transizione (ognuno verifica lo stato di partenza richiesto) copre naturalmente questo requisito senza codice aggiuntivo.
- Il costruttore deve accettare `STATUS_RETIRED` come stato iniziale valido (per coerenza con gli altri stati ammessi).

### 5.2 Modifiche alla classe `Hangar`

- Introduci un pool interno dei droni ritirati, mantenuto in modo simmetrico a `inFlightIds` (mappa `array<string,true>` di soli id, non di oggetti).
- Introduci i metodi pubblici:
  - `retiredCount(): int`
  - `retiredDroneIds(): list<string>`
  - `retireDrone(string $droneId): void`
- `retireDrone` deve:
  - Accettare solo un id di un drone **attualmente in maintenance** di questo hangar. In caso contrario deve sollevare `\RuntimeException` con un messaggio informativo.
  - Rifiutare id vuoto o fatto di soli spazi (`\InvalidArgumentException`), come già fatto da `sendToMaintenance` e `releaseFromMaintenance`.
  - Rimuovere il drone dal pool maintenance, portarlo in stato `retired`, registrare il suo id nel pool dei ritirati, e **liberare lo slot** (cioè il conteggio `insideCount()` deve decrementarsi).
- `addDrone` deve rifiutare un drone il cui stato corrente sia `retired` (`\RuntimeException`).
- Un drone già conosciuto dall'hangar come `retired` non deve poter essere riaggiunto: in `addDrone` il controllo di id già noto deve tenere conto anche del nuovo pool.

### 5.3 Invarianti da preservare

- `insideCount() = dockedCount() + maintenanceCount()` deve restare `<= capacity()`.
- `inFlightCount()` e `retiredCount()` **non** rientrano in `insideCount()`: i droni in volo o ritirati non occupano slot.
- L'ordine di inserimento in `docked` e `maintenance` resta stabile (non alterare la logica FIFO di `launchDrone`).

### 5.4 Aggiornamento dello script CLI

Aggiorna `bin/hangar.php` in modo da dimostrare la nuova funzionalità: dopo l'arrivo in maintenance, ritira uno dei due droni e stampa a video i contatori aggiornati (docked / maintenance / retired). L'output deve restare leggibile e coerente con lo stile già presente.

### 5.5 Stile

Tutto il codice aggiunto o modificato deve superare `composer lint` (PSR-12). L'esecuzione di `composer lint` deve terminare senza errori.

---

## 6. Gestione con Git

### 6.1 Repository remoto

Il lavoro deve risiedere su un **repository remoto**: crea un repository su GitHub.

### 6.2 Inizializzazione e primo commit

1. Entra nella directory `hangar-drones/` del materiale consegnato.
2. Inizializza il repository e verifica che il branch di default si chiami `main` (altrimenti rinominalo con `git branch -M main`).
3. Aggiungi un file `.gitignore` adeguato (almeno: `vendor/`, `.phpunit.result.cache`, `.phpcs-cache`, eventuali file di IDE). **Non** escludere `composer.lock`: va versionato.
4. Esegui un **commit iniziale** che contenga esclusivamente il materiale consegnato, senza alcuna tua modifica.
5. Fai push di `main`.

### 6.3 Branch del bugfix

Crea un branch dedicato al bugfix, a partire da `main`. Nome richiesto: `bugfix/flight-minutes-accumulation`.

### 6.4 Branch della feature

A partire da `main` **aggiornato col bugfix**, crea un nuovo branch dedicato alla feature. Nome richiesto: `feature/retire-drone`.

Su questo branch dovranno trovarsi **almeno 4 commit**, piccoli e atomici. Un singolo commit monolitico contenente tutta la feature è considerato errore di metodo. Esempi di suddivisione accettabile (a titolo indicativo, non vincolante):

- `add STATUS_RETIRED and Drone::retire()`
- `add Hangar::retireDrone() and retired pool`
- `reject retired drones in Hangar::addDrone()`
- `update CLI to demonstrate retire`

Al termine, merge su `main` e push.


### 6.5 Linee guida generali

- I messaggi dei commit devono essere chiari, in inglese o in italiano.
- Non mescolare commit di formattazione con commit di logica: se lanci `composer lint:fix`, fai un commit dedicato.
- Entrambi i branch (`bugfix/flight-minutes-accumulation` e `feature/retire-drone`) **non** devono essere cancellati: devono rimanere visibili nella topologia.
- `vendor/` non deve essere versionato.

---

## 7. Deliverable della Prova 1

Alla consegna deve essere presente tutto quanto segue:

1. Tutto il codice presente su repository e soltanto quello. Non includere `vendor/`, `.git/` o altro materiale non nel repository.
2. Un file `git.log` che contiene **tutto** l'output di `git log -g --all --pretty=fuller` (attenzione: potresti dover scorrere l'output con la barra spaziatrice).
3. Un breve file `NOTES.md` (massimo una pagina) in cui riassumi, a parole tue:
   - Come hai riprodotto il bug e in quale punto lo hai localizzato.
   - Le scelte implementative non banali della feature.
   - Eventuali assunzioni fatte laddove il testo fosse ambiguo.

Verificare inoltre che:
1. `composer install` seguito da `php bin/hangar.php` deve terminare senza errori e mostrare un output sensato che include la nuova funzionalità.
2. `composer lint` deve terminare **senza errori**.

**La consegna consisterà di un unico file zip, denominato `prova1_COGNOME_NOME.zip`**

---

## 8. Note pratiche

- **Non è ammesso l'uso di assistenti AI generativi.** È ammessa la consultazione della documentazione ufficiale di PHP, Composer, PHPUnit, PHP_CodeSniffer e Git.
- I **test automatici** saranno oggetto della Prova 2; in questa prova non è richiesto (né atteso) che la cartella `tests/` venga popolata. Restano benvenute verifiche locali informali per validare il tuo lavoro.
- In caso di dubbi sull'interpretazione del testo, applica il principio del comportamento minimo ragionevole e documenta la scelta in `NOTES.md`.
