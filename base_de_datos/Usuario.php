<?php
    require_once("AccesoDatos.php");
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Slim\Factory\AppFactory;
    
    class Usuario
    {
        public $id;
        public $nombre;
        public $apellido;
        public $correo;
        public $clave;
        public $id_perfil;
        public $foto;

        public static function TraerUsuarios()
        {
            $accesoDatos = AccesoDatos::ObjetoAccesoDatos(); 
            $query = $accesoDatos->RetornarConsulta("SELECT usuarios.id, usuarios.nombre, usuarios.apellido, usuarios.correo, usuarios.clave, usuarios.foto, perfiles.descripcion FROM usuarios INNER JOIN perfiles ON usuarios.id_perfil = perfiles.id");
            try
            {
                $query->execute();
                return $query->fetchAll(PDO::FETCH_CLASS, "Usuario");
            }
            catch (PDOException $e)
            {
                echo "Error: {$e->getMessage()}"; 
            }
        }

        public static function TraerUnoCorreoClave($json)
        {
            $usuario = json_decode($json);
            $accesoDatos = AccesoDatos::ObjetoAccesoDatos();
            
            $query = $accesoDatos->RetornarConsulta("SELECT * from usuarios WHERE correo = :correo AND clave = :clave");

            $query->bindValue(':clave', $usuario->clave, PDO::PARAM_STR);
            $query->bindValue(':correo', $usuario->correo, PDO::PARAM_STR);

            try
            {
                $query->execute();
                return $query->fetchObject("Usuario");
            }
            catch(PDOException $e)
            {
                echo "Error: {$e->getMessage()}";
            }
        }

        public static function TraerUnoID($id)
        {   
            $accesoDatos = AccesoDatos::ObjetoAccesoDatos();
            
            $query = $accesoDatos->RetornarConsulta("SELECT * from usuarios WHERE id = :id");

            $query->bindValue(':id', $id, PDO::PARAM_INT);

            try
            {
                $query->execute();
                return $query->fetchObject("Usuario");
            }
            catch(PDOException $e)
            {
                echo "Error: {$e->getMessage()}";
            }
        }

        public function VerificarBD(Request $request, Response $response, array $args): Response
        {
            $usuario = Usuario::TraerUnoCorreoClave($args["json"]);

            $response->getBody()->write(json_encode($usuario));
            
            return $response;
        }


        public function TraerTodos(Request $request, Response $response, array $args): Response 
        {
            if(isset($_COOKIE['idUsuario']))
            {
                $usuarios = Usuario::TraerUsuarios();
    
                $newResponse = $response->withStatus(200, "OK");
                $newResponse->getBody()->write(json_encode($usuarios));

                return $newResponse->withHeader('Content-Type', 'application/json');
            }
            else
            {
                echo "ERROR PRIMERO DEBE LOGEARSE";
                return $response->withStatus(403);
            }
        }

        public function AgregarUsuario(Request $request, Response $response, array $args) : Response
        {
            if(isset($_COOKIE['idUsuario']))
            {               
                $response->getBody()->write((string)true);
                return $response->withHeader('Content-Type', 'application/json');
            }
            else
            {
                echo "ERROR PRIMERO DEBE LOGEARSE";
                return $response->withStatus(403);
            }
        }

        public function AgregarUno()
        {
            $accesoDatos = AccesoDatos::ObjetoAccesoDatos();
            
            $query = $accesoDatos->RetornarConsulta("INSERT INTO usuarios (nombre, apellido, correo, clave, id_perfil, foto) VALUES (:nombre, :apellido, :correo, :clave, :id_perfil, :foto)");

            $query->bindValue(':nombre',$this->nombre, PDO::PARAM_STR);
            $query->bindValue(':apellido',$this->apellido, PDO::PARAM_STR);
            $query->bindValue(':correo',$this->correo, PDO::PARAM_STR);
            $query->bindValue(':clave',$this->clave, PDO::PARAM_STR);
            $query->bindValue(':id_perfil',$this->id_perfil, PDO::PARAM_INT);
            $query->bindValue(':foto',$this->foto, PDO::PARAM_STR);

            $obj = new stdClass();
            $obj->exito = false;
            $obj->mensaje = "No se pudo agregar el usuario";

            try
            {
                $query->execute();
                if($query->rowCount())
                {
                    $obj->exito = true;
                    $obj->mensaje = "Se agrego un usuario con el id {$accesoDatos->RetornarUltimoIdInsertado()}";
                }
            }
            catch(PDOException $e)
            {
                echo "Error: {$e->getMessage()}";
            }

            return json_encode($obj);
        }


        public function ModificarUsuario(Request $request, Response $response, array $args) : Response
        {
            if(isset($_COOKIE['idUsuario']))
            {
                $obj = json_decode($args["usuario_json"]);

                $usuarioPrevio = Usuario::TraerUnoID($obj->id);

                unlink($usuarioPrevio->foto);

                $this->nombre = $obj->nombre;
                $this->apellido = $obj->apellido;
                $this->correo = $obj->correo;
                $this->clave = $obj->clave;
                $this->id_perfil = $obj->id_perfil;
                $this->id = $obj->id;

                $archivos = $request->getUploadedFiles();
                $destino = "../fotos/";
                
                $nombreAnterior = $archivos['foto']->getClientFilename();
                $extension = explode(".", $nombreAnterior);
                $extension = array_reverse($extension);
                
                $nombreFinal = $destino .  $this->nombre . "." . $extension[0];
                $archivos['foto']->moveTo($destino .  $this->nombre . "." . $extension[0]);
                $this->foto = $nombreFinal;

                $mensajeJSON = $this->ModificarUno();
                $newResponse = $response->withStatus(200, "OK");
                $newResponse->getBody()->write($mensajeJSON);

                return $newResponse->withHeader('Content-Type', 'application/json');
            }
            else
            {
                echo "ERROR PRIMERO DEBE LOGEARSE";
                return $response->withStatus(403);
            }
        }

        public function ModificarUno()
        {
            $accesoDatos = AccesoDatos::ObjetoAccesoDatos();
            
            $query = $accesoDatos->RetornarConsulta("UPDATE usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo, clave = :clave, id_perfil = :id_perfil WHERE id = :id");

            $query->bindValue(':nombre',$this->nombre, PDO::PARAM_STR);
            $query->bindValue(':apellido',$this->apellido, PDO::PARAM_STR);
            $query->bindValue(':correo',$this->correo, PDO::PARAM_STR);
            $query->bindValue(':clave',$this->clave, PDO::PARAM_STR);
            $query->bindValue(':id_perfil',$this->id_perfil, PDO::PARAM_INT);
            $query->bindValue(':id',$this->id, PDO::PARAM_INT);

            $obj = new stdClass();
            $obj->exito = false;
            $obj->mensaje = "No se pudo modificar el usuario";

            try
            {
                $query->execute();
                if($query->rowCount())
                {
                    $obj->exito = true;
                    $obj->mensaje = "Se modifico el usuario con exito";
                }
            }
            catch(PDOException $e)
            {
                echo "Error: {$e->getMessage()}";
            }

            return json_encode($obj);
        }

        public function EliminarUno()
        {
            $accesoDatos = AccesoDatos::ObjetoAccesoDatos();
            
            $query = $accesoDatos->RetornarConsulta("DELETE FROM usuarios WHERE id = :id");

            $query->bindValue(':id',$this->id, PDO::PARAM_INT);

            $obj = new stdClass();
            $obj->exito = false;
            $obj->mensaje = "No se pudo eliminar el usuario";

            try
            {
                $query->execute();
                if($query->rowCount())
                {
                    $obj->exito = true;
                    $obj->mensaje = "Se elimino el usuario con exito";
                }
            }
            catch(PDOException $e)
            {
                echo "Error: {$e->getMessage()}";
            }

            return json_encode($obj);
        }

        public function EliminarUsuario(Request $request, Response $response, array $args) : Response
        {
            if(isset($_COOKIE['idUsuario']))
            {
                $id = $args['id'];
                $response->getBody()->write($id);
                return $response->withStatus(200);
            }
            else
            {
                $response->getBody()->write("ERROR PRIMERO DEBE LOGEARSE");
                return $response->withStatus(403);
            }
        }
    }
?>