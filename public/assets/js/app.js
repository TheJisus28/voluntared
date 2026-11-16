/* =====================================================================
   VoluntaRed - Comportamiento del lado del cliente (JavaScript sin dependencias,
   además de Bootstrap y Chart.js).
   ===================================================================== */
(() => {
    'use strict';

    const BASE = document.querySelector('meta[name="base-url"]')?.content ?? '';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const autenticado = document.body.dataset.autenticado === '1';

    /** fetch a la API REST con la sesión actual y el token CSRF. */
    async function api(ruta, opciones = {}) {
        const resp = await fetch(BASE + ruta, {
            credentials: 'same-origin',
            ...opciones,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': CSRF,
                ...(opciones.body ? { 'Content-Type': 'application/json' } : {}),
                ...(opciones.headers || {}),
            },
        });
        const datos = await resp.json().catch(() => ({}));
        if (!resp.ok) {
            throw new Error(datos.error || 'No se pudo completar la solicitud.');
        }
        return datos;
    }

    const escapar = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    // -----------------------------------------------------------------
    // Confirmación de acciones (el sistema "pide confirmación" en los CU)
    // Uso: <form data-confirmar="¿Seguro?" data-confirmar-boton="Sí" data-motivo="1">
    // -----------------------------------------------------------------
    const modalEl = document.getElementById('modalConfirmar');
    if (modalEl && window.bootstrap) {
        const modal = new bootstrap.Modal(modalEl);
        const txtMensaje = modalEl.querySelector('[data-modal-mensaje]');
        const bloqueMotivo = modalEl.querySelector('[data-modal-motivo]');
        const campoMotivo = modalEl.querySelector('#modalMotivo');
        const btnAceptar = modalEl.querySelector('[data-modal-aceptar]');
        let formularioPendiente = null;

        document.addEventListener('submit', (ev) => {
            const form = ev.target;
            if (!form.matches('form[data-confirmar]') || form.dataset.confirmado === '1') {
                return;
            }
            ev.preventDefault();
            formularioPendiente = form;
            txtMensaje.textContent = form.dataset.confirmar;
            btnAceptar.textContent = form.dataset.confirmarBoton || 'Confirmar';
            btnAceptar.className = 'btn ' + (form.dataset.confirmarEstilo || 'btn-danger');
            const pideMotivo = form.dataset.motivo === '1';
            bloqueMotivo.classList.toggle('d-none', !pideMotivo);
            campoMotivo.value = '';
            campoMotivo.classList.remove('is-invalid');
            modal.show();
            setTimeout(() => (pideMotivo ? campoMotivo : btnAceptar).focus(), 300);
        });

        btnAceptar.addEventListener('click', () => {
            if (!formularioPendiente) {
                return;
            }
            if (formularioPendiente.dataset.motivo === '1') {
                if (campoMotivo.value.trim().length < 10) {
                    campoMotivo.classList.add('is-invalid');
                    campoMotivo.focus();
                    return;
                }
                let oculto = formularioPendiente.querySelector('input[name="motivo"]');
                if (!oculto) {
                    oculto = document.createElement('input');
                    oculto.type = 'hidden';
                    oculto.name = 'motivo';
                    formularioPendiente.appendChild(oculto);
                }
                oculto.value = campoMotivo.value.trim();
            }
            formularioPendiente.dataset.confirmado = '1';
            modal.hide();
            formularioPendiente.requestSubmit ? formularioPendiente.requestSubmit() : formularioPendiente.submit();
        });
    }

    // Evitar doble envío de formularios
    document.addEventListener('submit', (ev) => {
        const form = ev.target;
        if (ev.defaultPrevented || form.dataset.sinBloqueo === '1') {
            return;
        }
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((b) => {
            setTimeout(() => { b.disabled = true; }, 0);
            setTimeout(() => { b.disabled = false; }, 6000);
        });
    });

    // Mostrar / ocultar contraseña
    document.querySelectorAll('[data-ver-contrasena]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const campo = document.getElementById(btn.dataset.verContrasena);
            const visible = campo.type === 'text';
            campo.type = visible ? 'password' : 'text';
            btn.setAttribute('aria-pressed', String(!visible));
            btn.querySelector('i').className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    });

    // Contador de caracteres
    document.querySelectorAll('[data-contar]').forEach((campo) => {
        const salida = document.getElementById(campo.dataset.contar);
        const max = campo.getAttribute('maxlength');
        const act = () => { salida.textContent = `${campo.value.length}/${max}`; };
        campo.addEventListener('input', act);
        act();
    });

    // Ubicación obligatoria solo para modalidad presencial o mixta
    const modalidad = document.getElementById('modalidad');
    const ubicacion = document.getElementById('ubicacion');
    if (modalidad && ubicacion) {
        const marca = document.getElementById('ubicacion-obligatoria');
        const act = () => {
            const req = modalidad.value === 'presencial' || modalidad.value === 'mixta';
            ubicacion.required = req;
            marca?.classList.toggle('d-none', !req);
        };
        modalidad.addEventListener('change', act);
        act();
    }

    // Vista previa de imágenes (foto o logo)
    document.querySelectorAll('[data-vista-previa]').forEach((input) => {
        input.addEventListener('change', () => {
            const img = document.getElementById(input.dataset.vistaPrevia);
            const archivo = input.files?.[0];
            if (img && archivo && archivo.type.startsWith('image/')) {
                img.src = URL.createObjectURL(archivo);
                img.classList.remove('d-none');
            }
        });
    });

    // -----------------------------------------------------------------
    // Contadores de notificaciones y mensajes (consulta periódica a la API)
    // -----------------------------------------------------------------
    function pintarContador(nombre, valor) {
        document.querySelectorAll(`[data-contador="${nombre}"]`).forEach((el) => {
            el.textContent = valor > 99 ? '99+' : valor;
            el.classList.toggle('d-none', !valor);
            el.setAttribute('aria-label', `${valor} sin leer`);
        });
    }

    async function actualizarContadores() {
        try {
            const datos = await api('/api/contadores');
            pintarContador('notificaciones', datos.notificaciones);
            pintarContador('mensajes', datos.mensajes);
        } catch (e) { /* sin conexión: se reintenta en el siguiente ciclo */ }
    }

    const iconos = {
        postulacion: 'bi-person-plus', respuesta: 'bi-reply', mensaje: 'bi-chat-dots',
        oportunidad: 'bi-megaphone', evaluacion: 'bi-star', sistema: 'bi-info-circle',
    };

    async function cargarNotificaciones() {
        const cont = document.getElementById('lista-notificaciones');
        if (!cont) {
            return;
        }
        try {
            const datos = await api('/api/notificaciones?limite=6');
            if (!datos.datos.length) {
                cont.innerHTML = '<div class="p-4 text-center text-body-secondary small">No tienes notificaciones.</div>';
                return;
            }
            cont.innerHTML = datos.datos.map((n) => `
                <a class="dropdown-item notif-item py-2 px-3 ${n.leida ? '' : 'no-leida'}" href="${escapar(n.url)}">
                    <div class="d-flex gap-2">
                        <i class="bi ${iconos[n.tipo] || 'bi-bell'} text-primary mt-1" aria-hidden="true"></i>
                        <div>
                            <div class="fw-semibold small">${escapar(n.titulo)}</div>
                            <div class="small text-body-secondary">${escapar(n.contenido)}</div>
                            <div class="small text-body-secondary opacity-75">${escapar(n.hace)}</div>
                        </div>
                    </div>
                </a>`).join('');
        } catch (e) {
            cont.innerHTML = '<div class="p-3 text-center text-danger small">No se pudieron cargar las notificaciones.</div>';
        }
    }

    if (autenticado) {
        actualizarContadores();
        setInterval(actualizarContadores, 20000);
        document.getElementById('campana')?.addEventListener('show.bs.dropdown', cargarNotificaciones);
    }

    // -----------------------------------------------------------------
    // Chat en tiempo real (consulta cada 3 segundos)
    // -----------------------------------------------------------------
    const chat = document.getElementById('chat');
    if (chat) {
        const idConv = chat.dataset.conversacion;
        const caja = document.getElementById('chat-mensajes');
        const form = document.getElementById('chat-form');
        const texto = document.getElementById('chat-texto');
        const estado = document.getElementById('chat-estado');
        let ultimo = parseInt(chat.dataset.ultimo || '0', 10);
        let enviando = false;

        const bajar = () => { caja.scrollTop = caja.scrollHeight; };
        bajar();

        const pintar = (m) => {
            if (document.querySelector(`[data-mensaje="${m.id}"]`)) {
                return;
            }
            caja.querySelector('.vacio')?.remove();
            const div = document.createElement('div');
            div.className = 'burbuja ' + (m.propio ? 'propia' : 'ajena');
            div.dataset.mensaje = m.id;
            div.innerHTML = `${escapar(m.contenido)}<div class="meta">${escapar(m.hora)}${m.propio ? ' <span class="estado-leido"></span>' : ''}</div>`;
            caja.appendChild(div);
            ultimo = Math.max(ultimo, m.id);
        };

        const marcarLeidos = (hasta) => {
            caja.querySelectorAll('.burbuja.propia').forEach((b) => {
                const el = b.querySelector('.estado-leido');
                if (el && parseInt(b.dataset.mensaje, 10) <= hasta) {
                    el.innerHTML = '· <i class="bi bi-check2-all" aria-hidden="true"></i><span class="visually-hidden">Leído</span>';
                }
            });
        };

        const consultar = async () => {
            try {
                const datos = await api(`/api/conversaciones/${idConv}/mensajes?desde=${ultimo}`);
                const habiaNuevos = datos.datos.length > 0;
                const cerca = caja.scrollHeight - caja.scrollTop - caja.clientHeight < 120;
                datos.datos.forEach(pintar);
                marcarLeidos(datos.ultimo_leido);
                if (habiaNuevos && cerca) {
                    bajar();
                }
            } catch (e) { /* reintento automático */ }
        };

        form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const contenido = texto.value.trim();
            if (!contenido || enviando) {
                return;
            }
            enviando = true;
            estado.textContent = 'Enviando…';
            try {
                const m = await api(`/api/conversaciones/${idConv}/mensajes`, {
                    method: 'POST',
                    body: JSON.stringify({ contenido }),
                });
                pintar(m);
                texto.value = '';
                estado.textContent = '';
                bajar();
            } catch (e) {
                estado.textContent = e.message;
            } finally {
                enviando = false;
                texto.focus();
            }
        });
        form.dataset.sinBloqueo = '1';

        texto.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter' && !ev.shiftKey) {
                ev.preventDefault();
                form.requestSubmit();
            }
        });

        consultar();
        setInterval(consultar, 3000);
    }

    // -----------------------------------------------------------------
    // Gráficos del tablero de estadísticas (Chart.js)
    // -----------------------------------------------------------------
    const datosGraficos = document.getElementById('datos-graficos');
    if (datosGraficos && window.Chart) {
        const g = JSON.parse(datosGraficos.textContent);
        const verde = '#1d4d35';
        const naranja = '#a3442a';
        const paleta = ['#1d4d35', '#a3442a', '#c99a2e', '#5f8b6e', '#7a5c3e', '#3f6f8f', '#8c6b8f', '#2f8a5b', '#b8b0a0', '#454c46'];
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = '#3f463f';
        Chart.defaults.borderColor = '#e1dbcd';

        new Chart(document.getElementById('graficoMeses'), {
            type: 'line',
            data: {
                labels: g.meses.etiquetas,
                datasets: [
                    { label: 'Postulaciones', data: g.meses.postulaciones, borderColor: verde, backgroundColor: 'rgba(29,77,53,.10)', fill: true, tension: .3 },
                    { label: 'Oportunidades publicadas', data: g.meses.oportunidades, borderColor: naranja, backgroundColor: naranja, tension: .3 },
                ],
            },
            options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });

        new Chart(document.getElementById('graficoCategorias'), {
            type: 'bar',
            data: {
                labels: g.categorias.etiquetas,
                datasets: [{ label: 'Oportunidades', data: g.categorias.oportunidades, backgroundColor: verde, borderRadius: 6 }],
            },
            options: { maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
        });

        new Chart(document.getElementById('graficoEstados'), {
            type: 'doughnut',
            data: {
                labels: g.estados.etiquetas,
                datasets: [{ data: g.estados.valores, backgroundColor: ['#e7b54a', '#1d4d35', '#a3442a', '#b8b0a0'] }],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });

        new Chart(document.getElementById('graficoHoras'), {
            type: 'bar',
            data: {
                labels: g.categorias.etiquetas,
                datasets: [{ label: 'Horas de voluntariado', data: g.categorias.horas, backgroundColor: paleta, borderRadius: 6 }],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
        });
    }
})();
