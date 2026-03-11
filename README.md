# 🏦 BancObsidiana - Laravel 12 Banking Ecosystem

[![Laravel 12](https://img.shields.io/badge/Framework-Laravel%2012-red)](https://laravel.com)
[![Docker](https://img.shields.io/badge/Deployment-Docker-blue)](https://www.docker.com/)
[![Railway](https://img.shields.io/badge/Host-Railway-black)](https://railway.app/)

BancObsidiana es una solución de banca online moderna que funciona como emisor y adquiriente dentro de un ecosistema de comercio electrónico. El sistema permite la gestión integral de clientes, cuentas y tarjetas, además de procesar pagos locales y externos mediante protocolos de interoperabilidad interbancaria.

## 🚀 Funcionalidades Principales
* **Onboarding Digital:** Registro de usuarios y apertura automática de cuentas.
* **Gestión de Tarjetas:** Emisión de plásticos virtuales con validación de Algoritmo de Luhn.
* **Motor de Transacciones:** Procesamiento de compras, transferencias y cobro de comisiones (fee del 2%).
* **Enrutamiento Inteligente:** Identificación de bancos externos mediante BIN (Bank Identification Number).
* **Seguridad:** Cifrado de datos sensibles (PAN/CVV) y análisis de contexto para prevención de fraude.

---

## 📈 Evolución del Proyecto (Sprints)

Aquí se detalla el progreso técnico basado en la documentación oficial de cada fase:

### 🔹 Sprint 1: Onboarding y Arquitectura Base
* **Objetivo:** Construir la base operativa y el modelo Entidad-Relación.
* **Hitos Logrados:**

* **Arquitectura:** Implementación del modelo jerárquico User-Client e integración con Laravel Auth.

### 🔹 Sprint 2: Gestión Interna y Seguridad
* **Objetivo:** Emisión de tarjetas y exposición de saldos vía API.
* **Hitos Logrados:**
    
* **Seguridad:** Uso de `Laravel Encrypted Casting` para proteger PAN y CVV en la base de datos MySQL.

### 🔹 Sprint 3: Interoperabilidad y Despliegue
* **Objetivo:** Dockerización y Protocolo Bancario Interequipos.
* **Hitos Logrados:**
    
* **Conectividad:** Implementación del endpoint `/api/v1/transaction/process` con lógica de enrutamiento externa.

---

## 🛠️ Stack Tecnológico
* **Backend:** Laravel 12 (PHP 8.2+)
* **Base de Datos:** MySQL / SQLite (Persistente en Docker)
* **Documentación:** Swagger / L5-Swagger (OpenAPI 3.0)
* **Infraestructura:** Docker & Railway



---

## 🔌 API Endpoints (Resumen)

| Método | Endpoint | Descripción | Acceso |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/transaction/process` | Procesa o enruta un pago | Público/Comercio |
| `GET` | `/api/my-cards` | Lista las tarjetas del usuario | Protegido (Sanctum) |
| `POST` | `/api/cards/request` | Solicita una nueva tarjeta | Protegido (Sanctum) |

---

## 🔒 Seguridad de la Información
El sistema se rige por los pilares de la seguridad informática aplicados al ciclo de vida del dato:
1.  **Confidencialidad:** Datos de tarjetas cifrados y acceso mediante tokens Bearer.
2.  **Integridad:** Transacciones atómicas mediante `DB::beginTransaction()`.
3.  **Disponibilidad:** Despliegue en contenedores Docker para alta resiliencia.

---

## ⚙️ Instalación Local

1. Clonar el repositorio:
   ```bash
   git clone [https://github.com/mattw-x/bancobsidiana.git](https://github.com/mattw-x/bancobsidiana.git)
   ```
