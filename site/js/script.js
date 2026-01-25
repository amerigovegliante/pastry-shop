/* File: site/js/script.js */

function gestisciAutocompilazione(datiUtente) {
    const box = document.getElementById('box-autocompilazione');
    const checkbox = document.getElementById('usaDatiProfilo');
    const campoNome = document.getElementById('nome');
    const campoCognome = document.getElementById('cognome');
    const campoTel = document.getElementById('telefono');

    // Controllo sicurezza
    if (!box || !checkbox || !campoNome || !campoCognome || !campoTel) return;

    // 1. Mostriamo il box (Progressive Enhancement: se JS è disattivo, il box resta invisibile)
    box.style.display = 'flex';

    // Funzione interna per applicare i dati
    function applicaDati() {
        if (checkbox.checked) {
            // Salvo i valori attuali (se l'utente aveva scritto qualcosa a mano)
            if (campoNome.value !== datiUtente.nome) campoNome.dataset.old = campoNome.value;
            if (campoCognome.value !== datiUtente.cognome) campoCognome.dataset.old = campoCognome.value;
            if (campoTel.value !== datiUtente.telefono) campoTel.dataset.old = campoTel.value;

            // Scrivo i dati del profilo
            campoNome.value = datiUtente.nome;
            campoCognome.value = datiUtente.cognome;
            campoTel.value = datiUtente.telefono;
        } else {
            // Ripristino i vecchi valori o svuoto
            campoNome.value = campoNome.dataset.old || "";
            campoCognome.value = campoCognome.dataset.old || "";
            campoTel.value = campoTel.dataset.old || "";
        }
    }

    // 2. Ascolto il click dell'utente
    checkbox.addEventListener('change', applicaDati);

    // 3. ESECUZIONE IMMEDIATA: Se il checkbox parte già selezionato, compilo subito!
    if (checkbox.checked) {
        applicaDati();
    }
}