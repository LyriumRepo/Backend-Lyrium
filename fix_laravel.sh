#!/bin/bash
# Script para arreglar estructura de archivos Laravel

cd /home/u701492565/domains/lyriumbiomarketplace.com/public_html/laravel

echo "=== Arreglando estructura de archivos ==="

# Función para arreglar archivos con backslash
fix_backslash_files() {
    local dir="$1"
    if [ -d "$dir" ]; then
        find "$dir" -maxdepth 1 -name "*\\*" -type f 2>/dev/null | while read file; do
            # Obtener nuevo nombre sin backslashes
            newname=$(echo "$file" | sed 's/\\/\//g')
            # Crear directorio destino
            target_dir=$(dirname "$newname")
            mkdir -p "$target_dir"
            # Mover archivo
            mv "$file" "$target_dir/"
            echo "Fixed: $file -> $target_dir/"
        done
    fi
}

# Arreglar cada directorio
fix_backslash_files "."
fix_backslash_files "app"
fix_backslash_files "app/Http"
fix_backslash_files "bootstrap"
fix_backslash_files "config"
fix_backslash_files "database"
fix_backslash_files "public"
fix_backslash_files "resources"
fix_backslash_files "routes"
fix_backslash_files "storage"

# Eliminar carpetas vacías con backslash
find . -maxdepth 3 -type d -name "*\\*" 2>/dev/null | while read dir; do
    rmdir "$dir" 2>/dev/null && echo "Removed empty dir: $dir"
done

echo ""
echo "=== Verificando estructura ==="
echo "app/Http/Controllers/Api/:"
ls -la app/Http/Controllers/Api/ 2>/dev/null || echo "No existe"

echo ""
echo "bootstrap/:"
ls -la bootstrap/ 2>/dev/null || echo "No existe"

echo ""
echo "config/:"
ls config/*.php 2>/dev/null | head -5 || echo "No hay archivos PHP"

echo ""
echo "artisan:"
cat artisan 2>/dev/null | head -5 || echo "No existe artisan"

echo ""
echo "=== Probando artisan ==="
php artisan --version
