<?php

/**
 * admin/dashboard.php — Visão geral (KPIs + gráficos).
 */

// KPIs
$totVendas   = (float) (query_row("SELECT COALESCE(SUM(total),0) AS t FROM orders WHERE status <> 'cancelado'")['t'] ?? 0);
$totPedidos  = (int) (query_row('SELECT COUNT(*) AS t FROM orders')['t'] ?? 0);
$totProdutos = (int) (query_row('SELECT COUNT(*) AS t FROM products')['t'] ?? 0);
$totClientes = (int) (query_row("SELECT COUNT(*) AS t FROM users WHERE role='customer'")['t'] ?? 0);
$msgNovas    = (int) (query_row('SELECT COUNT(*) AS t FROM contact_messages WHERE lida=0')['t'] ?? 0);
$pedPendentes = (int) (query_row("SELECT COUNT(*) AS t FROM orders WHERE status='pendente'")['t'] ?? 0);

// Pedidos por estado (gráfico de rosca).
$porEstado = query('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') ?: [];
$estadoLabels = [];
$estadoData = [];
foreach ($porEstado as $r) {
    $estadoLabels[] = estado_pedido($r['status']);
    $estadoData[] = (int) $r['n'];
}

// Vendas dos últimos 6 meses (gráfico de linhas).
$vendasMes = query(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS mes, COALESCE(SUM(total),0) AS total
     FROM orders WHERE status <> 'cancelado' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY mes ORDER BY mes"
) ?: [];
$mesLabels = array_map(static fn ($r) => $r['mes'], $vendasMes);
$mesData   = array_map(static fn ($r) => (float) $r['total'], $vendasMes);

// Últimos pedidos + produtos mais vendidos.
$ultimosPedidos = query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5') ?: [];
$topProdutos    = query('SELECT nome, vendas FROM products ORDER BY vendas DESC LIMIT 5') ?: [];

$tituloPainel = 'Dashboard';
require __DIR__ . '/../partials/admin_header.php';
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['Vendas totais', moeda($totVendas), 'bi-cash-stack', 'success'],
        ['Pedidos', (string) $totPedidos, 'bi-receipt', 'primary'],
        ['Produtos', (string) $totProdutos, 'bi-box-seam', 'info'],
        ['Clientes', (string) $totClientes, 'bi-people', 'warning'],
    ];
    foreach ($kpis as [$rot, $val, $ic, $cor]): ?>
        <div class="col-6 col-xl-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="kpi-icon bg-<?= $cor ?> bg-opacity-10 text-<?= $cor ?>"><i class="bi <?= $ic ?>"></i></span>
                    <div>
                        <div class="text-muted small"><?= e($rot) ?></div>
                        <div class="fs-5 fw-bold"><?= e($val) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($pedPendentes > 0 || $msgNovas > 0): ?>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <?php if ($pedPendentes > 0): ?>
            <a href="<?= url('admin/pedidos') ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-hourglass-split me-1"></i><?= $pedPendentes ?> pedido(s) pendente(s)
            </a>
        <?php endif; ?>
        <?php if ($msgNovas > 0): ?>
            <a href="<?= url('admin/mensagens') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-envelope me-1"></i><?= $msgNovas ?> mensagem(ns) por ler
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Gráficos -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Vendas (últimos 6 meses)</h2>
                <canvas id="graficoVendas" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Pedidos por estado</h2>
                <canvas id="graficoEstados" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tabelas -->
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0">Últimos pedidos</h2>
                    <a href="<?= url('admin/pedidos') ?>" class="small">Ver todos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Ref.</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($ultimosPedidos as $p): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($p['referencia']) ?></td>
                                    <td class="small"><?= e($p['cliente_nome']) ?></td>
                                    <td><?= e(moeda($p['total'])) ?></td>
                                    <td><span class="badge bg-<?= estado_cor($p['status']) ?>"><?= e(estado_pedido($p['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($ultimosPedidos === []): ?><tr><td colspan="4" class="text-muted text-center">Sem pedidos.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Produtos mais vendidos</h2>
                <?php foreach ($topProdutos as $tp): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-truncate" style="max-width:70%;"><?= e($tp['nome']) ?></span>
                        <span class="badge bg-primary rounded-pill"><?= (int) $tp['vendas'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('graficoVendas'), {
        type: 'line',
        data: {
            labels: <?= json_encode($mesLabels) ?>,
            datasets: [{
                label: 'Vendas (MZN)', data: <?= json_encode($mesData) ?>,
                borderColor: '#e0202f', backgroundColor: 'rgba(224,32,47,.1)', fill: true, tension: .3,
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
    new Chart(document.getElementById('graficoEstados'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($estadoLabels) ?>,
            datasets: [{ data: <?= json_encode($estadoData) ?>,
                backgroundColor: ['#f7d520','#3a5a70','#e0202f','#6c757d','#198754','#dc3545'] }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
</script>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
