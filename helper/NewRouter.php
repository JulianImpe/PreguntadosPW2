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

        // CASO 1: Si ya hay sesión y entran a login/registro → redirigir según rol
        switch ($controllerName) {
            case 'login':
            case 'registrarse':
            case 'homevista':
                if (isset($_SESSION['usuario_id'])) {
                    $this->redirectByRole();
                }
                break;

            case 'logout':
                session_destroy();
                header("Location: /homeVista");
                exit;

            // CASO 2: Rutas protegidas por rol
            case 'admin':
                if (!isset($_SESSION['usuario_id']) || $_SESSION["rol"] !== "admin") {
                    header("Location: /login/loginForm");
                    exit;
                }
                break;

            case 'editor':
                if (!isset($_SESSION['usuario_id']) || $_SESSION["rol"] !== "editor") {
                    header("Location: /login/loginForm");
                    exit;
                }
                break;

            // CASO 3: Rutas de jugadores (lobby, partida, perfil, ranking)
            case 'lobby':
            case 'partida':
            case 'perfil':
            case 'ranking':
                if (!isset($_SESSION['usuario_id'])) {
                    header("Location: /login/loginForm");
                    exit;
                }
                // Si es admin o editor, redirigir a su dashboard
                if ($_SESSION["rol"] === "admin") {
                    header("Location: /admin/dashboard");
                    exit;
                }
                if ($_SESSION["rol"] === "editor") {
                    header("Location: /editor/lobbyEditor");
                    exit;
                }
                break;

            default:
                // Cualquier otra ruta requiere sesión
                if (!isset($_SESSION['usuario_id'])) {
                    header("Location: /login/loginForm");
                    exit;
                }
                break;
        }

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
            header("location: /");
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