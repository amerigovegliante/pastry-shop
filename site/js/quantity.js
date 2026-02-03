document.addEventListener("DOMContentLoaded", () => {
if (document.body.dataset.page !== "dettagli") return;

const inputQty = document.getElementById("quantita");
const btnPiu = document.querySelector(".btn-qty-piu");
const btnMeno = document.querySelector(".btn-qty-meno");

if (inputQty && btnPiu && btnMeno) {
    btnPiu.addEventListener("click", () => {
        const max = parseInt(inputQty.max) || 10;
        let val = parseInt(inputQty.value);
        if (val < max) inputQty.value = val + 1;
    });

    btnMeno.addEventListener("click", () => {
        const min = parseInt(inputQty.min) || 1;
        let val = parseInt(inputQty.value);
        if (val > min) inputQty.value = val - 1;
    });
}
});

