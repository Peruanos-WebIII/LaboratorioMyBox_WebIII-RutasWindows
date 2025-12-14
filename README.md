# Script para la base de datos
Necesaria para el proyecto, esta base de datos es MySQL en PHPMyadmin:

bash
```
DROP DATABASE IF EXISTS mybox2;
CREATE DATABASE mybox2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE mybox2;

CREATE TABLE usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  usuario VARCHAR(30) NOT NULL,
  contra VARCHAR(255) NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  email VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE directorios (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(255) NOT NULL,
  parent_id INT NULL,
  usuario_id INT NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY parent_id (parent_id),
  KEY usuario_id (usuario_id),
  CONSTRAINT fk_directorios_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_directorios_parent
    FOREIGN KEY (parent_id) REFERENCES directorios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE archivos (
  id BIGINT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  directorio_id INT NULL,
  nombre VARCHAR(255) NOT NULL,
  extension VARCHAR(20) NULL,
  mime VARCHAR(120) NULL,
  tamano_bytes BIGINT NULL,
  contenido LONGBLOB NOT NULL,
  comentario VARCHAR(255) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY usuario_id (usuario_id),
  KEY directorio_id (directorio_id),
  CONSTRAINT fk_archivos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_archivos_directorio
    FOREIGN KEY (directorio_id) REFERENCES directorios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE compartidos (
  id BIGINT NOT NULL AUTO_INCREMENT,
  archivo_id BIGINT NULL,
  directorio_id INT NULL,
  propietario_id INT NOT NULL,
  compartido_con_id INT NOT NULL,
  permiso ENUM('lectura','escritura') NOT NULL DEFAULT 'lectura',
  tipo ENUM('archivo','directorio') NOT NULL DEFAULT 'archivo',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY archivo_id (archivo_id),
  KEY directorio_id (directorio_id),
  KEY propietario_id (propietario_id),
  KEY compartido_con_id (compartido_con_id),
  CONSTRAINT fk_comp_archivo
    FOREIGN KEY (archivo_id) REFERENCES archivos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comp_directorio
    FOREIGN KEY (directorio_id) REFERENCES directorios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comp_propietario
    FOREIGN KEY (propietario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comp_con
    FOREIGN KEY (compartido_con_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

