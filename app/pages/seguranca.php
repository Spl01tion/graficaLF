<?php

/**
 * seguranca.php — Segurança: como protegemos o site e os dados, e boas práticas para o cliente.
 * O layout (cabeçalho, índice, navegação legal) está em partials/pagina-legal.php.
 */

$titulo     = 'Segurança';
$descricao  = 'Conheça as medidas que a Gráfica Lifei adopta para proteger o site, a sua conta e os seus dados.';
$subtitulo  = 'As medidas que adoptamos para proteger a sua conta e os seus dados.';
$atualizado = '16 de Novembro de 2026';

$app   = e(APP_NAME);
$email = e(config_get('site_email', 'geral@graficalifei.co.mz'));

$seccoes = [
    [
        'id' => 'compromisso',
        'titulo' => 'O nosso compromisso',
        'html' => "<p>A segurança dos seus dados é uma prioridade para a {$app}. Aplicamos medidas técnicas e organizativas para impedir o acesso não autorizado, a perda ou a alteração da informação que nos confia.</p>",
    ],
    [
        'id' => 'medidas',
        'titulo' => 'Medidas técnicas',
        'html' => "<div class=\"row g-3\">
                       <div class=\"col-md-6\"><div class=\"legal-medida\"><i class=\"bi bi-lock\"></i><div><strong>Ligação cifrada</strong><p>Com ligação HTTPS, os dados viajam cifrados entre o seu navegador e o servidor, e o cookie de sessão só circula por essa ligação segura.</p></div></div></div>
                       <div class=\"col-md-6\"><div class=\"legal-medida\"><i class=\"bi bi-key\"></i><div><strong>Palavras-passe cifradas</strong><p>As palavras-passe são guardadas com um algoritmo de hash seguro. Nem a nossa equipa as consegue ver.</p></div></div></div>
                       <div class=\"col-md-6\"><div class=\"legal-medida\"><i class=\"bi bi-cookie\"></i><div><strong>Sessões protegidas</strong><p>Os cookies de sessão não são acessíveis por scripts e a sessão é renovada ao iniciar sessão.</p></div></div></div>
                       <div class=\"col-md-6\"><div class=\"legal-medida\"><i class=\"bi bi-ui-checks\"></i><div><strong>Formulários protegidos</strong><p>Todos os formulários têm protecção contra envios forjados a partir de outros sites (CSRF).</p></div></div></div>
                       <div class=\"col-md-6\"><div class=\"legal-medida\"><i class=\"bi bi-speedometer2\"></i><div><strong>Limite de tentativas</strong><p>O número de tentativas de login, recuperação de palavra-passe, pedidos e mensagens é limitado, para travar ataques automáticos e spam.</p></div></div></div>
                       <div class=\"col-md-6\"><div class=\"legal-medida\"><i class=\"bi bi-database-lock\"></i><div><strong>Base de dados protegida</strong><p>As consultas à base de dados são parametrizadas e o acesso à área de gestão é restrito a administradores.</p></div></div></div>
                   </div>",
    ],
    [
        'id' => 'conta',
        'titulo' => 'Proteja a sua conta',
        'html' => "<ul>
                       <li>Use uma palavra-passe forte e única, que não utilize noutros sites;</li>
                       <li>Não partilhe a sua palavra-passe com ninguém — a nossa equipa <strong>nunca</strong> a vai pedir;</li>
                       <li>Termine sessão quando usar um computador partilhado ou público;</li>
                       <li>Mantenha o navegador e o sistema operativo actualizados.</li>
                   </ul>",
    ],
    [
        'id' => 'fraude',
        'titulo' => 'Cuidado com fraudes',
        'html' => "<p>Comunicamos apenas a partir dos nossos contactos oficiais, indicados no rodapé do site. Desconfie de mensagens que:</p>
                   <ul>
                       <li>peçam a sua palavra-passe ou códigos de verificação;</li>
                       <li>peçam pagamentos para contas ou números diferentes dos confirmados pela nossa equipa;</li>
                       <li>contenham ligações para sites com endereço parecido, mas diferente, do nosso.</li>
                   </ul>
                   <p>Em caso de dúvida, confirme sempre connosco antes de efectuar qualquer pagamento.</p>",
    ],
    [
        'id' => 'reportar',
        'titulo' => 'Reportar um problema',
        'html' => "<p>Se suspeitar de acesso indevido à sua conta, receber uma mensagem suspeita em nosso nome ou encontrar uma vulnerabilidade no site, contacte-nos de imediato em <a href=\"mailto:{$email}\">{$email}</a>. Agradecemos que nos dê tempo para corrigir uma vulnerabilidade antes de a tornar pública.</p>
                   <p>Se detectarmos um incidente que afecte os seus dados, informamos os utilizadores afectados com a maior brevidade possível. Saiba mais sobre o tratamento dos seus dados na <a href=\"" . url('privacidade') . "\">Política de Privacidade</a>.</p>",
    ],
];

require __DIR__ . '/partials/pagina-legal.php';
