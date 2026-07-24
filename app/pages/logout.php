<?php

/**
 * logout.php — Termina a sessão.
 *
 * Aceita APENAS POST com token CSRF válido. Fazer logout por GET permitiria
 * a um site externo terminar a sessão do utilizador com um simples <img src>.
 * O botão de logout na navbar submete um formulário POST com csrf_field().
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('home');
}

csrf_verificar();

terminar_sessao();

// Recomeça uma sessão limpa só para transportar a mensagem flash.
iniciar_sessao();
flash('sucesso', 'Sessão terminada. Até breve!');

redirect('home');
