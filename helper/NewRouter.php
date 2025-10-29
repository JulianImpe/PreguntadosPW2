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

        switch ($controllerName) {
            case 'login':
            case 'registrarse':
                case 'homevista':
                // Si ya está logueado → no tiene sentido ver login o registrarse
                if (isset($_SESSION['usuario_id'])) {
                    header("Location: /lobby/base");
                    exit;
                }
                break;

            case 'logout':
                session_destroy();
                header("Location: /homeVista");
                exit;

            default:
                // Si intenta entrar a cualquier otro controlador sin sesión → al login
                if (!isset($_SESSION['usuario_id'])) {
                    header("Location: /login/loginForm");
                    exit;
                }
                break;
        }

        $controller = $this->getControllerFrom($controllerParam);
        $this->executeMethodFromController($controller, $methodParam);
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
