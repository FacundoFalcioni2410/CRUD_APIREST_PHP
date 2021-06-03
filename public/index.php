<?php
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Slim\Psr7\Response as ResponseMW;
    use Slim\Factory\AppFactory;
    use \Slim\Routing\RouteCollectorProxy;

    require __DIR__ . '../../vendor/autoload.php';
    require_once("../base_de_datos/Usuario.php");
    require_once("../base_de_datos/MiddleWare.php");

    $app = AppFactory::create();

    $app->group('/usuario', function (RouteCollectorProxy $grupo)
    {   
        $grupo->get('/', Usuario::class . ':TraerTodos')->add(\MiddleWare::class . ":TiempoRespuesta");
        $grupo->get('/{json}', Usuario::class . ':VerificarBD')->add(\MiddleWare::class . "::ComprobarExistencia")
                                                                ->add(\MiddleWare::class . ":TiempoRespuesta");
        $grupo->post('/', Usuario::class . ':AgregarUsuario');
        $grupo->put('/{usuario_json}', Usuario::class . ':ModificarUsuario')->add(\MiddleWare::class . ":TiempoRespuesta");
        $grupo->delete('/{id}', Usuario::class . ':EliminarUsuario');
    })->add(\MiddleWare::class . "::AgregarSoloADMIN")
      ->add(\MiddleWare::class . "::EliminarSoloSUPER_ADMIN");

    $app->run();
?>