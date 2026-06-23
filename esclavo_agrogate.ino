#include <Wire.h>
#include <Servo.h>
#include <SPI.h>
#include <MFRC522.h>

#define PIN_HUMEDAD A0
#define PIN_AGUA A1
#define TRIG_FRONTAL 6
#define ECHO_FRONTAL 7
#define SERVO_PIN 9
#define RST_PIN 5
#define SS_PIN 10
#define BOTON_MODO 4
#define BOTON_CONTROL 8
#define LED_MODO 2

Servo miMotor;
MFRC522 mfrc522(SS_PIN, RST_PIN);

byte datosSensores[5] = {0, 0, 0, 0, 0};

enum EstadoTranquera { CERRADA, ABIERTA_VEHICULO_PRESENTE, ABIERTA_CONTANDO_CIERRE };
EstadoTranquera estadoActual = CERRADA;

enum ModoFuncionamiento { AUTOMATICO, MANUAL };
ModoFuncionamiento modoActual = AUTOMATICO;

unsigned long tiempoInicioCierre = 0;
const unsigned long TIEMPO_CIERRE_RAPIDO = 1000; 
int posicionActualServo = 0; 
unsigned long tiempoUltimoMensaje = 0; 

bool ultimoEstadoBotonModo = HIGH;
bool ultimoEstadoBotonControl = HIGH;
bool portonAbiertoManual = false;
volatile bool abrirDesdeWeb = false;

void setup() {
  Serial.begin(9600);
  Wire.begin(8);
  Wire.onRequest(enviarDatos);
  Wire.onReceive(recibirEventoWeb);
  
  pinMode(TRIG_FRONTAL, OUTPUT);
  pinMode(ECHO_FRONTAL, INPUT);
  
  pinMode(BOTON_MODO, INPUT_PULLUP);
  pinMode(BOTON_CONTROL, INPUT_PULLUP);
  pinMode(LED_MODO, OUTPUT);
  
  // Estado inicial: Modo Automático (LED apagado)
  digitalWrite(LED_MODO, LOW);
  
  miMotor.attach(SERVO_PIN);
  miMotor.write(posicionActualServo);
  //delay(500);
  //miMotor.detach();
  
  inicializarRFID();
  Serial.println("====================");
  Serial.println("  SISTEMA ESCLAVO  ");
  Serial.println("====================");
}

void inicializarRFID() {
  SPI.begin();
  mfrc522.PCD_Init();
  mfrc522.PCD_SetAntennaGain(mfrc522.RxGain_max);
}

void reiniciarRFID_Hardware() {
  SPI.end();
  pinMode(RST_PIN, OUTPUT);
  digitalWrite(RST_PIN, LOW);
  delay(50);
  digitalWrite(RST_PIN, HIGH);
  inicializarRFID();
}

