<?php
declare(strict_types=1);

class PersonalizarController
{
    private PacienteModel $pacientes;
    private DietaModel $dietas;
    private AlimentoModel $alimentos;

    public function __construct()
    {
        $this->pacientes = new PacienteModel();
        $this->dietas    = new DietaModel();
        $this->alimentos = new AlimentoModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        $idPaciente = Sesion::resolverPacienteId();
        $paciente   = $this->pacientes->buscarPorId($idPaciente);
        Sesion::sincronizarPaciente($idPaciente, $paciente);

        if (!$paciente) {
            return ['paciente' => null];
        }

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'sustituir_alimento') {
            if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
            } else {
                try {
                    $this->dietas->sustituirAlimento(
                        (int)($_POST['id_menu'] ?? 0),
                        (int)($_POST['id_alimento_viejo'] ?? 0),
                        (int)($_POST['id_alimento_nuevo'] ?? 0)
                    );
                    header('Location: personalizar.php?id=' . $idPaciente);
                    exit;
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                } catch (Throwable $e) {
                    error_log('Error al sustituir alimento SNWO: ' . $e->getMessage());
                    $error = 'Ocurrió un error al guardar el cambio. Intenta de nuevo.';
                }
            }
        }

        $dieta = $this->dietas->obtenerDietaActual($idPaciente);

        // Modo búsqueda de sustituto: viene de un ?editar_menu=X&editar_alimento=Y
        $editandoMenu = (int)($_GET['editar_menu'] ?? 0);
        $editandoAlimento = (int)($_GET['editar_alimento'] ?? 0);
        $resultadosBusqueda = [];
        $tiempoSlot = null;

        if ($editandoMenu > 0) {
            $slot = $this->dietas->obtenerMenuPorId($editandoMenu);
            if ($slot) {
                $tiempoSlot = $slot['tiempo_comida'];
                $termino = trim((string)($_GET['buscar'] ?? ''));
                $categorias = DietaModel::CATEGORIAS_POR_TIEMPO[$tiempoSlot] ?? [];
                $resultadosBusqueda = $this->alimentos->buscarCompatibles(
                    $termino, $categorias, $tiempoSlot === 'Desayuno'
                );
            }
        }

        return [
            'paciente'    => $paciente,
            'idPaciente'  => $idPaciente,
            'dieta'       => $dieta,
            'dias'        => DietaModel::nombresDias(),
            'tiempos'     => DietaModel::tiemposComida(),
            'error'       => $error,
            'csrf'        => Sesion::csrfToken(),
            'editandoMenu'      => $editandoMenu,
            'editandoAlimento'  => $editandoAlimento,
            'tiempoSlot'        => $tiempoSlot,
            'resultadosBusqueda' => $resultadosBusqueda,
            'terminoBusqueda'   => trim((string)($_GET['buscar'] ?? '')),
        ];
    }
}
