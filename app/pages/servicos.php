<?php

/**
 * servicos.php — Página de serviços.
 *
 * Secções alternadas (imagem/texto) com o detalhe de cada serviço,
 * o processo de trabalho, acabamentos, extras e FAQ.
 */

$servicos = [
    [
        'slug'   => 'impressao-geral',
        'titulo' => 'Impressão geral',
        'texto'  => 'Cartões de visita, flyers, cartazes, catálogos, calendários e muito mais — impressão a cores de alta qualidade em vários formatos e gramagens.',
        'img'    => 'https://images.unsplash.com/photo-1503694978374-8a2fa686963a?w=700&q=80',
        'icon'   => 'bi-printer',
        'prazo'  => '24 a 48 horas',
        'inclui' => [
            'Papéis de 90g a 350g, mate ou brilhante',
            'Impressão digital e offset conforme a tiragem',
            'Corte, vinco e dobragem incluídos',
        ],
        'tags'   => ['Cartões de visita', 'Flyers', 'Cartazes', 'Calendários', 'Postais'],
    ],
    [
        'slug'   => 'banners',
        'titulo' => 'Design de Banner & Impressão',
        'texto'  => 'Banners em lona, roll-ups e telas de grande formato para eventos, feiras e pontos de venda. Do conceito à instalação.',
        'img'    => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=700&q=80',
        'icon'   => 'bi-easel',
        'prazo'  => '2 a 4 dias',
        'inclui' => [
            'Lona 440g resistente ao sol e à chuva',
            'Ilhoses, bainhas ou estrutura roll-up',
            'Apoio na montagem em Maputo',
        ],
        'tags'   => ['Banners', 'Roll-ups', 'Bandeiras', 'Telas', 'Backdrops'],
    ],
    [
        'slug'   => 'livros',
        'titulo' => 'Impressão de Capas de Livros',
        'texto'  => 'Capas duras e moles, encadernação profissional e acabamentos de luxo para livros, teses, portfólios e edições especiais.',
        'img'    => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=700&q=80',
        'icon'   => 'bi-book',
        'prazo'  => '3 a 5 dias',
        'inclui' => [
            'Capa dura, mole ou espiral',
            'Laminação mate, brilhante ou soft-touch',
            'Paginação e revisão do ficheiro final',
        ],
        'tags'   => ['Livros', 'Teses', 'Portfólios', 'Catálogos', 'Manuais'],
    ],
    [
        'slug'   => 'design',
        'titulo' => 'Serviços de Design',
        'texto'  => 'A nossa equipa criativa desenvolve a sua arte: logótipos, identidade visual, layouts para impressão e materiais promocionais.',
        'img'    => 'https://images.unsplash.com/photo-1524234107056-1c1f48f64ab8?w=700&q=80',
        'icon'   => 'bi-palette',
        'prazo'  => '2 a 7 dias',
        'inclui' => [
            'Duas propostas iniciais e revisões incluídas',
            'Ficheiros abertos e prontos para impressão',
            'Adaptação para redes sociais',
        ],
        'tags'   => ['Logótipos', 'Identidade visual', 'Layouts', 'Menus', 'Redes sociais'],
    ],
    [
        'slug'   => 'branding',
        'titulo' => 'Estratégias de Branding',
        'texto'  => 'Ajudamos a construir e a fortalecer a sua marca — do manual de identidade à aplicação consistente em todos os materiais.',
        'img'    => 'https://images.unsplash.com/photo-1611926653458-09294b3142bf?w=700&q=80',
        'icon'   => 'bi-stars',
        'prazo'  => 'Sob consulta',
        'inclui' => [
            'Manual de identidade e paleta de cores',
            'Aplicação em papelaria, frota e fardamento',
            'Acompanhamento contínuo da marca',
        ],
        'tags'   => ['Manual de marca', 'Papelaria', 'Fardamento', 'Sinalética'],
    ],
];

$titulo    = 'Serviços';
$descricao = 'Serviços de impressão e design da Gráfica Lifei: impressão geral, banners, capas de livros, design e branding.';
require __DIR__ . '/partials/header.php';
?>

