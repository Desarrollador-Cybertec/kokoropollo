<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\{Auditoria, Inventario, InventarioMovimiento};

final class InventarioController
{
    public function index(Request $request): void
    {
        AuthMiddleware::handle();

        $model    = new Inventario();
        $busqueda = trim($request->get('q', ''));
        $editarId = (int) $request->get('editar', 0);

        $items      = $busqueda !== '' ? $model->search($busqueda) : $model->all();
        $editarItem = $editarId > 0 ? $model->find($editarId) : null;

        $rol          = Rol::tryFrom(Session::get('rol') ?? '');
        $dashboardUrl = $rol?->dashboard() ?? '/dashboard';
        $soloLectura  = ($rol === Rol::Empleado);

        View::render('inventario/index', compact('items', 'editarItem', 'busqueda', 'dashboardUrl', 'soloLectura'));
    }

    public function store(Request $request): void
    {
        AuthMiddleware::handle();

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        if ($rol === Rol::Empleado) {
            Session::flash('error', 'No tiene permiso para modificar el inventario.');
            Response::redirect('/inventario');
        }

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/inventario');
        }

        $articulo  = trim($request->post('articulo', ''));
        $categoria = $request->post('categoria', 'Otros');
        $cantidad  = max(0, (int) $request->post('cantidad', 0));
        $valor     = max(0.0, (float) $request->post('valor', 0));

        if ($categoria === 'Pollo Crudo') {
            // Internamente inventario usa cuartos para permitir ventas por 1/4.
            $cantidad = $cantidad * 4;
            $valor = round($valor / 4, 2);
        }

        if ($articulo === '' || !in_array($categoria, Inventario::categorias(), strict: true)) {
            Response::redirect('/inventario');
        }

