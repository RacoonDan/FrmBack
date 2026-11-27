<?php
// auth.php - Protección opcional con clave.
// Si en Railway defines la variable CLAVE_VER, ver.php e imagen.php piden esa clave.
// Si no la defines, el acceso es libre.

function requerir_acceso(bool $mostrarLogin): void
{
    $clave = (string)getenv('CLAVE_VER');
    if ($clave === '') {
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
        ]);
        session_start();
    }

    if (isset($_GET['salir'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: ver.php');
        exit;
    }

    if (!empty($_SESSION['acceso_ok'])) {
        return;
    }

    if (!$mostrarLogin) {
        http_response_code(403);
        exit;
    }

    $error = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (hash_equals($clave, (string)($_POST['clave'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['acceso_ok'] = true;
            header('Location: ver.php');
            exit;
        }
        usleep(700000); // frena intentos repetidos
        $error = true;
    }

    http_response_code($error ? 401 : 200);
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Acceso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="h4 text-center mb-3">🔒 Acceso restringido</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2">Clave incorrecta.</div>
                        <?php endif; ?>
                        <form method="POST" autocomplete="off">
                            <label for="clave" class="form-label">Clave</label>
                            <input type="password" name="clave" id="clave" class="form-control mb-3" required autofocus>
                            <button type="submit" class="btn btn-navidad w-100">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
<?php
    exit;
}
