-- ==============================
-- 📘 BASE DE DATOS PREGUNTADOS
-- ==============================

DROP DATABASE IF EXISTS preguntados;
CREATE DATABASE preguntados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE preguntados;

-- ==============================
-- ENUMS Y TABLAS BÁSICAS
-- ==============================

CREATE TABLE Sexo (
                      ID INT AUTO_INCREMENT PRIMARY KEY,
                      Nombre ENUM('Masculino', 'Femenino', 'Prefiero no cargarlo') NOT NULL UNIQUE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
INSERT INTO Sexo (Nombre) VALUES ('Masculino'), ('Femenino'), ('Prefiero no cargarlo');

CREATE TABLE Rol (
                     ID INT AUTO_INCREMENT PRIMARY KEY,
                     Nombre ENUM('Jugador', 'Editor', 'Administrador') NOT NULL UNIQUE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
INSERT INTO Rol (Nombre) VALUES ('Jugador'), ('Editor'), ('Administrador');

CREATE TABLE GrupoDeEdad (
                             ID INT AUTO_INCREMENT PRIMARY KEY,
                             Nombre ENUM('Menor', 'Medio', 'Jubilado') NOT NULL UNIQUE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
INSERT INTO GrupoDeEdad (Nombre) VALUES ('Menor'), ('Medio'), ('Jubilado');

CREATE TABLE Pais (
                      ID INT AUTO_INCREMENT PRIMARY KEY,
                      Nombre VARCHAR(100) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
INSERT INTO Pais (Nombre) VALUES
                              ('Argentina'), ('Uruguay'), ('Chile'), ('Brasil'), ('Paraguay');

CREATE TABLE Provincia (
                           ID INT AUTO_INCREMENT PRIMARY KEY,
                           Nombre VARCHAR(100) NOT NULL,
                           Pais_ID INT NOT NULL,
                           FOREIGN KEY (Pais_ID) REFERENCES Pais(ID) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ==============================
-- PARTIDAS
-- ==============================

CREATE TABLE Estado_partida (
                                ID INT AUTO_INCREMENT PRIMARY KEY,
                                Nombre ENUM('EN_CURSO', 'FINALIZADA', 'ABANDONADA') NOT NULL UNIQUE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ==============================
-- MEDALLAS Y PREGUNTAS
-- ==============================

CREATE TABLE Medallas (
                          ID INT AUTO_INCREMENT PRIMARY KEY,
                          Nombre VARCHAR(100) NOT NULL UNIQUE,
                          Color VARCHAR(7) NOT NULL,
                          Imagen_url VARCHAR(500) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE Estado_pregunta (
                                 ID INT AUTO_INCREMENT PRIMARY KEY,
                                 Nombre ENUM('Pendiente', 'Aprobada', 'Rechazada') NOT NULL UNIQUE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
                          Dificultad DECIMAL(4,3) NULL DEFAULT NULL,
                          DificultadNivel ENUM('Nuevo','Fácil','Medio','Difícil') NULL DEFAULT 'Nuevo',
                          FOREIGN KEY (Medalla_ID) REFERENCES Medallas(ID) ON DELETE CASCADE,
                          FOREIGN KEY (Estado_ID) REFERENCES Estado_pregunta(ID),
                          FOREIGN KEY (Creada_por_usuario_ID) REFERENCES usuarios(ID) ON DELETE SET NULL,
                          FOREIGN KEY (Aprobada_por) REFERENCES usuarios(ID) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE Respuesta (
                           ID INT AUTO_INCREMENT PRIMARY KEY,
                           Pregunta_ID INT NOT NULL,
                           Texto VARCHAR(255) NOT NULL,
                           Es_Correcta BOOLEAN NOT NULL DEFAULT FALSE,
                           FOREIGN KEY (Pregunta_ID) REFERENCES Pregunta(ID) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE Usuario_pregunta_vista (
                                        ID INT AUTO_INCREMENT PRIMARY KEY,
                                        Usuario_ID INT NOT NULL,
                                        Pregunta_ID INT NOT NULL,
                                        Fecha_vista DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (Usuario_ID) REFERENCES usuarios(ID) ON DELETE CASCADE,
                                        FOREIGN KEY (Pregunta_ID) REFERENCES Pregunta(ID) ON DELETE CASCADE,
                                        UNIQUE KEY unique_usuario_pregunta (Usuario_ID, Pregunta_ID)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ==============================
-- DATOS DE EJEMPLO - MEDALLAS
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

-- ==============================
-- PREGUNTAS Y RESPUESTAS
-- ==============================

-- Medalla Roca (Medalla_ID = 1) — preguntas 1-5
INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID) VALUES
                                                        ('¿Qué dos Pokémon utiliza Brock en la versión original Roja/Azul al nivel más bajo?', 1, 2),
                                                        ('¿En qué ciudad de Kanto se encuentra el gimnasio de Brock?', 1, 2),
                                                        ('¿Qué movimiento de estado suele usar Onix bajo el mando de Brock para retrasar al retador?', 1, 2),
                                                        ('¿Qué tipo de Pokémon tiene ventaja marcada contra el equipo de Brock?', 1, 2),
                                                        ('¿Qué medalla recibe el jugador al vencer a Brock?', 1, 2);

INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta) VALUES
                                                            (1, 'Geodude y Onix', TRUE),
                                                            (1, 'Onix y Rhyhorn', FALSE),
                                                            (1, 'Geodude y Machop', FALSE),
                                                            (1, 'Rhyhorn y Sandshrew', FALSE),

                                                            (2, 'Ciudad Plateada', TRUE),
                                                            (2, 'Ciudad Celeste', FALSE),
                                                            (2, 'Ciudad Verde', FALSE),
                                                            (2, 'Ciudad Carmín', FALSE),

                                                            (3, 'Tumba Rocas', TRUE),
                                                            (3, 'Hiperrayo', FALSE),
                                                            (3, 'Rayo Solar', FALSE),
                                                            (3, 'Trampa Rocas', FALSE),

                                                            (4, 'Agua o Planta', TRUE),
                                                            (4, 'Fuego o Volador', FALSE),
                                                            (4, 'Eléctrico o Acero', FALSE),
                                                            (4, 'Hielo o Siniestro', FALSE),

                                                            (5, 'Medalla Roca', TRUE),
                                                            (5, 'Medalla Cascada', FALSE),
                                                            (5, 'Medalla Trueno', FALSE),
                                                            (5, 'Medalla Tierra', FALSE);

-- Medalla Cascada (Medalla_ID = 2) — preguntas 6-10
INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID) VALUES
                                                        ('¿Cuál es el Pokémon inicial de tipo Agua en la región de Kanto?', 2, 2),
                                                        ('¿En qué ciudad se encuentra el gimnasio de Misty?', 2, 2),
                                                        ('¿Qué líder entrega la Medalla Cascada?', 2, 2),
                                                        ('¿Qué tipo de Pokémon es más efectivo contra la especialidad de Misty?', 2, 2),
                                                        ('¿Qué movimiento aprendido por nivel es característico de Starmie en la versión original que Misty usa?', 2, 2);

INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta) VALUES
                                                            (6, 'Squirtle', TRUE),
                                                            (6, 'Bulbasaur', FALSE),
                                                            (6, 'Charmander', FALSE),
                                                            (6, 'Pikachu', FALSE),

                                                            (7, 'Ciudad Celeste', TRUE),
                                                            (7, 'Ciudad Plateada', FALSE),
                                                            (7, 'Ciudad Azulona', FALSE),
                                                            (7, 'Ciudad Carmín', FALSE),

                                                            (8, 'Misty', TRUE),
                                                            (8, 'Brock', FALSE),
                                                            (8, 'Lt. Surge', FALSE),
                                                            (8, 'Erika', FALSE),

                                                            (9, 'Eléctrico o Planta', TRUE),
                                                            (9, 'Roca o Tierra', FALSE),
                                                            (9, 'Fuego o Hielo', FALSE),
                                                            (9, 'Acero o Fantasma', FALSE),

                                                            (10, 'Psíquico', TRUE),
                                                            (10, 'Tajo Umbrío', FALSE),
                                                            (10, 'Surf', FALSE),
                                                            (10, 'Bola Sombra', FALSE);

-- Medalla Trueno (Medalla_ID = 3) — preguntas 11-15
INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID) VALUES
                                                        ('¿Qué tipo de Pokémon domina Lt. Surge en su gimnasio?', 3, 2),
                                                        ('¿En qué ciudad de Kanto se encuentra el gimnasio de Lt. Surge?', 3, 2),
                                                        ('¿Qué Pokémon poseía Lt. Surge en la versión Amarilla que no estaba en la Roja/Azul?', 3, 2),
                                                        ('¿Qué tipo de Pokémon es recomendable llevar para contrarrestarlo?', 3, 2),
                                                        ('¿Qué medalla entrega Lt. Surge al ser derrotado?', 3, 2);

INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta) VALUES
                                                            (11, 'Eléctrico', TRUE),
                                                            (11, 'Fuego', FALSE),
                                                            (11, 'Agua', FALSE),
                                                            (11, 'Tierra', FALSE),

                                                            (12, 'Ciudad Carmín', TRUE),
                                                            (12, 'Ciudad Celeste', FALSE),
                                                            (12, 'Ciudad Verde', FALSE),
                                                            (12, 'Ciudad Plateada', FALSE),

                                                            (13, 'Electabuzz', TRUE),
                                                            (13, 'Raichu', FALSE),
                                                            (13, 'Jolteon', FALSE),
                                                            (13, 'Pikachu', FALSE),

                                                            (14, 'Tierra', TRUE),
                                                            (14, 'Planta', FALSE),
                                                            (14, 'Hielo', FALSE),
                                                            (14, 'Fuego', FALSE),

                                                            (15, 'Medalla Trueno', TRUE),
                                                            (15, 'Medalla Roca', FALSE),
                                                            (15, 'Medalla Cascada', FALSE),
                                                            (15, 'Medalla Tierra', FALSE);

-- Medalla Arcoíris (Medalla_ID = 4) — preguntas 16-20
INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID) VALUES
                                                        ('¿Qué tipo de Pokémon usa Erika?', 4, 2),
                                                        ('¿En qué ciudad está su gimnasio?', 4, 2),
                                                        ('¿Qué medalla entrega Erika al retador vencido?', 4, 2),
                                                        ('¿Qué movimiento es muy útil contra su equipo de plantas?', 4, 2),
                                                        ('¿Cuál de los siguientes Pokémon aparece en el equipo de Erika en la versión original?', 4, 2);

INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta) VALUES
                                                            (16, 'Planta', TRUE),
                                                            (16, 'Agua', FALSE),
                                                            (16, 'Fuego', FALSE),
                                                            (16, 'Roca', FALSE),

                                                            (17, 'Ciudad Azulona', TRUE),
                                                            (17, 'Ciudad Plateada', FALSE),
                                                            (17, 'Ciudad Verde', FALSE),
                                                            (17, 'Ciudad Carmín', FALSE),

                                                            (18, 'Medalla Arcoíris', TRUE),
                                                            (18, 'Medalla Cascada', FALSE),
                                                            (18, 'Medalla Trueno', FALSE),
                                                            (18, 'Medalla Tierra', FALSE),

                                                            (19, 'Fuego o Volador', TRUE),
                                                            (19, 'Eléctrico o Hielo', FALSE),
                                                            (19, 'Tierra o Roca', FALSE),
                                                            (19, 'Agua o Fantasma', FALSE),

                                                            (20, 'Vileplume', TRUE),
                                                            (20, 'Venusaur', FALSE),
                                                            (20, 'Tangela', FALSE),
                                                            (20, 'Exeggutor', FALSE);

-- Medalla Alma (Medalla_ID = 5) — preguntas 21-25
INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID) VALUES
                                                        ('¿En qué gimnasio se encuentra Koga?', 5, 2),
                                                        ('¿Qué especialidad de tipo tiene Koga?', 5, 2),
                                                        ('¿Qué medalla entrega Koga al ser derrotado?', 5, 2),
                                                        ('¿Qué tipo de Pokémon debería llevar un retador para contrarrestarlo eficazmente?', 5, 2),
                                                        ('¿Qué cambio notable ocurre con Koga en generaciones posteriores?', 5, 2);

INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta) VALUES
                                                            (21, 'Ciudad Fucsia', TRUE),
                                                            (21, 'Ciudad Azulona', FALSE),
                                                            (21, 'Ciudad Plateada', FALSE),
                                                            (21, 'Ciudad Carmín', FALSE),

                                                            (22, 'Veneno', TRUE),
                                                            (22, 'Psíquico', FALSE),
                                                            (22, 'Agua', FALSE),
                                                            (22, 'Eléctrico', FALSE),

                                                            (23, 'Medalla Alma', TRUE),
                                                            (23, 'Medalla Pantano', FALSE),
                                                            (23, 'Medalla Tierra', FALSE),
                                                            (23, 'Medalla Trueno', FALSE),

                                                            (24, 'Tierra o Psíquico', TRUE),
                                                            (24, 'Fuego o Lucha', FALSE),
                                                            (24, 'Agua o Hielo', FALSE),
                                                            (24, 'Planta o Volador', FALSE),

                                                            (25, 'Se convierte en miembro del Alto Mando', TRUE),
                                                            (25, 'Se convierte en líder del Team Rocket', FALSE),
                                                            (25, 'Pierde su gimnasio y lo toma su hija', FALSE),
                                                            (25, 'Especializa en tipo Agua', FALSE);

