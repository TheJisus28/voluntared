// «Continuar con Google» con Firebase Authentication.
// Firebase solo abre la ventana de Google y entrega un token; la sesión la maneja PHP.
import { initializeApp } from 'https://www.gstatic.com/firebasejs/11.0.2/firebase-app.js';
import { getAuth, GoogleAuthProvider, signInWithPopup, signOut } from 'https://www.gstatic.com/firebasejs/11.0.2/firebase-auth.js';

const BASE = document.querySelector('meta[name="base-url"]')?.content ?? '';
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const config = JSON.parse(document.getElementById('firebase-config').textContent);

const auth = getAuth(initializeApp(config));
auth.languageCode = 'es';

const MENSAJES = {
    'auth/popup-blocked': 'El navegador bloqueó la ventana de Google. Permite las ventanas emergentes para este sitio.',
    'auth/operation-not-allowed': 'El inicio de sesión con Google todavía no está habilitado en Firebase.',
    'auth/configuration-not-found': 'El inicio de sesión con Google todavía no está habilitado en Firebase.',
    'auth/unauthorized-domain': 'Este dominio no está autorizado en Firebase (Authentication › Configuración › Dominios autorizados).',
    'auth/network-request-failed': 'No hay conexión con Google. Revisa tu internet e inténtalo de nuevo.',
};

document.querySelectorAll('[data-google-login]').forEach((boton) => {
    const salida = document.getElementById(boton.dataset.googleLogin);
    const texto = boton.querySelector('[data-texto]');
    const original = texto.textContent;

    boton.addEventListener('click', async () => {
        boton.disabled = true;
        texto.textContent = 'Conectando con Google…';
        salida.textContent = '';
        try {
            const credencial = await signInWithPopup(auth, new GoogleAuthProvider());
            const idToken = await credencial.user.getIdToken();
            const respuesta = await fetch(BASE + '/auth/google', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({ idToken }),
            });
            const datos = await respuesta.json().catch(() => ({}));
            await signOut(auth);
            if (!respuesta.ok) {
                throw new Error(datos.error || 'No se pudo iniciar sesión con Google.');
            }
            window.location.href = datos.redirect;
        } catch (error) {
            if (error.code !== 'auth/popup-closed-by-user' && error.code !== 'auth/cancelled-popup-request') {
                salida.textContent = MENSAJES[error.code] || error.message || 'No se pudo iniciar sesión con Google.';
            }
            boton.disabled = false;
            texto.textContent = original;
        }
    });
});
