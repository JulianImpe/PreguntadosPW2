<?php

class NewRouter
{
    private $configFactory;
    private $defaultController;
    private $defaultMethod;

    public function __construct($configFactory, $defaultController, $defaultMethod)
    {
        $this->configFactory = $configFactory;
        $this->defaultController = $defaultController;
        $this->defaultMethod = $defaultMethod;
    }

    public function executeController($controllerParam, $methodParam)
    {
        $controllerName = strtolower($controllerParam ?? '');

        // CASO 1: Rutas públicas (sin sesión requerida)
        if (in_array($controllerName, ['login', 'registrarse', 'homevista', ''])) {
            // Si ya tiene sesión y está intentando acceder a login/registro, redirigir a su dashboard
            if (isset($_SESSION['usuario_id']) && in_array($controllerName, ['login', 'registrarse'])) {
                $this->redirectByRole();
                exit;
            }
            if (in_array($controllerName, ['login', 'registrarse', 'homevista', 'reverseGeocode'])) {
                $controller = $this->getControllerFrom($controllerParam);
                $this->executeMethodFromController($controller, $methodParam);
                return;
            }
            // Si no tiene sesión o está en homeVista, dejar pasar
            $controller = $this->getControllerFrom($controllerParam);
            $this->executeMethodFromController($controller, $methodParam);
            return;
        }

        // CASO 2: Logout (siempre permitido)
        if ($controllerName === 'logout') {
            session_destroy();
            header("Location: /homeVista");
            exit;
        }

        // CASO 3: Verificar que el usuario tenga sesión para rutas protegidas
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: /login/loginForm");
            exit;
        }

        // CASO 4: Rutas exclusivas de ADMIN
        if ($controllerName === 'admin') {
            if ($_SESSION["rol"] !== "admin") {
                header("Location: /login/loginForm");
                exit;
            }
            $controller = $this->getControllerFrom($controllerParam);
            $this->executeMethodFromController($controller, $methodParam);
            return;
        }

        // CASO 5: Rutas exclusivas de EDITOR
        if ($controllerName === 'editor') {
            if ($_SESSION["rol"] !== "editor") {
                header("Location: /login/loginForm");
                exit;
            }
            $controller = $this->getControllerFrom($controllerParam);
            $this->executeMethodFromController($controller, $methodParam);
            return;
        }

        // CASO 6: Rutas exclusivas de JUGADOR (lobby, partida, perfil, ranking)
        if (in_array($controllerName, ['lobby', 'partida', 'perfil', 'ranking'])) {
            if ($_SESSION["rol"] !== "jugador") {
                // Si es admin o editor intentando entrar a rutas de jugador, redirigir a su dashboard
                $this->redirectByRole();
                exit;
            }
            $controller = $this->getControllerFrom($controllerParam);
            $this->executeMethodFromController($controller, $methodParam);
            return;
        }

        // CASO 7: Cualquier otra ruta protegida (genérica)
        $controller = $this->getControllerFrom($controllerParam);
        $this->executeMethodFromController($controller, $methodParam);
    }

    private function redirectByRole()
    {
        if ($_SESSION["rol"] === "admin") {
            header("Location: /admin/dashboard");
            exit;
        }

        if ($_SESSION["rol"] === "editor") {
            header("Location: /editor/lobbyEditor");
            exit;
        }

        // Jugador común
        header("Location: /lobby/base");
        exit;
    }

    private function getControllerFrom($controllerName)
    {
        $controllerName = $this->getControllerName($controllerName);
        $controller = $this->configFactory->get($controllerName);

        if ($controller == null) {
            header("location: /homeVista");
            exit;
        }

        return $controller;
    }

    private function executeMethodFromController($controller, $methodName)
    {
        call_user_func(
            [$controller, $this->getMethodName($controller, $methodName)]
        );
    }

    public function getControllerName($controllerName)
    {
        return $controllerName ?
            ucfirst($controllerName) . 'Controller' :
            $this->defaultController;
    }

    public function getMethodName($controller, $methodName)
    {
        return method_exists($controller, $methodName)
            ? $methodName
            : $this->defaultMethod;
    }
}