-- Medalla Pantano (Medalla_ID = 6) — preguntas 26-30
INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID) VALUES
                                                        ('¿Cuál es el tipo principal del gimnasio de Sabrina?', 6, 2),
                                                        ('¿En qué ciudad está su gimnasio en Kanto?', 6, 2),
                                                        ('¿Qué medalla entrega Sabrina?', 6, 2),
                                                        ('¿Qué tipo de Pokémon tiene ventaja frente al tipo Psíquico en Kanto original?', 6, 2),
                                                        ('¿Qué técnica o estrategia especial aplica Sabrina que la hace temible en el gimnasio?', 6, 2);

INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta) VALUES
                                                            (26, 'Psíquico', TRUE),
                                                            (26, 'Eléctrico', FALSE),
                                                            (26, 'Veneno', FALSE),
                                                            (26, 'Fuego', FALSE),

                                                            (27, 'Ciudad Azafrán', TRUE),
                                                            (27, 'Ciudad Celeste', FALSE),
                                                            (27, 'Ciudad Plateada', FALSE),
                                                            (27, 'Ciudad Verde', FALSE),

                                                            (28, 'Medalla Pantano', TRUE),
                                                            (28, 'Medalla Alma', FALSE),
                                                            (28, 'Medalla Arcoíris', FALSE),
                                                            (28, 'Medalla Volcán', FALSE),

                                                            (29, 'Siniestro o Fantasma', TRUE),
                                                            (29, 'Tierra o Planta', FALSE),
                                                            (29, 'Agua o Hielo', FALSE),
                                                            (29, 'Roca o Acero', FALSE),

                                                            (30, 'Laberinto de baldosas y teletransportadores', TRUE),
                                                            (30, 'Entrenadores dobles aleatorios', FALSE),
                                                            (30, 'Movimientos de estado continuos', FALSE),
                                                            (30, 'Equipo de solo una línea de Pokémon', FALSE);

