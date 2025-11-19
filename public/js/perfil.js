document.addEventListener('DOMContentLoaded', () => {
    const btnEditar = document.getElementById('btnEditarPassword');
    const formPassword = document.getElementById('formCambiarPassword');

    if (btnEditar && formPassword) {
        btnEditar.addEventListener('click', () =>
            formPassword.classList.toggle('hidden')
        );
    }
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toastEl => {
        setTimeout(() => {
            toastEl.classList.add('opacity-0', 'transition', 'duration-500');
            setTimeout(() => toastEl.remove(), 500);
        }, 3000);
    });
    const modal = document.getElementById('modalEditar');
    const btnCerrar = document.getElementById('btnCerrarModal');
    const tituloModal = document.getElementById('tituloModal');
    const campoInput = document.getElementById('campoEditar');
    const contenedorInput = document.getElementById('contenedorInput');

    if (btnCerrar) {
        btnCerrar.addEventListener('click', () =>
            modal.classList.add('hidden')
        );
    }
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.add('hidden');
    });
    window.abrirModalEditar = function(campo) {
        modal.classList.remove('hidden');
        campoInput.value = campo;
        let html = '';
        let titulo = '';

        switch (campo) {
            case 'email':
                titulo = 'Editar Email';
                html = `<input type="email" name="valor" class="w-full p-3 border rounded-lg" required>`;
                break;

            case 'nombre':
                titulo = 'Editar Nombre Completo';
                html = `<input type="text" name="valor" class="w-full p-3 border rounded-lg" required>`;
                break;

            case 'fecha':
                titulo = 'Editar Fecha de Nacimiento';
                html = `<input type="date" name="valor" class="w-full p-3 border rounded-lg" required>`;
                break;

            case 'sexo':
                titulo = 'Editar Género';
                html = `
                    <select name="valor" class="w-full p-3 border rounded-lg" required>
                        <option value="">Selecciona...</option>
                        <option value="1">Masculino</option>
                        <option value="2">Femenino</option>
                        <option value="3">Prefiero no cargarlo</option>
                    </select>`;
                break;
            case 'usuario':
                titulo = 'Editar Nombre de Usuario';
                html = `<input type="text" name="valor" class="w-full p-3 border rounded-lg" required>`;
                break;
        }
        tituloModal.textContent = titulo;
        contenedorInput.innerHTML = html;
    };
    const btnEditarFoto = document.getElementById('btnEditarFoto');

    if (btnEditarFoto) {
        btnEditarFoto.addEventListener('click', () => {
            modal.classList.remove('hidden');
            campoInput.value = 'foto';
            tituloModal.textContent = 'Cambiar Foto de Perfil';
            contenedorInput.innerHTML = `
                <input type="file" name="foto" accept="image/*"
                       class="w-full p-3 border rounded-lg" required>
                <p class="text-xs text-gray-500 mt-2">Formatos aceptados: JPG, PNG. Máx 5MB.</p>
            `;
        });
    }
});
