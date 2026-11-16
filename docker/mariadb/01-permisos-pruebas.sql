-- Se ejecuta solo al crear el volumen de la base de datos por primera vez.
-- Permite al usuario de la aplicación crear la base de datos de las pruebas automatizadas.
GRANT ALL PRIVILEGES ON `voluntared_test`.* TO 'voluntared'@'%';
FLUSH PRIVILEGES;
