<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Menu legali</title>
  </head>
  <body>
    <form method="post" action="privacy.php">
	<p>Ragione sociale Azienda<br /><input type="text" id="nome" name="nome" size="40"></p>
	<p>Indirizzo (Sede Legale)<input type="text" id="indirizzo" name="indirizzo">
		Nr: <input type="tel" id="nr" name="nr" maxlength="4" size="10">
		CAP <input type="text" id="cap" name="cap" size="15">
		Città: <input type="text" id="citta" name="citta" size="40">
		Provincia: <input type="text" id="prov" maxlength="2" name="prov" size="10">
	</p>	
	<p>Sito web (inserire www.)<br /><input type="text" id="sito" name="sito" placeholder="www..." size="30"></p>
	<p>Email<br /><input type="text" id="emailprivacy" name="emailprivacy" placeholder="usiamo la stessa dei contatti a meno di richieste specifiche" size="50"></p>
	<p>Nome del sito (opzionale)<br /><input type="text" id="nomesito" name="nomesito" size="30"></p>
	<p>Nome e Cognome Amministratore<br /><input type="text" id="amministratore" name="amministratore" size="40"></p>
	<p>Telefono<br /><input type="tel" id="tel" name="tel"></p>
	<p>Piva<br /><input type="text" id="piva" name="piva" size="30"></p>
	<p>Cod. Fiscale<br /><input type="text" id="cf" name="cf" size="30"></p>
	<hr>
	<p>Gestore Dati (in base a progetto e server)<br />	
	 <select name='gestore' id="gestore" name="gestore" >
        <option value='EDYSMA sas di Marco Gallerani & C con sede in via Emilia 357, 40011 Anzola Emilia (Bologna)'>edysma</option>
        <option value='FEDERICA MATTEUCCI con sede in via Goethe 12, 40128 Bologna'>FM</option>
    </select>
	</p>
	<hr>
	<p><input type="submit" id="invia" name="invia">
	</form>
  </body>
</html>


