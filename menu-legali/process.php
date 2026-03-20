<?php
include 'menu.php';

if(isset($_POST["invia"])) {
	//session_start();
	$nome = $_POST["nome"];
	$indirizzo = $_POST["indirizzo"];
	$nr = $_POST["nr"];
	$cap = $_POST["cap"];
	$citta = ($_POST["citta"]);
	$prov = strtoupper($_POST["prov"]);
	$sito = strtolower($_POST["sito"]);
	$emailprivacy = strtolower($_POST["emailprivacy"]);
	if(isset($_POST["nomesito"])) {
		$nomesito = $_POST["nomesito"];
	} else {
		$nomesito = $_POST["nome"];
	}
	
	//$pemail = 'privacy[@]' .str_replace('www.', '', $sito);
	$pemail = str_replace('@', '[@]', $emailprivacy);      
	$amministratore = $_POST["amministratore"];
	$tel = $_POST["tel"];
	$piva = $_POST["piva"];
	$cf = $_POST["cf"];
	$gestore = $_POST["gestore"];

	
	$_SESSION["nome"] = $nome;
	$_SESSION["indirizzo"] = $indirizzo .', ' .$nr .' '.$cap .' '.$citta.' ('.$prov.')';
	//$_SESSION["nr"] = $nr;
	//$_SESSION["cap"] = $cap;
	//$_SESSION["prov"] = $prov;
	$_SESSION["sito"] = $sito;
	$_SESSION["nomesito"] = $nomesito;
	$_SESSION["pemail"] = $pemail;
	$_SESSION["amministratore"] = $amministratore;
	$_SESSION["tel"] = $tel;
	$_SESSION["piva"] = $piva;
	$_SESSION["cf"] = $cf;
	$_SESSION["gestore"] = $gestore;
	/* Creo lo zip con i files vcf
	$zip = new ZipArchive;	
	$zipName = 'legal.zip';
	if($zip -> open($zipName, ZipArchive::CREATE ) === TRUE) {									   
		$zip -> addFile('privacy.php', 'privacy.php');
		$zip -> addFile('cookies.php', 'cookies.php');
		$zip ->close();
	}
	*/
}
?>