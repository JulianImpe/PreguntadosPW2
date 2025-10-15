CREATE DATABASE IF NOT EXISTS pokedex;
USE pokedex;

DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS pokemon;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL
);

CREATE TABLE pokemon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(200)
);

INSERT INTO usuarios (usuario, password) VALUES ('admin', 'admin');

INSERT INTO pokemon (numero, nombre, tipo, descripcion, imagen) VALUES
(1, 'Pokemon1', 'fuego', 'Descripción autogenerada 1', 'imagenes/pokemon1.png'),
(2, 'Pokemon2', 'agua', 'Descripción autogenerada 2', 'imagenes/pokemon2.png'),
(3, 'Pokemon3', 'planta', 'Descripción autogenerada 3', 'imagenes/pokemon3.png'),
(4, 'Pokemon4', 'electrico', 'Descripción autogenerada 4', 'imagenes/pokemon4.png'),
(5, 'Pokemon5', 'fuego', 'Descripción autogenerada 5', 'imagenes/pokemon5.png'),
(6, 'Pokemon6', 'agua', 'Descripción autogenerada 6', 'imagenes/pokemon6.png'),
(7, 'Pokemon7', 'planta', 'Descripción autogenerada 7', 'imagenes/pokemon7.png'),
(8, 'Pokemon8', 'electrico', 'Descripción autogenerada 8', 'imagenes/pokemon8.png'),
(9, 'Pokemon9', 'fuego', 'Descripción autogenerada 9', 'imagenes/pokemon9.png'),
(10,'Pokemon10','agua', 'Descripción autogenerada 10', 'imagenes/pokemon10.png');
