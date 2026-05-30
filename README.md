# EasyGES - ERP para Tienda de Motos

Sistema de gestión empresarial diseñado para tiendas de motos y equipamiento motero.

## Módulos

- **Dashboard** - Estadísticas generales del negocio
- **Facturación** - POS (Punto de Venta) y facturación tradicional
- **Inventario** - Productos, tallas, movimientos de stock
- **Compras** - Órdenes de compra y gestión de proveedores
- **Clientes** - Registro y gestión de clientes
- **Contabilidad** - Ingresos, gastos y utilidades
- **Reportes** - Informes de ventas, productos, clientes y compras
- **Administración** - Configuración de la empresa

## Requisitos

- PHP 8.0+
- MySQL 8.0+
- Apache con mod_rewrite

## Instalación Rápida (Docker)

```bash
docker compose up -d
```

| Servicio | URL |
|---|---|
| App | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |

## Usuarios por Defecto

| Usuario | Contraseña | Rol |
|---|---|---|
| admin | admin123 | Administrador |
| facturacion | fact123 | Facturación |
| inventario | inv123 | Inventario |
| compras | comp123 | Compras |
| vendedor | vend123 | Vendedor |

## Tecnologías

- PHP 8.2
- MySQL 8.0
- HTML/CSS/JavaScript vanilla
- Docker
