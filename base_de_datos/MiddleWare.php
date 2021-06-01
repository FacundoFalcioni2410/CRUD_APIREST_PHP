<?php
    require_once("Usuario.php");
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
    use Slim\Psr7\Response as ResponseMW;

    class MiddleWare
    {
        public static function ComprobarExistencia(Request $request, RequestHandler $handler) : ResponseMW
        {
            $response = $handler->handle($request);

            $usuario = json_decode((string) $response->getBody());
            
            if($usuario != null)
            {
                if(!isset($_COOKIE['idUsuario']))
                {
                   setcookie('idUsuario', $usuario->id,time() + 3600);
                }
                $responseMW = new ResponseMW();
                $responseMW->getBody()->write(json_encode($usuario));
                return $responseMW->withHeader('Content-Type', 'application/json');
            }
            else
            {
                $responseMW = new ResponseMW();
                $responseMW->getBody()->write((string) $response->getBody());
                return $responseMW->withStatus(403);
            }
        }

        public static function AgregarSoloADMIN(Request $request, RequestHandler $handler): ResponseMW
        {
            $response = $handler->handle($request);

            if($response->getStatusCode() == 200)
            {
                $ok = (string) $response->getBody();

                $mensajeJSON = new StdClass();
                $mensajeJSON->exito = false;
                $mensajeJSON->mensaje = 'No se pudo agregar el usuario';
    
                if($ok == true)
                {
                    $parametros = $request->getParsedBody();
    
                    $usuario = new Usuario();
    
                    $usuario->nombre = $parametros["nombre"];
                    $usuario->apellido = $parametros["apellido"];
                    $usuario->correo = $parametros["correo"];
                    $usuario->clave = $parametros["clave"];
                    $usuario->id_perfil = $parametros["id_perfil"];
                    $archivos = $request->getUploadedFiles();
                    $destino = "../fotos/";
    
                    $nombreAnterior = $archivos['foto']->getClientFilename();
                    $extension = explode(".", $nombreAnterior);
                    $extension = array_reverse($extension);
                    
                    $nombreFinal = $destino .  $usuario->nombre . "." . $extension[0];
                    $archivos['foto']->moveTo($destino .  $usuario->nombre . "." . $extension[0]);
                    $usuario->foto = $nombreFinal;
    
                    if($usuario->id_perfil == 1)
                    {
                        if($usuario->AgregarUno())
                        {
                            $mensajeJSON->exito = true;
                            $mensajeJSON->mensaje = 'Usuario agregado con exito';
                        }
                    }
                    else
                    {
                        unlink($usuario->foto);
                    }
                }
                
                $responseMW = new ResponseMW();
            
                $responseMW->getBody()->write(json_encode($mensajeJSON));
            
                return $responseMW->withHeader('Content-Type', 'application/json');
            }
            else
            {
                $responseMW = new ResponseMW();
                $responseMW->getBody()->write((string) $response->getBody());
                return $responseMW->withStatus(403);
            }
        }

        public static function EliminarSoloSUPER_ADMIN(Request $request, RequestHandler $handler): ResponseMW
        {
            $response = $handler->handle($request);

            if($response->getStatusCode() == 200)
            {
                $id = (string) $response->getBody();

                $mensajeJSON = new StdClass();
                $mensajeJSON->exito = false;
                $mensajeJSON->mensaje = 'No se pudo eliminar a el usuario';
    
                if($id != null)
                {    
                    $usuario = Usuario::TraerUnoID($id);
    
                    if($usuario->id_perfil == 5)
                    {
                        if($usuario->EliminarUno())
                        {
                            $mensajeJSON->exito = true;
                            $mensajeJSON->mensaje = 'Usuario eliminado con exito';
                            if($_COOKIE['idUsuario'] == $usuario->id)
                            {
                                setcookie('idUsuario','',1);
                            }
                        }
                    }
                }
    
                $responseMW = new ResponseMW();
                $responseMW->getBody()->write(json_encode($mensajeJSON));
                return $responseMW->withHeader('Content-Type', 'application/json');
            }
            else
            {
                $responseMW = new ResponseMW();
                $responseMW->getBody()->write((string) $response->getBody());
                return $responseMW->withStatus(403);
            }
        }
    }

?>