-- Medalla Volcán (Medalla_ID = 7) — preguntas 31-35
INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID) VALUES
                                                        ('¿En qué ubicación está el gimnasio de Blaine?', 7, 2),
                                                        ('¿Cuál es su tipo de especialidad?', 7, 2),
                                                        ('¿Qué medalla recibe el jugador al vencerlo?', 7, 2),
                                                        ('¿Qué tipo de Pokémon tiene ventaja contra su equipo?', 7, 2),
                                                        ('¿Qué estrategia de entorno tiene el gimnasio de Blaine que lo hace más complicado?', 7, 2);

INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta) VALUES
                                                            (31, 'Isla Canela', TRUE),
                                                            (31, 'Ciudad Azafrán', FALSE),
                                                            (31, 'Ciudad Verde', FALSE),
                                                            (31, 'Ciudad Celeste', FALSE),

                                                            (32, 'Fuego', TRUE),
                                                            (32, 'Planta', FALSE),
                                                            (32, 'Roca', FALSE),
                                                            (32, 'Agua', FALSE),

                                                            (33, 'Medalla Volcán', TRUE),
                                                            (33, 'Medalla Tierra', FALSE),
                                                            (33, 'Medalla Roca', FALSE),
                                                            (33, 'Medalla Pantano', FALSE),

                                                            (34, 'Agua o Tierra', TRUE),
                                                            (34, 'Eléctrico o Hielo', FALSE),
                                                            (34, 'Planta o Bicho', FALSE),
                                                            (34, 'Fantasma o Siniestro', FALSE),

                                                            (35, 'Laberinto de flechas móviles o escaleras', TRUE),
                                                            (35, 'Solo batalla de líderes antiguos', FALSE),
                                                            (35, 'Combates dobles invisibles', FALSE),
                                                            (35, 'Peleas solo con los Pokémon iniciales del adversario', FALSE);

