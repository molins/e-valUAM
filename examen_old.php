<?php

	include 'funciones.php';

	session_start();
	date_default_timezone_set('Europe/Madrid');

	$con = connect()
	or die('No se ha podido conectar con la base de datos. Prueba de nuevo más tarde. Si ves al técnico dile que "'. pg_last_error().'"');

	// Este primer if es en caso de que sea el comienzo del examen
	if (isset($_REQUEST['idExamen'])) {
		// Nos ponemos como locos a inicializar todo
		$_SESSION['idExamen'] = $_REQUEST['idExamen'];
		$_SESSION['sigueExamen?'] = true;

		// Ciertos parametros vienen de la base de datos.
		$result =  pg_query_params($con,
			'SELECT num_preguntas, num_por_nodo, num_dificultades, num_respuestas, duracion, materias.id as materias_id, acepta_duda
			FROM examenes, materias
			WHERE examenes.id =  $1 and id_materia = materias.id',
			array($_SESSION['idExamen']))
		or die('La consulta fallo: ' . pg_last_error());

		$line = pg_fetch_array($result, null, PGSQL_ASSOC);

		$_SESSION['restante'] = $line['duracion'];
		$_SESSION['final'] =  time() + ($line['duracion'] * 60);
		$_SESSION['materias_id'] = $line['materias_id'];

		$_SESSION['num_preguntas'] = $line['num_preguntas'];
		$_SESSION['numRespondidas'] = 0;
		$_SESSION['numCorrectas'] = 0;

		$_SESSION['num_dificultades'] = $line['num_dificultades'];
		$_SESSION['num_respuestas'] = $line['num_respuestas'];
		$_SESSION['nivel'] = 1;
		$_SESSION['numEnNivel'] = 0;

		$_SESSION['num_por_nodo'] = $line['num_por_nodo'];
		$_SESSION['numEnNodo'] = 0;
		$_SESSION['numCorrectasEnNodo'] = 0;

		$_SESSION['acepta_duda'] = ($line['acepta_duda'] == 't' ? TRUE : FALSE);

		unset($_REQUEST['idExamen']);

		pg_free_result($result);

		//Por ultimo, guardamos en la base de datos que este examen comienza
		$time = time();


		pg_query_params($con,
			'INSERT INTO alumnos_por_examen VALUES ($1, $2, $3);',
			array($_SESSION['idUsuario'], $_SESSION['idExamen'], date(DATE_ISO8601)))
		or die('La actualizacion falló: '.pg_last_error());

		$result =  pg_query_params($con,
			'SELECT id
			FROM alumnos_por_examen
			WHERE id_alumno = $1 and id_examen = $2 and timestamp = $3
			LIMIT 1',
			array($_SESSION['idUsuario'], $_SESSION['idExamen'], date(DATE_ISO8601, $time)))
		or die('La consulta fallo: ' . pg_last_error());

		$row = pg_fetch_array($result, null, PGSQL_ASSOC);
		$_SESSION['idAlumnoExamen'] = $row['id'];
		pg_free_result($result);

	}
	else if ($_SESSION['sigueExamen?'])
	{
		// Aquí se llega después de responder a una pregunta
		// Primero guardamos la respuesta
		$idRespuesta = $_SESSION['respuestas'][$_REQUEST['respuesta']];
		$time = time();

		$_SESSION['restante'] = ceil(($_SESSION['final'] - $time) / 60);

		$duda = NULL;
		if ($_SESSION["acepta_duda"])
			$duda = ($_REQUEST['duda'] == 't' ? 't' : 'f');

		pg_query_params($con,
			'INSERT INTO respuestas_por_alumno VALUES ($1, $2, $3, $4, $5);',
			array($_SESSION['idUsuario'], $idRespuesta, date(DATE_ISO8601, $time), $_SESSION['idAlumnoExamen'], $duda))
		or die('La actualizacion falló: '.pg_last_error());

		// Examen de Sacha
		if (intval($_SESSION['materias_id']) == 7) {
			if (strcmp($_REQUEST['respuesta'], $_SESSION['correcta']) == 0) {
				$_SESSION['numCorrectas']++;
				$_SESSION['correcta'] = TRUE;
			} else {
				$_SESSION['correcta'] = FALSE;
			}

			$_SESSION['numRespondidas']++;

			if ($_SESSION['numRespondidas'] == 30) {
				$_SESSION['nivel']++;
			}

			if ($_SESSION['numRespondidas'] == $_SESSION['num_preguntas']) {
				$_SESSION['sigueExamen?'] = false;
				header("Location: ./finExamen_old.php");
				exit;
			}
		} else {

			// Lógica del examen
			if (strcmp($_REQUEST['respuesta'], $_SESSION['correcta']) == 0) {
				$_SESSION['numCorrectasEnNodo']++;
				$_SESSION['correcta'] = TRUE;
			} else {
				$_SESSION['correcta'] = FALSE;
			}

			$_SESSION['numEnNodo']++;


			if ($_SESSION['numEnNodo'] == $_SESSION['num_por_nodo']) {
				$_SESSION['numRespondidas']++;

				if ($_SESSION['numCorrectasEnNodo'] == $_SESSION['num_por_nodo']) {
					$_SESSION['numCorrectas']++;
					$_SESSION['numEnNivel']++;

					if ($_SESSION['numEnNivel'] == $_SESSION['num_preguntas'] / $_SESSION['num_dificultades']) {
						$_SESSION['nivel']++;

						$_SESSION['numEnNivel'] = 0;
					}
				}


				$_SESSION['numEnNodo'] = 0;
				$_SESSION['numCorrectasEnNodo'] = 0;
			}

			if ($_SESSION['numRespondidas'] == $_SESSION['num_preguntas']) {
				$_SESSION['sigueExamen?'] = false;
				header("Location: ./finExamen_old.php");
				exit;
			}

		}


	} else {
		header("Location: /error.php");
		exit;
	}

	/*
	<p><?php echo $_REQUEST['respuesta']." ".$_SESSION['correcta']; ?></p>
			<p><?php echo $_SESSION['numRespondidas']; ?></p>
			*/

