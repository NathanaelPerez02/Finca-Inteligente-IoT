#include <SPI.h>
#include <MFRC522.h>
#include <Servo.h>

const int trigPin = 6;
const int echoPin = 7;
const int pinRST = 5;
const int pinSS = 10;
const int pinServo = 9;
const int pinNivelAgua = A1;
const int pinHumedadSuelo = A0;

MFRC522 mfrc522(pinSS, pinRST);
Servo miServo;

int ultimaDistanciaValida = 100;

void setup() {
  Serial.begin(9600);   
  SPI.begin();          
  mfrc522.PCD_Init();   
  
  miServo.attach(pinServo);
  miServo.write(0);     
  
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);
  
  Serial.println("=== SISTEMA COMPLETO: ACCESO + AGUA RECALIBRADO + SUELO ===");
}

int obtenerDistanciaEstable() {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);

  long duracion = pulseIn(echoPin, HIGH, 30000); 
  int distanciaDirecta = duracion * 0.034 / 2;

  if (distanciaDirecta <= 0 || distanciaDirecta > 400) {
    return ultimaDistanciaValida;
  }

  if (abs(distanciaDirecta - ultimaDistanciaValida) > 40) {
    delay(30);
    digitalWrite(trigPin, HIGH); delayMicroseconds(10); digitalWrite(trigPin, LOW);
    int reVerificacion = pulseIn(echoPin, HIGH, 30000) * 0.034 / 2;
    
    if (abs(reVerificacion - distanciaDirecta) < 10 && reVerificacion > 0) {
      ultimaDistanciaValida = distanciaDirecta;
    }
  } else {
    ultimaDistanciaValida = distanciaDirecta;
  }

  return ultimaDistanciaValida;
}

void loop() {
  int valorAguaRaw = analogRead(pinNivelAgua);
  int porcentajeAgua = map(valorAguaRaw, 0, 1750, 0, 100);
  
  if (porcentajeAgua < 0) porcentajeAgua = 0;
  if (porcentajeAgua > 100) porcentajeAgua = 100;

  int valorSueloRaw = analogRead(pinHumedadSuelo);
  int porcentajeSuelo = map(valorSueloRaw, 1023, 200, 0, 100);
  
  if (porcentajeSuelo < 0) porcentajeSuelo = 0;
  if (porcentajeSuelo > 100) porcentajeSuelo = 100;

  int distancia = obtenerDistanciaEstable();

  Serial.println("----------------------------------------");
  Serial.print("Nivel de Piscina: "); 
  Serial.print(porcentajeAgua); 
  Serial.println("%");
  
  Serial.print("Humedad de Suelo: "); 
  Serial.print(porcentajeSuelo); 
  Serial.println("%");
  
  if (distancia > 15) {
    Serial.print("[DESPEJADO] Camino libre. Distancia: "); 
    Serial.print(distancia); 
    Serial.println(" cm");
  } 
  else if (distancia > 10 && distancia <= 15) {
    Serial.print("[ACERCANDOSE] Vehiculo cerca. Distancia: "); 
    Serial.print(distancia); 
    Serial.println(" cm");
  } 
  else if (distancia <= 10) {
    Serial.print("[ZONA OCUPADA] Esperando tarjeta RFID... Distancia: "); 
    Serial.print(distancia); 
    Serial.println(" cm");

    if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
      Serial.print("TARJETA DETECTADA! UID Hex:");
      for (byte i = 0; i < mfrc522.uid.size; i++) {
        Serial.print(mfrc522.uid.uidByte[i] < 0x10 ? " 0" : " ");
        Serial.print(mfrc522.uid.uidByte[i], HEX);
      }
      Serial.println();

      Serial.println("ACCESO CONCEDIDO. Abriendo pluma (90°)...");
      miServo.write(90); 
      
      Serial.println("Manteniendo pluma arriba por 5 segundos...");
      delay(5000); 

      Serial.println("Tiempo cumplido. Cerrando pluma automaticamente (0°)...");
      miServo.write(0); 
      
      mfrc522.PICC_HaltA(); 
    }
  }

  delay(400); 
}