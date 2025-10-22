<?php

class NewRouter
{


    private $configFactory;
    private $defaultController;
    private $defaultMethod;

    public function __construct($configFactory, $defaultController,$defaultMethod)
    {

        $this->configFactory = $configFactory;
        $this->defaultController = $defaultController;
        $this->defaultMethod = $defaultMethod;
    }

    public function executeController($controllerParam, $methodParam)
    {
// --- Si el usuario ya esta logueado lo mando al lobby sino lo manda al login ---
        $controllerName = strtolower($controllerParam ?? '');
        switch ($controllerName) {

            
            case 'login':
            case 'registrarse':
                
                if (isset($_SESSION['usuario'])) {
                    header("Location: /PreguntadosPW2/lobby/base");
                    exit;
                }
                break;

            
            default:
                if (!isset($_SESSION['usuario'])) {
                    header("Location: /PreguntadosPW2/login/loginForm");
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
            array(
                $controller,
                $this->getMethodName($controller, $methodName)
            )
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
        return method_exists($controller, $methodName) ? $methodName : $this->defaultMethod;
    }
}