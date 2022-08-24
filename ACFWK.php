<?php
class Dashboard
{
    public $conn;

    //CREATING OBJECT WITH GLOBAL THE CONNECTION TO DATABASE AS A PARAMETER
    function __construct($conn)
    {
        $this->conn = $conn;
    }

    function newOpenDashboard()
    {
        //THOSE VALUES ARE SET BY DEFAULT ON EACH TABLE.
        mysqli_query($this->conn, "CREATE TABLE IF NOT EXISTS usuarios (id INT(9) NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(300) NOT NULL, correo VARCHAR(300) NOT NULL, pswd VARCHAR(300) NOT NULL, rol VARCHAR(300), estado VARCHAR(300) NOT NULL); ");
        mysqli_query($this->conn, "CREATE TABLE IF NOT EXISTS `validacion_usuario` (id INT(9) NOT NULL AUTO_INCREMENT PRIMARY KEY, codigo VARCHAR(300) NOT NULL, correo VARCHAR(300) NOT NULL);");
    }

    //ADDING A USER ONTO THE DB
    function addAccount($user, $email, $password)
    {
        if (isset($user) && isset($email) && isset($password)) {

            if (!empty(trim($user)) && !empty(trim($email)) && !empty($password)) {

                $form_usuario = mysqli_real_escape_string($this->conn, htmlspecialchars($user));
                $form_email = mysqli_real_escape_string($this->conn, htmlspecialchars($email));

                if (filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
                    $verifico_email = mysqli_query($this->conn, "SELECT `correo` FROM `usuarios` WHERE correo = '$form_email'");

                    if (mysqli_num_rows($verifico_email) > 0) {
                        echo "El email ingresado ya se encuentra utilizado, utilice otro correo para registrarse.";
                    } else {
                        $usuario_hash_password = password_hash($password, PASSWORD_DEFAULT);

                        $inserto_usuario = mysqli_query($this->conn, "INSERT INTO `usuarios` (nombre, correo, pswd, estado) VALUES ('$form_usuario', '$form_email', '$usuario_hash_password', 'Pendiente')");

                        if ($inserto_usuario === TRUE) {
                            echo "Registro exitoso, se ha enviado un correo para confirmar su validacion de la cuenta";
                            $this -> sendActivationEmail($email, $user);
                        } else {
                            echo "Algo no salió como esperabamos, error.";
                        }
                    }
                } else {
                    echo "La dirección de email ingresada no es válida.";
                }
            } else {
                echo "Por favor complete los campos vacios.";
            }
        }
    }

    //SEND VERFIFICATION EMAIL
    function sendActivationEmail($email, $username)
    {
        //SET BY DEFAULT
        $ASUNTO = 'Confirma tu correo';

        $REMITENTE = 'ESCEN <no-reply@escen.com>';

        $MESSAGE = $username . ",\n Para proceder con la validacion de tu cuenta es necesario que ingreses al siguiente link: \n" . $this->generateValidationURL("http://localhost/simulador%20ceneval/activacion.php", $email);

        mail($email, $ASUNTO, $MESSAGE, 'From: ' . $REMITENTE);
    }

    //VALIDATE A USER
    function validateUser($code, $email)
    {
        $qry = "SELECT * FROM validacion_usuario WHERE codigo = '$code' AND correo = '$email'";
        $res = mysqli_query($this->conn, $qry);
        //asking if the data exist
        if (mysqli_num_rows($res) > 0) {
            //Updating account state...
            $query = "UPDATE usuarios set estado = 'Activo' WHERE correo = '$email' AND estado = 'Pendiente'";
            //in case the data match
            if (mysqli_query($this->conn, $query)) {
                $res = mysqli_fetch_assoc($res);
                $qry = "DELETE FROM validacion_usuario WHERE id = '" . $res['id'] . "'";
                mysqli_query($this->conn, $qry);
                echo "validado con exito";
            }
        } else {
            echo "Ocurrio un error con la validacion o este usuario ya fue validado";
        }
    }

    //CREATE A URL WITH THE VALIDATION DATA
    function generateValidationURL($domain, $email)
    {
        $codigo = substr(md5(uniqid(mt_rand(), true)), 0, 10);
        $qry = "INSERT INTO validacion_usuario (correo, codigo) VALUES ('$email', '$codigo')";
        mysqli_query($this->conn, $qry);

        return $domain . "?code=" . $codigo . "&email=" . $email;
    }
}
