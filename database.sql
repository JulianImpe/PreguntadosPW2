-- ==============================
-- 📘 BASE DE DATOS PREGUNTADOS
-- ==============================

DROP DATABASE IF EXISTS preguntados;
CREATE DATABASE preguntados;
USE preguntados;

-- ==============================
-- ENUMS Y TABLAS BÁSICAS
-- ==============================

CREATE TABLE Sexo (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre ENUM('Masculino', 'Femenino', 'Prefiero no cargarlo') NOT NULL UNIQUE
);

INSERT INTO Sexo (Nombre) VALUES ('Masculino'), ('Femenino'), ('Prefiero no cargarlo');

CREATE TABLE Rol (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre ENUM('Jugador', 'Editor', 'Administrador') NOT NULL UNIQUE
);

INSERT INTO Rol (Nombre) VALUES ('Jugador'), ('Editor'), ('Administrador');

CREATE TABLE GrupoDeEdad (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre ENUM('Menor', 'Medio', 'Jubilado') NOT NULL UNIQUE
);

INSERT INTO GrupoDeEdad (Nombre) VALUES ('Menor'), ('Medio'), ('Jubilado');

CREATE TABLE Pais (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL
);

INSERT INTO Pais (Nombre) VALUES 
('Argentina'),
('Uruguay'),
('Chile'),
('Brasil'),
('Paraguay');

CREATE TABLE Provincia (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Pais_ID INT NOT NULL,
    FOREIGN KEY (Pais_ID) REFERENCES Pais(ID) ON DELETE CASCADE
);

INSERT INTO Provincia (Nombre, Pais_ID) VALUES 
('Buenos Aires', 1),
('Córdoba', 1),
('Santa Fe', 1),
('Mendoza', 1);

CREATE TABLE Mapa (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Provincia_ID INT NOT NULL,
    Latitud DECIMAL(10, 8) NULL,
    Longitud DECIMAL(11, 8) NULL,
    FOREIGN KEY (Provincia_ID) REFERENCES Provincia(ID) ON DELETE CASCADE
);

-- ==============================
-- USUARIOS
-- ==============================

CREATE TABLE usuarios (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    fecha_nac DATE NOT NULL,
    foto_perfil VARCHAR(255) NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    Sexo_ID INT NOT NULL,
    Rol_ID INT NOT NULL DEFAULT 1,
    Puntaje_total INT DEFAULT 0,
    Mapa_ID INT NULL,
    FOREIGN KEY (Rol_ID) REFERENCES Rol(ID),
    FOREIGN KEY (Sexo_ID) REFERENCES Sexo(ID),
    FOREIGN KEY (Mapa_ID) REFERENCES Mapa(ID) ON DELETE SET NULL
);

-- ==============================
-- PARTIDAS
-- ==============================

CREATE TABLE Estado_partida (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre ENUM('EN_CURSO', 'FINALIZADA', 'ABANDONADA') NOT NULL UNIQUE
);

INSERT INTO Estado_partida (Nombre) VALUES ('EN_CURSO'), ('FINALIZADA'), ('ABANDONADA');

CREATE TABLE Partida (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Usuario_ID INT NOT NULL,
    Estado_ID INT NOT NULL DEFAULT 1,
    Hora_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    Hora_finalizacion DATETIME NULL,
    Puntaje_obtenido INT DEFAULT 0,
    FOREIGN KEY (Usuario_ID) REFERENCES usuarios(ID) ON DELETE CASCADE,
    FOREIGN KEY (Estado_ID) REFERENCES Estado_partida(ID)
);

-- ==============================
-- MEDALLAS Y PREGUNTAS
-- ==============================

CREATE TABLE Medallas (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL UNIQUE,
    Color VARCHAR(7) NOT NULL,
    Imagen_url VARCHAR(500) NOT NULL
);

CREATE TABLE Estado_pregunta (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre ENUM('Pendiente', 'Aprobada', 'Rechazada') NOT NULL UNIQUE
);

