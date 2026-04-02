#!/usr/bin/env bash
# ============================================================
#  NATURACOR — Generador de Reporte de Cobertura de Tests
# ============================================================
# Uso: bash generar_cobertura.sh
# Resultado: abre storage/app/coverage/index.html en el navegador
# ============================================================

echo ""
echo "🌿 NATURACOR — Reporte de Cobertura de Código"
echo "================================================"
echo ""

# Verificar que Xdebug esté instalado
php -r "if(!extension_loaded('xdebug')){echo 'ERROR: Xdebug no instalado.'.PHP_EOL;exit(1);}"

if [ $? -ne 0 ]; then
    echo "Instala Xdebug: https://xdebug.org/wizard"
    echo "O en XAMPP: activa xdebug en php.ini"
    exit 1
fi

echo "✅ Xdebug detectado"
echo "🧪 Corriendo tests con cobertura..."
echo ""

# Crear directorio de salida
mkdir -p storage/app/coverage

# Generar reporte HTML
XDEBUG_MODE=coverage php artisan test \
    --coverage-html=storage/app/coverage \
    --env=testing

echo ""
echo "================================================"
echo "✅ Reporte generado en: storage/app/coverage/"
echo "📂 Abre: storage/app/coverage/index.html"
echo "================================================"
echo ""

# Abrir automáticamente en Windows (si disponible)
if command -v start &> /dev/null; then
    start storage/app/coverage/index.html
fi
