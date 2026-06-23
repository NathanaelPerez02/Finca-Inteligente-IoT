#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Wire.h>
#include <LittleFS.h>
#include <time.h>

// CONFIGURACIÓN
//const char* ssid     = "TIGO-F826";
//const char* password = "4D9697509665";

//const char* ssid     = "A36T";
//const char* password = "totirris1234";

const char* ssid     = "GalaxyA21s86DD";
const char* password = "horz9202";

//const char* ssid     = "Redmi Note 10 Pro";
//const char* password = "12345678.";

const String urlAPI    = "https://finca-inteligente-iot-production.up.railway.app/api_sensores.php";
const String miUsuario = "oldtote";

ESP8266WebServer server(80);

const bool MODO_PRUEBA = false; // true = valores fijos para testear , false = datos reales

byte humedad       = MODO_PRUEBA ? 45 : 0;
byte agua          = MODO_PRUEBA ? 72 : 0;
byte distancia     = MODO_PRUEBA ? 100 : 0;
byte modo          = 0;
byte estado_puerta = 0;

// ── INTERVALOS ────────────────────────────────────────────────────────────────
const unsigned long INTERVALO_NORMAL = 30000;  // 30 seg en reposo
const unsigned long INTERVALO_ACTIVO =  1000;
// ──────────────────────────────────────────────────────────────────────────────

unsigned long tiempoUltimaPeticionI2C = 0;
unsigned long tiempoUltimoEnvio       = 0;
unsigned long tiempoUltimoCheck       = 0;

// Variables para detectar cambios
bool estadoAnteriorPuerta = false;
byte modoAnterior         = 0;
byte distanciaAnterior    = 255;

