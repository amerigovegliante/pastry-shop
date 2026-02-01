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

// ====================================
// PASTICCERIA PADOVANA - MAIN SCRIPT
// ====================================

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
    if (menu) {
        menu.classList.toggle('active');
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