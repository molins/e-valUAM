<?php

	include 'funciones_profesor.php';

	check_login();

?>

<!DOCTYPE html>

<html>
	<head>
		<title>e-valUAM 2.0 - Ayuda</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" type="text/css" href="../estilo.css">
		<link rel="shortcut icon" href="../favicon.png" type="image/png"/>
		<!-- bootstrap -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
		<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
	</head>

	<body>
		<?php mostrar_header_profesor(); mostrar_navegacion_profesor(basename(__FILE__)); ?>
		<main class="container-fluid">
			<div class="row">
				<div class="col-md-12">
					<h2>Novedades</h2>
					<p>Para acceder a las preguntas enviada por los alumnos, <a href="visorPreguntasAlumnos.php">pulsa aquí.</a></p>
					<br>
					<br>

					<h2>Introducción</h2>
					<p>En e-valUAM todo se organiza en torno a tres conceptos principales: materias, preguntas y exámenes.</p>
					<p>Una <strong>pregunta</strong> es exactamente lo que parece: una cuestión que los alumnos deberán responder. Tiene una respuesta correcta y varias incorrectas, además de poder contener imágenes o grabaciones de audio.</p>
					<p>Las preguntas se dividen en <strong>niveles</strong>. Los niveles menores agrupan las preguntas más básicas mientras que los niveles más altos agrupan las preguntas avanzadas. Un alumno no responderá preguntas de un nivel alto hasta que no haya respondido correctamente suficientes preguntas del nivel anterior. El número de pregutnas que deberá responder dependerá del número de niveles total y del número de preguntas que tenga un examen (por ejemplo, si el examen tiene 30 preguntas y tres niveles, deberá responder correctamente 10 preguntas del primer nivel para empezar a ver pregutnas del segundo nivel, y otras 10 correctas para pasar al tercer nivel).</p>
					<p>Todas las preguntas pertenecen a una <strong>materia</strong>, que no es más que un conjunto de preguntas con un tema común y unas características iguales (número de niveles y número de respuestas)</p>
					<p>Las preguntas se agrupan en materias para que un mismo conjunto de preguntas, es decir, para una misma materia, se puedan plantear varios exámenes distintos. Un <strong>examen</strong> define cuántas preguntas tendrán que responder los alumnos y en cuánto tiempo, además de un par de cuestiones adicionales, como si podrán ver los resultados al final o si podrán marcar si dudaban al responder la pregunta.</p>
					<p>e-valUAM pretende facilitar la labor de los docentes al permitir crear un conjunto robusto y extenso de preguntas con el que se puedan ir creando exámenes o pruebas de autoevaluación para los alumnos de una manera rápida y sencilla. Permite ver cada examen, analizar qué preguntas estaban peor planteadas o qué partes del temario no llegaron bien a los alumnos.</p>
					<p>e-valUAM es una herramienta actualmente en desarrollo, por lo que cada vez irá añadiendo más características</p>

					<h2>Preguntas frecuentes</h2>
					<ul>
						<li>
							<h3>Quiero que mis preguntas tengan imagenes/audio, pero no sé cómo</h3>
							<p>Incluir archivos multimedia requiere de dos pasos. Da igual el órden en el que se hagan, pero ambos deben realizarse para que la pregunta se muetre correctamente a los alumnos</p>
							<p>Por un lado, se deberán subir los ficheros de audio o imagen al servidor. Lo puedes hacer desde la pestaña de <a href="gestionMultimedia.php">Ficheros multimedia.</a></p>
							<p>Por otro, deberás indicar al crear la pregunta los nombres de los ficheros de imagen/audio que quieres asociar a ella.</p>
							<p>Si al crear la pregunta se te olvidó escribir el nombre o hay un error, puedes editar la pregunta desde la página de <a href="gestionPreguntas.php">Preguntas</a>. Si se te ha olvidado el nombre del fichero, puedes consultarlo en la sección de abajo del todo de <a href="gestionMultimedia.php">Ficheros multimedia.</a></p>
						</li>
						<li>
							<h3>Cuando entro en la sección de alumnos, no logro ver mi examen</h3>
							<p>Para que un examen sea visible a los alumnos no basta con crearlo, sino que hay que marcarlo como visible. Cuando crees el examen en <a hreg="gestionExamenes.php">Exámenes</a>, revisa que marcas la casilla de <em>¿Está el examen visible?</em></p>
						</li>
						<li>
							<h3>He creado un examen pero hay un error en alguno de sus campos. ¿Cómo puedo corregirlo?</h3>
							<p>Ahora mismo la única manera es borrar el examen que tiene el error y crear uno nuevo con ese campo corregido. Estamos trabajando en ofrecer algo más cómodo.</p>
						</li>
						<li>
							<h3>No encuentro respuesta a mi pregunta en esta página</h3>
							<p>Escribe un correo electrónico a sacha.gomez@uam.es / pablo.molins@uam.es y te responderemos lo antes posible.</p>
						</li>
					</ul>

					<br>
				</div>
			</div>
		</main>
	</body>
</html>
