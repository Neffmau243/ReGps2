# 🚗 ReGPS - Sistema de Rastreo GPS en Tiempo Real

[![Laravel](https://img.shields.io/badge/Laravel-12-FF6B35.svg)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D.svg)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6.svg)](https://typescriptlang.org)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-En%20Desarrollo-orange.svg)]()

Sistema profesional de rastreo GPS en tiempo real con geofencing inteligente, alertas automáticas y gestión completa de dispositivos y empleados. **Interfaz moderna con tema negro/naranja y diseño 100% responsive.**

---

## 🎨 Características del Diseño

### 🌓 Tema Visual
- **Colores Principales**: Negro (#0A0A0A) + Naranja (#FF6B35)
- **Diseño Moderno**: Gradientes, glassmorphism y sombras suaves
- **Iconografía**: Bootstrap Icons integrados
- **Animaciones**: Transiciones fluidas y feedback visual

### 📱 Diseño Responsive
- **Mobile First**: Optimizado desde 320px
- **Breakpoints**:
  - Mobile: < 640px
  - Tablet: 641px - 1024px
  - Desktop: > 1024px
- **Menú Hamburguesa**: Navegación móvil con overlay
- **Componentes Adaptables**: Todos los elementos se ajustan al viewport

---

## 🚀 Inicio Rápido

### Requisitos Previos
- PHP 8.4 o superior
- Composer
- Node.js 18+ y npm
- SQLite (o MySQL/PostgreSQL)

### Instalación Completa
```bash
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/regps.git
cd ReGps/ReGps

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias Node.js
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar base de datos en .env
# DB_CONNECTION=sqlite
# DB_DATABASE=/ruta/absoluta/database/database.sqlite

# 6. Crear y migrar base de datos
touch database/database.sqlite
php artisan migrate --seed

# 7. Iniciar servidores
# Terminal 1 - Laravel
php artisan serve

# Terminal 2 - Vite (desarrollo frontend)
npm run dev
```

### Acceso a la Aplicación
- **URL**: http://localhost:8000
- **Admin**: test@regps.com / 123456
- **Empleado**: empleado@regps.com / 123456

---

## 📦 Stack Tecnológico

### Backend
- **Framework**: Laravel 12.x
- **Lenguaje**: PHP 8.4+
- **Base de Datos**: SQLite / MySQL / PostgreSQL
- **Autenticación**: Laravel Sanctum (API Tokens)
- **Validación**: Request Validation con reglas personalizadas

### Frontend
- **Framework**: Vue 3 (Composition API)
- **Lenguaje**: TypeScript 5
- **Build Tool**: Vite
- **CSS**: CSS Variables + Responsive Design
- **Iconos**: Bootstrap Icons
- **Router**: Vue Router 4
- **State Management**: Pinia

---

## 🎯 Características Principales

### Backend ✅
- ✅ **Autenticación completa** con Laravel Sanctum
- ✅ **Sistema de roles** (Administrador/Empleado)
- ✅ **CRUD completo** de Usuarios, Empleados, Dispositivos
- ✅ **Sistema GPS** con validación y optimización
- ✅ **Geofencing avanzado** (Círculos y Polígonos)
- ✅ **Alertas automáticas** inteligentes
- ✅ **Historial de zonas** con timestamps
- ✅ **API RESTful** con 39 endpoints

### Frontend 🚧
- ✅ **Sistema de autenticación** completo
- ✅ **Navegación responsive** con menú hamburguesa
- ✅ **Componentes UI** profesionales (Modal, Card, Button, Loading)
- ✅ **Footer** con enlaces y contacto
- ✅ **Tema negro/naranja** consistente
- ✅ **CSS Variables** para personalización
- ⏳ **Vistas de gestión** (Usuarios, Dispositivos, Zonas)
- ⏳ **Mapa interactivo** con Leaflet
- ⏳ **Rastreo GPS** en tiempo real

---

## 🧩 Componentes UI Disponibles

### Componentes Base

#### `<Navbar />`
Barra de navegación responsive con:
- Logo animado
- Links con iconos
- Menú hamburguesa móvil
- Información de usuario
- Botón logout

#### `<Footer />`
Footer profesional con:
- Logo y descripción
- Enlaces rápidos
- Recursos y ayuda
- Información de contacto
- Redes sociales
- Copyright y legales

#### `<Modal />`
Modal versátil:
```vue
<Modal 
  title="Crear Usuario" 
  icon="bi-person-plus" 
  size="lg"
  @close="closeModal"
>
  <template #default>
    <!-- Contenido -->
  </template>
  <template #footer>
    <Button variant="secondary" @click="closeModal">Cancelar</Button>
    <Button variant="primary" @click="save">Guardar</Button>
  </template>
</Modal>
```

#### `<Button />`
Botón personalizable:
```vue
<Button 
  variant="primary|secondary|danger|success|warning"
  size="sm|md|lg"
  icon="bi-save"
  :loading="saving"
  :fullWidth="true"
  @click="handleClick"
>
  Guardar
</Button>
```

#### `<Card />`
Tarjeta con header y footer:
```vue
<Card 
  title="Estadísticas" 
  icon="bi-graph-up"
  :hover="true"
>
  <template #default>
    <!-- Contenido -->
  </template>
  <template #footer>
    <!-- Acciones -->
  </template>
</Card>
```

#### `<Loading />`
Indicador de carga:
```vue
<Loading message="Cargando..." :fullscreen="true" />
```

---

## 🎨 Sistema de Colores

### Variables CSS Disponibles
```css
/* Colores principales */
--color-primary: #FF6B35         /* Naranja principal */
--color-primary-dark: #E55A2B    /* Naranja oscuro */
--color-primary-light: #FF8C5E   /* Naranja claro */

/* Tonos de negro */
--color-dark: #0A0A0A            /* Negro principal */
--color-dark-100: #1A1A1A        /* Fondo cards */
--color-dark-200: #2A2A2A        /* Fondo inputs */
--color-dark-300: #3A3A3A        /* Bordes */

/* Colores de estado */
--color-success: #10B981         /* Verde éxito */
--color-warning: #F59E0B         /* Amarillo advertencia */
--color-danger: #EF4444          /* Rojo peligro */
--color-info: #3B82F6            /* Azul info */
```

### Clases Utilitarias
```css
/* Textos */
.text-primary, .text-success, .text-warning, .text-danger

/* Fondos */
.bg-dark, .bg-dark-100, .bg-dark-200

/* Botones */
.btn, .btn-primary, .btn-secondary, .btn-danger, .btn-success

/* Cards */
.card, .card-header, .card-body, .card-footer

/* Badges */
.badge, .badge-primary, .badge-success, .badge-warning, .badge-danger

/* Alerts */
.alert, .alert-success, .alert-warning, .alert-danger, .alert-info
```

---

## 📱 Diseño Responsive

### Breakpoints
```css
/* Mobile */
@media (max-width: 640px) {
  /* Menú hamburguesa activo */
  /* Botones full-width */
  /* Cards compactos */
}

/* Tablet */
@media (min-width: 641px) and (max-width: 1024px) {
  /* Layout adaptado */
  /* Grid 2 columnas */
}

/* Desktop */
@media (min-width: 1025px) {
  /* Layout completo */
  /* Efectos hover */
}
```

### Características Móviles
- ✅ Menú hamburguesa con overlay
- ✅ Navegación lateral deslizable
- ✅ Touch-friendly (mínimo 44px de área táctil)
- ✅ Formularios full-width
- ✅ Cards adaptables
- ✅ Tablas con scroll horizontal
- ✅ Footer colapsable

---

## 📡 API Endpoints

### Autenticación
```http
POST /api/auth/login              # Login
POST /api/auth/logout             # Logout
GET  /api/auth/me                 # Usuario actual
```

### Ubicaciones GPS ⭐
```http
POST /api/ubicaciones             # Enviar ubicación
GET  /api/ubicaciones             # Listar (Admin)
```

**Datos esperados:**
```json
{
  "DispositivoID": 1,
  "Latitud": -12.0464,
  "Longitud": -77.0428,
  "Velocidad": 45.5,
  "Direccion": "Lima, Perú",
  "FechaHora": "2025-11-17 15:30:00"
}
```

### Zonas (Geofencing) ⭐
```http
GET  /api/zonas                   # Listar zonas
POST /api/zonas                   # Crear zona (Admin)
POST /api/zonas/verificar-ubicacion  # Verificar si está en zona
```

### Alertas
```http
GET  /api/alertas                 # Listar alertas
GET  /api/alertas/{id}            # Ver alerta
```

**Total: 37 endpoints**

Ver documentación completa en [`FINAL.md`](FINAL.md)

---

## 🏗️ Arquitectura

```
Controllers → Services → Models → Database
```

### Services (Lógica de Negocio)
- **MovementDetectionService** - Estados del dispositivo
- **RouteService** - Gestión de rutas y estadísticas
- **GpsOptimizationService** - Validación y optimización

### Modelos
- Usuario, Empleado, Dispositivo
- Ubicacion, Zona, HistorialZona
- Alerta, Permiso, RolPermiso

---

## 🔐 Seguridad

- ✅ Laravel Sanctum (tokens API)
- ✅ Roles: Administrador / Empleado
- ✅ 24 permisos granulares
- ✅ Rate limiting (60 req/min)
- ✅ Contraseñas hasheadas
- ✅ Validaciones estrictas

---

## 🧮 Algoritmos

- **Haversine** - Distancia entre coordenadas GPS
- **Ray Casting** - Punto dentro de polígono
- **Douglas-Peucker** - Simplificación de rutas
- **Promedio Móvil** - Suavizado de datos

---

## 🚨 Alertas Automáticas

El sistema genera alertas automáticamente cuando:
- ⚡ Velocidad > 80 km/h
- 🚫 Entrada a zona restringida
- ⚠️ Salida de zona permitida
- 📡 Dispositivo inactivo > 15 min
- 🔴 Sin conexión > 30 min

---

## 📚 Documentación

| Archivo | Descripción |
|---------|-------------|
| [`FINAL.md`](FINAL.md) | **Documentación completa del backend** |
| [`API_ENDPOINTS.md`](API_ENDPOINTS.md) | Todos los endpoints con ejemplos |
| [`AUTENTICACION.md`](AUTENTICACION.md) | Guía de autenticación |
| [`SERVICES_IMPLEMENTADOS.md`](SERVICES_IMPLEMENTADOS.md) | Lógica de negocio |
| [`TABLA_PORCENTAJES.md`](TABLA_PORCENTAJES.md) | Métricas del proyecto |
| [`ANALISIS_FUNCIONALIDADES.md`](ANALISIS_FUNCIONALIDADES.md) | Análisis detallado |

---

## 🧪 Usuario de Prueba

```
Email: test@regps.com
Contraseña: 123456
Rol: Administrador
```

---

## 🛠️ Comandos Útiles

```bash
# Limpiar ubicaciones antiguas
php artisan ubicaciones:limpiar --dias=90

# Eliminar ubicaciones archivadas
php artisan ubicaciones:eliminar-archivadas

# Ver rutas API
php artisan route:list --path=api

# Ejecutar pruebas
php test-completo.php
php test-services.php
```

---

## 📊 Estadísticas

- **Completitud**: 75.2%
- **Endpoints**: 37
- **Tablas BD**: 11
- **Services**: 3
- **Permisos**: 24
- **Líneas de código**: ~3000+

---

## 🎯 Casos de Uso

- 🚚 Empresas de transporte y logística
- 📦 Servicios de delivery
- 👮 Seguridad y vigilancia
- 👷 Gestión de personal en campo
- 🚗 Flotas de vehículos

---

## 🚀 Próximas Mejoras

- [ ] WebSockets para tiempo real
- [ ] Dashboard con Vue.js
- [ ] Exportación PDF/Excel
- [ ] App móvil
- [ ] Machine Learning

---

## 📞 Soporte

Para documentación completa, ver [`FINAL.md`](FINAL.md)

---

## 📄 Licencia

MIT License

---

**Desarrollado con ❤️ usando Laravel 11**

**Estado**: ✅ Production Ready (75.2%)
