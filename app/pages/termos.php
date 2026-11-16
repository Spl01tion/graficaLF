<?php

/**
 * termos.php — Termos & Condições de utilização do site e dos serviços.
 * O layout (cabeçalho, índice, navegação legal) está em partials/pagina-legal.php.
 */

$titulo     = 'Termos & Condições';
$descricao  = 'Termos e condições de utilização do site e dos serviços de impressão e design da Gráfica Lifei.';
$subtitulo  = 'As regras que orientam a utilização do nosso site e dos nossos serviços.';
$atualizado = '16 de Novembro de 2026';

$app = e(APP_NAME);

$seccoes = [
    [
        'id' => 'aceitacao',
        'titulo' => 'Aceitação dos termos',
        'html' => "<p>Ao aceder ou utilizar o site da {$app}, criar uma conta ou fazer um pedido, declara que leu, compreendeu e aceita estes Termos &amp; Condições. Se não concordar com algum ponto, pedimos que não utilize o site.</p>
                   <p>Podemos actualizar estes termos a qualquer momento. A versão em vigor é sempre a publicada nesta página, com a data da última actualização indicada no topo.</p>",
    ],
    [
        'id' => 'servicos',
        'titulo' => 'Serviços prestados',
        'html' => "<p>A {$app} presta serviços de impressão (cartões de visita, flyers, banners, roll-ups, catálogos, stickers, vestuário personalizado, entre outros) e de design gráfico e branding, em Maputo, Moçambique.</p>
                   <p>As imagens dos produtos no site são ilustrativas. Podem existir ligeiras diferenças de cor entre o que vê no ecrã e o produto impresso, devido às calibrações de cada monitor e às características dos materiais.</p>",
    ],
    [
        'id' => 'conta',
        'titulo' => 'Conta de utilizador',
        'html' => "<ul>
                       <li>Os dados fornecidos no registo devem ser verdadeiros, completos e mantidos actualizados.</li>
                       <li>É responsável por manter a sua palavra-passe em segredo e por toda a actividade feita na sua conta.</li>
                       <li>Deve avisar-nos de imediato se suspeitar de uso não autorizado da sua conta.</li>
                       <li>Podemos suspender ou encerrar contas que violem estes termos ou sejam usadas de forma fraudulenta.</li>
                   </ul>",
    ],
    [
        'id' => 'pedidos',
        'titulo' => 'Pedidos e orçamentos',
        'html' => "<p>Os pedidos feitos através do site <strong>não são pagos online</strong>. Ao submeter um pedido, recebe uma referência e a nossa equipa entra em contacto para confirmar os detalhes, os prazos e a forma de pagamento.</p>
                   <ul>
                       <li>O pedido só se considera aceite depois de confirmado pela {$app}.</li>
                       <li>Trabalhos personalizados podem exigir aprovação de uma prova (digital ou física) antes da produção.</li>
                       <li>Depois de aprovada a prova, erros de texto, ortografia ou imagem que constem nela são da responsabilidade do cliente.</li>
                       <li>Podemos recusar pedidos cujo conteúdo seja ilegal, ofensivo ou que viole direitos de terceiros.</li>
                   </ul>",
    ],
    [
        'id' => 'precos',
        'titulo' => 'Preços e pagamento',
        'html' => "<p>Os preços são apresentados em Meticais (MZN) e podem ser alterados sem aviso prévio. O valor aplicável é o confirmado no momento da aceitação do pedido.</p>
                   <p>Cupões de desconto são válidos apenas dentro das condições e datas indicadas, não são acumuláveis (salvo indicação em contrário) e não podem ser trocados por dinheiro.</p>
                   <p>Para trabalhos de maior volume ou personalizados, poderá ser pedido um adiantamento antes do início da produção.</p>",
    ],
    [
        'id' => 'entrega',
        'titulo' => 'Prazos, entrega e levantamento',
        'html' => "<p>Os prazos indicados são estimativas e começam a contar após a aprovação final da arte e, quando aplicável, do pagamento do adiantamento.</p>
                   <ul>
                       <li><strong>Levantamento:</strong> o cliente é avisado quando o trabalho estiver pronto para levantar na loja.</li>
                       <li><strong>Entrega:</strong> feita no endereço indicado no pedido. Custos e prazos de entrega são confirmados pela nossa equipa.</li>
                   </ul>
                   <p>Não nos responsabilizamos por atrasos causados por informação incorrecta, ausência do cliente no local de entrega ou motivos de força maior.</p>",
    ],
    [
        'id' => 'ficheiros',
        'titulo' => 'Ficheiros e direitos de autor',
        'html' => "<p>Ao enviar logótipos, imagens, textos ou outros ficheiros para impressão, o cliente garante que é titular dos direitos ou que tem autorização para os utilizar. A {$app} não se responsabiliza por violações de direitos de autor ou de marca nos materiais fornecidos pelo cliente.</p>
                   <p>Os trabalhos de design criados pela nossa equipa passam a ser do cliente após o pagamento integral, salvo acordo diferente por escrito. Reservamo-nos o direito de mostrar trabalhos realizados no nosso portfólio, excepto se o cliente pedir o contrário.</p>",
    ],
    [
        'id' => 'reclamacoes',
        'titulo' => 'Reclamações e devoluções',
        'html' => "<p>Por se tratar de produtos personalizados, não aceitamos devoluções por arrependimento. Em caso de defeito de produção da nossa responsabilidade, o cliente deve contactar-nos no prazo de <strong>5 dias úteis</strong> após o levantamento ou a entrega, com fotografias do problema.</p>
                   <p>Confirmado o defeito, procedemos à reimpressão do trabalho ou, não sendo possível, ao reembolso do valor correspondente.</p>",
    ],
    [
        'id' => 'responsabilidade',
        'titulo' => 'Limitação de responsabilidade',
        'html' => "<p>Fazemos o possível para manter o site disponível e com informação correcta, mas não garantimos que esteja livre de erros ou interrupções. A responsabilidade da {$app} por qualquer pedido fica limitada ao valor pago por esse pedido.</p>",
    ],
    [
        'id' => 'lei',
        'titulo' => 'Lei aplicável',
        'html' => "<p>Estes termos regem-se pela lei da República de Moçambique. Qualquer litígio será, sempre que possível, resolvido de forma amigável; não sendo possível, é competente o foro da cidade de Maputo.</p>",
    ],
];

require __DIR__ . '/partials/pagina-legal.php';
