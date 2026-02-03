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
    
    const menuBtn = document.querySelector('.menu-toggle');
    if (menuBtn) {
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.setAttribute('aria-controls', 'menu'); // Collega logicamente il bottone al menu
    }

});

// Header scroll effect
window.addEventListener('scroll', () => {
    const header = document.getElementById('header');
    if (header) {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }
});

// Mobile menu toggle 
function toggleMenu() {
    const menu = document.getElementById('menu');
    const btn = document.querySelector('.menu-toggle');
    
    if (menu && btn) {
        const isNowActive = menu.classList.toggle('active');
        btn.setAttribute('aria-expanded', isNowActive);
        btn.setAttribute('aria-label', isNowActive ? 'Chiudi menu' : 'Apri menu');

        if (isNowActive) {
            setTimeout(() => {
                const firstLink = menu.querySelector('a');
                if (firstLink) {
                    firstLink.focus();
                }
            }, 50);
            document.body.style.overflow = 'hidden';

        } else {
            btn.focus();
            document.body.style.overflow = '';
        }
    }
}

// Password visibility toggle (Login page)
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (!passwordInput || !toggleButton || !eyeIcon) return;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.setAttribute('aria-pressed', 'true');
        toggleButton.setAttribute('aria-label', 'Nascondi password');
        toggleButton.setAttribute('title', 'Nascondi password');
        eyeIcon.innerHTML = `
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" fill="none" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="12" cy="12" r="3" fill="currentColor"/>
            <line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="1.5"/>
        `;
    } else {
        passwordInput.type = 'password';
        toggleButton.setAttribute('aria-pressed', 'false');
        toggleButton.setAttribute('aria-label', 'Mostra password');
        toggleButton.setAttribute('title', 'Mostra password');
        eyeIcon.innerHTML = `
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" fill="none" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="12" cy="12" r="3" fill="currentColor"/>
        `;
    }
}

// Quantity controls (Product details page)
function increaseQuantity() {
    const input = document.getElementById('quantity');
    if (!input) return;
    
    const currentValue = parseInt(input.value);
    if (currentValue < 99) {
        input.value = currentValue + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('quantity');
    if (!input) return;
    
    const currentValue = parseInt(input.value);
    if (currentValue > 1) {
        input.value = currentValue - 1;
    }
}

// Size selector (Product details page)
document.addEventListener('DOMContentLoaded', () => {
    const sizeOptions = document.querySelectorAll('.size-option');
    
    sizeOptions.forEach(button => {
        button.addEventListener('click', function() {
            sizeOptions.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

// Order status controls (Admin orders page)
function changeStatus(button, direction) {
    const container = button.closest('.stato-ordine');
    if (!container) return;
    
    const select = container.querySelector('select');
    if (!select) return;
    
    const currentIndex = select.selectedIndex;
    const newIndex = currentIndex + direction;
    
    if (newIndex >= 0 && newIndex < select.options.length) {
        select.selectedIndex = newIndex;
        updateProgress(select);
    }
}

function updateProgress(select) {
    const container = select.closest('td');
    if (!container) return;
    
    const progressSpan = container.querySelector('.progresso-stato');
    if (!progressSpan) return;
    
    const selectedIndex = select.selectedIndex + 1;
    const totalOptions = select.options.length;
    
    progressSpan.textContent = `${selectedIndex}/${totalOptions}`;
}

// Close mobile menu when clicking outside
document.addEventListener('click', (e) => {
    const menu = document.getElementById('menu');
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (menu && menuToggle) {
        if (menu.classList.contains('active') && 
            !menu.contains(e.target) && 
            !menuToggle.contains(e.target)) {
            menu.classList.remove('active');
        }
    }
});

// Prevent body scroll when mobile menu is open
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            const menu = document.getElementById('menu');
            if (menu && menu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    });
});



const menuElement = document.getElementById('menu');
if (menuElement) {
    observer.observe(menuElement, { attributes: true });
}

document.addEventListener('DOMContentLoaded', () => {
    const menu = document.getElementById('menu');
    
    // Se il menu esiste, attiviamo i listener
    if (menu) {
        // Selezioniamo tutti i link (comprese le icone per ili mobile)
        const menuLinks = menu.querySelectorAll('a, button');

        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (menu.classList.contains('active')) {
                    toggleMenu();
                }
            });
        });

        if (menuLinks.length > 0) {
            const lastLink = menuLinks[menuLinks.length - 1];
            
            lastLink.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' && !e.shiftKey && menu.classList.contains('active')) {
                    toggleMenu(); 
                }
            });
        }
    }

    document.addEventListener('keydown', function(e) {
        const menu = document.getElementById('menu');
        if (e.key === 'Escape' && menu && menu.classList.contains('active')) {
            toggleMenu();
            const btn = document.querySelector('.menu-toggle');
            if(btn) btn.focus();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const deleteForm = document.querySelector('.form-eliminazione-account');
    
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const conferma = confirm('Sei sicuro di voler eliminare il tuo account? Questa azione è irreversibile e cancellerà tutti i tuoi dati.');
            if (!conferma) {
                e.preventDefault();
            }
        });
    }
});


