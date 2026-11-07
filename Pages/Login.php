<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = md5($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        if (function_exists('logActivity')) {
            logActivity('User login: ' . $username);
        }

        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Username atau password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login Hermas Cell</title>

    <meta name="description"
          content="Halaman login resmi Sistem Kasir Hermas Cell.">

    <meta name="robots"
          content="noindex, nofollow">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;

            min-height: 100vh;

            background:
                linear-gradient(
                    135deg,
                    #f8fafc,
                    #eef2f7
                );

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 20px;

            color: #0f172a;
        }


        /* LOGIN CARD */

        .login-card {

            width: 100%;
            max-width: 420px;

            background: #ffffff;

            border-radius: 20px;

            padding: 38px 34px;

            border: 1px solid #e5e7eb;

            box-shadow:
                0 20px 50px
                rgba(15, 23, 42, 0.10);
        }


        /* LOGO */

        .logo {

            text-align: center;

            margin-bottom: 30px;
        }


        .logo-icon {

            width: 65px;
            height: 65px;

            margin: auto;

            margin-bottom: 14px;

            border-radius: 18px;

            background: #f8fafc;

            border: 2px solid #0f172a;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 30px;
        }


        .logo h1 {

            font-size: 28px;

            color: #0f172a;

            margin-bottom: 5px;
        }


        .logo p {

            font-size: 14px;

            color: #64748b;
        }



        /* ERROR */

        .error {

            background: #fff1f2;

            border: 1px solid #fecdd3;

            color: #be123c;

            padding: 12px 14px;

            border-radius: 10px;

            margin-bottom: 18px;

            font-size: 13px;
        }



        /* FORM */

        .form-group {

            margin-bottom: 17px;
        }


        label {

            display: block;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 7px;

            color: #334155;
        }



        .input-box {

            position: relative;
        }



        .input-icon {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 17px;

            color: #64748b;

            pointer-events: none;
        }



        .form-control {

            width: 100%;

            height: 52px;

            padding-left: 43px;

            padding-right: 45px;

            border: 1.5px solid #dbe2ea;

            border-radius: 10px;

            font-size: 14px;

            background: #ffffff;

            color: #0f172a;

            outline: none;

            transition: 0.2s;
        }


        .form-control:focus {

            border-color: #0f172a;

            box-shadow:
                0 0 0 3px
                rgba(15, 23, 42, 0.06);
        }



        /* EYE PASSWORD */

        .show-password {

            position: absolute;

            right: 13px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            font-size: 17px;

            cursor: pointer;
        }



        /* OPTIONS */

        .login-options {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 22px;

            font-size: 12px;
        }


        .remember {

            display: flex;

            align-items: center;

            gap: 6px;

            color: #475569;
        }



        /* BUTTON */

        .btn-login {

            width: 100%;

            height: 52px;

            border: none;

            border-radius: 10px;

            background: #0f2345;

            color: white;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s;
        }


        .btn-login:hover {

            background: #08182f;

            transform: translateY(-1px);
        }



        /* DIVIDER */

        .divider {

            display: flex;

            align-items: center;

            margin: 22px 0;

            font-size: 12px;

            color: #94a3b8;
        }


        .divider::before,
        .divider::after {

            content: "";

            flex: 1;

            height: 1px;

            background: #e5e7eb;
        }


        .divider span {

            margin: 0 12px;
        }



        /* HOME BUTTON */

        .home-button {

            width: 100%;

            height: 48px;

            border-radius: 10px;

            border: 1px solid #cbd5e1;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            color: #0f172a;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s;
        }


        .home-button:hover {

            background: #f8fafc;
        }



        /* FOOTER */

        .footer {

            margin-top: 28px;

            text-align: center;

            font-size: 11px;

            color: #94a3b8;

            line-height: 1.6;
        }



        /* RESPONSIVE */

        @media (max-width: 480px) {

            .login-card {

                padding: 30px 22px;

                border-radius: 16px;
            }


            .logo h1 {

                font-size: 25px;
            }

        }

    </style>

</head>


<body>


<div class="login-card">


    <!-- LOGO -->

    <div class="logo">

        <div class="logo-icon">
            🏪
        </div>

        <h1>
            Hermas Cell
        </h1>

        <p>
            Login Sistem Kasir
        </p>

    </div>



    <!-- ERROR LOGIN -->

    <?php if ($error): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>



    <!-- FORM LOGIN -->

    <form
        method="POST"
        autocomplete="on"
    >


        <!-- USERNAME -->

        <div class="form-group">

            <label for="username">
                Username
            </label>


            <div class="input-box">


                <span class="input-icon">
                    👤
                </span>


                <input

                    type="text"

                    id="username"

                    name="username"

                    class="form-control"

                    placeholder="Masukkan username"

                    autocomplete="username"

                    required

                    autofocus

                >


            </div>

        </div>



        <!-- PASSWORD -->

        <div class="form-group">

            <label for="password">
                Password
            </label>
            <div class="input-box">
                <span class="input-icon">
                    🔒
                </span>
                <input

                    type="password"

                    id="password"

                    name="password"

                    class="form-control"

                    placeholder="Masukkan password"

                    autocomplete="current-password"

                    required

                >
                <button

                    type="button"

                    class="show-password"

                    onclick="togglePassword()"

                >

                    👁

                </button>


            </div>

        </div>



        <!-- REMEMBER -->

        <div class="login-options">


            <label class="remember">

                <input
                    type="checkbox"
                    name="remember"
                >

                Ingat saya

            </label>


        </div>



        <!-- LOGIN -->

        <button

            type="submit"

            class="btn-login"

        >

            🔒 Login

        </button>


    </form>



    <!-- DIVIDER -->

    <div class="divider">

        <span>
            atau
        </span>

    </div>

    <!-- FOOTER -->

    <div class="footer">

        © <?php echo date('Y'); ?>
        Hermas Cell

        <br>

        Seluruh hak cipta dilindungi.

    </div>


</div>

<script>

function togglePassword() {

    const password =
        document.getElementById('password');


    if (password.type === 'password') {

        password.type = 'text';

    } else {

        password.type = 'password';

    }

}

</script>

</body>
</html>