// CÓDIGO WEB LOCAL
const char PAGINA_WEB[] PROGMEM = R"=====(
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgroGate Local</title>
  <style>
    body { font-family: sans-serif; background: #1e1e24; color: white; text-align: center; margin-top: 50px; }
    .tarjeta { background: #27272a; padding: 20px; border-radius: 10px; display: inline-block; margin: 10px; width: 200px; vertical-align: top; }
    .valor { font-size: 2.5rem; font-weight: bold; color: #4ade80; }
  </style>
</head>
<body>
  <h1>Panel Local de Emergencia</h1>
  <div class="tarjeta"><h3>Modo</h3><div class="valor" id="m" style="color: #a78bfa;">--</div></div>
  <div class="tarjeta"><h3>Tranquera</h3><div class="valor" id="d">-- cm</div></div>
  <div class="tarjeta"><h3>Humedad</h3><div class="valor" id="h">--%</div></div>
  <div class="tarjeta"><h3>Piscina</h3><div class="valor" id="a">--%</div></div>
  <script>
    setInterval(() => {
      fetch('/datos').then(r => r.json()).then(d => {
        document.getElementById('h').innerText = d.humedad + '%';
        document.getElementById('a').innerText = d.agua + '%';
        document.getElementById('d').innerText = d.distancia + ' cm';
        let uiModo = document.getElementById('m');
        if (d.modo === 0) {
          uiModo.innerText = 'AUTO';
          uiModo.style.color = '#4ade80';
        } else {
          uiModo.innerText = 'MANUAL';
          uiModo.style.color = '#fbbf24';
        }
      });
    }, 500);
  </script>
</body>
</html>
)=====";

void manejarRaiz()  { server.send(200, "text/html", FPSTR(PAGINA_WEB)); }
void manejarDatos() {
  String json = "{\"humedad\":"   + String(humedad)   +
                ",\"agua\":"      + String(agua)       +
                ",\"distancia\":" + String(distancia)  +
                ",\"modo\":"      + String(modo)       + "}";
  server.send(200, "application/json", json);
}

String obtenerTimestamp() {
  time_t ahora = time(nullptr);
  struct tm* infoTiempo = localtime(&ahora);
  char buffer[20];
  sprintf(buffer, "%04d-%02d-%02d_%02d:%02d:%02d",
    infoTiempo->tm_year + 1900, infoTiempo->tm_mon + 1, infoTiempo->tm_mday,
    infoTiempo->tm_hour, infoTiempo->tm_min, infoTiempo->tm_sec);
  return String(buffer);
}

bool enviarNube(String hum, String ag, String dist, String mod, String timestamp) {
  WiFiClientSecure client;
  client.setInsecure();
  HTTPClient http;

  String urlFinal = urlAPI + "?usuario=" + miUsuario +
                    "&humedad=" + hum + "&agua=" + ag +
                    "&acceso="  + dist + "&modo=" + mod +
                    "&estado_tranquera=" + String(estado_puerta) +
                    "&fecha=" + timestamp;

  http.begin(client, urlFinal);
  int codigo = http.GET();

  if (codigo == HTTP_CODE_OK) {
    String respuesta = http.getString();

    // Comando abrir
    if (respuesta.indexOf("\"abrir\":1") != -1) {
      Serial.println(">>> ORDEN WEB: ABRIR TRANQUERA");
      Wire.beginTransmission(8);
      Wire.write('A');
      Wire.endTransmission();
      delay(50);
    }

    // Comando cerrar — letra C al Arduino
    if (respuesta.indexOf("\"cerrar\":1") != -1) {
      Serial.println(">>> ORDEN WEB: CERRAR TRANQUERA");
      Wire.beginTransmission(8);
      Wire.write('C');
      Wire.endTransmission();
      delay(50);
    }

    // Comando modo
    if (respuesta.indexOf("\"set_modo\":1") != -1) {
      Wire.beginTransmission(8);
      Wire.write('M');
      Wire.endTransmission();
    } else if (respuesta.indexOf("\"set_modo\":0") != -1) {
      Wire.beginTransmission(8);
      Wire.write('U');
      Wire.endTransmission();
    }
  }

  http.end();
  return (codigo > 0);
}

void sincronizarDatosPendientes() {
  if (!LittleFS.exists("/offline.txt")) return;
  Serial.println("¡Internet detectado! Sincronizando datos almacenados...");

  File archivo = LittleFS.open("/offline.txt", "r");
  bool exitoTotal = true;

  while (archivo.available()) {
    String linea = archivo.readStringUntil('\n');
    linea.trim();
    if (linea.length() > 0) {
      int coma1 = linea.indexOf(',');
      int coma2 = linea.indexOf(',', coma1 + 1);
      int coma3 = linea.indexOf(',', coma2 + 1);
      int coma4 = linea.indexOf(',', coma3 + 1);

      String h_guardada = linea.substring(0, coma1);
      String a_guardada = linea.substring(coma1 + 1, coma2);
      String d_guardada = linea.substring(coma2 + 1, coma3);
      String m_guardada = linea.substring(coma3 + 1, coma4);
      String t_guardado = linea.substring(coma4 + 1);

      if (!enviarNube(h_guardada, a_guardada, d_guardada, m_guardada, t_guardado)) {
        exitoTotal = false;
        break;
      }
      delay(500);
    }
  }
  archivo.close();

  if (exitoTotal) {
    LittleFS.remove("/offline.txt");
    Serial.println("Sincronización completada y memoria local limpiada.");
  }
}

void setup() {
  Serial.begin(9600);
  Wire.begin(D2, D1);

  if (!LittleFS.begin()) {
    Serial.println("Error al montar LittleFS");
  }

  WiFi.begin(ssid, password);
  Serial.print("Conectando a WiFi...");
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  Serial.println("\nWiFi Conectado. IP: " + WiFi.localIP().toString());

  configTime(-21600, 0, "pool.ntp.org", "time.nist.gov");

  server.on("/",      manejarRaiz);
  server.on("/datos", manejarDatos);
  server.begin();
}

void loop() {
  server.handleClient();

  // ── Leer I2C SIEMPRE (modo prueba o no) ────────────────────────────────────
  if (millis() - tiempoUltimaPeticionI2C >= 200) {
    tiempoUltimaPeticionI2C = millis();
    Wire.requestFrom(8, 5);
    if (Wire.available() >= 5) {
      byte hum_real  = Wire.read();
      byte agua_real = Wire.read();
      distancia      = Wire.read();  // siempre real
      modo           = Wire.read();  // siempre real
      estado_puerta  = Wire.read();  // siempre real

      // Solo humedad y agua se protegen en modo prueba
      if (!MODO_PRUEBA) {
        humedad = hum_real;
        agua    = agua_real;
      }
    }
  }

  // ── Detectar cambios → envío inmediato ─────────────────────────────────────
  bool estadoActualPuerta = (estado_puerta == 1);

  if (estadoActualPuerta != estadoAnteriorPuerta || modo != modoAnterior 
      || abs((int)distancia - (int)distanciaAnterior) > 15) {
        
        Serial.println(">>> CAMBIO DETECTADO. Enviando dato inmediato...");
        estadoAnteriorPuerta = estadoActualPuerta;
        modoAnterior         = modo;
        distanciaAnterior    = distancia;

    // Enviar ahí mismo sin esperar la siguiente iteración
    if (WiFi.status() == WL_CONNECTED) {
      enviarNube(String(humedad), String(agua), String(distancia), String(modo), obtenerTimestamp());
    }
    tiempoUltimoEnvio = millis(); // evita doble envío inmediato
  }

  // ── Intervalo dinámico ──────────────────────────────────────────────────────
  unsigned long intervaloActual = (distancia <= 30 || estado_puerta == 1)
                                   ? INTERVALO_ACTIVO
                                   : INTERVALO_NORMAL;

  // ── Envío periódico ─────────────────────────────────────────────────────────
  if (millis() - tiempoUltimoEnvio >= intervaloActual) {
    tiempoUltimoEnvio = millis();

    if (WiFi.status() == WL_CONNECTED) {
      sincronizarDatosPendientes();
      Serial.print("Enviando a la nube (intervalo: ");
      Serial.print(intervaloActual / 1000);
      Serial.println(" seg)...");
      enviarNube(String(humedad), String(agua), String(distancia), String(modo), obtenerTimestamp());
    } else {
      String timestampActual = obtenerTimestamp();
      Serial.println("Sin conexión. Guardando localmente: " + timestampActual);
      File archivo = LittleFS.open("/offline.txt", "a");
      if (archivo) {
        archivo.println(String(humedad) + "," + String(agua) + "," +
                        String(distancia) + "," + String(modo) + "," + timestampActual);
        archivo.close();
      }
    }
  }

  if (millis() - tiempoUltimoCheck >= 500) {
    tiempoUltimoCheck = millis();

    WiFiClientSecure clientCheck; 
    clientCheck.setInsecure();
    HTTPClient httpCheck;
    httpCheck.begin(clientCheck, "https://finca-inteligente-iot-production.up.railway.app/check_comando.php?usuario=" + miUsuario);
    int cod = httpCheck.GET();
    if (cod == HTTP_CODE_OK) {
        String resp = httpCheck.getString();
        if (resp.indexOf("\"hay_comando\":1") != -1) {
            Serial.println(">>> COMANDO PENDIENTE DETECTADO. Enviando ahora...");
            tiempoUltimoEnvio = 0; // fuerza envío inmediato
        }
    }
    httpCheck.end();
}
}
