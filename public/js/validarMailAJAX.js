document.addEventListener("DOMContentLoaded", function () {
    const emailInput = document.getElementById("email");
    emailInput.addEventListener("keyup", function () {
        const email = this.value;

        if (email.length < 5) {
            document.getElementById("msg-email").textContent = "";
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                const msg = document.getElementById("msg-email");

                if (data.existe) {
                    msg.textContent = "Este email ya está registrado";
                    msg.classList.add("text-red-700");
                    msg.classList.remove("text-green-700");
                } else {
                    msg.textContent = "✔ Email disponible";
                    msg.classList.add("text-green-700");
                    msg.classList.remove("text-red-700");
                }
            }
        };

        xhr.open("GET", "/registrarse/validarEmail?email=" + encodeURIComponent(email), true);
        xhr.send();
    });
});
