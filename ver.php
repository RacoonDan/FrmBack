<?php
// ver.php - Lista lo guardado en la BD, con botones para filtrar por persona.
require __DIR__ . '/auth.php';
requerir_acceso(true);
require __DIR__ . '/conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');
$conexion->query("SET time_zone = '+00:00'"); // las fechas se leen en UTC y se convierten abajo

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fechaLima(string $f): string
{
    $d = new DateTime($f, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone('America/Lima'));
    return $d->format('d/m/Y H:i');
}

// Personas con su cantidad de registros
$personas = $conexion->query(
    'SELECT p.id, p.nombre, COUNT(r.id) AS total
       FROM personas p LEFT JOIN registros r ON r.persona_id = p.id
      GROUP BY p.id, p.nombre ORDER BY p.nombre'
)->fetch_all(MYSQLI_ASSOC);
$totalGeneral = array_sum(array_column($personas, 'total'));

$filtro = (int)($_GET['persona'] ?? 0);
if (!in_array($filtro, array_map('intval', array_column($personas, 'id')), true)) {
    $filtro = 0;
}
$nombreFiltro = '';
foreach ($personas as $p) {
    if ((int)$p['id'] === $filtro) {
        $nombreFiltro = $p['nombre'];
    }
}

// Registros (sin descargar los BLOB: solo se consulta si existe imagen)
$sql = 'SELECT r.id, r.fecha_registro, p.nombre';
for ($n = 1; $n <= 3; $n++) {
    $sql .= ", r.descripcion$n, r.enlace$n, (r.imagen$n IS NOT NULL) AS tiene$n";
}
$sql .= ' FROM registros r JOIN personas p ON p.id = r.persona_id';
if ($filtro) {
    $sql .= ' WHERE r.persona_id = ?';
}
$sql .= ' ORDER BY r.id DESC';
$st = $conexion->prepare($sql);
$filtro ? $st->execute([$filtro]) : $st->execute();
$registros = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$protegido = ((string)getenv('CLAVE_VER') !== '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Pedidos registrados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <nav class="navbar navbar-dark nav-navidad">
        <div class="container">
            <a class="navbar-brand fw-bold" href="ver.php">🎄 Back Office Mood navidad</a>
            <div class="d-flex gap-2">
                <a href="formulario.html" class="btn btn-sm btn-outline-light">Ir al formulario</a>
                <?php if ($protegido): ?>
                    <a href="ver.php?salir=1" class="btn btn-sm btn-outline-light">Salir</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <header class="portada portada-corta text-center">
        <div class="container">
            <p class="sobre-titulo mb-1">Intercambio navideño</p>
            <h1 class="mb-0">Pedidos registrados</h1>
        </div>
    </header>

    <main class="container my-4">
        <div class="d-flex flex-wrap gap-2 mb-4" role="group" aria-label="Filtrar por persona">
            <a href="ver.php" class="btn <?= $filtro === 0 ? 'btn-navidad' : 'btn-outline-success' ?>">
                Todos <span class="badge text-bg-light"><?= (int)$totalGeneral ?></span>
            </a>
            <?php foreach ($personas as $p): ?>
                <a href="ver.php?persona=<?= (int)$p['id'] ?>"
                   class="btn <?= $filtro === (int)$p['id'] ? 'btn-navidad' : 'btn-outline-success' ?>">
                    <?= e($p['nombre']) ?> <span class="badge text-bg-light"><?= (int)$p['total'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!$registros): ?>
            <div class="alert alert-secondary">
                <?= $filtro ? e($nombreFiltro) . ' aún no ha enviado ningún pedido.' : 'Todavía no hay pedidos registrados.' ?>
            </div>
        <?php endif; ?>

        <?php foreach ($registros as $r): ?>
            <section class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <strong>🎁 <?= e($r['nombre']) ?></strong>
                    <small class="text-muted">#<?= (int)$r['id'] ?> · <?= e(fechaLima($r['fecha_registro'])) ?></small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php for ($n = 1; $n <= 3; $n++):
                            $desc = $r["descripcion$n"];
                            $enlace = $r["enlace$n"];
                            $tiene = (int)$r["tiene$n"] === 1;
                            $vacia = ($desc === null || $desc === '') && !$enlace && !$tiene;
                        ?>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <?php if ($tiene): ?>
                                        <a href="imagen.php?id=<?= (int)$r['id'] ?>&n=<?= $n ?>" target="_blank" rel="noopener">
                                            <img src="imagen.php?id=<?= (int)$r['id'] ?>&n=<?= $n ?>" loading="lazy"
                                                 alt="Opción <?= $n ?> de <?= e($r['nombre']) ?>"
                                                 class="card-img-top imagen-registro">
                                        </a>
                                    <?php else: ?>
                                        <div class="sin-imagen card-img-top">🎁</div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h3 class="h6 text-muted">Opción <?= $n ?></h3>
                                        <?php if ($vacia): ?>
                                            <p class="text-muted fst-italic mb-0">Sin datos</p>
                                        <?php endif; ?>
                                        <?php if ($desc !== null && $desc !== ''): ?>
                                            <p class="mb-2"><?= nl2br(e($desc)) ?></p>
                                        <?php endif; ?>
                                        <?php if ($enlace): ?>
                                            <a href="<?= e($enlace) ?>" target="_blank" rel="noopener noreferrer"
                                               class="btn btn-sm btn-outline-success">Ver enlace ↗</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    </main>

    <footer class="text-center py-3 mt-4">🎄 Feliz Navidad</footer>
</body>
</html>