-- Medalla Tierra (Medalla_ID = 8) — preguntas 36-40
INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID) VALUES
                                                        ('¿Qué tipo de Pokémon domina Giovanni?', 8, 2),
                                                        ('¿En qué ciudad se encuentra su gimnasio en Kanto?', 8, 2),
                                                        ('¿Qué medalla entrega Giovanni a quien lo derrota?', 8, 2),
                                                        ('¿Qué otro rol "oscuro" tiene Giovanni aparte de líder de gimnasio?', 8, 2),
                                                        ('¿Qué tipo de estrategia puede hacerle frente al equipo de Giovanni?', 8, 2);

INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta) VALUES
                                                            (36, 'Tierra', TRUE),
                                                            (36, 'Hielo', FALSE),
                                                            (36, 'Fantasma', FALSE),
                                                            (36, 'Fuego', FALSE),

                                                            (37, 'Ciudad Verde', TRUE),
                                                            (37, 'Ciudad Plateada', FALSE),
                                                            (37, 'Ciudad Celeste', FALSE),
                                                            (37, 'Ciudad Carmín', FALSE),

                                                            (38, 'Medalla Tierra', TRUE),
                                                            (38, 'Medalla Volcán', FALSE),
                                                            (38, 'Medalla Trueno', FALSE),
                                                            (38, 'Medalla Alma', FALSE),

                                                            (39, 'Jefe del Team Rocket', TRUE),
                                                            (39, 'Líder del Alto Mando', FALSE),
                                                            (39, 'Entrenador de doble tipo Agua', FALSE),
                                                            (39, 'Profesional de concursos Pokémon', FALSE),

                                                            (40, 'Agua o Hielo', TRUE),
                                                            (40, 'Planta o Eléctrico', FALSE),
                                                            (40, 'Fuego o Fantasma', FALSE),
                                                            (40, 'Volador o Bicho', FALSE);