void loop() {
  int distanciaFrontal = obtenerDistancia();
  
  datosSensores[0] = constrain(map(analogRead(PIN_HUMEDAD), 1023, 0, 0, 100), 0, 100);
  datosSensores[1] = constrain(map(analogRead(PIN_AGUA), 0, 700, 0, 100), 0, 100);
  datosSensores[2] = distanciaFrontal;
  datosSensores[3] = (modoActual == AUTOMATICO) ? 0 : 1;
  datosSensores[4] = (estadoActual != CERRADA || portonAbiertoManual) ? 1 : 0;

  bool lecturaBotonModo = digitalRead(BOTON_MODO);
  if (lecturaBotonModo == LOW && ultimoEstadoBotonModo == HIGH) {
    delay(50); 
    if (modoActual == AUTOMATICO) {
      modoActual = MANUAL;
      digitalWrite(LED_MODO, HIGH); // Enciende el LED en modo manual
      Serial.println(">>> MODO: MANUAL ACTIVADO");
    } else {
      modoActual = AUTOMATICO;
      estadoActual = CERRADA;
      portonAbiertoManual = false;
      moverServo(0);
      reiniciarRFID_Hardware();
      digitalWrite(LED_MODO, LOW); // Apaga el LED en modo automático
      Serial.println(">>> MODO: AUTOMATICO ACTIVADO");
    }
  }
  ultimoEstadoBotonModo = lecturaBotonModo;

  if (millis() - tiempoUltimoMensaje >= 2000) {
    tiempoUltimoMensaje = millis();
    Serial.print("[INFO] Modo: ");
    Serial.print(modoActual == AUTOMATICO ? "AUTO" : "MANUAL");
    Serial.print(" | Radar: "); Serial.print(distanciaFrontal);
    Serial.print(" cm | Tierra: "); Serial.print(datosSensores[0]);
    Serial.print("% | Agua: "); Serial.print(datosSensores[1]);
    Serial.println("%");
  }

  if (modoActual == MANUAL) {
    bool lecturaBotonControl = digitalRead(BOTON_CONTROL);
    if (lecturaBotonControl == LOW && ultimoEstadoBotonControl == HIGH) {
      delay(50);
      if (!portonAbiertoManual) {
        Serial.println(">>> MANUAL: Abriendo porton...");
        moverServo(90);
        portonAbiertoManual = true;
      } else {
        Serial.println(">>> MANUAL: Cerrando porton...");
        moverServo(0);
        portonAbiertoManual = false;
      }
    }
    ultimoEstadoBotonControl = lecturaBotonControl;
  } 
  else {
    switch (estadoActual) {
      case CERRADA:
        if (distanciaFrontal > 0 && distanciaFrontal <= 10) {
          if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
            Serial.println(">>> AUTO: Acceso concedido. Abriendo...");
            moverServo(90);
            estadoActual = ABIERTA_VEHICULO_PRESENTE;
            reiniciarRFID_Hardware();
          }
        }
        break;
        
      case ABIERTA_VEHICULO_PRESENTE:
        if (distanciaFrontal > 30) {
          Serial.println(">>> AUTO: Vehiculo alejandose. Contando 1 segundo...");
          tiempoInicioCierre = millis();
          estadoActual = ABIERTA_CONTANDO_CIERRE;
        }
        break;
        
      case ABIERTA_CONTANDO_CIERRE:
        if (distanciaFrontal <= 30) {
          Serial.println(">>> PELIGRO: Objeto detectado. Pausando cierre.");
          estadoActual = ABIERTA_VEHICULO_PRESENTE;
        }
        else if (millis() - tiempoInicioCierre >= TIEMPO_CIERRE_RAPIDO) {
          Serial.println(">>> AUTO: Area libre. Cerrando porton.");
          moverServo(0);
          estadoActual = CERRADA;
          reiniciarRFID_Hardware();
        }
        break;
    }
  }

  if (abrirDesdeWeb) {
    Serial.println(">>> APERTURA REMOTA DESDE LA WEB");
    moverServo(90);
    estadoActual = ABIERTA_VEHICULO_PRESENTE;
    abrirDesdeWeb = false;
  }
}

int obtenerDistancia() {
  // Hacemos hasta 3 intentos para filtrar el ruido y los "ecos fantasmas"
  for (int i = 0; i < 3; i++) {
    digitalWrite(TRIG_FRONTAL, LOW); delayMicroseconds(2);
    digitalWrite(TRIG_FRONTAL, HIGH); delayMicroseconds(10);
    digitalWrite(TRIG_FRONTAL, LOW);
    
    long d = pulseIn(ECHO_FRONTAL, HIGH, 30000);
    int distancia = (d == 0) ? 255 : constrain(d * 0.034 / 2, 0, 255);
    
    // Si la distancia es válida (menor a 255), la aceptamos de inmediato
    if (distancia < 255) {
      return distancia; 
    }
    
    // Si dio 255, hacemos un silencio de 40 milisegundos para limpiar el aire y reintentamos
    delay(40);
  }
  
  // Si falló 3 veces seguidas, entonces realmente no hay nada enfrente
  return 255; 
}

void moverServo(int destino) {
  //miMotor.attach(SERVO_PIN);
  if (posicionActualServo < destino) {
    for (int pos = posicionActualServo; pos <= destino; pos++) {
      miMotor.write(pos);
      delay(30); 
    }
  } else {
    for (int pos = posicionActualServo; pos >= destino; pos--) {
      miMotor.write(pos);
      delay(30); 
    }
  }
  posicionActualServo = destino;
  //miMotor.detach();
}

void enviarDatos() { 
  Wire.write(datosSensores, 5); 
}

void recibirEventoWeb(int cuantos) {
  if (Wire.available()) {
    char comando = Wire.read();
    if (comando == 'A') {
      // Abrir tranquera (solo en modo manual, desde web)
      if (modoActual == MANUAL) {
        moverServo(90);
        portonAbiertoManual = true;
        Serial.println(">>> WEB: Abriendo tranquera en modo MANUAL");
      }
      abrirDesdeWeb = false;
    } else if (comando == 'C') {
      if (modoActual == MANUAL) {
        moverServo(0);
        portonAbiertoManual = false;
        Serial.println(">>> WEB: Cerrando tranquera en modo MANUAL");
      }
    } else if (comando == 'M') {
      modoActual = MANUAL;
      digitalWrite(LED_MODO, HIGH);
      Serial.println(">>> WEB: Modo MANUAL activado");
    } else if (comando == 'U') {
      modoActual = AUTOMATICO;
      estadoActual = CERRADA;
      portonAbiertoManual = false;
      moverServo(0);
      digitalWrite(LED_MODO, LOW);
      Serial.println(">>> WEB: Modo AUTOMATICO activado");
    }
  }
}