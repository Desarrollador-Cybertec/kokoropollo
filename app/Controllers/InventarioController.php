<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\AuthMiddleware;
use App\Models\Inventario;

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

        View::render('inventario/index', compact('items', 'editarItem', 'busqueda', 'dashboardUrl'));
    }

    public function store(Request $request): void
    {
        AuthMiddleware::handle();

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

        (new Inventario())->create($articulo, $categoria, $cantidad, $valor);
        Session::flash('exito', "Artículo \"{$articulo}\" agregado correctamente.");
        Response::redirect('/inventario');
    }

    public function update(Request $request): void
    {
        AuthMiddleware::handle();

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
            (new Inventario())->update($id, $articulo, $categoria, $cantidad, $valor);
            Session::flash('exito', "Artículo \"{$articulo}\" actualizado correctamente.");
        }

        Response::redirect('/inventario');
    }

    public function destroy(Request $request): void
    {
        AuthMiddleware::handle();

        if (!Csrf::validateToken($request->csrfToken())) {
            Response::redirect('/inventario');
        }

        $id = (int) $request->post('id', 0);
        if ($id > 0) {
            try {
                (new Inventario())->delete($id);
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
