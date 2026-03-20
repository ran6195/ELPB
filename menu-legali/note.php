<?php 
session_start();
include 'process.php'; 
?>
<hr>
<p>Di seguito elencati i dati sociali di <?php echo str_replace('www.', '', strtolower($_SESSION["sito"])); ?>:</p>
<table style="width: 100%;">
	<tbody>
		<tr>
			<td>Ragione sociale:</td>
			<td><?php echo $_SESSION["nome"]; ?></td>
		</tr>
		<tr>
			<td>Sede legale:</td>
			<td>
				<p><?php echo $_SESSION["indirizzo"]; ?></p>
			</td>
		</tr>
		<tr>
			<td>Amministratore:</td>
			<td><?php echo $_SESSION["amministratore"]; ?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Telefono:</td>
			<td><strong><?php echo $_SESSION["tel"]; ?></strong></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2">
				<p>Per contatti via email <span style="color: #ff0000;">(VERIFICARE URL)</span> <a href="index.php?option=com_content&amp;view=article&amp;id=28&amp;Itemid=137">clicca qui&nbsp;</a></p>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Partita Iva:</td>
			<td><?php echo $_SESSION["piva"]; ?></td>
		</tr>
		<tr>
			<td>Codice Fiscale:</td>
			<td><?php echo $_SESSION["cf"]; ?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2">
				<p><em><a href="#">Clicca qui <span style="color: #ff0000;">(VERIFICARE URL)</span</a>per note relative alla privacy</em></p>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>