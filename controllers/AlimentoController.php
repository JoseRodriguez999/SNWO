<?php
declare(strict_types=1);

class AlimentoController
{
    private AlimentoModel $alimentos;

    /** Roles que pueden agregar alimentos al catálogo */
    private const ROLES_EDITORES = ['SuperAdmin', 'Administrador', 'Nutricionista'];

    public function __construct()
    {
        $this->alimentos = new AlimentoModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        $puedeCrear = in_array($_SESSION['rol'] ?? '', self::ROLES_EDITORES, true);
        $error = '';
        $old   = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_alimento') {
            $old   = $_POST;
            $error = $puedeCrear ? $this->crear() : 'No tienes permiso para agregar alimentos.';
            if ($error === '') {
                $_SESSION['flash_exito'] = 'Alimento agregado al catálogo.';
                header('Location: alimentos.php');
                exit;
            }
        }

        $errorEdicion = '';
        $oldEdit = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_alimento') {
            $oldEdit = [
                'id_alimento' => (int)($_POST['id_alimento'] ?? 0), 'nombre' => trim((string)($_POST['nombre'] ?? '')),
                'id_categoria' => $_POST['id_categoria'] ?? '', 'porcion_gramos' => $_POST['porcion_gramos'] ?? '',
                'calorias' => $_POST['calorias'] ?? '', 'proteinas_g' => $_POST['proteinas_g'] ?? '',
                'carbohidratos_g' => $_POST['carbohidratos_g'] ?? '', 'grasas_g' => $_POST['grasas_g'] ?? '',
                'fibra_g' => $_POST['fibra_g'] ?? '', 'sodio_mg' => $_POST['sodio_mg'] ?? '', 'azucar_g' => $_POST['azucar_g'] ?? '',
            ];
            $errorEdicion = $puedeCrear ? $this->editar() : 'No tienes permiso para editar alimentos.';
            if ($errorEdicion === '') {
                $_SESSION['flash_exito'] = 'Alimento actualizado correctamente.';
                header('Location: alimentos.php');
                exit;
            }
        }

        $exito = '';
        if (!empty($_SESSION['flash_exito'])) {
            $exito = (string)$_SESSION['flash_exito'];
            unset($_SESSION['flash_exito']);
        }

        return [
            'alimentos'  => $this->alimentos->listarConCategoria(),
            'total'      => $this->alimentos->contarTotal(),
            'categorias' => $this->alimentos->listarCategorias(),
            'csrf'       => Sesion::csrfToken(),
            'error'      => $error,
            'errorEdicion' => $errorEdicion,
            'oldEdit'    => $oldEdit,
            'exito'      => $exito,
            'old'        => $old,
            'puedeCrear' => $puedeCrear,
        ];
    }

    /** Número decimal opcional: '' => null; acepta coma decimal. false si no es válido. */
    private function numero(string $v, bool $obligatorio)
    {
        $v = str_replace(',', '.', trim($v));
        if ($v === '') return $obligatorio ? false : null;
        return is_numeric($v) ? (float)$v : false;
    }

    /**
     * Lee y valida los campos del formulario (alta y edición).
     * @return array|string  Datos limpios, o un mensaje de error
     */
    private function leerFormulario()
    {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $idCat  = (int)($_POST['id_categoria'] ?? 0);
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            return 'Escribe el nombre del alimento (máximo 100 caracteres).';
        }
        $validas = array_map(static fn($c) => (int)$c['id_categoria'], $this->alimentos->listarCategorias());
        if (!in_array($idCat, $validas, true)) {
            return 'Selecciona una categoría válida.';
        }

        // campo => [etiqueta, obligatorio, máximo]
        $campos = [
            'porcion_gramos'  => ['El campo Porción (g)', true, 2000],
            'calorias'        => ['El campo Calorías', true, 5000],
            'proteinas_g'     => ['El campo Proteínas', true, 500],
            'carbohidratos_g' => ['El campo Carbohidratos', true, 500],
            'grasas_g'        => ['El campo Grasas', true, 500],
            'fibra_g'         => ['El campo Fibra', false, 500],
            'sodio_mg'        => ['El campo Sodio', false, 20000],
            'azucar_g'        => ['El campo Azúcar', false, 500],
        ];
        $d = ['id_categoria' => $idCat, 'nombre' => $nombre];
        foreach ($campos as $c => [$etq, $obl, $max]) {
            $n = $this->numero((string)($_POST[$c] ?? ''), $obl);
            if ($n === false) return "$etq debe ser un número" . ($obl ? ' (obligatorio).' : '.');
            if ($n !== null && ($n < 0 || $n > $max)) return "$etq debe estar entre 0 y $max.";
            $d[$c] = $n;
        }
        if ($d['porcion_gramos'] <= 0) {
            return 'La porción debe ser mayor que 0.';
        }
        if ($d['azucar_g'] !== null && $d['carbohidratos_g'] !== null && $d['azucar_g'] > $d['carbohidratos_g']) {
            return 'El azúcar no puede ser mayor que los carbohidratos totales.';
        }
        return $d;
    }

    /** @return string Vacío si se creó bien; mensaje de error en caso contrario */
    private function crear(): string
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
        }
        $d = $this->leerFormulario();
        if (is_string($d)) {
            return $d;
        }
        if ($this->alimentos->existeNombre($d['nombre'])) {
            return 'Ya existe un alimento con ese nombre en el catálogo.';
        }
        try {
            $id = $this->alimentos->crear($d);
            (new AuditoriaModel())->registrar((int)($_SESSION['id_usuario'] ?? 0) ?: null, 'ALIMENTO_CREADO', 'tbl_alimentos', $id);
            return '';
        } catch (PDOException $e) {
            error_log('Error al crear alimento SNWO: ' . $e->getMessage());
            return 'Ocurrió un error al guardar el alimento. Intenta de nuevo.';
        }
    }

    /** @return string Vacío si se actualizó bien; mensaje de error en caso contrario */
    private function editar(): string
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
        }
        $id = (int)($_POST['id_alimento'] ?? 0);
        if ($id <= 0 || !$this->alimentos->obtenerPorId($id)) {
            return 'El alimento ya no existe.';
        }
        $d = $this->leerFormulario();
        if (is_string($d)) {
            return $d;
        }
        if ($this->alimentos->existeNombre($d['nombre'], $id)) {
            return 'Ya existe otro alimento con ese nombre en el catálogo.';
        }
        try {
            $this->alimentos->actualizar($id, $d);
            (new AuditoriaModel())->registrar((int)($_SESSION['id_usuario'] ?? 0) ?: null, 'ALIMENTO_EDITADO', 'tbl_alimentos', $id);
            return '';
        } catch (PDOException $e) {
            error_log('Error al editar alimento SNWO: ' . $e->getMessage());
            return 'Ocurrió un error al guardar los cambios. Intenta de nuevo.';
        }
    }
}
