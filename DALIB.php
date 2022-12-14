<?php
include($_SERVER['DOCUMENT_ROOT']."/db.php");
session_start();
$datosUsuario;

//CREATE A OPEN SYSTEM
function newOpenDashboard()
{
    global $conn;
    //THOSE VALUES ARE SET BY DEFAULT ON EACH TABLE.
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS usuarios (id INT(9) NOT NULL AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(300) NOT NULL, correo VARCHAR(300) NOT NULL, pswd VARCHAR(300) NOT NULL, rol VARCHAR(300), estado VARCHAR(300) NOT NULL); ");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `validacion_usuario` (id INT(9) NOT NULL AUTO_INCREMENT PRIMARY KEY, codigo VARCHAR(300) NOT NULL, correo VARCHAR(300) NOT NULL);");
}

//ADDING A USER ONTO THE DB
function addAccount($user, $email, $password)
{
    global $conn;
    if (isset($user) && isset($email) && isset($password)) {

        if (!empty(trim($user)) && !empty(trim($email)) && !empty($password)) {

            $form_usuario = mysqli_real_escape_string($conn, htmlspecialchars($user));
            $form_email = mysqli_real_escape_string($conn, htmlspecialchars($email));

            if (filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
                $verifico_email = mysqli_query($conn, "SELECT `correo` FROM `usuarios` WHERE correo = '$form_email'");

                if (mysqli_num_rows($verifico_email) > 0) {
                    echo "El email ingresado ya se encuentra utilizado, utilice otro correo para registrarse.";
                } else {
                    $usuario_hash_password = password_hash($password, PASSWORD_DEFAULT);

                    $inserto_usuario = mysqli_query($conn, "INSERT INTO `usuarios` (nombre, correo, pswd, estado) VALUES ('$form_usuario', '$form_email', '$usuario_hash_password', 'Pendiente')");

                    if ($inserto_usuario === TRUE) {
                        echo "Registro exitoso, se ha enviado un correo para confirmar su validacion de la cuenta";
                        sendActivationEmail($email, $user);
                    } else {
                        echo "Algo no sali?? como esperabamos, error.";
                    }
                }
            } else {
                echo "La direcci??n de email ingresada no es v??lida.";
            }
        } else {
            echo "Por favor complete los campos vacios.";
        }
    }
}

//GET USER EMAIL
function getUserEmail()
{
    global $datosUsuario;
    return $datosUsuario['correo'];
}

//GET USER ID
function getUserID()
{
    global $datosUsuario;
    return $datosUsuario['id'];
}

//GET USER ROLE
function  getUserRole()
{
    global $datosUsuario;
    return $datosUsuario['role'];
}

//GET USER NAME
function getUserName()
{
    global $datosUsuario;
    return $datosUsuario['nombre'];
}

//CREATE A NEW SESSION
function userLogin($email, $password)
{
    global $conn;
    if (isset($email) && isset($password)) {

        if (!empty(trim($email)) && !empty(trim($password))) {

            $form_email = mysqli_real_escape_string($conn, htmlspecialchars(trim($email)));
            $query = mysqli_query($conn, "SELECT * FROM usuarios WHERE correo = '$form_email' AND estado = 'Activo'");

            if (mysqli_num_rows($query) > 0) {
                $row = mysqli_fetch_assoc($query);

                if (password_verify($password, $row['pswd'])) {

                    session_regenerate_id(true);
                    $_SESSION['email'] = $form_email;
                    echo 'window.Location = '.$_SERVER['DOCUMENT_ROOT'].'/index.php';
                    exit;
                } else {
                    echo "El correo no pertenece a ninguna cuenta";
                }
            } else {
                echo "Contrase??a o correo incorrectos.";
            }
        } else {
            echo "Por favor complete los campos vacios.";
        }
    }
}

//ask server if there is a user
function sessionExist(){
 if(isset($_SESSION['email'])){
    return true;
 } else{
    return false;
 } 
}

//GET CURRENT USER DATA
function userData($URL)
{
    global $conn;
    global $datosUsuario;
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($_SESSION['email']) && !empty($_SESSION['email'])) {

        $email = $_SESSION['email'];

        $get_datos_usuario = mysqli_query($conn, "SELECT * FROM usuarios WHERE correo = '$email'");
        $datosUsuario =  mysqli_fetch_assoc($get_datos_usuario);
    } else {
        header('Location: '.$URL);
        exit;
    }
}

//SEND VERFIFICATION EMAIL
function sendActivationEmail($email, $username)
{
    //SET BY DEFAULT
    $ASUNTO = 'Confirma tu correo';

    $REMITENTE = 'ESCEN <no-reply@escen.com>';

    $MESSAGE = $username . ",\n Para proceder con la validacion de tu cuenta es necesario que ingreses al siguiente link: \n" . generateValidationURL("http://localhost/simulador%20ceneval/sesion/activacion.php", $email);

    mail($email, $ASUNTO, $MESSAGE, 'From: ' . $REMITENTE);
}

//VALIDATE A USER
function validateUser($code, $email)
{
    global $conn;
    $qry = "SELECT * FROM validacion_usuario WHERE codigo = '$code' AND correo = '$email'";
    $res = mysqli_query($conn, $qry);
    //asking if the data exist
    if (mysqli_num_rows($res) > 0) {
        //Updating account state...
        $query = "UPDATE usuarios set estado = 'Activo' WHERE correo = '$email' AND estado = 'Pendiente'";
        //in case the data match
        if (mysqli_query($conn, $query)) {
            $res = mysqli_fetch_assoc($res);
            $qry = "DELETE FROM validacion_usuario WHERE id = '" . $res['id'] . "'";
            mysqli_query($conn, $qry);
            echo "validado con exito";
        }
    } else {
        echo "Ocurrio un error con la validacion o este usuario ya fue validado";
    }
}

//CREATE A URL WITH THE VALIDATION DATA
function generateValidationURL($domain, $email)
{
    global $conn;
    $codigo = substr(md5(uniqid(mt_rand(), true)), 0, 10);
    $qry = "INSERT INTO validacion_usuario (correo, codigo) VALUES ('$email', '$codigo')";
    mysqli_query($conn, $qry);

    return $domain . "?code=" . $codigo . "&email=" . $email;
}
