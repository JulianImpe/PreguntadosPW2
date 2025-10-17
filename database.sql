CREATE DATABASE IF NOT EXISTS preguntados;
USE preguntados;

DROP TABLE IF EXISTS usuarios;



CREATE TABLE usuarios (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL UNIQUE,
     password VARCHAR(255) NOT NULL,
      email VARCHAR(255) NOT NULL UNIQUE,
          fecha_nac DATE NOT NULL,
              foto_perfil VARCHAR(255) NULL,
    nombre_completo VARCHAR(255) NOT NULL);




