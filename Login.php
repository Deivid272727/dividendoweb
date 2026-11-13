<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>


<!DOCTYPE html>
<html lang = "es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="EI=edge">
    <!--<script src="https://accounts.google.com/gsi/client" async defer></script> -->

<!--================ CSS =============-->
<link rel="stylesheet" href="Css/Login.css">



<!--======== Boxicons CSS ============-->
<link href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css" rel="stylesheet">
<link rel= "stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css"/>


<title> Dividendo la venganza</title>
</head>
<body>

 
    
    <!-- Aqui empieza el codigo y el header del sidebard-->
<nav class="sidebar close">
    <header>
        <div class="image-text">
            <span class="image">
                <img src="logo.png" alt="logo">
            </span>
          <div class="text header-text">
                <span class="name">Y esta mafudada?</span>
                <span class="profession">No se bro</span>
            </div>
        </div>
        <i class ="bx bx-chevron-right toggle"></i> <!-- Esta cosa es el boton del side bar osea el > -->
    </header>
 
    <!-- Aqui estan los segmentos del sidebar-->
         <!-- Busqueda-->
    <div class="menu-bar">
        <div class="menu">
             <li class="search-box">
                        <i class="bx bx-search icon"></i>
                        <input type="search" placeholder="Search...">
                    </a>
                        <!-- nose-->
                </li>
            <ul class="menu-links">
                <li class="nav-link">
                    <a href="index.php">
                        <i class="bx bx-home-alt icon"></i>
                        <span class="text nav-text">Casitá</span>
                    </a>
                        <!-- Aqui estan los segmentos del sidebar-->
                              </li>
            <ul class="menu-links">
                <li class="nav-link">
                    <a href="Perfil.php">
                        <i class="bx bx-home-alt icon"></i>
                        <span class="text nav-text">Perfil</span>
                    </a>
                             <!-- Aqui estan los segmentos del sidebar-->
                                   </li>
            <ul class="menu-links">
                <li class="nav-link">
                    <a href="municipios.php">
            <i class='bx bxs-tree-alt icon'></i>
                
            
                        <span class="text nav-text">Parques</span>
                    </a>
                                  <!-- Aqui estan los segmentos del sidebar-->
                                        </li>
            <ul class="menu-links">
                <li class="nav-link">
                    <!--<a href="#">
                        <i class="bx bx-heart icon"></i>
                        <span class="text nav-text">Santa chad</span>
                    </a>-->

                        <!-- nose-->

                         </li>
            <ul class="menu-links">
               
            </ul>
        </div> 


        <div class="bottom-content">

         <li class="">
                    <a href="Login.php">
                        <i class="bx bx-log-in icon"></i>
                        <span class="text nav-text">Iniciar Sesión</span>
                    </a>
                </li>

                 <li class="mode">
                    <div class="moon-sun">
                           <i class="bx bx-moon icon moon"></i>
                             <i class="bx bx-sun icon sun"></i>
                    </div>
                    <span class="mode-text text"> Dark Mode</span>

                    <div class="toggle-switch">
                        <span class="switch"></span>
                    </div>
                </li>
     </div>
        
    </div>

</nav><!-- aqui termina -->   


<!-- Header de la pagina -->
<section class = "home">
   
<!-- Registrarse -->
    <div class = "container" id="container">
     <div class="form-container sign-up">
    <form id="firebase-register-form">
        <h1>Crear una cuenta</h1>
        <div class="social-icons">

    <div id="g_id_onload"
         data-client_id="1088139853006-cdps7llshndaa283unf04bt3nhq5a1pu.apps.googleusercontent.com"
         data-callback="handleCredentialResponse">
    </div>

    <div class="g_id_signin"
         data-type="standard"
         data-theme="outline"
         data-size="large">
    </div>

</div>
        <!--<span>or use your email for registration</span>-->
        
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" 
               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).{8,}$"
               title="Debe tener mínimo 8 caracteres, una mayúscula, una minúscula, un número y un símbolo"
               required>

               <!--Aqui es donde nos vendes tu alma -->       
        <table>
            <tr>
                <td><input type="checkbox" name="privacidad" id="privacidad" required></td>
                <td>Acepto el <a href="privacidad.php" target="_blank">Aviso de privacidad</a></td>
            </tr>
        </table>
        <button type="submit" id="firebase-register-btn">Registrarse</button>
    </form>
</div>

<div class="form-container sign-in">
    <form id="firebase-login-form">
        <h1>Inicio de sesión</h1>
        <div class="social-icons">
            <a href="#" class="icon"><i class="fa-brands fa-google.plus-g"></i></a>
        </div>
        <!--<span>or use your email password</span> -->
        
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <a href="PasswordForgot.php"> ¿Olvidaste tu contraseña?  </a>

        <button type="submit" id="firebase-login-btn">Iniciar Sesión</button> 
    </form>
</div>


       <div class="toggle-container">
       <div class="toggle">
        <div class="toggle-panel toggle-left"> 
            <h1> Bienvenido a Dividendo </h1>
            <p> Cuidar nuestros parques es tarea de todos </p>
            <button class="hidden" id= "login"> Ya tengo cuenta</button>
            </div>


             <div class="toggle-panel toggle-right"> 
            <h1> Bienvenido a Dividendo</h1>
            <p> Pequeñas acciones, grandes cambios </p>
            <button class="hidden" id= "register"> Registrar una cuenta</button>
            </div>
        </div>
    </div>
</div>

</section>



 <script src="script2.js"></script>
 <script type="module" src="firebase-auth.js"></script>
<script>

function handleCredentialResponse(response)
{
    fetch("google_login.php",
    {
        method: "POST",
        headers:
        {
            "Content-Type":
            "application/x-www-form-urlencoded"
        },

        body:
        "credential=" +
        encodeURIComponent(response.credential)
    })

    .then(res => res.text())

    .then(data =>
    {
        if(data.trim() === "success")
        {
            window.location =
            "index.php";
        }
        else
        {
            alert(data);
        }
    });
}

</script>

</body>

</html>