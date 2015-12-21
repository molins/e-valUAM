<?php

	include 'funciones_profesor.php';

	check_login();


	$con = connect()
    or die('No se ha podido conectar con la base de datos. Prueba de nuevo más tarde. Si ves al técnico dile que "'. pg_last_error().'"');
?>


<html>
	<head>
		<title>e-valUAM 2.0 - Zona del profesor</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" type="text/css" href="../estilo.css">
		<link rel="shortcut icon" href="favicon.png" type="image/png"/>
		<!-- bootstrap -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
		<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
		
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js">
		</script>
		<script>
			$(document).ready(function() {
			  $(window).keydown(function(event){
			    if(event.keyCode == 13) {
			      event.preventDefault();
			      return false;
			    }
			  });
			});

			function loadXMLDoc() {
				var min = document.getElementById("min").value;
				var num = $("input[name='idExamen']:checked").val();

				if (num == null)
					return;

				var xmlhttp;
				if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
					xmlhttp = new XMLHttpRequest();
				} else { // code for IE6, IE5
					xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
				}

				xmlhttp.onreadystatechange = function() {
					if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
						document.getElementById("seleccionAlumno").innerHTML = xmlhttp.responseText;
					}
				}

				xmlhttp.open("post", "estadisticasRequest.php", true);
		        xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		        xmlhttp.send("id=" + num + "&min=" + min);
			}
		</script>
	</head>

	<body>

		<?php mostrar_header_profesor(); mostrar_navegacion_profesor(basename(__FILE__)); ?>

		<main class="container-fluid">
			<div class="row">
				<div class="col-md-12">
					<h1>Estadísticas por pregunta</h1>
					<p>En esta página se pueden ver las estadísticas de fallo por pregunta.</p>
					<p>Primero deberas seleccionar una materia y un número mínimo de veces que se debe haberse respondido una pregunta para tenerser en cuenta.</p>
					<p>Al final de la página aparecerá una tabla con toda la información.</p>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12" id="seleccionExamen">
					<form>
						<h2>Materias disponibles</h2>
						<p>Número mínimo de veces que debe haberse respondido una pregunta: <input onchange="loadXMLDoc()" id="min" value="5" type="number" name="min" min="1" size="4"></p>
						<table class="table table-hover">
							<thead>
							<tr>
								<th>Nombre materia</th>
								<th>Seleccionar</th>
							</tr>
							</thead>
							<tbody>
							<?php

								$result =  pg_query_params($con, 
									'SELECT ma.nombre AS nombre_ma, ma.id AS ma_id 
									FROM  materias AS ma INNER JOIN profesor_por_materia AS pm ON ma.id = pm.id_materia
									WHERE pm.id_alumno = $1', array($_SESSION['idUsuario']))
								or die('La consulta fallo: ' . pg_last_error());

								if (pg_num_rows($result) == 0) {
									echo "<tr><td>Aún no hay datos para mostrar.</td><td></td></tr>";
								} else {
									while ($examen = pg_fetch_array($result, null, PGSQL_ASSOC)) { 
										echo "<tr><td>".$examen['nombre_ma']."</td><td><input type=\"radio\" name=\"idExamen\" value=\"".$examen['ma_id']."\" onclick=\"loadXMLDoc()\"></td></tr>";
									}
								}
							?>
							</tbody>
						</table>
					</form>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12" id="seleccionAlumno">
				</div>
			</div>
		</main>
	</body>
</html>
