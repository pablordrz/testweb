<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaTech Solutions | Portal Corporativo</title>
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
        header .logo {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        header .logo span { color: #4dd0e1; }
        nav a {
            color: #dfe6ee;
            text-decoration: none;
            margin-left: 25px;
            font-size: 0.95rem;
        }
        nav a:hover { color: #4dd0e1; }
        .hero {
            background: #fff;
            padding: 60px 40px;
            text-align: center;
            border-bottom: 1px solid #e0e4e8;
        }
        .hero h1 {
            font-size: 2.2rem;
            margin-bottom: 15px;
            color: #1a2b4c;
        }
        .hero p {
            font-size: 1.05rem;
            color: #607080;
            max-width: 600px;
            margin: 0 auto;
        }
        .cards {
            display: flex;
            gap: 25px;
            padding: 50px 40px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            width: 280px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid #eceff2;
        }
        .card h3 {
            color: #1a2b4c;
            margin-bottom: 10px;
            font-size: 1.15rem;
        }
        .card p {
            font-size: 0.9rem;
            color: #6b7684;
            line-height: 1.5;
        }
        .badge {
            display: inline-block;
            background: #e8f0fe;
            color: #1a56b0;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 12px;
            margin-bottom: 12px;
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
            <a href="#">Servicios</a>
            <a href="#">Soporte</a>
            <a href="#">Contacto</a>
        </nav>
    </header>

    <section class="hero">
        <h1>Bienvenido al Portal Interno</h1>
        <p>Gestiona tus documentos, informes y accesos corporativos desde un único lugar, de forma rápida y segura.</p>
    </section>

    <section class="cards">
        <div class="card">
            <span class="badge">Documentos</span>
            <h3>Centro de Documentación</h3>
            <p>Consulta manuales, políticas internas y plantillas oficiales de la organización.</p>
        </div>
        <div class="card">
            <span class="badge">Soporte</span>
            <h3>Mesa de Ayuda</h3>
            <p>Abre incidencias técnicas y da seguimiento a tus tickets con el equipo de IT.</p>
        </div>
        <div class="card">
            <span class="badge">Reportes</span>
            <h3>Informes de Actividad</h3>
            <p>Descarga reportes mensuales de uso y rendimiento de los sistemas internos.</p>
        </div>
    </section>

    <footer>
        &copy; 2026 NovaTech Solutions &mdash; Portal Interno de Empleados
    </footer>
</body>
</html>
