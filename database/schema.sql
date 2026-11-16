-- =====================================================================
-- VoluntaRed - Esquema de base de datos (MySQL 8 / MariaDB 10.4+)
-- Basado en el diccionario de datos (sección 2.9 del ERS).
-- Los campos marcados con [IMPL] se agregaron durante la implementación
-- para soportar requisitos del ERS (bloqueo por intentos fallidos,
-- verificación de correo, moderación y cierre de sesión con JWT).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS token_revocado;
DROP TABLE IF EXISTS notificacion;
DROP TABLE IF EXISTS mensaje;
DROP TABLE IF EXISTS conversacion;
DROP TABLE IF EXISTS evaluacion;
DROP TABLE IF EXISTS participacion;
DROP TABLE IF EXISTS postulacion;
DROP TABLE IF EXISTS oportunidad_has_habilidad;
DROP TABLE IF EXISTS oportunidad;
DROP TABLE IF EXISTS voluntario_has_categoria;
DROP TABLE IF EXISTS voluntario_has_habilidad;
DROP TABLE IF EXISTS habilidad;
DROP TABLE IF EXISTS categoria;
DROP TABLE IF EXISTS fundacion;
DROP TABLE IF EXISTS perfil_voluntario;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS rol;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- rol: catálogo de tipos de usuario
-- ---------------------------------------------------------------------
CREATE TABLE rol (
    idRol  INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- usuario: cuenta de acceso de personas u organizaciones
-- ---------------------------------------------------------------------
CREATE TABLE usuario (
    idUsuario           INT AUTO_INCREMENT PRIMARY KEY,
    Rol_idRol           INT NOT NULL,
    nombre              VARCHAR(100) NOT NULL,
    apellido            VARCHAR(100) NULL,
    email               VARCHAR(150) NOT NULL UNIQUE,
    contrasena          VARCHAR(255) NOT NULL,
    telefono            VARCHAR(15) NULL,
    foto                VARCHAR(255) NULL,
    activo              BOOLEAN NOT NULL DEFAULT TRUE,
    resetCode           VARCHAR(6) NULL,
    resetCodeExpiration DATETIME NULL,
    fechaCreacion       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    emailVerificado     BOOLEAN NOT NULL DEFAULT FALSE,          -- [IMPL] RF1
    intentosFallidos    TINYINT NOT NULL DEFAULT 0,              -- [IMPL] RF2
    bloqueadoHasta      DATETIME NULL,                           -- [IMPL] RF2
    CONSTRAINT fk_usuario_rol FOREIGN KEY (Rol_idRol) REFERENCES rol (idRol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- perfil_voluntario: información complementaria del voluntario
-- ---------------------------------------------------------------------
CREATE TABLE perfil_voluntario (
    idPerfilVoluntario INT AUTO_INCREMENT PRIMARY KEY,
    Usuario_idUsuario  INT NOT NULL UNIQUE,
    fechaNacimiento    DATE NOT NULL,
    biografia          VARCHAR(500) NULL,
    disponibilidad     ENUM('mananas','tardes','noches','fines_de_semana','flexible') NOT NULL,
    ciudad             VARCHAR(80) NOT NULL,
    departamento       VARCHAR(80) NOT NULL,
    CONSTRAINT fk_perfil_usuario FOREIGN KEY (Usuario_idUsuario) REFERENCES usuario (idUsuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- fundacion: organización asociada a un usuario con rol fundación
-- ---------------------------------------------------------------------
CREATE TABLE fundacion (
    idFundacion        INT AUTO_INCREMENT PRIMARY KEY,
    Usuario_idUsuario  INT NOT NULL UNIQUE,
    nit                VARCHAR(20) NOT NULL UNIQUE,
    razonSocial        VARCHAR(150) NOT NULL,
    descripcion        TEXT NOT NULL,
    mision             VARCHAR(500) NULL,
    direccion          VARCHAR(150) NOT NULL,
    ciudad             VARCHAR(80) NOT NULL,
    sitioWeb           VARCHAR(200) NULL,
    documentoSoporte   VARCHAR(255) NOT NULL,
    estadoVerificacion ENUM('pendiente','verificada','rechazada') NOT NULL DEFAULT 'pendiente',
    fechaVerificacion  DATETIME NULL,
    motivoRechazo      VARCHAR(300) NULL,                        -- [IMPL] RF5
    CONSTRAINT fk_fundacion_usuario FOREIGN KEY (Usuario_idUsuario) REFERENCES usuario (idUsuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categoria y habilidad: catálogos administrados
-- ---------------------------------------------------------------------
CREATE TABLE categoria (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE habilidad (
    idHabilidad INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE voluntario_has_habilidad (
    PerfilVoluntario_id   INT NOT NULL,
    Habilidad_idHabilidad INT NOT NULL,
    PRIMARY KEY (PerfilVoluntario_id, Habilidad_idHabilidad),
    CONSTRAINT fk_vh_perfil FOREIGN KEY (PerfilVoluntario_id) REFERENCES perfil_voluntario (idPerfilVoluntario) ON DELETE CASCADE,
    CONSTRAINT fk_vh_habilidad FOREIGN KEY (Habilidad_idHabilidad) REFERENCES habilidad (idHabilidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE voluntario_has_categoria (
    PerfilVoluntario_id   INT NOT NULL,
    Categoria_idCategoria INT NOT NULL,
    PRIMARY KEY (PerfilVoluntario_id, Categoria_idCategoria),
    CONSTRAINT fk_vc_perfil FOREIGN KEY (PerfilVoluntario_id) REFERENCES perfil_voluntario (idPerfilVoluntario) ON DELETE CASCADE,
    CONSTRAINT fk_vc_categoria FOREIGN KEY (Categoria_idCategoria) REFERENCES categoria (idCategoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- oportunidad: actividad de voluntariado publicada por una fundación
-- ---------------------------------------------------------------------
CREATE TABLE oportunidad (
    idOportunidad         INT AUTO_INCREMENT PRIMARY KEY,
    Fundacion_idFundacion INT NOT NULL,
    Categoria_idCategoria INT NOT NULL,
    titulo                VARCHAR(150) NOT NULL,
    descripcion           TEXT NOT NULL,
    requisitos            VARCHAR(500) NULL,
    modalidad             ENUM('presencial','virtual','mixta') NOT NULL,
    ubicacion             VARCHAR(200) NULL,
    ciudad                VARCHAR(80) NOT NULL,
    fechaInicio           DATE NOT NULL,
    fechaFin              DATE NOT NULL,
    horaInicio            TIME NOT NULL,
    horaFin               TIME NOT NULL,
    cuposTotales          INT NOT NULL,
    cuposDisponibles      INT NOT NULL,
    estado                ENUM('abierta','cerrada','finalizada','cancelada') NOT NULL DEFAULT 'abierta',
    fechaPublicacion      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    visible               BOOLEAN NOT NULL DEFAULT TRUE,          -- [IMPL] moderación
    CONSTRAINT fk_oportunidad_fundacion FOREIGN KEY (Fundacion_idFundacion) REFERENCES fundacion (idFundacion) ON DELETE CASCADE,
    CONSTRAINT fk_oportunidad_categoria FOREIGN KEY (Categoria_idCategoria) REFERENCES categoria (idCategoria),
    CONSTRAINT ck_oportunidad_fechas CHECK (fechaFin >= fechaInicio),
    CONSTRAINT ck_oportunidad_cupos CHECK (cuposTotales > 0 AND cuposDisponibles >= 0 AND cuposDisponibles <= cuposTotales),
    INDEX ix_oportunidad_busqueda (estado, ciudad, modalidad, fechaInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE oportunidad_has_habilidad (
    Oportunidad_idOportunidad INT NOT NULL,
    Habilidad_idHabilidad     INT NOT NULL,
    PRIMARY KEY (Oportunidad_idOportunidad, Habilidad_idHabilidad),
    CONSTRAINT fk_oh_oportunidad FOREIGN KEY (Oportunidad_idOportunidad) REFERENCES oportunidad (idOportunidad) ON DELETE CASCADE,
    CONSTRAINT fk_oh_habilidad FOREIGN KEY (Habilidad_idHabilidad) REFERENCES habilidad (idHabilidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- postulacion: solicitud de un voluntario a una oportunidad
-- ---------------------------------------------------------------------
CREATE TABLE postulacion (
    idPostulacion             INT AUTO_INCREMENT PRIMARY KEY,
    Usuario_idUsuario         INT NOT NULL,
    Oportunidad_idOportunidad INT NOT NULL,
    mensaje                   VARCHAR(500) NULL,
    estado                    ENUM('pendiente','aceptada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
    fechaPostulacion          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaRespuesta            DATETIME NULL,
    CONSTRAINT uq_postulacion UNIQUE (Usuario_idUsuario, Oportunidad_idOportunidad),
    CONSTRAINT fk_postulacion_usuario FOREIGN KEY (Usuario_idUsuario) REFERENCES usuario (idUsuario) ON DELETE CASCADE,
    CONSTRAINT fk_postulacion_oportunidad FOREIGN KEY (Oportunidad_idOportunidad) REFERENCES oportunidad (idOportunidad) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- participacion: asistencia y horas de un voluntario aceptado
-- ---------------------------------------------------------------------
CREATE TABLE participacion (
    idParticipacion           INT AUTO_INCREMENT PRIMARY KEY,
    Postulacion_idPostulacion INT NOT NULL UNIQUE,
    asistio                   BOOLEAN NOT NULL,
    horasCumplidas            DECIMAL(5,2) NOT NULL,
    observacion               VARCHAR(300) NULL,
    fechaRegistro             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_participacion_postulacion FOREIGN KEY (Postulacion_idPostulacion) REFERENCES postulacion (idPostulacion) ON DELETE CASCADE,
    CONSTRAINT ck_participacion_horas CHECK (horasCumplidas >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evaluacion: calificación mutua voluntario <-> fundación
-- ---------------------------------------------------------------------
CREATE TABLE evaluacion (
    idEvaluacion                  INT AUTO_INCREMENT PRIMARY KEY,
    Participacion_idParticipacion INT NOT NULL,
    evaluador_id                  INT NOT NULL,
    evaluado_id                   INT NOT NULL,
    calificacion                  TINYINT NOT NULL,
    comentario                    VARCHAR(500) NULL,
    fecha                         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    visible                       BOOLEAN NOT NULL DEFAULT TRUE,  -- [IMPL] moderación
    CONSTRAINT uq_evaluacion UNIQUE (Participacion_idParticipacion, evaluador_id),
    CONSTRAINT fk_evaluacion_participacion FOREIGN KEY (Participacion_idParticipacion) REFERENCES participacion (idParticipacion) ON DELETE CASCADE,
    CONSTRAINT fk_evaluacion_evaluador FOREIGN KEY (evaluador_id) REFERENCES usuario (idUsuario) ON DELETE CASCADE,
    CONSTRAINT fk_evaluacion_evaluado FOREIGN KEY (evaluado_id) REFERENCES usuario (idUsuario) ON DELETE CASCADE,
    CONSTRAINT ck_evaluacion_calificacion CHECK (calificacion BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- conversacion y mensaje: chat entre voluntario y fundación
-- ---------------------------------------------------------------------
CREATE TABLE conversacion (
    idConversacion            INT AUTO_INCREMENT PRIMARY KEY,
    voluntario_id             INT NOT NULL,
    fundacion_id              INT NOT NULL,
    Oportunidad_idOportunidad INT NULL,
    fechaCreacion             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conversacion_voluntario FOREIGN KEY (voluntario_id) REFERENCES usuario (idUsuario) ON DELETE CASCADE,
    CONSTRAINT fk_conversacion_fundacion FOREIGN KEY (fundacion_id) REFERENCES usuario (idUsuario) ON DELETE CASCADE,
    CONSTRAINT fk_conversacion_oportunidad FOREIGN KEY (Oportunidad_idOportunidad) REFERENCES oportunidad (idOportunidad) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensaje (
    idMensaje                   INT AUTO_INCREMENT PRIMARY KEY,
    Conversacion_idConversacion INT NOT NULL,
    emisor_id                   INT NOT NULL,
    contenido                   TEXT NOT NULL,
    leido                       BOOLEAN NOT NULL DEFAULT FALSE,
    fechaEnvio                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mensaje_conversacion FOREIGN KEY (Conversacion_idConversacion) REFERENCES conversacion (idConversacion) ON DELETE CASCADE,
    CONSTRAINT fk_mensaje_emisor FOREIGN KEY (emisor_id) REFERENCES usuario (idUsuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- notificacion: avisos generados por el sistema
-- ---------------------------------------------------------------------
CREATE TABLE notificacion (
    idNotificacion    INT AUTO_INCREMENT PRIMARY KEY,
    Usuario_idUsuario INT NOT NULL,
    tipo              ENUM('postulacion','respuesta','mensaje','oportunidad','evaluacion','sistema') NOT NULL,
    titulo            VARCHAR(100) NOT NULL,
    contenido         VARCHAR(300) NOT NULL,
    enlace            VARCHAR(255) NULL,
    leida             BOOLEAN NOT NULL DEFAULT FALSE,
    fechaCreacion     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacion_usuario FOREIGN KEY (Usuario_idUsuario) REFERENCES usuario (idUsuario) ON DELETE CASCADE,
    INDEX ix_notificacion_usuario (Usuario_idUsuario, leida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- token_revocado [IMPL]: identificadores (jti) de los JWT invalidados
-- al cerrar sesión, hasta su fecha de expiración.
-- ---------------------------------------------------------------------
CREATE TABLE token_revocado (
    jti        VARCHAR(64) PRIMARY KEY,
    expiracion DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rol (idRol, nombre) VALUES (1, 'voluntario'), (2, 'fundacion'), (3, 'administrador');
