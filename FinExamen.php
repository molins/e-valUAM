<?php

	include 'funciones.php';

	session_start();

	// Calculamos la nota
	if($_SESSION['num_respuestas'] <= 1)
		$prob_fallo = 0;
	else
		$prob_fallo = 1.0 / ($_SESSION['num_respuestas'] -1);

	$valor_pregunta = 10.0 / $_SESSION['num_preguntas'];
	$num_fallos = $_SESSION['num_preguntas'] - $_SESSION['numCorrectas'];

	$nota = $valor_pregunta * ($_SESSION['numCorrectas'] - $prob_fallo * $num_fallos);

	// Puede salir negativa, porque compensamos al poder responder al azar, pero 0 es el mínimo
	if ($nota < 0)
		$nota = 0;

	// Se guarda en la base de datos
	$con = connect()
    or die('No se ha podido conectar con la base de datos. Prueba de nuevo más tarde. Si ves al técnico dile que "'. pg_last_error().'"');

    pg_query_params($con,
		'UPDATE alumnos_por_examen SET nota = $1 WHERE id = $2;',
		array($nota, $_SESSION['idAlumnoExamen']))
	or die('La actualizacion falló: '.pg_last_error());

	// Miramos si debe actualizarse el saco

	if($_SESSION['tipo_examen']=='saco'){
		$result = pg_query_params($con,
		'SELECT nota FROM alumnos_por_examen WHERE id_alumno = $1 AND id_examen = $2;',
		array($_SESSION['idUsuario'], $_SESSION['idExamen']))
		or die('La actualizacion falló: '.pg_last_error());

		// Miramos cuandos 9 ha habido
		$nueves = 0;
		while ($res = pg_fetch_array($result, null, PGSQL_ASSOC)) {
			if ($res['nota'] >= 9)
				$nueves++;
		}

		pg_free_result($result);


		// Si hay 0 o 1 nueve, está en el saco 1. Si 2 o 3, saco 2. 4 o 5, saco 3.
		if (($_SESSION['saco'] < 3) && (($_SESSION['saco'] * 2) < $nueves)) {
			pg_query_params($con,
			'UPDATE saco_por_examen SET num_saco = $1 WHERE id_alumno = $2 and id_examen = $3;',
			array($_SESSION['saco'] + 1, $_SESSION['idUsuario'], $_SESSION['idExamen']))
			or die('La actualizacion falló: '.pg_last_error());
		}
	}


?>

<!DOCTYPE html>

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
	</head>

	<body>
		<?php mostrar_header(); ?>

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
			</button><p>Respuesta incorrecta.</p></div>
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
					<h1>Fin del examen.</h1>
				</div>
			</div>
				<?php
					$result = pg_query_params($con,
						'SELECT mostrar_resultados FROM examenes WHERE id = $1;',
						array($_SESSION['idExamen']))
					or die('La busqueda falló: '.pg_last_error());

					$row = pg_fetch_array($result, null, PGSQL_ASSOC);

					if ($row['mostrar_resultados'] == 'parcial') {
						echo "<div class=\"row\"><div class=\"col-md-12\"><p>Tu nota es ".$nota.".</p></div></div>";
					} else if ($row['mostrar_resultados'] == 'completo') {
						
							if($_SESSION['num_respuestas'] != 1 ){
								$result =  pg_query_params(
								$con,
								'SELECT p.texto AS preg, r2.correcta AS cor, r2.texto AS res, r2.timestamp AS time, p.imagen AS img
								FROM preguntas AS p INNER JOIN
								(respuestas AS r INNER JOIN respuestas_por_alumno AS rpa ON r.id = rpa.id_respuesta) AS r2 ON p.id = r2.id_pregunta
								WHERE r2.id_alumno_examen = $1
								ORDER BY time',
								array(intval($_SESSION['idAlumnoExamen'])))
							or die('La consulta fallo: ' . pg_last_error());
						} else{
							$result =  pg_query_params(
								$con,
					'SELECT p.texto AS preg, resp.correcta AS cor, resp.texto AS res, resp.timestamp AS time, p.imagen AS img, resp.respuesta AS rpa
								FROM preguntas AS p INNER JOIN
								(SELECT * FROM respuestas AS r NATURAL JOIN respuestas_abiertas AS rpa  WHERE id_alumno_examen = $1 ) AS resp 									ON p.id = resp.id_pregunta
								ORDER BY time;',
								array(intval($_SESSION['idAlumnoExamen'])))
							or die('La consulta fallo: ' . pg_last_error());

						}

						echo "<div class=\"row\"><div class=\"col-md-12\"><h1>Resultados:</h1>";
						echo "<p>Tu nota es ".number_format($nota, 2).".</p>";
						echo "<p>A continuación verás tus respuestas. Aparecerán en rojo aquellas que sean incorrectas.</p></div></div>";
						
						
						for ($i = 1; $res = pg_fetch_array($result, null, PGSQL_ASSOC); $i++) {
							echo "<div class=\"row\"><div class=\"col-md-12\"><section class=\"respuestas\">";
								echo "<p class=\"lead\">[Preg. #".$i."] ".$res['preg'].":</p>";
								if (strlen($res['img']) >= 5) {
										echo "<img id=\"imagen\" src=\"./multimedia/".$_SESSION['materias_id']."/".$res['img']."\"/>"; //ID EXAMEN
									}
								if($_SESSION['num_respuestas'] != 1 ){//Tipo test
									if ($res['cor'] == 't') {
										echo "<p class=\"correcta\">".$res['res']."</p>";
									} else {
										echo "<p class=\"incorrecta\">".$res['res']."</p>";
									}
								}else { //Respuesta abierta
									if (strcmp($res['res'],$res['rpa']) == 0) {
										echo "<p class=\"correcta\"> ".$res['rpa']."</p>";
									} else {
										echo "<p class=\"incorrecta\"> ".$res['rpa']."</p>";
									}
								}
							echo "</section></div></div>";
						}
					}
				?>
		</main>
		<footer class="container-fluid">
			<div class="row">
				<div class="col-md-12">
					<a class="btn btn-primary" href="eleccionExamen.php" role="button">Terminar</a>
				</div>
			</div>
		</footer>
	</body>
</html>


<?php
	unset($_REQUEST['idExamen']);
	unset($_SESSION['sigueExamen?']);
	unset($_SESSION['idExamen']);
	unset($_SESSION['tipo_examen']);
	//session_destroy();
?>
