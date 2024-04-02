<?php
session_start();  // Inicia a session



$email = $_POST['email'];
$senha = $_POST['senha'];


if((!$email) || (!$senha)){

	echo " Por favor, todos campos devem ser preenchidos! <br /><br />";
	
	include "f_login.php";

}

else{

	 $senha_encriptada = md5($senha);





include "config.php";

	$sql = mysqli_query($conn, "SELECT * FROM usuario WHERE email='{$email}' AND senha='{$senha_encriptada}'");
	
	$login_check = mysqli_num_rows($sql);

	if($login_check > 0){

		while($row = mysqli_fetch_array($sql)){

			foreach( $row AS $key => $val ){

				$$key = stripslashes( $val );

			}

//ainda não abordaremos as sessoes no PHP!

			$_SESSION['email'] = $email;

			
//REDIRECIONAMENTO PARA A PAGINA DE LOGADO NO SISTEMA

			header("Location: f_pagina_inicial.php");

		}

	}
	else{

		echo " Voc&ecirc; n&atilde;o pode logar-se! <br />Este usu&aacute;rio e/ou senha n&atilde;o s&atilde;o v&aacute;lidos!<br />
			Por favor tente novamente ou contate o adminstrador do sistema!<br />";

		include "f_login.php";

	}
}
?>
