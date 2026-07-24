/* ============================================================
   Gráfica Lifei — JavaScript do site (vanilla)
   ------------------------------------------------------------
   Interatividade leve. A lógica pesada (carrinho, wishlist)
   entra nos Módulos 5 e 6; por agora, tratamos do essencial.
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    // ---- Auto-esconder os toasts de notificação ao fim de 4s ----
    document.querySelectorAll('.toast.show').forEach((toast) => {
        setTimeout(() => {
            bootstrap.Toast.getOrCreateInstance(toast).hide();
        }, 4000);
    });

    // ---- Revelar secções ao fazer scroll (micro-animação) ----
    const alvos = document.querySelectorAll('[data-revelar]');
    if (alvos.length) {
        const observador = new IntersectionObserver((entradas) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('revelado');
                    observador.unobserve(entrada.target);
                }
            });
        }, { threshold: 0.12 });

        alvos.forEach((el) => observador.observe(el));
    }
});
