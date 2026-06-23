# Agrogate

## Descripción
Agrogate es un sistema integral de automatización agrícola basado en tecnologías de Internet de las Cosas (IoT). Su propósito principal es mejorar la eficiencia operativa mediante el monitoreo en tiempo real, el control automatizado de accesos y la gestión remota de información.

## Características Principales
* **Monitoreo en tiempo real:** Medición de humedad del suelo y niveles de agua en reservorios.
* **Seguridad y Acceso:** Gestión de acceso vehicular mediante RFID y automatización de tranqueras.
* **Arquitectura Distribuida:** Utiliza un nodo de adquisición (Arduino) y un nodo de procesamiento/comunicación (NodeMCU) para mayor escalabilidad.
* **Resiliencia:** Implementa almacenamiento local con LittleFS para evitar la pérdida de información durante fallos de conectividad WiFi.
* **Interfaz Web:** Visualización de datos y sincronización con API externa.

## Hardware y Conexiones
El sistema se divide en dos capas principales:
1. **Capa física:** Basada en Arduino Uno R3 para lectura de sensores y control de actuadores.
2. **Capa de comunicación:** Basada en NodeMCU ESP8266 para conectividad WiFi y lógica de red.
* La comunicación entre ambos microcontroladores se realiza mediante el protocolo I2C (líneas SDA y SCL).

## Instalación
1. **Montaje:** Conectar los sensores, actuadores y microcontroladores según el diagrama de conexiones (esquema eléctrico) detallado en el "Documento Proyecto Final --- Micro.pdf".
2. **Programación:** 
    * Cargar el código de adquisición de datos en el nodo esclavo (Arduino).
    * Configurar el nodo maestro (NodeMCU) para gestionar el servidor web y la comunicación con la API.
3. **Resiliencia:** Asegurar la implementación de LittleFS para el archivo `offline.txt` en el NodeMCU.

## Equipo de Desarrollo
* **Fátima Arely Cruz Márquez:** Interfaz web y frontend.
* **Kevin Nathanael Granados Pérez:** Diseño de circuitos e integración de hardware.
* **Josué Iván Molina Romero:** Montaje físico, pruebas de sensores y soporte.
* **Cristian Geovanny Rubio García:** Documentación técnica y gestión de API.