        try {
            (new Inventario())->create($articulo, $categoria, $cantidad, $valor);
            (new Auditoria())->registrar(
                Session::get('usuario', ''), 'inventario', 'crear',
                "Artículo creado: {$articulo} ({$categoria})"
            );
            Session::flash('exito', "Artículo \"{$articulo}\" agregado correctamente.");
        } catch (\Throwable $e) {
            \App\Core\Logger::getInstance()->error('Error al crear artículo de inventario', ['error' => $e->getMessage()]);
            Session::flash('error', 'Error al guardar el artículo. Intente de nuevo.');
        }
        Response::redirect('/inventario');
    }

    public function update(Request $request): void
    {
        AuthMiddleware::handle();

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        if ($rol === Rol::Empleado) {
            Session::flash('error', 'No tiene permiso para modificar el inventario.');
            Response::redirect('/inventario');
        }

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/inventario');
        }

        $id        = (int) $request->post('id', 0);
        $articulo  = trim($request->post('articulo', ''));
        $categoria = $request->post('categoria', 'Otros');
        $cantidad  = max(0, (int) $request->post('cantidad', 0));
        $valor     = max(0.0, (float) $request->post('valor', 0));

        if ($categoria === 'Pollo Crudo') {
            // Internamente inventario usa cuartos para permitir ventas por 1/4.
            $cantidad = $cantidad * 4;
            $valor = round($valor / 4, 2);
        }

        if ($id > 0 && $articulo !== '' && in_array($categoria, Inventario::categorias(), strict: true)) {
            try {
                (new Inventario())->update($id, $articulo, $categoria, $cantidad, $valor);
                (new Auditoria())->registrar(
                    Session::get('usuario', ''), 'inventario', 'editar',
                    "Artículo editado: {$articulo} (id={$id})"
                );
                Session::flash('exito', "Artículo \"{$articulo}\" actualizado correctamente.");
            } catch (\Throwable $e) {
                \App\Core\Logger::getInstance()->error('Error al actualizar artículo de inventario', ['error' => $e->getMessage(), 'id' => $id]);
                Session::flash('error', 'Error al actualizar el artículo. Intente de nuevo.');
            }
        } else {
            Session::flash('error', 'Datos inválidos. Verifique el formulario.');
        }

        Response::redirect('/inventario');
    }

    public function movimiento(Request $request): void
    {
        AuthMiddleware::handle();

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        if ($rol === Rol::Empleado) {
            Session::flash('error', 'No tiene permiso para modificar el inventario.');
            Response::redirect('/inventario');
        }

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/inventario');
        }

        $id       = (int) $request->post('id', 0);
        $tipo     = $request->post('tipo', '');
        $cantidad = max(1, (int) $request->post('cantidad', 1));

        if ($id <= 0 || !in_array($tipo, ['entrada', 'salida'], strict: true)) {
            Session::flash('error', 'Datos inválidos.');
            Response::redirect('/inventario');
        }

        $item = (new Inventario())->find($id);
        if (!$item) {
            Session::flash('error', 'Artículo no encontrado.');
            Response::redirect('/inventario');
        }

        // Pollo Crudo se almacena en cuartos; el usuario ingresa pollos enteros
        $cantidadInterna = ($item['categoria'] === 'Pollo Crudo') ? $cantidad * 4 : $cantidad;
        $delta           = $tipo === 'entrada' ? $cantidadInterna : -$cantidadInterna;
        $usuario         = Session::get('usuario', '');

        try {
            (new Inventario())->ajustar($id, $delta);
            // Se guarda en unidades visibles (pollos para Pollo Crudo, uds para el resto)
            (new InventarioMovimiento())->registrar($id, $tipo, $cantidad, $usuario);
            (new Auditoria())->registrar(
                $usuario, 'inventario', $tipo,
                ucfirst($tipo) . " de {$cantidad} en: {$item['articulo']}"
            );
            $label = $tipo === 'entrada' ? 'Entrada' : 'Salida';
            Session::flash('exito', "{$label} de {$cantidad} registrada en \"{$item['articulo']}\".");
        } catch (\Throwable $e) {
            \App\Core\Logger::getInstance()->error('Error al registrar movimiento', ['error' => $e->getMessage()]);
            Session::flash('error', 'Error al registrar el movimiento. Intente de nuevo.');
        }

        Response::redirect('/inventario');
    }

    public function historial(Request $request): void
    {
        AuthMiddleware::handle();

        $inventarioId = (int) $request->get('articulo_id', 0) ?: null;
        $categoria    = $request->get('categoria', '');
        $desde        = $request->get('desde', '');
        $hasta        = $request->get('hasta', '');
        $pagina       = max(1, (int) $request->get('pagina', 1));

        if ($categoria !== '' && !in_array($categoria, \App\Models\Inventario::categorias(), strict: true)) {
            $categoria = '';
        }
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : '';
        $hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : '';

        $result = (new InventarioMovimiento())->filtrar(
            $inventarioId,
            $categoria !== '' ? $categoria : null,
            $desde      !== '' ? $desde      : null,
            $hasta      !== '' ? $hasta      : null,
            $pagina
        );

        Response::json($result);
    }

    public function destroy(Request $request): void
    {
        AuthMiddleware::handle();

        $rol = Rol::tryFrom(Session::get('rol') ?? '');
        if ($rol === Rol::Empleado) {
            Session::flash('error', 'No tiene permiso para modificar el inventario.');
            Response::redirect('/inventario');
        }

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/inventario');
        }

        $id = (int) $request->post('id', 0);
        if ($id > 0) {
            try {
                $item = (new Inventario())->find($id);
                (new Inventario())->delete($id);
                (new Auditoria())->registrar(
                    Session::get('usuario', ''), 'inventario', 'eliminar',
                    "Artículo eliminado: " . ($item['articulo'] ?? "id={$id}")
                );
                Session::flash('exito', 'Artículo eliminado correctamente.');
            } catch (\PDOException $e) {
                // FK violation: el artículo tiene ventas registradas
                if ($e->getCode() === '23000') {
                    Session::flash('error', 'No se puede eliminar este artículo porque tiene ventas registradas. Puedes poner su cantidad en 0 si ya no está disponible.');
                } else {
                    throw $e;
                }
            }
        }

        Response::redirect('/inventario');
    }
}