-- ==============================
-- ACTUALIZACIÓN DE ESTADOS Y DIFICULTAD
-- ==============================

-- Actualizar todas las preguntas a estado Aprobada
UPDATE pregunta
SET Estado_ID = 2
WHERE Estado_ID = 1;

-- Inicializar Dificultad para preguntas ya existentes (Laplace smoothing)
UPDATE Pregunta
SET Dificultad = ROUND(1 - ((Cant_veces_correcta + 1) / (GREATEST(Cant_veces_respondida,0) + 2)), 3),
    DificultadNivel =
        CASE
            WHEN Cant_veces_respondida < 5 THEN 'Nuevo'
            WHEN (1 - ((Cant_veces_correcta + 1) / (Cant_veces_respondida + 2))) <= 0.33 THEN 'Fácil'
            WHEN (1 - ((Cant_veces_correcta + 1) / (Cant_veces_respondida + 2))) <= 0.66 THEN 'Medio'
            ELSE 'Difícil'
            END;


CREATE TABLE Pregunta_sugerida (
                                   ID INT AUTO_INCREMENT PRIMARY KEY,
                                   Texto TEXT NOT NULL,
                                   Medalla_ID INT NOT NULL,
                                   Sugerida_por_usuario_ID INT NOT NULL,
                                   Fecha_sugerencia DATETIME DEFAULT CURRENT_TIMESTAMP,
                                   Estado ENUM('Pendiente', 'Aprobada', 'Rechazada') DEFAULT 'Pendiente',
                                   Revisada_por INT NULL,
                                   Fecha_revision DATETIME NULL,
                                   Motivo_rechazo TEXT NULL,
                                   FOREIGN KEY (Medalla_ID) REFERENCES Medallas(ID) ON DELETE CASCADE,
                                   FOREIGN KEY (Sugerida_por_usuario_ID) REFERENCES usuarios(ID) ON DELETE CASCADE,
                                   FOREIGN KEY (Revisada_por) REFERENCES usuarios(ID) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla para respuestas de preguntas sugeridas
CREATE TABLE Respuesta_sugerida (
                                    ID INT AUTO_INCREMENT PRIMARY KEY,
                                    Pregunta_sugerida_ID INT NOT NULL,
                                    Texto VARCHAR(255) NOT NULL,
                                    Es_Correcta BOOLEAN NOT NULL DEFAULT FALSE,
                                    FOREIGN KEY (Pregunta_sugerida_ID) REFERENCES Pregunta_sugerida(ID) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Modificar tabla Reporte para agregar más información
ALTER TABLE Reporte
    ADD COLUMN Estado ENUM('Pendiente', 'En_revision', 'Resuelto', 'Rechazado') DEFAULT 'Pendiente' AFTER Motivo,
ADD COLUMN Fecha_resolucion DATETIME NULL AFTER Estado;

-- Insertar datos de ejemplo para preguntas sugeridas
INSERT INTO Pregunta_sugerida (Texto, Medalla_ID, Sugerida_por_usuario_ID, Estado) VALUES
                                                                                       ('¿Cuál es el Pokémon legendario de tipo Fuego en la primera generación?', 7, 1, 'Pendiente'),
                                                                                       ('¿Qué movimiento aprende Pikachu al nivel 26 en Pokémon Amarillo?', 3, 1, 'Pendiente'),
                                                                                       ('¿En qué ruta se encuentra Snorlax durmiendo bloqueando el paso?', 1, 1, 'Pendiente');

-- Insertar respuestas para las preguntas sugeridas
INSERT INTO Respuesta_sugerida (Pregunta_sugerida_ID, Texto, Es_Correcta) VALUES
-- Para pregunta 1 (Pokémon legendario de fuego)
(1, 'Moltres', TRUE),
(1, 'Articuno', FALSE),
(1, 'Zapdos', FALSE),
(1, 'Ho-Oh', FALSE),

-- Para pregunta 2 (Movimiento de Pikachu)
(2, 'Trueno', TRUE),
(2, 'Rayo', FALSE),
(2, 'Impactrueno', FALSE),
(2, 'Bola Voltio', FALSE),

-- Para pregunta 3 (Snorlax)
(3, 'Ruta 12 y 16', TRUE),
(3, 'Ruta 10 y 11', FALSE),
(3, 'Ruta 5 y 6', FALSE),
(3, 'Ruta 20 y 21', FALSE);

-- Insertar reportes de ejemplo (asegúrate de que existan las preguntas con estos IDs)
INSERT INTO Reporte (Usuario_ID, Pregunta_ID, Motivo, Estado) VALUES
                                                                  (1, 5, 'La respuesta correcta está marcada incorrectamente', 'Pendiente'),
                                                                  (1, 12, 'Pregunta duplicada con la ID #8', 'Pendiente'),
                                                                  (1, 20, 'Las opciones son confusas y poco claras', 'Pendiente');