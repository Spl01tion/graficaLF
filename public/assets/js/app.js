/* ============================================================
   Gráfica Lifei — JavaScript do site (vanilla)
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    // Token CSRF partilhado (colocado num <meta> pelo header).
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ROOT = document.querySelector('meta[name="app-root"]')?.content || '';

    // ---- Auto-esconder toasts ----
    document.querySelectorAll('.toast.show').forEach((t) => {
        setTimeout(() => bootstrap.Toast.getOrCreateInstance(t).hide(), 4000);
    });

    // ---- Navbar em formato "pill" ao fazer scroll ----
    const navbar = document.getElementById('navbarPrincipal');
    const navMenu = document.getElementById('navMenu');
    if (navbar) {
        const aoScrollar = () => navbar.classList.toggle('navbar-pill', window.scrollY > 60);
        aoScrollar();
        window.addEventListener('scroll', aoScrollar, { passive: true });
    }
    // Enquanto o menu hamburger (offcanvas do navbar) está aberto, suspende o formato pill.
    if (navbar && navMenu) {
        navMenu.addEventListener('show.bs.collapse', () => navbar.classList.add('menu-aberto'));
        navMenu.addEventListener('hidden.bs.collapse', () => navbar.classList.remove('menu-aberto'));
    }

    // ---- Revelar ao fazer scroll ----
    const alvos = document.querySelectorAll('[data-revelar]');
    if (alvos.length) {
        const obs = new IntersectionObserver((entradas) => {
            entradas.forEach((e) => {
                if (e.isIntersecting) { e.target.classList.add('revelado'); obs.unobserve(e.target); }
            });
        }, { threshold: 0.12 });
        alvos.forEach((el) => obs.observe(el));
    }

    // ---- Contadores animados (faixa de números da homepage) ----
    const contadores = document.querySelectorAll('[data-contador]');
    if (contadores.length) {
        const semAnimacao = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const contar = (el) => {
            const alvo = parseInt(el.dataset.contador, 10) || 0;
            if (semAnimacao) { el.textContent = alvo.toLocaleString('pt-PT'); return; }

            const duracao = 1600;
            const inicio = performance.now();
            const passo = (agora) => {
                const p = Math.min((agora - inicio) / duracao, 1);
                // easing "ease-out" — trava perto do fim, fica mais natural.
                const valor = Math.round(alvo * (1 - Math.pow(1 - p, 3)));
                el.textContent = valor.toLocaleString('pt-PT');
                if (p < 1) requestAnimationFrame(passo);
            };
            requestAnimationFrame(passo);
        };

        const obsNum = new IntersectionObserver((entradas) => {
            entradas.forEach((e) => {
                if (e.isIntersecting) { contar(e.target); obsNum.unobserve(e.target); }
            });
        }, { threshold: 0.5 });
        contadores.forEach((el) => obsNum.observe(el));
    }

    // ---- Copiar código de cupão ----
    document.body.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('[data-copiar]');
        if (!btn) return;

        try {
            await navigator.clipboard.writeText(btn.dataset.copiar);
            toast('Código copiado: ' + btn.dataset.copiar, 'dark');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-check2 ms-2';
                setTimeout(() => { icon.className = 'bi bi-clipboard ms-2'; }, 2000);
            }
        } catch (e) {
            toast('Não foi possível copiar. Código: ' + btn.dataset.copiar, 'danger');
        }
    });

    // Mostra um toast dinâmico.
    function toast(mensagem, tipo = 'dark') {
        let cont = document.querySelector('.toast-container');
        if (!cont) {
            cont = document.createElement('div');
            cont.className = 'toast-container position-fixed top-0 end-0 p-3';
            cont.style.zIndex = '1200';
            document.body.appendChild(cont);
        }
        const el = document.createElement('div');
        el.className = `toast align-items-center text-bg-${tipo} border-0 show`;
        el.innerHTML = `<div class="d-flex"><div class="toast-body">${mensagem}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        cont.appendChild(el);
        setTimeout(() => bootstrap.Toast.getOrCreateInstance(el).hide(), 3500);
    }

    // Actualiza um badge de contador (carrinho/wishlist).
    function setBadge(nome, valor) {
        const badge = document.querySelector(`[data-badge="${nome}"]`);
        if (!badge) return;
        badge.textContent = valor;
        badge.hidden = (valor <= 0);
    }

    // ---- Adicionar ao carrinho (cards e página de produto) ----
    document.body.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.btn-add-carrinho');
        if (!btn) return;
        ev.preventDefault();

        const dados = new URLSearchParams();
        dados.append('_token', csrf);
        dados.append('id_produto', btn.dataset.produto);
        dados.append('quantidade', btn.dataset.quantidade || '1');
        // Opções escolhidas (na página de produto), enviadas como opcoes[]
        document.querySelectorAll('.opcao-produto:checked').forEach((o) => dados.append('opcoes[]', o.value));

        btn.disabled = true;
        try {
            const r = await fetch(`${ROOT}/carrinho?acao=adicionar`, {
                method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: dados,
            });
            const j = await r.json();
            if (j.ok) {
                setBadge('carrinho', j.contador);
                document.getElementById('corpo-carrinho')?.replaceWith(
                    new DOMParser().parseFromString(j.offcanvas, 'text/html').getElementById('corpo-carrinho')
                );
                toast(j.mensagem, 'dark');
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('offcanvasCarrinho')).show();
            } else {
                toast(j.mensagem || 'Erro ao adicionar.', 'danger');
            }
        } catch (e) {
            toast('Erro de ligação.', 'danger');
        } finally {
            btn.disabled = false;
        }
    });

    // ---- Remover item do carrinho (dentro do offcanvas) ----
    document.body.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.btn-remover-item');
        if (!btn) return;
        ev.preventDefault();

        const dados = new URLSearchParams();
        dados.append('_token', csrf);
        dados.append('chave', btn.dataset.chave);

        const r = await fetch(`${ROOT}/carrinho?acao=remover`, {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: dados,
        });
        const j = await r.json();
        if (j.ok) {
            setBadge('carrinho', j.contador);
            document.getElementById('corpo-carrinho')?.replaceWith(
                new DOMParser().parseFromString(j.offcanvas, 'text/html').getElementById('corpo-carrinho')
            );
        }
    });

    // ---- Toggle da wishlist ----
    document.body.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.btn-wishlist');
        if (!btn) return;
        ev.preventDefault();

        const dados = new URLSearchParams();
        dados.append('_token', csrf);
        dados.append('id_produto', btn.dataset.produto);

        const r = await fetch(`${ROOT}/wishlist?acao=toggle`, {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: dados,
        });
        const j = await r.json();
        if (j.ok) {
            setBadge('wishlist', j.contador);
            const icon = btn.querySelector('i');
            btn.classList.toggle('ativo', j.adicionado);
            btn.setAttribute('aria-pressed', j.adicionado ? 'true' : 'false');
            icon.className = j.adicionado ? 'bi bi-heart-fill text-primary' : 'bi bi-heart';
            toast(j.adicionado ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.', 'dark');
        }
    });
});