INSERT INTO Estado_pregunta (Nombre) VALUES ('Pendiente'), ('Aprobada'), ('Rechazada');

CREATE TABLE Pregunta (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Texto TEXT NOT NULL,
    Medalla_ID INT NOT NULL,
    Cant_veces_respondida INT DEFAULT 0,
    Cant_veces_correcta INT DEFAULT 0,
    Estado_ID INT NOT NULL DEFAULT 1,
    Creada_por_usuario_ID INT NULL,
    Fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Aprobada_por INT NULL,
    FOREIGN KEY (Medalla_ID) REFERENCES Medallas(ID) ON DELETE CASCADE,
    FOREIGN KEY (Estado_ID) REFERENCES Estado_pregunta(ID),
    FOREIGN KEY (Creada_por_usuario_ID) REFERENCES usuarios(ID) ON DELETE SET NULL,
    FOREIGN KEY (Aprobada_por) REFERENCES usuarios(ID) ON DELETE SET NULL
);

CREATE TABLE Opcion (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Pregunta_ID INT NOT NULL,
    Texto VARCHAR(255) NOT NULL,
    EsCorrecta BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (Pregunta_ID) REFERENCES Pregunta(ID) ON DELETE CASCADE
);

CREATE TABLE Pregunta_partida (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Partida_ID INT NOT NULL,
    Pregunta_ID INT NOT NULL,
    Orden_en_partida INT NOT NULL,
    EsCorrecta BOOLEAN NULL,
    Tiempo_respuesta INT NULL,
    Fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Partida_ID) REFERENCES Partida(ID) ON DELETE CASCADE,
    FOREIGN KEY (Pregunta_ID) REFERENCES Pregunta(ID) ON DELETE CASCADE,
    UNIQUE KEY unique_partida_orden (Partida_ID, Orden_en_partida)
);

-- ==============================
-- REPORTES Y RELACIONES
-- ==============================

CREATE TABLE Reporte (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Usuario_ID INT NOT NULL,
    Pregunta_ID INT NOT NULL,
    Motivo TEXT NULL,
    Fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    Revisado BOOLEAN DEFAULT FALSE,
    Revisado_por INT NULL,
    Accion_tomada VARCHAR(50) NULL,
    FOREIGN KEY (Usuario_ID) REFERENCES usuarios(ID) ON DELETE CASCADE,
    FOREIGN KEY (Pregunta_ID) REFERENCES Pregunta(ID) ON DELETE CASCADE,
    FOREIGN KEY (Revisado_por) REFERENCES usuarios(ID) ON DELETE SET NULL
);

CREATE TABLE Usuario_pregunta_vista (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Usuario_ID INT NOT NULL,
    Pregunta_ID INT NOT NULL,
    Fecha_vista DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Usuario_ID) REFERENCES usuarios(ID) ON DELETE CASCADE,
    FOREIGN KEY (Pregunta_ID) REFERENCES Pregunta(ID) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_pregunta (Usuario_ID, Pregunta_ID)
);

-- ==============================
-- DATOS DE EJEMPLO
-- ==============================

INSERT INTO Medallas (Nombre, Color, Imagen_url) VALUES
('Medalla Roca', '#8B7355', 'boulder_badge.png'),       -- Brock
('Medalla Cascada', '#4DA6FF', 'cascade_badge.png'),    -- Misty
('Medalla Trueno', '#FFD700', 'thunder_badge.png'),     -- Lt. Surge
('Medalla Arcoíris', '#FF80FF', 'rainbow_badge.png'),   -- Erika
('Medalla Alma', '#9400D3', 'soul_badge.png'),          -- Koga
('Medalla Pantano', '#00FF7F', 'marsh_badge.png'),      -- Sabrina
('Medalla Volcán', '#FF4500', 'volcano_badge.png'),     -- Blaine
('Medalla Tierra', '#654321', 'earth_badge.png');        -- Giovanni


SELECT '✅ Base de datos creada correctamente' AS resultado;
