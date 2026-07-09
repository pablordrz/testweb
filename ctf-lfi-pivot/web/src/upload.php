<?php
/*
 * CTF LAB - Unrestricted File Upload deliberadamente vulnerable
 * NO usar este código en un entorno real. Es material didáctico para CTF.
 */

$message = '';
$maxSize = 1 * 1024 * 1024; // 1 MB

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__ . '/uploads/';
    $filename = basename($_FILES['file']['name']);
    $target = $uploadDir . $filename;

    if ($_FILES['file']['size'] > $maxSize) {
        $message = "El archivo supera el límite de 1MB permitido.";
    } elseif (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        // Vulnerabilidad: no hay whitelist de extensiones ni comprobación real
        // del tipo de contenido, solo se acepta cualquier archivo tal cual
        // (siempre que no supere el límite de tamaño anterior).
        $message = "Archivo subido correctamente: uploads/" . htmlspecialchars($filename);
    } else {
        $message = "Error al subir el archivo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaTech Solutions | Subida de Documentos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6f8;
            color: #2c3e50;
        }
        header {
            background: linear-gradient(135deg, #1a2b4c, #2c4a7c);
            color: #fff;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        header .logo { font-size: 1.5rem; font-weight: 700; letter-spacing: 1px; }
        header .logo span { color: #4dd0e1; }
        nav a { color: #dfe6ee; text-decoration: none; margin-left: 25px; font-size: 0.95rem; }
        nav a:hover { color: #4dd0e1; }
        .container {
            max-width: 500px;
            margin: 60px auto;
            background: #fff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid #eceff2;
        }
        .container h1 { color: #1a2b4c; margin-bottom: 10px; font-size: 1.4rem; }
        .container p.desc { color: #6b7684; font-size: 0.9rem; margin-bottom: 25px; }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #dfe3e8;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        button {
            background: #1a56b0;
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
        }
        button:hover { background: #164a97; }
        .msg {
            margin-top: 20px;
            padding: 12px 16px;
            border-radius: 6px;
            background: #e8f0fe;
            color: #1a56b0;
            font-size: 0.9rem;
        }
        footer {
            text-align: center;
            padding: 25px;
            font-size: 0.8rem;
            color: #9aa5b1;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">Nova<span>Tech</span> Solutions</div>
        <nav>
            <a href="?page=welcome.php">Inicio</a>
            <a href="upload.php">Subir Documento</a>
        </nav>
    </header>

    <div class="container">
        <h1>Centro de Documentación</h1>
        <p class="desc">Sube aquí los informes o plantillas que quieras compartir con el equipo.</p>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit">Subir archivo</button>
        </form>
        <?php if ($message): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>
    </div>

    <footer>&copy; 2026 NovaTech Solutions &mdash; Portal Interno de Empleados</footer>
</body>
</html>
