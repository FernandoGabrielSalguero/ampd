<?php
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = "La sesión expiró por inactividad. Por favor, iniciá sesión nuevamente.";
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 1:
            $error = "Usuario o contraseña incorrectos.";
            break;
        case 2:
            $error = "El nombre de usuario o correo ya está registrado.";
            break;
        case 3:
            $error = "Ocurrió un error inesperado. Por favor, intentá nuevamente.";
            break;
    }
} elseif (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "✅ Registro exitoso. Ya podés iniciar sesión.";
}


if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = "La sesión expiró por inactividad. Por favor, iniciá sesión nuevamente.";
} elseif (isset($_GET['error']) && $_GET['error'] == 1) {
    $error = "Usuario o contraseña incorrectos.";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar o Registrarse</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #8e44ad, #3498db);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
        }

        .form-container {
            display: flex;
            width: 200%;
            transition: transform 0.8s ease-in-out;
        }

        form {
            width: 50%;
            padding: 0 15px;
            opacity: 0;
            transition: opacity 0.6s ease;
        }

        form.active {
            opacity: 1;
        }

        .login-container.show-register .form-container {
            transform: translateX(-50%);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #673ab7;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .form-group button:hover {
            background-color: #5e35b1;
        }

        .toggle-link {
            text-align: center;
            margin-top: 15px;
            color: #3498db;
            cursor: pointer;
            font-weight: bold;
            transition: color 0.3s;
        }

        .toggle-link:hover {
            color: #21618c;
        }

        h1 {
            text-align: center;
            color: #673ab7;
            margin-bottom: 20px;
        }

        .error {
            color: red;
            margin-bottom: 10px;
            text-align: center;
        }

        .success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="login-container" id="loginContainer">
        <div class="form-container">
            <!-- LOGIN -->
            <form action="/login_handler.php" method="POST" class="login-form active">
                <h1>Iniciar Sesión</h1>
                <?php if (!empty($error)): ?>
                    <div class="error"><?= $error ?></div>
                <?php elseif (!empty($success)): ?>
                    <div class="success"><?= $success ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" name="usuario" id="usuario" required>
                </div>
                <div class="form-group password-container">
                    <label for="contrasena">Contraseña:</label>
                    <input type="password" name="contrasena" id="contrasena" required>
                </div>
                <div class="form-group">
                    <button type="submit">INGRESAR</button>
                </div>
                <div class="toggle-link" onclick="toggleForm()">¿No tenés cuenta? Registrate</div>
            </form>

            <!-- REGISTRO -->
            <form action="/register_handler.php" method="POST" class="register-form">
                <h1>Registrarse</h1>
                <div class="form-group">
                    <label for="user_name">Nombre de usuario:</label>
                    <input type="text" name="user_name" id="user_name" required>
                </div>
                <div class="form-group">
                    <label for="pass">Contraseña:</label>
                    <input type="password" name="pass" id="pass" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo electrónico:</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <button type="submit">REGISTRAR</button>
                </div>
                <div class="toggle-link" onclick="toggleForm()">¿Ya tenés cuenta? Iniciá sesión</div>
            </form>
        </div>
    </div>

    <script>
        const container = document.getElementById('loginContainer');
        const loginForm = document.querySelector('.login-form');
        const registerForm = document.querySelector('.register-form');

        function toggleForm() {
            container.classList.toggle('show-register');
            loginForm.classList.toggle('active');
            registerForm.classList.toggle('active');
        }
    </script>
</body>

</html>