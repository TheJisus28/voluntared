-- VoluntaRed: base de datos completa con datos de demostración (importable desde phpMyAdmin).
-- Contraseñas de prueba: admin@voluntared.co / Admin2026 · fundaciones: Fundacion2026 · voluntarios: Voluntario2026


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!40000 DROP DATABASE IF EXISTS `voluntared`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `voluntared` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `voluntared`;
DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categoria` (
  `idCategoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`idCategoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1,'Educación','Refuerzo escolar, alfabetización, tutorías y bibliotecas comunitarias.'),(2,'Medio ambiente','Reforestación, limpieza de fuentes hídricas, reciclaje y educación ambiental.'),(3,'Salud','Brigadas de salud, campañas de prevención y acompañamiento a pacientes.'),(4,'Protección animal','Refugios, adopciones, esterilización y bienestar animal.'),(5,'Adulto mayor','Acompañamiento, recreación y cuidado de personas mayores.'),(6,'Niñez y juventud','Recreación, lectura, prevención y liderazgo juvenil.'),(7,'Gestión del riesgo','Prevención y atención de emergencias e inundaciones.'),(8,'Cultura y deporte','Eventos culturales, deportivos y artísticos comunitarios.');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `conversacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversacion` (
  `idConversacion` int(11) NOT NULL AUTO_INCREMENT,
  `voluntario_id` int(11) NOT NULL,
  `fundacion_id` int(11) NOT NULL,
  `Oportunidad_idOportunidad` int(11) DEFAULT NULL,
  `fechaCreacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idConversacion`),
  KEY `fk_conversacion_voluntario` (`voluntario_id`),
  KEY `fk_conversacion_fundacion` (`fundacion_id`),
  KEY `fk_conversacion_oportunidad` (`Oportunidad_idOportunidad`),
  CONSTRAINT `fk_conversacion_fundacion` FOREIGN KEY (`fundacion_id`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversacion_oportunidad` FOREIGN KEY (`Oportunidad_idOportunidad`) REFERENCES `oportunidad` (`idOportunidad`) ON DELETE SET NULL,
  CONSTRAINT `fk_conversacion_voluntario` FOREIGN KEY (`voluntario_id`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `conversacion` WRITE;
/*!40000 ALTER TABLE `conversacion` DISABLE KEYS */;
INSERT INTO `conversacion` VALUES (1,8,2,1,'2026-10-17 15:10:00'),(2,9,3,5,'2026-10-20 08:20:00'),(3,10,4,9,'2026-10-20 11:00:00');
/*!40000 ALTER TABLE `conversacion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `evaluacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluacion` (
  `idEvaluacion` int(11) NOT NULL AUTO_INCREMENT,
  `Participacion_idParticipacion` int(11) NOT NULL,
  `evaluador_id` int(11) NOT NULL,
  `evaluado_id` int(11) NOT NULL,
  `calificacion` tinyint(4) NOT NULL,
  `comentario` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idEvaluacion`),
  UNIQUE KEY `uq_evaluacion` (`Participacion_idParticipacion`,`evaluador_id`),
  KEY `fk_evaluacion_evaluador` (`evaluador_id`),
  KEY `fk_evaluacion_evaluado` (`evaluado_id`),
  CONSTRAINT `fk_evaluacion_evaluado` FOREIGN KEY (`evaluado_id`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluacion_evaluador` FOREIGN KEY (`evaluador_id`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluacion_participacion` FOREIGN KEY (`Participacion_idParticipacion`) REFERENCES `participacion` (`idParticipacion`) ON DELETE CASCADE,
  CONSTRAINT `ck_evaluacion_calificacion` CHECK (`calificacion` between 1 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `evaluacion` WRITE;
/*!40000 ALTER TABLE `evaluacion` DISABLE KEYS */;
INSERT INTO `evaluacion` VALUES (1,1,8,2,5,'Excelente organización y un equipo muy acogedor. Los niños son maravillosos.','2026-08-31 20:00:00',1),(2,1,2,8,5,'Ana fue puntual y comprometida en todas las sesiones.','2026-08-30 19:00:00',1),(3,2,11,2,4,'Muy buena experiencia, solo faltó algo de material.','2026-08-31 20:00:00',1),(4,2,2,11,4,'Buen manejo del grupo de niños.','2026-08-30 19:00:00',1),(5,4,9,3,5,'Jornada muy bien planificada y con un gran impacto.','2026-06-09 20:00:00',1),(6,4,3,9,5,'Excelente liderazgo y conocimiento ambiental.','2026-06-08 19:00:00',1),(7,5,14,3,4,'Muy bonita jornada, el horario fue exigente por el calor.','2026-06-09 20:00:00',1),(8,5,3,14,5,'Sus fotos fueron clave para el informe.','2026-06-08 19:00:00',1),(9,6,3,15,4,'Muy colaborador con la logística.','2026-06-08 19:00:00',1),(10,7,10,4,5,'Aprendí muchísimo, el equipo veterinario es excelente.','2026-05-18 20:00:00',1),(11,7,4,10,5,'Gran conocimiento y trato con los animales.','2026-05-17 19:00:00',1),(12,8,12,4,4,'Bien organizada, aunque la jornada fue larga.','2026-05-18 20:00:00',1),(13,9,12,5,5,'Los abuelos son encantadores, volveré.','2026-03-16 20:00:00',1),(14,9,5,12,5,'Muy empática con los adultos mayores.','2026-03-15 19:00:00',1),(15,10,15,5,4,'Buena experiencia.','2026-03-16 20:00:00',1);
/*!40000 ALTER TABLE `evaluacion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fundacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fundacion` (
  `idFundacion` int(11) NOT NULL AUTO_INCREMENT,
  `Usuario_idUsuario` int(11) NOT NULL,
  `nit` varchar(20) NOT NULL,
  `razonSocial` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `mision` varchar(500) DEFAULT NULL,
  `direccion` varchar(150) NOT NULL,
  `ciudad` varchar(80) NOT NULL,
  `sitioWeb` varchar(200) DEFAULT NULL,
  `documentoSoporte` varchar(255) NOT NULL,
  `estadoVerificacion` enum('pendiente','verificada','rechazada') NOT NULL DEFAULT 'pendiente',
  `fechaVerificacion` datetime DEFAULT NULL,
  `motivoRechazo` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`idFundacion`),
  UNIQUE KEY `Usuario_idUsuario` (`Usuario_idUsuario`),
  UNIQUE KEY `nit` (`nit`),
  CONSTRAINT `fk_fundacion_usuario` FOREIGN KEY (`Usuario_idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fundacion` WRITE;
/*!40000 ALTER TABLE `fundacion` DISABLE KEYS */;
INSERT INTO `fundacion` VALUES (1,2,'900456789-1','Fundación Sembrando Futuro','Trabajamos desde 2012 por la educación de niños y jóvenes de los barrios vulnerables de Montería con refuerzo escolar, bibliotecas comunitarias y actividades recreativas.','Garantizar que cada niño de nuestra comunidad tenga acompañamiento escolar y espacios seguros para aprender y jugar.','Calle 29 # 4-55, barrio Centro','Montería','https://sembrandofuturo.org','documentos/demo-9004567891.pdf','verificada','2026-01-17 15:00:00',NULL),(2,3,'901234567-3','Fundación Río Sinú Verde','Organización ambiental dedicada a la conservación de la cuenca del río Sinú mediante jornadas de limpieza, reforestación con especies nativas y educación ambiental en colegios.','Proteger el río Sinú y sus ecosistemas con la participación activa de la comunidad.','Carrera 1 # 41-20, Ronda del Sinú','Montería','https://riosinuverde.org','documentos/demo-9012345673.pdf','verificada','2026-01-27 15:00:00',NULL),(3,4,'900987654-2','Corporación Huellitas de Córdoba','Refugio y centro de bienestar animal que rescata, esteriliza y da en adopción perros y gatos en situación de calle en Cereté y Montería.','Reducir el abandono animal a través del rescate, la esterilización y la adopción responsable.','Calle 12 # 15-30, Cereté','Cereté',NULL,'documentos/demo-9009876542.pdf','verificada','2026-02-06 15:00:00',NULL),(4,5,'900111222-5','Fundación Abuelos Felices','Hogar de día que brinda acompañamiento, alimentación, recreación y atención básica en salud a más de 60 adultos mayores de Montería.','Dignificar la vejez con amor, compañía y cuidado.','Transversal 9 # 22-14, barrio La Castellana','Montería',NULL,'documentos/demo-9001112225.pdf','verificada','2026-02-16 15:00:00',NULL),(5,6,'901555666-0','Fundación Manos que Ayudan','Brigadas de salud y entrega de medicamentos en zonas rurales del departamento de Córdoba.',NULL,'Calle 8 # 10-21, Sahagún','Sahagún',NULL,'documentos/demo-9015556660.pdf','pendiente',NULL,NULL),(6,7,'900333444-8','Asociación Brisas del Sinú','Asociación comunitaria de pescadores y familias ribereñas.',NULL,'Calle Real # 3-10, Lorica','Lorica',NULL,'documentos/demo-9003334448.pdf','rechazada','2026-09-14 15:00:00','El certificado adjunto está vencido (expedido hace más de 30 días) y no corresponde al NIT registrado.');
/*!40000 ALTER TABLE `fundacion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `habilidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `habilidad` (
  `idHabilidad` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  PRIMARY KEY (`idHabilidad`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `habilidad` WRITE;
/*!40000 ALTER TABLE `habilidad` DISABLE KEYS */;
INSERT INTO `habilidad` VALUES (9,'Apoyo psicosocial'),(14,'Atención al público'),(7,'Cocina'),(12,'Construcción y reparaciones'),(4,'Diseño gráfico'),(1,'Enseñanza y tutorías'),(6,'Fotografía y video'),(11,'Idioma inglés'),(13,'Jardinería y siembra'),(3,'Logística de eventos'),(5,'Manejo de redes sociales'),(2,'Primeros auxilios'),(8,'Programación'),(10,'Recreación infantil');
/*!40000 ALTER TABLE `habilidad` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `mensaje`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mensaje` (
  `idMensaje` int(11) NOT NULL AUTO_INCREMENT,
  `Conversacion_idConversacion` int(11) NOT NULL,
  `emisor_id` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fechaEnvio` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idMensaje`),
  KEY `fk_mensaje_conversacion` (`Conversacion_idConversacion`),
  KEY `fk_mensaje_emisor` (`emisor_id`),
  CONSTRAINT `fk_mensaje_conversacion` FOREIGN KEY (`Conversacion_idConversacion`) REFERENCES `conversacion` (`idConversacion`) ON DELETE CASCADE,
  CONSTRAINT `fk_mensaje_emisor` FOREIGN KEY (`emisor_id`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `mensaje` WRITE;
/*!40000 ALTER TABLE `mensaje` DISABLE KEYS */;
INSERT INTO `mensaje` VALUES (1,1,8,'Hola, buenas tardes. ¿Las sesiones de refuerzo son todos los sábados?',1,'2026-10-17 15:10:00'),(2,1,2,'¡Hola, Ana! Sí, todos los sábados de 8:00 a 11:00 a. m. durante cinco semanas.',1,'2026-10-17 16:02:00'),(3,1,8,'Perfecto. ¿Debo llevar algún material?',1,'2026-10-17 16:05:00'),(4,1,2,'No es necesario, nosotros entregamos las guías y los útiles. Solo trae mucha energía 😊',1,'2026-10-18 09:30:00'),(5,1,8,'¡Genial! Allá estaré. Muchas gracias.',0,'2026-10-18 09:41:00'),(6,2,9,'Buenos días. ¿Hay punto de hidratación durante la jornada de limpieza?',1,'2026-10-20 08:20:00'),(7,2,3,'Hola, Juan Pablo. Sí, tendremos un punto de hidratación y refrigerio a las 10:00 a. m.',1,'2026-10-20 10:45:00'),(8,2,9,'Excelente, ¿puedo llevar a un amigo que no está registrado?',0,'2026-10-21 18:12:00'),(9,3,4,'Hola, Valentina. Te aceptamos en la jornada de adopción. ¿Podrías llegar a las 7:30 a. m. para ayudar con el montaje?',1,'2026-10-20 11:00:00'),(10,3,10,'¡Claro que sí! Allá estaré a las 7:30.',0,'2026-10-20 12:15:00');
/*!40000 ALTER TABLE `mensaje` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notificacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificacion` (
  `idNotificacion` int(11) NOT NULL AUTO_INCREMENT,
  `Usuario_idUsuario` int(11) NOT NULL,
  `tipo` enum('postulacion','respuesta','mensaje','oportunidad','evaluacion','sistema') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `contenido` varchar(300) NOT NULL,
  `enlace` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fechaCreacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idNotificacion`),
  KEY `ix_notificacion_usuario` (`Usuario_idUsuario`,`leida`),
  CONSTRAINT `fk_notificacion_usuario` FOREIGN KEY (`Usuario_idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notificacion` WRITE;
/*!40000 ALTER TABLE `notificacion` DISABLE KEYS */;
INSERT INTO `notificacion` VALUES (1,8,'respuesta','¡Tu postulación fue aceptada!','Fundación Sembrando Futuro aceptó tu postulación a «Refuerzo escolar en matemáticas para niños de primaria».','/postulaciones',0,'2026-10-18 12:00:00'),(2,8,'oportunidad','Nueva oportunidad para ti','Fundación Sembrando Futuro publicó «Tutorías virtuales de inglés para bachillerato» en Montería, afín a tus intereses.','/oportunidades/3',0,'2026-10-19 12:00:00'),(3,8,'mensaje','Nuevo mensaje de Fundación Sembrando Futuro','No es necesario, nosotros entregamos las guías y los útiles.','/mensajes/1',1,'2026-10-18 12:00:00'),(4,8,'evaluacion','Recibiste una evaluación','Fundación Sembrando Futuro te calificó con 5 de 5 estrellas por «Club de lectura infantil «Leer es volar»».','/historial',1,'2026-09-02 12:00:00'),(5,2,'postulacion','Nueva postulación','Mateo Vargas se postuló a «Refuerzo escolar en matemáticas para niños de primaria».','/fundacion/oportunidades/1/postulaciones',0,'2026-10-20 12:00:00'),(6,2,'postulacion','Nueva postulación','Santiago Ramírez se postuló a «Torneo de fútbol por la paz».','/fundacion/oportunidades/4/postulaciones',0,'2026-10-21 12:00:00'),(7,3,'mensaje','Nuevo mensaje de Juan Pablo Torres','Excelente, ¿puedo llevar a un amigo que no está registrado?','/mensajes/2',0,'2026-10-21 12:00:00'),(8,3,'postulacion','Nueva postulación','Andrés Morales se postuló a «Jornada de limpieza de la ronda del río Sinú».','/fundacion/oportunidades/5/postulaciones',0,'2026-10-19 12:00:00'),(9,1,'sistema','Nueva fundación por verificar','Fundación Manos que Ayudan (NIT 901555666-0) se registró y espera la revisión de su documento soporte.','/admin/fundaciones',0,'2026-10-19 12:00:00'),(10,7,'sistema','Verificación rechazada','La verificación de Asociación Brisas del Sinú fue rechazada. Motivo: El certificado adjunto está vencido.','/fundacion/perfil',0,'2026-09-14 12:00:00');
/*!40000 ALTER TABLE `notificacion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `oportunidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oportunidad` (
  `idOportunidad` int(11) NOT NULL AUTO_INCREMENT,
  `Fundacion_idFundacion` int(11) NOT NULL,
  `Categoria_idCategoria` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `requisitos` varchar(500) DEFAULT NULL,
  `modalidad` enum('presencial','virtual','mixta') NOT NULL,
  `ubicacion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(80) NOT NULL,
  `fechaInicio` date NOT NULL,
  `fechaFin` date NOT NULL,
  `horaInicio` time NOT NULL,
  `horaFin` time NOT NULL,
  `cuposTotales` int(11) NOT NULL,
  `cuposDisponibles` int(11) NOT NULL,
  `estado` enum('abierta','cerrada','finalizada','cancelada') NOT NULL DEFAULT 'abierta',
  `fechaPublicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idOportunidad`),
  KEY `fk_oportunidad_fundacion` (`Fundacion_idFundacion`),
  KEY `fk_oportunidad_categoria` (`Categoria_idCategoria`),
  KEY `ix_oportunidad_busqueda` (`estado`,`ciudad`,`modalidad`,`fechaInicio`),
  CONSTRAINT `fk_oportunidad_categoria` FOREIGN KEY (`Categoria_idCategoria`) REFERENCES `categoria` (`idCategoria`),
  CONSTRAINT `fk_oportunidad_fundacion` FOREIGN KEY (`Fundacion_idFundacion`) REFERENCES `fundacion` (`idFundacion`) ON DELETE CASCADE,
  CONSTRAINT `ck_oportunidad_fechas` CHECK (`fechaFin` >= `fechaInicio`),
  CONSTRAINT `ck_oportunidad_cupos` CHECK (`cuposTotales` > 0 and `cuposDisponibles` >= 0 and `cuposDisponibles` <= `cuposTotales`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `oportunidad` WRITE;
/*!40000 ALTER TABLE `oportunidad` DISABLE KEYS */;
INSERT INTO `oportunidad` VALUES (1,1,1,'Refuerzo escolar en matemáticas para niños de primaria','Acompaña a niños de 3.º a 5.º de primaria del barrio La Granja en sus tareas de matemáticas. Trabajamos en grupos pequeños con material didáctico preparado por la fundación.\n\nCada voluntario tendrá a cargo de 3 a 4 niños durante el periodo.','Mayor de 16 años. Paciencia y gusto por enseñar. Asistir mínimo a 4 de las 6 sesiones.','presencial','Sede comunitaria barrio La Granja, Calle 50 # 12-30','Montería','2026-11-07','2026-12-12','08:00:00','11:00:00',10,9,'abierta','2026-10-16 10:00:00',1),(2,1,6,'Club de lectura infantil «Leer es volar»','Sesiones de lectura en voz alta, cuentacuentos y manualidades con niños de 5 a 10 años en la biblioteca comunitaria.','Gusto por la lectura y el trabajo con niños.','presencial','Biblioteca comunitaria Sembrando Futuro, Calle 29 # 4-55','Montería','2026-08-01','2026-08-29','09:00:00','12:00:00',6,3,'finalizada','2026-07-14 10:00:00',1),(3,1,1,'Tutorías virtuales de inglés para bachillerato','Clases de conversación y refuerzo de inglés para estudiantes de 10.º y 11.º que se preparan para las pruebas Saber 11. Las sesiones son por videollamada.','Nivel de inglés B1 o superior. Computador con cámara y buena conexión.','virtual',NULL,'Montería','2026-11-03','2026-12-15','18:00:00','19:30:00',5,5,'abierta','2026-10-19 10:00:00',1),(4,1,8,'Torneo de fútbol por la paz','Organización y arbitraje de un torneo relámpago de fútbol infantil con equipos de cinco barrios de Montería. Necesitamos apoyo en logística, hidratación, arbitraje y registro fotográfico.',NULL,'presencial','Cancha sintética del barrio Mocarí','Montería','2026-12-05','2026-12-05','07:00:00','13:00:00',12,12,'abierta','2026-10-20 10:00:00',1),(5,2,2,'Jornada de limpieza de la ronda del río Sinú','Recolección de residuos sólidos en la margen izquierda del río Sinú, clasificación del material reciclable y registro de los residuos encontrados para el informe ambiental.','Llevar gorra, bloqueador y botella de agua reutilizable. La fundación entrega guantes y bolsas.','presencial','Avenida Primera con calle 41, frente al muelle turístico','Montería','2026-11-15','2026-11-15','07:00:00','12:00:00',30,28,'abierta','2026-10-13 10:00:00',1),(6,2,2,'Siembra de 500 árboles nativos en el parque lineal','Reforestación con especies nativas (ceiba, roble, guayacán) en el parque lineal Ronda del Sinú. Incluye charla de educación ambiental.','Mayor de 14 años (menores con autorización).','presencial','Parque lineal Ronda del Sinú, sector norte','Montería','2026-06-06','2026-06-07','06:30:00','11:30:00',25,22,'finalizada','2026-05-15 10:00:00',1),(7,2,2,'Taller de reciclaje y compostaje para colegios','Talleres prácticos de separación en la fuente y compostaje en cuatro instituciones educativas oficiales. Parte del contenido se dicta virtualmente.',NULL,'mixta','Institución Educativa La Ribera y otras sedes','Montería','2026-09-15','2026-09-30','14:00:00','17:00:00',6,4,'finalizada','2026-08-25 10:00:00',1),(8,2,7,'Simulacro comunitario de inundaciones','Apoyo a la Defensa Civil en el simulacro de evacuación por creciente del río Sinú en el barrio Rancho Grande: señalización de rutas, puntos de encuentro y atención de participantes.','Preferiblemente con curso de primeros auxilios.','presencial','Barrio Rancho Grande, punto de encuentro Parque Principal','Montería','2026-11-29','2026-11-29','08:00:00','12:00:00',15,14,'abierta','2026-10-17 10:00:00',1),(9,3,4,'Jornada de adopción y esterilización de mascotas','Apoyo en la jornada de adopción responsable y esterilización gratuita: registro de asistentes, acompañamiento de las mascotas en recuperación y orientación a las familias adoptantes.','No tener alergias a perros o gatos.','presencial','Parque Simón Bolívar','Cereté','2026-11-22','2026-11-22','08:00:00','16:00:00',12,11,'abierta','2026-10-18 10:00:00',1),(10,3,4,'Paseadores voluntarios para el refugio','Paseos y socialización de los perros del refugio tres mañanas por semana durante el último trimestre del año.','Compromiso mínimo de dos mañanas por semana.','presencial','Refugio Huellitas, vereda Mateo Gómez','Cereté','2026-10-01','2026-12-31','07:00:00','09:00:00',4,0,'cerrada','2026-09-10 10:00:00',1),(11,3,3,'Campaña de vacunación antirrábica','Apoyo a la Secretaría de Salud de Cereté en la vacunación casa a casa de perros y gatos.',NULL,'presencial','Barrios del sur de Cereté','Cereté','2026-05-16','2026-05-16','07:00:00','15:00:00',10,8,'finalizada','2026-04-25 10:00:00',1),(12,4,5,'Acompañamiento a adultos mayores en el hogar de día','Conversación, lectura, juegos de mesa y caminatas con los abuelos del hogar de día. Cada voluntario acompaña a un pequeño grupo durante la mañana.','Calidez, paciencia y puntualidad.','presencial','Hogar de día Abuelos Felices, Transversal 9 # 22-14','Montería','2026-11-01','2027-01-30','09:00:00','11:00:00',8,8,'abierta','2026-10-14 10:00:00',1),(13,4,5,'Celebración navideña con los abuelos','Organización de la novena y la cena navideña para 60 adultos mayores: decoración, preparación de alimentos, música y entrega de regalos.',NULL,'presencial','Hogar de día Abuelos Felices','Montería','2026-12-18','2026-12-18','15:00:00','20:00:00',15,15,'abierta','2026-10-21 10:00:00',1),(14,4,3,'Brigada de salud y toma de tensión','Jornada de toma de tensión arterial, glucometría y orientación en hábitos saludables para adultos mayores.','Estudiantes o profesionales del área de la salud.','presencial','Hogar de día Abuelos Felices','Montería','2026-03-14','2026-03-14','07:00:00','13:00:00',6,4,'finalizada','2026-02-19 10:00:00',1),(15,4,8,'Taller de manualidades para abuelos','Taller de tejido y pintura para los abuelos del hogar de día.',NULL,'presencial','Hogar de día Abuelos Felices','Montería','2026-10-10','2026-10-10','14:00:00','17:00:00',5,5,'cancelada','2026-09-22 10:00:00',1);
/*!40000 ALTER TABLE `oportunidad` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `oportunidad_has_habilidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oportunidad_has_habilidad` (
  `Oportunidad_idOportunidad` int(11) NOT NULL,
  `Habilidad_idHabilidad` int(11) NOT NULL,
  PRIMARY KEY (`Oportunidad_idOportunidad`,`Habilidad_idHabilidad`),
  KEY `fk_oh_habilidad` (`Habilidad_idHabilidad`),
  CONSTRAINT `fk_oh_habilidad` FOREIGN KEY (`Habilidad_idHabilidad`) REFERENCES `habilidad` (`idHabilidad`),
  CONSTRAINT `fk_oh_oportunidad` FOREIGN KEY (`Oportunidad_idOportunidad`) REFERENCES `oportunidad` (`idOportunidad`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `oportunidad_has_habilidad` WRITE;
/*!40000 ALTER TABLE `oportunidad_has_habilidad` DISABLE KEYS */;
INSERT INTO `oportunidad_has_habilidad` VALUES (1,1),(1,10),(2,1),(2,10),(3,1),(3,11),(4,3),(4,6),(4,10),(5,3),(5,13),(6,13),(7,1),(7,13),(8,2),(8,3),(9,2),(9,14),(10,14),(11,2),(11,14),(12,9),(12,14),(13,3),(13,7),(14,2),(14,9),(15,4);
/*!40000 ALTER TABLE `oportunidad_has_habilidad` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `participacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `participacion` (
  `idParticipacion` int(11) NOT NULL AUTO_INCREMENT,
  `Postulacion_idPostulacion` int(11) NOT NULL,
  `asistio` tinyint(1) NOT NULL,
  `horasCumplidas` decimal(5,2) NOT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `fechaRegistro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idParticipacion`),
  UNIQUE KEY `Postulacion_idPostulacion` (`Postulacion_idPostulacion`),
  CONSTRAINT `fk_participacion_postulacion` FOREIGN KEY (`Postulacion_idPostulacion`) REFERENCES `postulacion` (`idPostulacion`) ON DELETE CASCADE,
  CONSTRAINT `ck_participacion_horas` CHECK (`horasCumplidas` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `participacion` WRITE;
/*!40000 ALTER TABLE `participacion` DISABLE KEYS */;
INSERT INTO `participacion` VALUES (1,4,1,12.00,'Muy creativa con las actividades de lectura.','2026-08-30 18:00:00'),(2,5,1,9.00,NULL,'2026-08-30 18:00:00'),(3,6,0,0.00,'No asistió a las sesiones y no avisó.','2026-08-30 18:00:00'),(4,13,1,10.00,'Lideró uno de los grupos de siembra.','2026-06-08 18:00:00'),(5,14,1,10.00,'Hizo el registro fotográfico de la jornada.','2026-06-08 18:00:00'),(6,15,1,8.00,NULL,'2026-06-08 18:00:00'),(7,27,1,8.00,NULL,'2026-05-17 18:00:00'),(8,28,1,8.00,NULL,'2026-05-17 18:00:00'),(9,31,1,6.00,'Apoyó en la orientación psicosocial.','2026-03-15 18:00:00'),(10,32,1,6.00,NULL,'2026-03-15 18:00:00');
/*!40000 ALTER TABLE `participacion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `perfil_voluntario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perfil_voluntario` (
  `idPerfilVoluntario` int(11) NOT NULL AUTO_INCREMENT,
  `Usuario_idUsuario` int(11) NOT NULL,
  `fechaNacimiento` date NOT NULL,
  `biografia` varchar(500) DEFAULT NULL,
  `disponibilidad` enum('mananas','tardes','noches','fines_de_semana','flexible') NOT NULL,
  `ciudad` varchar(80) NOT NULL,
  `departamento` varchar(80) NOT NULL,
  PRIMARY KEY (`idPerfilVoluntario`),
  UNIQUE KEY `Usuario_idUsuario` (`Usuario_idUsuario`),
  CONSTRAINT `fk_perfil_usuario` FOREIGN KEY (`Usuario_idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `perfil_voluntario` WRITE;
/*!40000 ALTER TABLE `perfil_voluntario` DISABLE KEYS */;
INSERT INTO `perfil_voluntario` VALUES (1,8,'2001-04-12','Estudiante de Licenciatura en Matemáticas de la Universidad de Córdoba. Me encanta enseñar y trabajar con niños.','fines_de_semana','Montería','Córdoba'),(2,9,'1998-09-03','Ingeniero ambiental. Participo en jornadas de limpieza y siembra desde hace tres años.','tardes','Montería','Córdoba'),(3,10,'2000-01-25','Estudiante de Medicina Veterinaria y Zootecnia. Rescato y cuido animales en mi tiempo libre.','flexible','Cereté','Córdoba'),(4,11,'2003-06-30','Entrenador de fútbol infantil y diseñador gráfico aficionado.','noches','Montería','Córdoba'),(5,12,'1995-11-18','Psicóloga. Me interesa el acompañamiento emocional a adultos mayores y pacientes.','mananas','Montería','Córdoba'),(6,13,'1999-02-14','Desarrollador de software. Quiero enseñar programación y matemáticas a jóvenes.','flexible','Montería','Córdoba'),(7,14,'2002-08-09','Comunicadora social. Apoyo con fotografía y redes a causas ambientales y animalistas.','fines_de_semana','Lorica','Córdoba'),(8,15,'1993-03-21','Socorrista de la Defensa Civil y maestro de obra.','fines_de_semana','Montería','Córdoba');
/*!40000 ALTER TABLE `perfil_voluntario` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `postulacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `postulacion` (
  `idPostulacion` int(11) NOT NULL AUTO_INCREMENT,
  `Usuario_idUsuario` int(11) NOT NULL,
  `Oportunidad_idOportunidad` int(11) NOT NULL,
  `mensaje` varchar(500) DEFAULT NULL,
  `estado` enum('pendiente','aceptada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  `fechaPostulacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fechaRespuesta` datetime DEFAULT NULL,
  PRIMARY KEY (`idPostulacion`),
  UNIQUE KEY `uq_postulacion` (`Usuario_idUsuario`,`Oportunidad_idOportunidad`),
  KEY `fk_postulacion_oportunidad` (`Oportunidad_idOportunidad`),
  CONSTRAINT `fk_postulacion_oportunidad` FOREIGN KEY (`Oportunidad_idOportunidad`) REFERENCES `oportunidad` (`idOportunidad`) ON DELETE CASCADE,
  CONSTRAINT `fk_postulacion_usuario` FOREIGN KEY (`Usuario_idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `postulacion` WRITE;
/*!40000 ALTER TABLE `postulacion` DISABLE KEYS */;
INSERT INTO `postulacion` VALUES (1,8,1,'Estudio Licenciatura en Matemáticas y tengo experiencia dando clases particulares.','aceptada','2026-10-17 09:07:00','2026-10-18 17:00:00'),(2,13,1,'Me gustaría apoyar con ejercicios de lógica y matemáticas.','pendiente','2026-10-20 12:28:00',NULL),(3,11,1,NULL,'pendiente','2026-10-21 13:35:00',NULL),(4,8,2,'¡Me encanta leerles a los niños!','aceptada','2026-07-16 10:14:00','2026-07-17 17:00:00'),(5,11,2,NULL,'aceptada','2026-07-17 11:21:00','2026-07-18 17:00:00'),(6,14,2,'Puedo apoyar también con fotografías de las sesiones.','aceptada','2026-07-18 12:28:00','2026-07-19 17:00:00'),(7,13,2,NULL,'rechazada','2026-07-24 18:10:00','2026-07-25 17:00:00'),(8,8,3,'Tengo nivel B2 certificado.','pendiente','2026-10-20 09:07:00',NULL),(9,11,4,'Soy entrenador de fútbol infantil, puedo arbitrar.','pendiente','2026-10-21 09:07:00',NULL),(10,9,5,'Llevo la cámara para el registro de la jornada.','aceptada','2026-10-14 09:07:00','2026-10-15 17:00:00'),(11,14,5,NULL,'aceptada','2026-10-15 10:14:00','2026-10-16 17:00:00'),(12,15,5,NULL,'pendiente','2026-10-19 14:42:00',NULL),(13,9,6,NULL,'aceptada','2026-05-17 10:14:00','2026-05-18 17:00:00'),(14,14,6,NULL,'aceptada','2026-05-18 11:21:00','2026-05-19 17:00:00'),(15,15,6,NULL,'aceptada','2026-05-20 13:35:00','2026-05-21 17:00:00'),(16,9,7,NULL,'aceptada','2026-08-28 11:21:00','2026-08-29 17:00:00'),(17,13,7,'Puedo dictar la parte virtual.','aceptada','2026-08-29 12:28:00','2026-08-30 17:00:00'),(18,15,8,'Soy socorrista de la Defensa Civil.','aceptada','2026-10-18 09:07:00','2026-10-19 17:00:00'),(19,9,8,NULL,'pendiente','2026-10-19 10:14:00',NULL),(20,10,9,'Estudio Veterinaria y he apoyado jornadas de esterilización.','aceptada','2026-10-19 09:07:00','2026-10-20 17:00:00'),(21,14,9,NULL,'pendiente','2026-10-21 11:21:00',NULL),(22,10,10,NULL,'aceptada','2026-09-11 09:07:00','2026-09-12 17:00:00'),(23,14,10,NULL,'aceptada','2026-09-12 10:14:00','2026-09-13 17:00:00'),(24,12,10,NULL,'aceptada','2026-09-13 11:21:00','2026-09-14 17:00:00'),(25,9,10,NULL,'aceptada','2026-09-14 12:28:00','2026-09-15 17:00:00'),(26,15,10,NULL,'pendiente','2026-09-16 14:42:00',NULL),(27,10,11,NULL,'aceptada','2026-04-27 10:14:00','2026-04-28 17:00:00'),(28,12,11,NULL,'aceptada','2026-04-29 12:28:00','2026-04-30 17:00:00'),(29,12,12,'Soy psicóloga y he trabajado con adultos mayores.','pendiente','2026-10-16 10:14:00',NULL),(30,8,12,NULL,'cancelada','2026-10-17 11:21:00','2026-10-18 17:00:00'),(31,12,14,NULL,'aceptada','2026-02-22 11:21:00','2026-02-23 17:00:00'),(32,15,14,NULL,'aceptada','2026-02-24 13:35:00','2026-02-25 17:00:00'),(33,11,15,NULL,'cancelada','2026-09-24 10:14:00','2026-09-25 17:00:00');
/*!40000 ALTER TABLE `postulacion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol` (
  `idRol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  PRIMARY KEY (`idRol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (3,'administrador'),(2,'fundacion'),(1,'voluntario');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `token_revocado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `token_revocado` (
  `jti` varchar(64) NOT NULL,
  `expiracion` datetime NOT NULL,
  PRIMARY KEY (`jti`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `token_revocado` WRITE;
/*!40000 ALTER TABLE `token_revocado` DISABLE KEYS */;
/*!40000 ALTER TABLE `token_revocado` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `idUsuario` int(11) NOT NULL AUTO_INCREMENT,
  `Rol_idRol` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `resetCode` varchar(6) DEFAULT NULL,
  `resetCodeExpiration` datetime DEFAULT NULL,
  `fechaCreacion` datetime NOT NULL DEFAULT current_timestamp(),
  `emailVerificado` tinyint(1) NOT NULL DEFAULT 0,
  `intentosFallidos` tinyint(4) NOT NULL DEFAULT 0,
  `bloqueadoHasta` datetime DEFAULT NULL,
  PRIMARY KEY (`idUsuario`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuario_rol` (`Rol_idRol`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`Rol_idRol`) REFERENCES `rol` (`idRol`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,3,'Administrador','VoluntaRed','admin@voluntared.co','$2y$10$eUJRAJ16bI58VAOtgAvVN.VeATgA23Og0rS5RdqUlI32X5mmSup3S','3001234567',NULL,1,NULL,NULL,'2025-12-26 08:00:00',1,0,NULL),(2,2,'Laura','Martínez','laura@sembrandofuturo.org','$2y$10$JfuP4bbKXPAqdS7M5HXC1.9pq2P/p4wHGV.Tei2jeFs6Xsz3PAH0e','3104567890','uploads/fotos/logo-sembrando.svg',1,NULL,NULL,'2026-01-15 10:15:00',1,0,NULL),(3,2,'Carlos','Pérez','carlos@riosinuverde.org','$2y$10$YFQm1iOV/OR8C/dUipjMo.8RvoBYehW9wiLx0mAFFSJSKSumiobGK','3012345678','uploads/fotos/logo-rio.svg',1,NULL,NULL,'2026-01-25 10:15:00',1,0,NULL),(4,2,'Andrea','López','andrea@huellitascordoba.org','$2y$10$KZzXnZ1V7mRh4yANdttwIOLWz42iSeN8CcPOoh8GOrjiAlIrR436i','3157894561','uploads/fotos/logo-huellitas.svg',1,NULL,NULL,'2026-02-04 10:15:00',1,0,NULL),(5,2,'María Fernanda','Ruiz','mfruiz@abuelosfelices.org','$2y$10$BORYxAZaer7VUff6Nl8m6.saxBfPWBUqWeVhUzJmy91UknySLcxAe','3205551234','uploads/fotos/logo-abuelos.svg',1,NULL,NULL,'2026-02-14 10:15:00',1,0,NULL),(6,2,'Jorge','Díaz','jorge@manosqueayudan.org','$2y$10$7krudlcdzrTZzBR9J0lYn.HRiY84TVb1uJPDfjW9eMno7yQHT1Dd.','3016667788',NULL,1,NULL,NULL,'2026-10-19 10:15:00',1,0,NULL),(7,2,'Pedro','Suárez','contacto@brisasdelsinu.org','$2y$10$fc23QSkHoTizu93nFduG7egvHM7VKZwxYqFvPJPLFCSHGLSs6ImVO','3112223344',NULL,1,NULL,NULL,'2026-09-12 10:15:00',1,0,NULL),(8,1,'Ana Sofía','Gómez','ana.gomez@correo.com','$2y$10$O4Hjw2jGPzDFTR88euYEru3IjMq5vR9d1.0OrFzaZDPbD7/zqB6ia','3001112233',NULL,1,NULL,NULL,'2026-03-06 19:30:00',1,0,NULL),(9,1,'Juan Pablo','Torres','juanp.torres@correo.com','$2y$10$5cM1zOs1PCkCoAyQUCzsju/PDohgT/NKayXJWJSgeh.2MCt7yidEe','3002223344',NULL,1,NULL,NULL,'2026-03-16 19:30:00',1,0,NULL),(10,1,'Valentina','Herrera','valentina.herrera@correo.com','$2y$10$xedQmK4Ubm6ywp65aNNDDeP8DzokfneYwB16q7L154og49D8Npdta','3003334455',NULL,1,NULL,NULL,'2026-03-26 19:30:00',1,0,NULL),(11,1,'Santiago','Ramírez','santiago.ramirez@correo.com','$2y$10$fSD33shrFuHBFin22S2Laurt4Nmvv7wlitn024aefH5QtV6q.SkAi','3004445566',NULL,1,NULL,NULL,'2026-04-05 19:30:00',1,0,NULL),(12,1,'Daniela','Castro','daniela.castro@correo.com','$2y$10$Db5HTlcw.FHFAHcvZA62QOaeRhZhd6.J41aNcWu.BydT4rs8i8gDG','3005556677',NULL,1,NULL,NULL,'2026-04-15 19:30:00',1,0,NULL),(13,1,'Mateo','Vargas','mateo.vargas@correo.com','$2y$10$wmC03pz.xS34wpz31emzAO7YEfkGSZDI.qZ1RpZ.cklNZ6vqHbSn.','3006667788',NULL,1,NULL,NULL,'2026-06-24 19:30:00',1,0,NULL),(14,1,'Camila','Rojas','camila.rojas@correo.com','$2y$10$Jvxkvfs1Qe5ZdmzB8UTYbe5S5smCCCWJhHkmUMLuUwEWfL0ynNVx.','3007778899',NULL,1,NULL,NULL,'2026-05-15 19:30:00',1,0,NULL),(15,1,'Andrés','Morales','andres.morales@correo.com','$2y$10$GIcZiWFl81KBArcPBJpHNeBEOcSEJlEiNDJGB7z4mwk0s36gT3yQa','3008889900',NULL,1,NULL,NULL,'2026-05-05 19:30:00',1,0,NULL),(16,1,'Luis','Fernández','luis.fernandez@correo.com','$2y$10$/U.9B0GNJH0WsuzLu1cjqusf4WDEWSRq76rUQAqtEzhakKmv9AGoa',NULL,NULL,1,NULL,NULL,'2026-10-21 11:00:00',1,0,NULL),(17,1,'Kevin','Pineda','kevin.pineda@correo.com','$2y$10$lDA0vnqAOZm0uv0kkeP23u2GEZ1eUSSrmXdy6KifGr89ddrNKgcbq',NULL,NULL,0,NULL,NULL,'2026-07-24 09:00:00',1,0,NULL);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `voluntario_has_categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voluntario_has_categoria` (
  `PerfilVoluntario_id` int(11) NOT NULL,
  `Categoria_idCategoria` int(11) NOT NULL,
  PRIMARY KEY (`PerfilVoluntario_id`,`Categoria_idCategoria`),
  KEY `fk_vc_categoria` (`Categoria_idCategoria`),
  CONSTRAINT `fk_vc_categoria` FOREIGN KEY (`Categoria_idCategoria`) REFERENCES `categoria` (`idCategoria`),
  CONSTRAINT `fk_vc_perfil` FOREIGN KEY (`PerfilVoluntario_id`) REFERENCES `perfil_voluntario` (`idPerfilVoluntario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `voluntario_has_categoria` WRITE;
/*!40000 ALTER TABLE `voluntario_has_categoria` DISABLE KEYS */;
INSERT INTO `voluntario_has_categoria` VALUES (1,1),(1,6),(2,2),(2,7),(3,3),(3,4),(4,6),(4,8),(5,3),(5,5),(6,1),(7,2),(7,4),(8,3),(8,7);
/*!40000 ALTER TABLE `voluntario_has_categoria` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `voluntario_has_habilidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voluntario_has_habilidad` (
  `PerfilVoluntario_id` int(11) NOT NULL,
  `Habilidad_idHabilidad` int(11) NOT NULL,
  PRIMARY KEY (`PerfilVoluntario_id`,`Habilidad_idHabilidad`),
  KEY `fk_vh_habilidad` (`Habilidad_idHabilidad`),
  CONSTRAINT `fk_vh_habilidad` FOREIGN KEY (`Habilidad_idHabilidad`) REFERENCES `habilidad` (`idHabilidad`),
  CONSTRAINT `fk_vh_perfil` FOREIGN KEY (`PerfilVoluntario_id`) REFERENCES `perfil_voluntario` (`idPerfilVoluntario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `voluntario_has_habilidad` WRITE;
/*!40000 ALTER TABLE `voluntario_has_habilidad` DISABLE KEYS */;
INSERT INTO `voluntario_has_habilidad` VALUES (1,1),(1,10),(1,11),(2,3),(2,6),(2,13),(3,2),(3,14),(4,4),(4,5),(4,10),(5,2),(5,7),(5,9),(6,1),(6,8),(7,5),(7,6),(8,2),(8,3),(8,12);
/*!40000 ALTER TABLE `voluntario_has_habilidad` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

