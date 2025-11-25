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

        if (in_array($controllerName, ['login', 'registrarse', 'homevista', ''])) {
            
            if (isset($_SESSION['usuario_id']) && in_array($controllerName, ['login', 'registrarse'])) {
                $this->redirectByRole();
                exit;
            }
            if (in_array($controllerName, ['login', 'registrarse', 'homevista', 'reverseGeocode'])) {
                $controller = $this->getControllerFrom($controllerParam);
                $this->executeMethodFromController($controller, $methodParam);
                return;
            }
            $controller = $this->getControllerFrom($controllerParam);
            $this->executeMethodFromController($controller, $methodParam);
            return;
        }

        if ($controllerName === 'logout') {
            session_destroy();
            header("Location: /homeVista");
            exit;
        }

        if (!isset($_SESSION['usuario_id'])) {
            header("Location: /login/loginForm");
            exit;
        }

        if ($controllerName === 'admin') {
            if ($_SESSION["rol"] !== "admin") {
                header("Location: /login/loginForm");
                exit;
            }
            $controller = $this->getControllerFrom($controllerParam);
            $this->executeMethodFromController($controller, $methodParam);
            return;
        }

        if ($controllerName === 'editor') {
            if ($_SESSION["rol"] !== "editor") {
                header("Location: /login/loginForm");
                exit;
            }
            $controller = $this->getControllerFrom($controllerParam);
            $this->executeMethodFromController($controller, $methodParam);
            return;
        }


        if (in_array($controllerName, ['lobby', 'partida', 'perfil', 'ranking'])) {
            if ($_SESSION["rol"] !== "jugador") {
                
                $this->redirectByRole();
                exit;
            }
            $controller = $this->getControllerFrom($controllerParam);
            $this->executeMethodFromController($controller, $methodParam);
            return;
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