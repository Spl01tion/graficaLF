<?php

/**
 * privacidade.php — Política de Privacidade (que dados recolhemos e como os usamos).
 * O layout (cabeçalho, índice, navegação legal) está em partials/pagina-legal.php.
 */

$titulo     = 'Política de Privacidade';
$descricao  = 'Saiba que dados pessoais a Gráfica Lifei recolhe, para que fins os utiliza e quais são os seus direitos.';
$subtitulo  = 'Como recolhemos, usamos e protegemos os seus dados pessoais.';
$atualizado = '16 de Novembro de 2026';

$app   = e(APP_NAME);
$email = e(config_get('site_email', 'geral@graficalifei.co.mz'));

$seccoes = [
    [
        'id' => 'introducao',
        'titulo' => 'Introdução',
        'html' => "<p>A {$app} respeita a sua privacidade. Esta política explica que dados pessoais recolhemos quando utiliza o nosso site, para que os usamos e que direitos tem sobre eles. Ao utilizar o site, concorda com o tratamento dos seus dados nos termos aqui descritos.</p>",
    ],
    [
        'id' => 'dados',
        'titulo' => 'Dados que recolhemos',
        'html' => "<p>Recolhemos apenas os dados que nos fornece directamente:</p>
                   <div class=\"table-responsive\">
                       <table class=\"table table-bordered align-middle small\">
                           <thead class=\"table-light\"><tr><th>Quando</th><th>Dados</th></tr></thead>
                           <tbody>
                               <tr><td>Criação de conta</td><td>Nome, e-mail, telefone e palavra-passe (guardada cifrada)</td></tr>
                               <tr><td>Pedido</td><td>Nome, e-mail, telefone, tipo de entrega, endereço (se aplicável), observações e produtos escolhidos</td></tr>
                               <tr><td>Formulário de contacto</td><td>Nome, e-mail, telefone e a mensagem enviada</td></tr>
                           </tbody>
                       </table>
                   </div>
                   <p>Não recolhemos dados de pagamento através do site, já que os pedidos não são pagos online.</p>",
    ],
    [
        'id' => 'finalidades',
        'titulo' => 'Para que usamos os dados',
        'html' => "<ul>
                       <li>Processar, confirmar e entregar os seus pedidos;</li>
                       <li>Responder a pedidos de orçamento e mensagens;</li>
                       <li>Gerir a sua conta (acesso, histórico de pedidos, recuperação de palavra-passe);</li>
                       <li>Enviar e-mails relacionados com o serviço (confirmação de pedido, alterações de estado);</li>
                       <li>Cumprir obrigações legais e fiscais;</li>
                       <li>Proteger o site contra abusos e fraude.</li>
                   </ul>
                   <p>Não vendemos nem alugamos os seus dados a terceiros, e não os usamos para publicidade sem o seu consentimento.</p>",
    ],
    [
        'id' => 'cookies',
        'titulo' => 'Cookies',
        'html' => "<p>O site utiliza apenas <strong>cookies essenciais</strong> ao seu funcionamento:</p>
                   <ul>
                       <li><strong>Cookie de sessão</strong> — mantém a sessão iniciada, o carrinho e a lista de desejos enquanto navega;</li>
                       <li><strong>Protecção de formulários</strong> — um código de segurança associado à sessão que impede o envio de formulários a partir de outros sites.</li>
                   </ul>
                   <p>Não utilizamos cookies de publicidade nem de rastreio de terceiros. Pode bloquear os cookies nas definições do navegador, mas algumas funções (iniciar sessão, carrinho) deixarão de funcionar.</p>",
    ],
    [
        'id' => 'partilha',
        'titulo' => 'Partilha com terceiros',
        'html' => "<p>Os seus dados só são partilhados quando é necessário para prestar o serviço, nomeadamente com:</p>
                   <ul>
                       <li>o fornecedor de alojamento do site e o serviço de envio de e-mails;</li>
                       <li>parceiros de entrega, apenas com os dados necessários para a entrega (nome, telefone e endereço);</li>
                       <li>autoridades públicas, quando exigido por lei.</li>
                   </ul>",
    ],
    [
        'id' => 'conservacao',
        'titulo' => 'Conservação dos dados',
        'html' => "<p>Guardamos os dados da conta enquanto esta estiver activa. Os dados de pedidos são conservados pelo período exigido pela legislação fiscal e comercial. As mensagens de contacto são guardadas apenas pelo tempo necessário para dar seguimento ao assunto.</p>",
    ],
    [
        'id' => 'direitos',
        'titulo' => 'Os seus direitos',
        'html' => "<p>Pode, a qualquer momento, pedir para:</p>
                   <ul>
                       <li><strong>aceder</strong> aos dados pessoais que temos sobre si;</li>
                       <li><strong>rectificar</strong> dados incorrectos ou desactualizados;</li>
                       <li><strong>apagar</strong> a sua conta e os dados associados (excepto os que temos de guardar por lei);</li>
                       <li><strong>opor-se</strong> a determinado uso dos seus dados.</li>
                   </ul>
                   <p>Para exercer estes direitos, envie um e-mail para <a href=\"mailto:{$email}\">{$email}</a>. Respondemos no prazo máximo de 30 dias.</p>",
    ],
    [
        'id' => 'menores',
        'titulo' => 'Menores de idade',
        'html' => "<p>O site não se destina a menores de 18 anos. Não recolhemos intencionalmente dados de menores; se tomar conhecimento de que isso aconteceu, contacte-nos para os apagarmos.</p>",
    ],
    [
        'id' => 'alteracoes',
        'titulo' => 'Alterações a esta política',
        'html' => "<p>Esta política pode ser actualizada para reflectir alterações no site ou na lei. A data da última actualização está sempre indicada no topo da página. Saiba como protegemos os seus dados na página de <a href=\"" . url('seguranca') . "\">Segurança</a>.</p>",
    ],
];

require __DIR__ . '/partials/pagina-legal.php';
