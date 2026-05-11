# Sustentación de Artículos Científicos — NATURACOR

## Marco Bibliográfico y Artículos de Soporte
**Fecha:** 09/05/2026  
**Versión:** 1.0  
**Propósito:** Documentar los artículos científicos que sustentan las decisiones técnicas y metodológicas del proyecto

---

## 1. Introducción

Este documento presenta los artículos científicos, libros y estándares internacionales que sustentan las decisiones de diseño e implementación del proyecto NATURACOR. Cada entrada incluye su relación directa con el sistema y cómo se aplica en la implementación.

---

## 2. Artículos de Sistemas de Recomendación

### ART-001: Recommender Systems Handbook

| Campo | Detalle |
|-------|---------|
| **Autores** | Ricci, F., Rokach, L., & Shapira, B. |
| **Año** | 2015 |
| **Publicación** | Springer (2nd ed.) |
| **DOI** | 10.1007/978-1-4899-7637-6 |
| **Tipo** | Libro de referencia |
| **Resumen** | Manual integral de sistemas de recomendación que cubre CF, CB, híbridos y evaluación |
| **Aplicación en NATURACOR** | Marco teórico general del motor de recomendación. Fundamenta la taxonomía CB + CF + Híbrido y las métricas Precision@K, Hit Rate |
| **Archivo relacionado** | `RecomendacionEngine.php`, `MetricsService.php` |

---

### ART-002: Amazon.com Recommendations — Item-to-Item CF

| Campo | Detalle |
|-------|---------|
| **Autores** | Linden, G., Smith, B., & York, J. |
| **Año** | 2003 |
| **Publicación** | IEEE Internet Computing, 7(1), 76-80 |
| **DOI** | 10.1109/MIC.2003.1167344 |
| **Resumen** | Describe el algoritmo de filtrado colaborativo item-to-item de Amazon |
| **Aplicación en NATURACOR** | Inspira el componente de co-ocurrencia item-item. NATURACOR usa Jaccard en lugar de coseno por simplicidad y transparencia |
| **Archivo relacionado** | `CoocurrenciaService.php` |

---

### ART-003: Recommender Systems: The Textbook

| Campo | Detalle |
|-------|---------|
| **Autores** | Aggarwal, C. C. |
| **Año** | 2016 |
| **Publicación** | Springer |
| **DOI** | 10.1007/978-3-319-29659-3 |
| **Resumen** | Textbook que cubre fundamentos matemáticos de SR, incluyendo decaimiento temporal y fusión híbrida |
| **Aplicación en NATURACOR** | Fundamenta el decaimiento exponencial `e^(-λ·t)`, la compensación por grado (análogo a IDF), y la fusión lineal ponderada |
| **Archivo relacionado** | `PerfilSaludService.php`, `RecomendacionEngine.php` |

---

### ART-004: Evaluating Recommendation Systems

| Campo | Detalle |
|-------|---------|
| **Autores** | Shani, G., & Gunawardana, A. |
| **Año** | 2011 |
| **Publicación** | En Ricci et al. (Eds.), Recommender Systems Handbook. Springer |
| **Resumen** | Protocolo de evaluación de sistemas de recomendación: métricas offline, online y estudios controlados |
| **Aplicación en NATURACOR** | Fundamenta la estrategia de evaluación: Precision@K, Hit Rate, A/B testing, y el embudo de conversión (mostrada → clic → agregada → comprada) |
| **Archivo relacionado** | `MetricsService.php`, `AbTestingService.php` |

---

### ART-005: Hybrid Recommender Systems

| Campo | Detalle |
|-------|---------|
| **Autores** | Burke, R. |
| **Año** | 2002 |
| **Publicación** | User Modeling and User-Adapted Interaction, 12(4), 331-370 |
| **Resumen** | Taxonomía de 7 estrategias de hibridización en SR |
| **Aplicación en NATURACOR** | Justifica la estrategia de fusión ponderada (weighted hybrid) usada en el motor |

---

## 3. Artículos de Estadística y Experimentación

### ART-006: Statistical Power Analysis

| Campo | Detalle |
|-------|---------|
| **Autores** | Cohen, J. |
| **Año** | 1988 |
| **Publicación** | Lawrence Erlbaum Associates (2nd ed.) |
| **Resumen** | Define tamaños de efecto (d pequeño=0.2, mediano=0.5, grande=0.8) y análisis de potencia estadística |
| **Aplicación en NATURACOR** | Cohen's d para medir el impacto real del recomendador. Cálculo de tamaño de muestra mínimo (64 por grupo) |
| **Archivo relacionado** | `AbTestingService.php::cohensD()` |

---

### ART-007: Welch's t-test

