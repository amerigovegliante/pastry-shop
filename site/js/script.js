document.addEventListener('DOMContentLoaded', function () {

    const box = document.getElementById('box-autocompilazione');
    
    if (box) {
        const checkbox = document.getElementById('usaDatiProfilo');
        const campoNome = document.getElementById('nome');
        const campoCognome = document.getElementById('cognome');
        const campoTel = document.getElementById('telefono');

        // Controllo che tutti i campi input esistano
        if (checkbox && campoNome && campoCognome && campoTel) {
            
            // 1. Recupero i dati dal DATASET dell'HTML (che sono stati scritti da PHP)
            const datiUtente = {
                nome: box.dataset.nome || "",
                cognome: box.dataset.cognome || "",
                telefono: box.dataset.telefono || ""
            };

            // 2. Mostro il box dell'autocompilazione
            box.style.display = 'flex';

            function applicaDati() {
                if (checkbox.checked) {
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

            // 3. Ascolto il click dell'utente
            checkbox.addEventListener('change', applicaDati);

            applicaDati();
        }
    }
    

});