?>



<html>
	<head>
		<title>e-valUAM 2.0</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" type="text/css" href="estilo.css">
		<link rel="shortcut icon" href="favicon.png" type="image/png"/>
		<!-- bootstrap -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
		<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>

		<script type="text/javascript">
			var minRes = <?php echo $_SESSION['restante']; ?>;
			var mostrar = getCookie('mostrar');

			if (mostrar == null)
				setCookie('mostrar', 'true', null);

			window.onbeforeunload = function(e) {
				return "Si sales de esta página sin dar una respuesta esta pregunta se contabilizará como una respuesta erronea.";
			};

			function goodExit() {
				window.onbeforeunload = null;
			}

			function countredirect(){
				if (minRes > 0){
					minRes -= 1;
					if (mostrar == 'true')
							document.getElementById("tiempo").innerHTML = minRes + " minutos restantes.";
				} else {
					goodExit();
					window.location = "./finExamen_old.php";
					return
				}

				setTimeout("countredirect()", 60000);
			}

			function cambiarTiempo(){
				if (mostrar == 'true')
					mostrar = 'false';
				else
					mostrar = 'true';

				setCookie('mostrar', mostrar, null);

				mostrarTiempo();
			}

			function mostrarTiempo() {
				if (mostrar == 'true') {
					document.getElementById("tiempo").innerHTML = minRes + " minutos restantes.";
					document.getElementById("botonMostrar").innerHTML = "Ocultar";
				} else {
					document.getElementById("tiempo").innerHTML = "";
					document.getElementById("botonMostrar").innerHTML = "Mostrar tiempo restante";
				}
			}

			function getCookie(c_name) {
				var c_value = document.cookie;
				var c_start = c_value.indexOf(" " + c_name + "=");
				if (c_start == -1)
				{
					c_start = c_value.indexOf(c_name + "=");
				}
				if (c_start == -1)
				{
					c_value = null;
				}
				else
				{
					c_start = c_value.indexOf("=", c_start) + 1;
					var c_end = c_value.indexOf(";", c_start);
					if (c_end == -1)
					{
						c_end = c_value.length;
					}
					c_value = unescape(c_value.substring(c_start,c_end));
				}
				return c_value;
			}

			function setCookie(c_name, value, exdays)
			{
				var exdate=new Date();
				exdate.setDate(exdate.getDate() + exdays);
				var c_value = escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
				document.cookie=c_name + "=" + c_value;
			}
		</script>
	</head>

	<body onload="countredirect();mostrarTiempo()">
		<?php mostrar_header(); ?>

		<div class="container-fluid">
			<div class="row">
				<div class="col-md-4 col-md-offset-4">
					<p class="text-center"><span id="tiempo"></span> <button class="btn btn-primary" id="botonMostrar" onclick="cambiarTiempo()"></button></p>
				</div>
			</div>
		</div>

		<?php
			if (isset($_SESSION['feedback'])) {
				if ($_SESSION['correcta']) {
		?>
			<div class="container-fluid">
			<div class="alert alert-success" role="alert">
			<button type="button" class="close" data-dismiss="alert">
			  <span aria-hidden="true">&times;</span>
			  <span class="sr-only">Cerrar</span>
			</button><p>¡Correcto! <?php echo $_SESSION['feedback'];?></p></div>
			</div>
		<?php
				} else {
		?>
			<div class="container-fluid">
			<div class="alert alert-danger" role="alert">
			<button type="button" class="close" data-dismiss="alert">
			  <span aria-hidden="true">&times;</span>
			  <span class="sr-only">Cerrar</span>
			</button><p>No… <?php echo $_SESSION['feedback'];?></p></div>
			</div>
		<?php
				}
			}
			unset($_SESSION['feedback']);
			unset($_SESSION['correcta']);
		?>

		<main class="container-fluid">
			<div class="row">
				<div class="col-md-12">
					<?php
						$result =  pg_query_params($con,
							'	(SELECT id, texto, imagen, audio, feedback
								FROM preguntas
								WHERE id_materia = $1 and dificultad = $2 and borrada = FALSE
								)
							EXCEPT
								(SELECT preguntas.id, preguntas.texto, preguntas.imagen, preguntas.audio, preguntas.feedback
								FROM (preguntas INNER JOIN respuestas ON preguntas.id = id_pregunta)
									INNER JOIN respuestas_por_alumno ON respuestas.id = id_respuesta
								WHERE id_alumno = $3 and id_alumno_examen = $4
								)
							',
							array($_SESSION['materias_id'], $_SESSION['nivel'], $_SESSION['idUsuario'], $_SESSION['idAlumnoExamen']))
						or die('La consulta de la pregunta falló: ' . pg_last_error());

						// Examen de Santiago
						if (pg_num_rows($result) == 0) {
							$_SESSION['sigueExamen?'] = false;
							header("Location: ./finExamen_old.php");
							exit;
						}

						$pregunta = pg_fetch_array($result, rand(0, pg_num_rows($result) - 1), PGSQL_ASSOC);

						echo "<h1 class=\"activaAudioPrincipal\" id=\"textoPregunta\">".$pregunta['texto']."</h1>";

						if (strlen($pregunta['imagen']) >= 5) {
							echo "<img class=\"img-responsive activaAudioPrincipal\"  id=\"imagen\" src=\"./multimedia/".$_SESSION['materias_id']."/".$pregunta['imagen']."\"/>";
						}

						if (isset($pregunta['audio'])) {
							echo "<audio controls preload=\"auto\" id=\"audioPrincipal\">";
							echo "<source src=\"./multimedia/".$_SESSION['materias_id']."/".$pregunta['audio']."\" type=\"audio/mpeg\">";
							echo "Tu navegador no soporta audio. Por favor, actualiza <a href=\"http://browsehappy.com/\">a un navegador más moderno.</a>";
							echo "</audio>";
						}

						$_SESSION['feedback'] = $pregunta['feedback'];
					?>
				</div>
			</div>
			<div class="row" id="respuestas">
					<?php
						$letras = array("A", "B", "C", "D", "E", "F", "G", "H");

						$result =  pg_query_params($con,
							'SELECT id, texto, correcta, imagen, audio
							FROM respuestas
							WHERE id_pregunta = $1
							ORDER BY RANDOM()',
							array($pregunta['id']))
						or die('La consulta fallo: ' . pg_last_error());

						switch ($_SESSION['num_respuestas']) {
							case 2:
								$class = "col-md-6";
								break;
							case 3:
								$class = "col-md-4";
								break;
							case 4:
								$class = "col-md-3";
								break;
							case 5:
							case 6:
								$class = "col-md-2";
								break;
							default:
								$class = "col-md-1";
						}


						for ($i = 0; $respuestas = pg_fetch_array($result, null, PGSQL_ASSOC); $i++) {
							echo "<div class=\"$class\">";
							echo "<p class=\"lead\" id=\"respueta\">".$respuestas['texto']."</p>";

							if (isset($respuestas['imagen'])) {
								echo "<img class=\"img-responsive\" src=\"./multimedia/".$_SESSION['materias_id']."/".$respuestas['imagen']."\"/>";
							}

							if (isset($respuestas['audio'])) {
								echo "<audio controls preload=\"auto\" id=\"audioPrincipal\">";
								echo "<source src=\"./multimedia/".$_SESSION['materias_id']."/".$respuestas['audio']."\" type=\"audio/mpeg\">";
								echo "Tu navegador no soporta audio. Por favor, actualiza <a href=\"http://browsehappy.com/\">a un navegador más moderno.</a>";
								echo "</audio>";
							}
							echo "</div>";


							if ($respuestas['correcta'] == "t")
								$_SESSION['correcta'] = $letras[$i];

							$_SESSION['respuestas'][$letras[$i]] = $respuestas['id'];
						}
					?>
			</div>
		</main>

		<footer class="container-fluid">
			<form action="examen_old.php" method="post" onsubmit="goodExit()">
				<?php if ($_SESSION['acepta_duda']) { ?>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
							  <label>
							    <input name="duda" type="checkbox" value="t"> Dudo de la respuesta
							  </label>
							</div>
						</div>
					</div>
				<?php } ?>
				<div class="row">
					<?php

						switch ($_SESSION['num_respuestas']) {
							case 2:
								$class = "col-md-6";
								break;
							case 3:
								$class = "col-md-4";
								break;
							case 4:
								$class = "col-md-3";
								break;
							case 5:
							case 6:
								$class = "col-md-2";
								break;
							default:
								$class = "col-md-1";
						}

						for ($i = 0; $i < (0 + $_SESSION['num_respuestas']); $i++) {
							echo "<div class=\"$class\">";
								echo "<button name=\"respuesta\" value=\"$letras[$i]\" type=\"submit\" class=\"btn btn-primary btn-lg btn-block\">$letras[$i]</button>";
							echo "</div>";
						}
						pg_free_result($result);
					?>
				</div>
			</form>
		</footer>
	</body>
</html>