| Campo | Detalle |
|-------|---------|
| **Autores** | Welch, B. L. |
| **Año** | 1947 |
| **Publicación** | Biometrika, 34(1-2), 28-35 |
| **Resumen** | Generalización del test t de Student para varianzas desiguales |
| **Aplicación en NATURACOR** | Test principal para comparar tickets promedio entre grupo control y tratamiento. Implementado en PHP puro con aproximación de Lanczos |
| **Archivo relacionado** | `AbTestingService.php::welchTTest()` |

---

## 4. Artículos de Pronóstico y Series Temporales

### ART-008: Exponential Smoothing — The State of the Art

| Campo | Detalle |
|-------|---------|
| **Autores** | Gardner, E. S. |
| **Año** | 1985 |
| **Publicación** | Journal of Forecasting, 4(1), 1-28 |
| **Resumen** | Revisión exhaustiva de métodos de suavizado exponencial para pronóstico de series temporales |
| **Aplicación en NATURACOR** | Fundamenta el modelo SES para predicción de demanda semanal. `Ŝ_t = α·Y_t + (1-α)·Ŝ_{t-1}` |
| **Archivo relacionado** | `DemandaForecastService.php` |

---

## 5. Artículos de Minería de Datos y NLP

### ART-009: Normalized PMI in Collocation Extraction

| Campo | Detalle |
|-------|---------|
| **Autores** | Bouma, G. |
| **Año** | 2009 |
| **Publicación** | Proceedings of GSCL |
| **Resumen** | Propone NPMI como mejora de PMI para extracción de colocaciones, con rango normalizado [-1, 1] |
| **Aplicación en NATURACOR** | Complementa Jaccard en la co-ocurrencia producto-producto, corrigiendo sesgo de frecuencia |
| **Archivo relacionado** | `CoocurrenciaService.php` |

---

### ART-010: Matrix Factorization for Recommender Systems

| Campo | Detalle |
|-------|---------|
| **Autores** | Koren, Y., Bell, R., & Volinsky, C. |
| **Año** | 2009 |
| **Publicación** | Computer, 42(8), 30-37 (IEEE) |
| **Resumen** | Técnicas de factorización matricial (SVD) para filtrado colaborativo |
| **Aplicación en NATURACOR** | Referenciado como **trabajo futuro**: evolución del motor hacia factorización de matrices para mayor precisión |

---

## 6. Normas ISO Aplicadas

### NORM-001: ISO/IEC 25010:2023

| Campo | Detalle |
|-------|---------|
| **Título** | Systems and software engineering — Product quality model |
| **Aplicación** | Evaluación de las 8 características de calidad del producto software |
| **Documento** | `metricas_calidad.md`, `aplicacion_iso_25000.md` |

### NORM-002: ISO/IEC/IEEE 29119:2022

| Campo | Detalle |
|-------|---------|
| **Título** | Software and systems engineering — Software testing |
| **Aplicación** | Proceso de pruebas, documentación, técnicas y cobertura |
| **Documento** | `Plan_de_Pruebas_NATURACOR.md`, `aplicacion_iso_29119.md` |

### NORM-003: ISO/IEC 27001:2022

| Campo | Detalle |
|-------|---------|
| **Título** | Information security management systems — Requirements |
| **Aplicación** | Controles de seguridad: RBAC, CSRF, Bcrypt, auditoría |
| **Documento** | `seguridad.md`, `aplicacion_iso_27000.md` |

### NORM-004: ISO 9001:2015

| Campo | Detalle |
|-------|---------|
| **Título** | Quality management systems — Requirements |
| **Aplicación** | Trazabilidad de requerimientos, gestión de calidad |
| **Documento** | `matriz_trazabilidad.md` |

---

## 7. Espacio para Artículos Adicionales

> **📌 Nota:** Esta sección está reservada para artículos que se incorporen durante la fase de revisión de la tesis. Agregar siguiendo el formato de las secciones anteriores.

### ART-011: (Por agregar)

| Campo | Detalle |
|-------|---------|
| **Autores** | — |
| **Año** | — |
| **Publicación** | — |
| **Resumen** | — |
| **Aplicación en NATURACOR** | — |

### ART-012: (Por agregar)

| Campo | Detalle |
|-------|---------|
| **Autores** | — |
| **Año** | — |
| **Publicación** | — |
| **Resumen** | — |
| **Aplicación en NATURACOR** | — |

---

## 8. Resumen de Cobertura Bibliográfica

| Área | Artículos | Cobertura |
|------|:---------:|-----------|
| Sistemas de recomendación | 5 | CB, CF, Híbrido, Evaluación, Amazon |
| Estadística experimental | 2 | t-test Welch, Cohen's d |
| Pronóstico | 1 | SES (Gardner) |
| Minería de datos | 2 | NPMI, Factorización matricial |
| Normas ISO | 4 | 25010, 29119, 27001, 9001 |
| **Total** | **14** | — |

---

**Fin del documento.**