<!-- ============ CABEÇALHO ============ -->
<section class="page-header text-white py-5">
    <div class="container py-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="etiqueta-seccao etiqueta-clara">O que fazemos</span>
                <h1 class="fw-bold mt-3 mb-2">Os nossos serviços</h1>
                <p class="text-white-50 mb-4" style="max-width: 560px;">
                    Da ideia ao produto final: imprimimos, desenhamos e acompanhamos cada
                    projecto até estar nas suas mãos — tudo no mesmo sítio.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?= url('contacto') ?>" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-chat-dots me-1"></i>Pedir orçamento
                    </a>
                    <a href="<?= url('shop') ?>" class="btn btn-outline-light btn-lg px-4">Ver a loja</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="row g-3">
                    <?php
                    $promessas = [
                        ['icon' => 'bi-clock-history', 'texto' => 'Orçamento respondido em 24h'],
                        ['icon' => 'bi-brush',         'texto' => 'Design incluído no serviço'],
                        ['icon' => 'bi-eye',           'texto' => 'Prova digital antes de imprimir'],
                        ['icon' => 'bi-truck',         'texto' => 'Entrega em Maputo e arredores'],
                    ];
                    foreach ($promessas as $p): ?>
                        <div class="col-12">
                            <div class="promessa d-flex align-items-center gap-3">
                                <i class="bi <?= $p['icon'] ?> text-warning fs-5"></i>
                                <span class="small"><?= e($p['texto']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ ÍNDICE RÁPIDO ============ -->
<nav class="nav-servicos" aria-label="Índice dos serviços">
    <div class="container">
        <div class="nav-servicos__lista">
            <?php foreach ($servicos as $s): ?>
                <a href="#<?= e($s['slug']) ?>">
                    <i class="bi <?= $s['icon'] ?>"></i><?= e($s['titulo']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<!-- ============ SECÇÕES ALTERNADAS ============ -->
<section class="py-5">
    <div class="container py-4">
        <?php foreach ($servicos as $i => $s): ?>
            <div class="row servico-bloco align-items-center g-4 g-lg-5 mb-5 pb-lg-4 flex-lg-row<?= $i % 2 ? '-reverse' : '' ?>"
                 id="<?= e($s['slug']) ?>" data-revelar>
                <div class="col-lg-6">
                    <div class="servico-moldura">
                        <img src="<?= e($s['img']) ?>" alt="<?= e($s['titulo']) ?>" class="servico-img shadow-sm" loading="lazy">
                        <span class="servico-numero"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="servico-prazo">
                            <i class="bi bi-clock me-1"></i><?= e($s['prazo']) ?>
                        </span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <span class="icone-circulo mb-3"><i class="bi <?= $s['icon'] ?>"></i></span>
                    <h2 class="fw-bold"><?= e($s['titulo']) ?></h2>
                    <p class="text-muted"><?= e($s['texto']) ?></p>

                    <ul class="lista-check mb-4">
                        <?php foreach ($s['inclui'] as $item): ?>
                            <li><i class="bi bi-check2-circle"></i><span><?= e($item) ?></span></li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php foreach ($s['tags'] as $t): ?>
                            <span class="chip"><?= e($t) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= url('contacto') ?>" class="btn btn-primary">
                            <i class="bi bi-chat-dots me-1"></i>Pedir orçamento
                        </a>
                        <a href="<?= url('shop') ?>" class="btn btn-outline-secondary">Ver produtos</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ PROCESSO DE TRABALHO ============ -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5" data-revelar>
            <span class="etiqueta-seccao">Como trabalhamos</span>
            <h2 class="fw-bold mt-2 mb-1">Do pedido à entrega em 5 passos</h2>
            <p class="text-muted mb-0">Sem surpresas: sabe sempre em que ponto está o seu trabalho.</p>
        </div>

        <div class="processo">
            <?php
            $passos = [
                ['icon' => 'bi-chat-square-text', 'titulo' => 'Pedido',    'texto' => 'Conta-nos o que precisa por formulário, telefone ou WhatsApp.'],
                ['icon' => 'bi-calculator',       'titulo' => 'Orçamento', 'texto' => 'Respondemos em até 24h com preço, prazo e materiais.'],
                ['icon' => 'bi-brush',            'titulo' => 'Arte',      'texto' => 'Envia a sua arte ou a nossa equipa desenha por si.'],
                ['icon' => 'bi-eye',              'titulo' => 'Prova',     'texto' => 'Aprova a prova digital antes de qualquer impressão.'],
                ['icon' => 'bi-box-seam',         'titulo' => 'Entrega',   'texto' => 'Imprimimos, acabamos e entregamos onde precisar.'],
            ];
            foreach ($passos as $i => $p): ?>
                <div class="processo__passo" data-revelar>
                    <span class="processo__marcador"><i class="bi <?= $p['icon'] ?>"></i></span>
                    <span class="processo__n">Passo <?= $i + 1 ?></span>
                    <h3 class="h6 fw-bold mb-1"><?= e($p['titulo']) ?></h3>
                    <p class="text-muted small mb-0"><?= e($p['texto']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ ACABAMENTOS E MATERIAIS ============ -->
<section class="py-5">
    <div class="container py-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5" data-revelar>
                <span class="etiqueta-seccao">Acabamentos</span>
                <h2 class="fw-bold mt-2 mb-3">Os detalhes que fazem a diferença</h2>
                <p class="text-muted">
                    O acabamento certo transforma um impresso comum num material que as pessoas
                    guardam. Se não souber o que escolher, aconselhamos — faz parte do serviço.
                </p>
                <a href="<?= url('contacto') ?>" class="btn btn-outline-primary mt-2">Falar com um consultor</a>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <?php
                    $acabamentos = [
                        ['icon' => 'bi-droplet-half', 'titulo' => 'Verniz UV',      'texto' => 'Brilho localizado que realça logótipos.'],
                        ['icon' => 'bi-layers',       'titulo' => 'Laminação',      'texto' => 'Mate, brilhante ou soft-touch.'],
                        ['icon' => 'bi-scissors',     'titulo' => 'Corte especial', 'texto' => 'Formatos à medida e cantos redondos.'],
                        ['icon' => 'bi-gem',          'titulo' => 'Douramento',     'texto' => 'Foil dourado ou prateado a quente.'],
                        ['icon' => 'bi-vector-pen',   'titulo' => 'Relevo',         'texto' => 'Alto e baixo relevo para toque premium.'],
                        ['icon' => 'bi-recycle',      'titulo' => 'Papel reciclado', 'texto' => 'Opções ecológicas certificadas.'],
                    ];
                    foreach ($acabamentos as $a): ?>
                        <div class="col-sm-6" data-revelar>
                            <div class="card border-0 shadow-sm h-100 card-acabamento">
                                <div class="card-body d-flex gap-3 p-3">
                                    <i class="bi <?= $a['icon'] ?> fs-4 text-primary"></i>
                                    <div>
                                        <h3 class="h6 fw-bold mb-1"><?= e($a['titulo']) ?></h3>
                                        <p class="text-muted small mb-0"><?= e($a['texto']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ SERVIÇOS COMPLEMENTARES ============ -->
<section class="py-5 bg-dark-blue text-white">
    <div class="container py-4">
        <div class="text-center mb-5" data-revelar>
            <span class="etiqueta-seccao etiqueta-clara">Também fazemos</span>
            <h2 class="fw-bold mt-3 mb-1">Serviços complementares</h2>
            <p class="text-white-50 mb-0">Pequenos trabalhos que resolvemos no mesmo dia.</p>
        </div>
        <div class="row g-3">
            <?php
            $extras = [
                ['icon' => 'bi-file-earmark-text', 'nome' => 'Fotocópias e digitalização'],
                ['icon' => 'bi-person-vcard',      'nome' => 'Crachás e cartões de acesso'],
                ['icon' => 'bi-sticky',            'nome' => 'Autocolantes e vinil de corte'],
                ['icon' => 'bi-cup-hot',           'nome' => 'Canecas e brindes'],
                ['icon' => 'bi-award',             'nome' => 'Carimbos personalizados'],
                ['icon' => 'bi-tags',              'nome' => 'Estampagem de vestuário'],
                ['icon' => 'bi-signpost-2',        'nome' => 'Sinalética e placas'],
                ['icon' => 'bi-envelope-paper',    'nome' => 'Convites e envelopes'],
            ];
            foreach ($extras as $x): ?>
                <div class="col-6 col-lg-3" data-revelar>
                    <div class="extra-item h-100">
                        <i class="bi <?= $x['icon'] ?>"></i>
                        <span><?= e($x['nome']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ PERGUNTAS FREQUENTES ============ -->
<section class="py-5">
    <div class="container py-4">
        <div class="row g-5">
            <div class="col-lg-4" data-revelar>
                <span class="etiqueta-seccao">Dúvidas</span>
                <h2 class="fw-bold mt-2 mb-3">Perguntas frequentes</h2>
                <p class="text-muted">
                    Não encontrou a sua pergunta? Escreva-nos — respondemos no mesmo dia útil.
                </p>
                <a href="<?= url('contacto') ?>" class="btn btn-primary">Fazer uma pergunta</a>
            </div>
            <div class="col-lg-8" data-revelar>
                <div class="accordion accordion-flush accordion-faq" id="faqServicos">
                    <?php
                    $faq = [
                        ['p' => 'Em que formato devo enviar os meus ficheiros?',
                         'r' => 'O ideal é PDF em alta resolução (300 dpi) com 3 mm de sangria e as fontes convertidas em curvas. Também aceitamos AI, PSD, CDR e JPG — se o ficheiro tiver algum problema, avisamos antes de imprimir.'],
                        ['p' => 'E se eu não tiver nenhuma arte pronta?',
                         'r' => 'Sem problema. A nossa equipa de design cria a arte a partir da sua ideia, do seu logótipo ou até de um simples rascunho. O design está incluído na maioria dos serviços.'],
                        ['p' => 'Qual é a quantidade mínima de encomenda?',
                         'r' => 'Depende do produto. Em impressão digital fazemos a partir de uma unidade; em offset compensa a partir de 500 unidades. Dizemos sempre qual a opção mais económica para a sua tiragem.'],
                        ['p' => 'Quanto tempo demora um trabalho?',
                         'r' => 'A maioria dos trabalhos de impressão fica pronta em 24 a 48 horas após a aprovação da prova. Grandes formatos e acabamentos especiais podem levar 3 a 5 dias.'],
                        ['p' => 'Fazem entregas fora de Maputo?',
                         'r' => 'Sim. Entregamos em Maputo e Matola e enviamos para as restantes províncias através de transportadora, com o custo indicado no orçamento.'],
                        ['p' => 'Como funciona o pagamento?',
                         'r' => 'Trabalhamos com transferência bancária, M-Pesa e numerário. Em encomendas grandes pedimos 50% no início e o restante na entrega.'],
                    ];
                    foreach ($faq as $i => $f): ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>"
                                        aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="faq<?= $i ?>">
                                    <?= e($f['p']) ?>
                                </button>
                            </h3>
                            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqServicos">
                                <div class="accordion-body text-muted"><?= e($f['r']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CTA FINAL ============ -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="cta-servicos text-center text-white p-4 p-lg-5" data-revelar>
            <h2 class="fw-bold mb-2">Não encontrou o que procura?</h2>
            <p class="text-white-50 mb-4">
                Fazemos trabalhos personalizados. Conte-nos a sua ideia e nós dizemos como a imprimir.
            </p>
            <div class="d-flex gap-3 flex-wrap justify-content-center">
                <a href="<?= url('contacto') ?>" class="btn btn-light btn-lg px-4">
                    <i class="bi bi-envelope me-1"></i>Pedir orçamento
                </a>
                <a href="<?= e(config_get('whatsapp_url', '#')) ?>" class="btn btn-outline-light btn-lg px-4"
                   target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-1"></i>Falar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