// FORM LATO CLIENT ------------------------------------

document.addEventListener('DOMContentLoaded', () => {

    // regole di validazione
    const Validators = {
        email: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val),
        nomeCognome: (val) => /^[a-zA-ZÀ-ÿ\s]+$/u.test(val),
        telefono: (val) => /^\d{10}$/.test(val),
        password: (val) => val.length >= 8 && /[A-Z]/.test(val) && /[a-z]/.test(val) && /\d/.test(val) && /[\W_]/.test(val),
        prezzo: (val) => val !== '' && parseFloat(val) >= 0,
        descrizione: (val) => val.trim().length > 0 && val.trim().length <= 255,
        testoBreve: (val) => val.trim().length > 0 && val.trim().length <= 30,
        file: (input) => {
            if (input.files.length > 0) {
                const f = input.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
                return validTypes.includes(f.type) && f.size <= 1048576; // 1MB
            }
            return !input.hasAttribute('required');
        }
    };

    // creazione e gestione dei meassaggi di errore
    const UI = {
        show: (input, msg) => {
            const errorId = 'err-' + (input.id || input.name); 
            let errorSpan = document.getElementById(errorId);

            if (!errorSpan) {
                errorSpan = document.createElement('span');
                errorSpan.id = errorId;
                errorSpan.className = 'error-message-js';
                errorSpan.setAttribute('role', 'alert');

                const parent = input.parentNode.classList.contains('password-wrapper') 
                               ? input.parentNode.parentNode 
                               : input.parentNode;
                
                parent.appendChild(errorSpan);
            }

            errorSpan.textContent = msg;
            input.setAttribute('aria-invalid', 'true');
            
            const currentDescribedBy = input.getAttribute('aria-describedby') || '';
            if (!currentDescribedBy.includes(errorId)) {
                input.setAttribute('aria-describedby', (currentDescribedBy + ' ' + errorId).trim());
            }
        },
        
        reset: (input) => {
            const errorId = 'err-' + (input.id || input.name);
            const errorSpan = document.getElementById(errorId);

            if (errorSpan) {
                errorSpan.remove();
            }
            
            input.removeAttribute('aria-invalid');

            const currentDescribedBy = input.getAttribute('aria-describedby') || '';
            if (currentDescribedBy.includes(errorId)) {
                const newDescribedBy = currentDescribedBy.replace(errorId, '').trim();
                if (newDescribedBy) {
                    input.setAttribute('aria-describedby', newDescribedBy);
                } else {
                    input.removeAttribute('aria-describedby');
                }
            }
        }
    };

    // funzione di validazione per ogni campo
    const validateField = (input) => {
        const val = input.value.trim();
        const name = input.name || input.id; 
        
        UI.reset(input); 

        // required check
        if (input.type !== 'file' && input.hasAttribute('required') && val === '') {
            UI.show(input, 'Questo campo è obbligatorio.');
            return false;
        }

        let isValid = true;
        let errorMsg = '';

        // controlli specifici per campo
        switch (name) {
            case 'email':
                // see siamo nel LOGIN, saltiamo il controllo email strict per permettere l'uso di "admin" o "user"
                if (document.getElementById('formLogin')) break;

                if (val !== '' && !Validators.email(val)) { 
                    isValid = false; 
                    errorMsg = 'Formato email non valido (es. nome@esempio.it).'; 
                }
                break;
                
            case 'nome':
            case 'cognome':
                if (document.getElementById('formAggiungiProdotto') && name === 'nome') {
                    if (!Validators.testoBreve(val)) { isValid = false; errorMsg = 'Massimo 30 caratteri.'; }
                } else {
                    if (val !== '' && !Validators.nomeCognome(val)) { isValid = false; errorMsg = 'Sono ammessi solo lettere e spazi.'; }
                }
                break;

            case 'telefono':
                if (val !== '' && !Validators.telefono(val)) { 
                    isValid = false; 
                    errorMsg = 'Il numero di telefono deve essere da 10 cifre numeriche, senza spazi, trattini o prefissi.'; 
                }
                break;

            case 'password':
                if (document.getElementById('formLogin')) break;
                
                if (!Validators.password(val)) { 
                    isValid = false; 
                    errorMsg = 'La password deve contenere almeno 8 caratteri di cui: una lettera maiuscola, una minuscola, un numero e un simbolo speciale (es. @, #, !).'; 
                }
                break;

            case 'nuova_password':
                if (val !== '' && !Validators.password(val)) { 
                    isValid = false; 
                    errorMsg = 'La password deve contenere almeno 8 caratteri, una lettera maiuscola, una minuscola, un numero e un simbolo speciale.'; 
                }
                break;

            case 'prezzo':
                if (!Validators.prezzo(val)) { isValid = false; errorMsg = 'Inserire un prezzo valido (es. 10.50).'; }
                break;

            case 'descrizione':
            case 'testoAlternativo':
            case 'messaggio': 
                if (!Validators.descrizione(val)) { isValid = false; errorMsg = 'Il testo deve essere compreso tra 1 e 255 caratteri.'; }
                break;

            case 'immagine':
                if (input.files.length === 0) {
                    if (input.hasAttribute('required')) {
                        isValid = false;
                        errorMsg = 'Questo campo è obbligatorio.';
                    }
                } 
                else if (!Validators.file(input)) { 
                    isValid = false; 
                    const f = input.files[0];
                    const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    
                    if (!validTypes.includes(f.type)) {
                        errorMsg = 'Formato non valido (sono ammessi solo JPG, PNG, WEBP).';
                    } else if (f.size > 1048576) {
                        errorMsg = 'Il file è troppo grande (Massimo 1MB).';
                    }
                }
                break;
        }

        if (!isValid) {
            UI.show(input, errorMsg);
            return false;
        }

        return true;
    };

    // attivatore della validazione sui form
    const setupFormValidation = (selector) => {
        const form = selector.startsWith('.') || selector.startsWith('#') 
                     ? document.querySelector(selector) 
                     : document.getElementById(selector);
        
        if (!form) return;

        const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');

        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => UI.reset(input));
        });

        form.addEventListener('submit', (e) => {
            let formValid = true;
            inputs.forEach(input => {
                if (!validateField(input)) formValid = false;
            });

            if (!formValid) {
                e.preventDefault();
                const firstError = form.querySelector('[aria-invalid="true"]');
                if (firstError) firstError.focus();
            }
        });
    };

    // INIZIALIZZAZIONE
    setupFormValidation('formRegistrazione');
    setupFormValidation('formLogin');          
    setupFormValidation('.profile-form');      
    setupFormValidation('formAggiungiProdotto'); 
    setupFormValidation('formContattaci'); 

});