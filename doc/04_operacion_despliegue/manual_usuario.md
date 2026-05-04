# Manual de Usuario — NATURACOR

## Sistema Web de Punto de Venta y Gestión Integral
**Fecha:** 03/05/2026  
**Versión:** 1.2 — Reubicada en `04_operacion_despliegue/`  
**Estándar de referencia:** ISO 9001:2015 (Orientación al usuario)

---

## Tabla de Contenido

1. [Introducción](#1-introducción)
2. [Acceso al Sistema](#2-acceso-al-sistema)
3. [Pantalla Principal y Navegación](#3-pantalla-principal-y-navegación)
4. [Módulo POS — Punto de Venta](#4-módulo-pos--punto-de-venta)
5. [Módulo Caja](#5-módulo-caja)
6. [Módulo Clientes](#6-módulo-clientes)
7. [Módulo Cordiales](#7-módulo-cordiales)
8. [Módulo Fidelización](#8-módulo-fidelización)
9. [Módulo Inventario](#9-módulo-inventario)
10. [Módulo Recetario](#10-módulo-recetario)
11. [Módulo Reclamos](#11-módulo-reclamos)
12. [Módulo Reportes y Boletas](#12-módulo-reportes-y-boletas)
13. [Módulo IA (Asistente)](#13-módulo-ia-asistente)
14. [Panel de Administración](#14-panel-de-administración)
15. [Preguntas Frecuentes (FAQ)](#15-preguntas-frecuentes-faq)
16. [Soporte Técnico](#16-soporte-técnico)

---

## 1. Introducción

**NATURACOR** es un sistema web diseñado para gestionar las operaciones diarias de una tienda de productos naturales. El sistema permite:

- ✅ Registrar ventas de productos y cordiales (bebidas naturales)
- ✅ Gestionar la caja diaria con apertura y cierre
- ✅ Llevar un control de inventario con alertas de stock bajo
- ✅ Administrar clientes con programa de fidelización
- ✅ Consultar el recetario de productos naturales por enfermedad
- ✅ Registrar y dar seguimiento a reclamos
- ✅ Generar reportes y boletas en PDF
- ✅ Recibir recomendaciones inteligentes basadas en el perfil del cliente

### Requisitos del equipo

- Navegador web: Google Chrome, Firefox o Edge (versión 120 o superior)
- Conexión a red local o Internet
- Resolución de pantalla: mínimo 1366 × 768 píxeles

---

## 2. Acceso al Sistema

### 2.1. Iniciar Sesión

1. Abra su navegador web y escriba la dirección del sistema: `http://127.0.0.1:8000`
2. El sistema le redirigirá a la pantalla de **Login**
3. Ingrese su **correo electrónico** y **contraseña**
4. Presione el botón **"Iniciar Sesión"**

> **📌 Nota:** Si no tiene cuenta, solicite al administrador que le cree un usuario.

### 2.2. Cerrar Sesión

1. Haga clic en su nombre de usuario en la esquina superior derecha
2. Seleccione **"Cerrar Sesión"**
3. El sistema le redirigirá a la pantalla de login

---

## 3. Pantalla Principal y Navegación

Al iniciar sesión, verá la **barra lateral izquierda** con el menú de módulos:

| Icono | Módulo | Acceso |
|-------|--------|--------|
| 🏠 | Dashboard | Solo administrador |
| 🛒 | POS | Todos |
| 📦 | Productos | Todos |
| 👥 | Clientes | Todos |
| 💰 | Caja | Todos |
| 🧃 | Cordiales | Todos |
| ⭐ | Fidelización | Todos |
| 📖 | Recetario | Todos |
| 📢 | Reclamos | Todos |
| 📊 | Reportes | Solo administrador |
| 🤖 | Asistente IA | Todos |
| 🏢 | Sucursales | Solo administrador |
| 👤 | Usuarios | Solo administrador |

---

## 4. Módulo POS — Punto de Venta

Este es el módulo principal que usará todos los días para registrar ventas.

### 4.1. Abrir el POS

1. Haga clic en **"POS"** en el menú lateral
2. El sistema mostrará la interfaz de punto de venta

> **⚠️ Importante:** Debe tener una **caja abierta** para poder registrar ventas. Si no tiene caja abierta, el sistema le mostrará un aviso.

### 4.2. Buscar Productos

**Por nombre:**
1. Escriba el nombre o parte del nombre del producto en el campo **"Buscar producto"**
2. El sistema mostrará los productos coincidentes en tiempo real
3. Haga clic en el producto deseado para agregarlo al carrito

**Por código de barras:**
1. Coloque el cursor en el campo de búsqueda
2. Escanee el código de barras con el lector
3. El producto se agregará automáticamente al carrito

### 4.3. Agregar un Cliente

1. En la sección **"Cliente"**, escriba el DNI o nombre del cliente
2. El sistema buscará automáticamente clientes coincidentes
3. Seleccione el cliente de la lista
4. Si el cliente no existe, presione **"Nuevo Cliente"** para registrarlo

### 4.4. Registrar la Venta

1. Verifique que los productos y cantidades sean correctos
2. (Opcional) Agregue cordiales usando el selector de bebidas
3. Seleccione el **método de pago**: Efectivo, Yape o Plin
4. Presione el botón **"Confirmar Venta"**
5. El sistema mostrará:
   - ✅ Número de boleta generado (ej: `B001-000042`)
   - ✅ Premios ganados (si el cliente alcanzó el umbral de fidelización)
   - ✅ Promociones aplicadas (ej: toma gratis por litro puro)

### 4.5. Imprimir o Compartir Boleta

Después de confirmar la venta, puede:
- **Ver boleta:** Presione "Ver Boleta" para ver el detalle completo
- **Descargar PDF:** Presione "Descargar PDF" (formato 80mm para impresora térmica)
- **Imprimir ticket:** Presione "Ticket" para formato de impresora térmica
- **Enviar por WhatsApp:** Presione el ícono de WhatsApp

---

## 5. Módulo Caja

### 5.1. Abrir Caja (Inicio de turno)

1. Acceda al módulo **"Caja"** desde el menú lateral
2. Si no tiene caja abierta, verá el formulario de apertura
3. Ingrese el **monto inicial** en efectivo (conteo de billetes y monedas)
4. Presione **"Abrir Caja"**

> **📌 Solo puede tener UNA caja abierta a la vez.**

### 5.2. Registrar Movimientos

Durante el día puede registrar movimientos manuales:

1. Seleccione el tipo: **Ingreso** o **Egreso**
2. Ingrese el **monto** y una **descripción** (ej: "Compra de bolsas")
3. Seleccione el **método de pago**
4. Presione **"Registrar Movimiento"**

### 5.3. Cerrar Caja (Fin de turno)

1. Al finalizar su turno, vaya al módulo **"Caja"**
2. Cuente el **efectivo real** que tiene en caja
3. Ingrese el monto en el campo **"Conteo Real"**
4. (Opcional) Agregue notas en el campo de observaciones
5. Presione **"Cerrar Caja"**
6. El sistema calculará la **diferencia** entre lo esperado y lo real:
   - 🟢 **Diferencia positiva o cero:** Todo está correcto
   - 🔴 **Diferencia negativa:** Falta dinero en caja

### 5.4. Consultar Sesiones Anteriores

En la parte inferior de la página verá las últimas 10 sesiones de caja cerradas. Haga clic en una para ver el detalle completo con todos los movimientos.

---

## 6. Módulo Clientes

### 6.1. Registrar un Nuevo Cliente

1. Acceda a **"Clientes"** → **"Nuevo Cliente"**
2. Complete los campos:
   - **DNI** (obligatorio, 8 dígitos, debe ser único)
   - **Nombre** (obligatorio)
   - **Apellido** (opcional)
   - **Teléfono** (opcional)
3. Presione **"Guardar"**

### 6.2. Buscar un Cliente

1. En la lista de clientes, use el campo de búsqueda
2. Puede buscar por **DNI**, **nombre** o **apellido**
3. Los resultados se filtran automáticamente

### 6.3. Ver Detalle del Cliente

Haga clic en el nombre de un cliente para ver:
- Datos personales
- **Historial completo de compras** con fechas y totales
- **Acumulado de fidelización** (cuánto ha gastado en productos naturales)
- **Premios pendientes y entregados**

### 6.4. Registrar Padecimientos del Cliente

1. Desde la ficha del cliente, busque la sección **"Padecimientos"**
2. Seleccione las enfermedades/condiciones que el cliente declara
3. Presione **"Guardar"**

> **📌 Esto mejora las recomendaciones de productos que el sistema le mostrará en el POS.**

---

## 7. Módulo Cordiales

### 7.1. Tipos de Cordiales Disponibles

| Tipo | Precio |
|------|--------|
| Consumo en tienda S/3 | S/ 3.00 |
| Consumo en tienda S/5 | S/ 5.00 |
| Para llevar S/3 | S/ 3.00 |
| Para llevar S/5 | S/ 5.00 |
| Litro Especial | S/ 40.00 |
| Medio Litro Especial | S/ 20.00 |
| Litro Puro | S/ 80.00 |
| Medio Litro Puro | S/ 40.00 |
| Invitado (gratis) | S/ 0.00 |

### 7.2. Registrar Venta de Cordial

Los cordiales se pueden vender de dos formas:

**Desde el POS (integrado con la venta):**
1. En la interfaz del POS, use el selector de cordiales
2. Seleccione tipo y cantidad
3. Se sumará al total de la venta

**Desde el módulo Cordiales:**
1. Acceda a **"Cordiales"** → **"Nueva Venta"**
2. Seleccione el tipo, cantidad y cliente
3. Confirme la venta

### 7.3. Promociones Automáticas

- **Litro Puro S/80:** Al comprar un litro puro, el sistema otorga automáticamente **1 toma para llevar S/5 gratis**.

---

## 8. Módulo Fidelización

### 8.1. ¿Cómo funciona el programa?

El cliente acumula el monto de sus compras de productos naturales y cordiales. Cuando alcanza **S/ 500 acumulados**, recibe automáticamente un premio:

> 🎁 **Premio:** 1 Botella de Litro Especial gratis (S/40)

El acumulado es **permanente** (no se reinicia mensualmente, solo anualmente).

### 8.2. Ver Premios Pendientes

1. Acceda al módulo **"Fidelización"**
2. Verá la lista de premios **pendientes de entrega**
3. Cada premio muestra: nombre del cliente, fecha de generación, descripción

### 8.3. Marcar Premio como Entregado

1. Ubique el premio en la lista
2. Comunique al cliente que tiene el premio disponible
3. Entregue el premio físicamente
4. Presione **"Entregar"**
5. El sistema registra la fecha y hora de entrega

---

## 9. Módulo Inventario

### 9.1. Gestionar Productos

**Crear un producto:**
1. Acceda a **"Productos"** → **"Nuevo Producto"**
2. Complete: nombre, descripción, precio (con IGV), stock, stock mínimo
3. (Opcional) Agregue imagen y código de barras
4. Presione **"Guardar"**

**Editar un producto:**
1. Haga clic en el botón de edición del producto
2. Modifique los campos necesarios
3. Presione **"Actualizar"**

### 9.2. Alertas de Stock Bajo

Los productos con **stock actual ≤ stock mínimo** se muestran con una alerta visual (badge rojo). Esto le indica que debe reabastecer.

### 9.3. Importar/Exportar

- **Exportar a Excel:** Presione "Exportar" para descargar el catálogo completo
- **Descargar plantilla:** Presione "Plantilla" para obtener un formato Excel vacío
- **Importar desde Excel:** Presione "Importar" y seleccione su archivo completado

---

## 10. Módulo Recetario

### 10.1. Consultar Recetario

1. Acceda a **"Recetario"** en el menú lateral
2. Busque la enfermedad o condición por nombre
3. Haga clic en la enfermedad para ver:
   - Descripción de la condición
   - **Productos naturales recomendados**
   - **Instrucciones de uso** para cada producto

### 10.2. Administrar Recetario (Admin)

1. **Crear enfermedad:** Complete nombre, descripción y categoría
2. **Vincular productos:** Seleccione los productos naturales que ayudan con esa condición
3. **Agregar instrucciones:** Para cada producto vinculado, escriba las instrucciones de preparación/uso

---

## 11. Módulo Reclamos

### 11.1. Registrar un Reclamo

1. Acceda a **"Reclamos"** → **"Nuevo Reclamo"**
2. Complete:
   - Cliente (si está registrado)
   - Tipo de reclamo
   - Descripción detallada del problema
3. Presione **"Registrar"**
4. El reclamo se crea con estado **"Pendiente"**

### 11.2. Flujo de Estados

```
Pendiente → En Proceso (escalar) → Resuelto
```

- **Escalar:** Cambia el reclamo a "En Proceso" para atención del administrador
- **Resolver:** El administrador registra la resolución y cierra el reclamo

---

## 12. Módulo Reportes y Boletas

### 12.1. Generar un Reporte de Ventas

1. Acceda a **"Reportes"**
2. Seleccione los filtros:
   - 📅 Fecha desde y hasta
   - 🏢 Sucursal
   - 👤 Empleado
   - 💳 Método de pago
3. Presione **"Generar Reporte"**
4. El sistema mostrará la tabla de ventas con totales

### 12.2. Boletas

Desde cualquier venta puede:
- **Ver boleta completa** con desglose de IGV
- **Descargar en PDF** (formato 80mm)
- **Imprimir ticket** para impresora térmica

---

## 13. Módulo IA (Asistente)

### 13.1. Hacer una Consulta

1. Acceda a **"Asistente IA"**
2. Escriba su pregunta sobre el negocio, por ejemplo:
   - "¿Cuáles son los productos más vendidos este mes?"
   - "¿Qué clientes han disminuido sus compras?"
   - "Analiza las tendencias de ventas de la última semana"
3. Presione **"Analizar"**
4. El sistema procesará su consulta y mostrará la respuesta

> **📌 El sistema intentará usar IA avanzada (Groq/Gemini). Si no hay conexión, funcionará en modo offline con análisis local.**

---

## 14. Panel de Administración

> **⚠️ Solo accesible para usuarios con rol "Administrador".**

### 14.1. Dashboard

Muestra los **KPIs del día, semana y mes**:
- Total de ventas
- Número de transacciones
- Ticket promedio
- Productos con stock bajo
- Productos en riesgo de desabasto (predicción de demanda)

### 14.2. Gestión de Sucursales

1. Acceda a **"Sucursales"**
2. Puede crear, editar o desactivar sucursales
3. Cada sucursal tiene: nombre, dirección, teléfono, RUC

### 14.3. Gestión de Usuarios

1. Acceda a **"Usuarios"**
2. Puede crear usuarios con:
   - Nombre, email, contraseña
   - **Rol:** Administrador o Empleado
   - **Sucursal asignada**
3. Puede desactivar usuarios sin eliminarlos

---

## 15. Preguntas Frecuentes (FAQ)

**P: ¿Puedo vender sin abrir caja?**
R: Sí, puede registrar ventas, pero no se vincularán a una sesión de caja. Se recomienda siempre abrir caja al inicio del turno.

**P: ¿Qué pasa si el stock llega a 0?**
R: El sistema mostrará "Stock insuficiente" y no permitirá vender ese producto.

**P: ¿El cliente pierde su acumulado al final del año?**
R: El acumulado es permanente durante el año fiscal configurado. El administrador puede reiniciar los acumulados al inicio de cada año.

**P: ¿Las recomendaciones son diagnósticos médicos?**
R: No. Las recomendaciones se basan en el historial de compras y el recetario, no constituyen un diagnóstico médico.

**P: ¿Puedo usar el sistema desde mi celular?**
R: El sistema es responsivo y funciona en tablets. Sin embargo, para la operación óptima del POS se recomienda una pantalla de escritorio.

**P: ¿Qué hago si olvidé mi contraseña?**
R: Solicite al administrador que restablezca su contraseña.

---

## 16. Soporte Técnico

Para soporte técnico, contacte al equipo de desarrollo:

| Contacto | Responsabilidad |
|----------|-----------------|
| **Bendezu Lagos Jack Joshua** | Líder de Proyecto |
| **Julca Laureano Dickmar Wilber** | QA y Pruebas |
| **Reyes Cordero Italo Eduardo** | Desarrollo y Análisis |

**Repositorio:** `github.com/75220834-cloud/PROYECTO-NATURACOR`
