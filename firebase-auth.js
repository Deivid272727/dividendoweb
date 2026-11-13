
import { auth, database } from "./firebase-config.js";

import {
    ref,
    set
} from "https://www.gstatic.com/firebasejs/12.6.0/firebase-database.js";

import {
    signInWithEmailAndPassword,
    createUserWithEmailAndPassword,
    updateProfile
} from "https://www.gstatic.com/firebasejs/12.6.0/firebase-auth.js";

console.log("Firebase Authentication cargado correctamente");

const loginForm = document.getElementById("firebase-login-form");

if (loginForm) {
    loginForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const email = loginForm.querySelector('input[name="email"]').value.trim();
        const password = loginForm.querySelector('input[name="password"]').value;
        const button = document.getElementById("firebase-login-btn");

        button.disabled = true;
        button.textContent = "Iniciando sesión...";

        try {
            const resultado = await signInWithEmailAndPassword(
                auth,
                email,
                password
            );

            
console.log("Inicio de sesión exitoso");

// Obtener el token del usuario autenticado
const idToken = await resultado.user.getIdToken();

// Enviar el token al servidor PHP
const respuesta = await fetch("firebase-session.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/json"
    },
    credentials: "same-origin",
    body: JSON.stringify({
        idToken: idToken
    })
});

// Leer la respuesta del servidor
const datos = await respuesta.json();

console.log("Estado HTTP:", respuesta.status);
console.log("Respuesta de PHP:", datos);

if (!respuesta.ok || !datos.success) {
    throw new Error(datos.message || "No se pudo crear la sesión PHP.");
}

// Si Firebase y PHP iniciaron sesión correctamente
console.log("Sesión PHP creada correctamente");

// Redirigir al inicio
window.location.href = "index.php";


        } catch (error) {

console.error("Error completo del inicio de sesión:", error);
console.error("Mensaje:", error.message);

            if (
                error.code === "auth/invalid-credential" ||
                error.code === "auth/wrong-password" ||
                error.code === "auth/user-not-found"
            ) {
                alert("Correo o contraseña incorrectos.");
            } else {
                alert("No se pudo iniciar sesión. Intenta nuevamente.");
            }

        } finally {
            button.disabled = false;
            button.textContent = "Sign In";
        }
    });
}

const registerForm = document.getElementById("firebase-register-form");
console.log("Formulario de registro:", registerForm);

if (registerForm) {
    registerForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        console.log("¡SE EJECUTÓ EL REGISTRO!");

        const name = registerForm.querySelector('[name="name"]').value.trim();
        const email = registerForm.querySelector('[name="email"]').value.trim();
        const password = registerForm.querySelector('[name="password"]').value;

        const button = document.getElementById("firebase-register-btn");

        if (!name) {
            alert("Por favor, escribe tu nombre.");
            return;
        }

        button.disabled = true;
        button.textContent = "Registrando...";

        try {
            // Registrar usuario en Firebase Authentication
            const resultado = await createUserWithEmailAndPassword(
                auth,
                email,
                password
            );

            // Guardar su nombre en Firebase Authentication
            await updateProfile(resultado.user, {
                displayName: name
            });
            // Guardar perfil en Realtime Database
const uid = resultado.user.uid;

await set(ref(database, "users/" + uid), {
    nombre: name,
    correo: email
});

console.log("Perfil guardado en Realtime Database");

            alert("¡Cuenta creada correctamente! Ya puedes iniciar sesión.");

            console.log("Usuario registrado correctamente");

            registerForm.reset();

            // Volver al formulario de inicio de sesión
            document.getElementById("container").classList.remove("active");

        } catch (error) {
            console.error("Error de registro:", error.code);

            if (error.code === "auth/email-already-in-use") {
                alert("Este correo ya está registrado.");
            } else if (error.code === "auth/weak-password") {
                alert("La contraseña es demasiado débil.");
            } else if (error.code === "auth/invalid-email") {
                alert("El correo electrónico no es válido.");
            } else {
                alert("No se pudo crear la cuenta. Intenta nuevamente.");
            }

        } finally {
            button.disabled = false;
            button.textContent = "Sign Up";
        }
    });
}
