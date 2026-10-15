<?php

namespace App\Services\Intranet;

use App\Repositories\Intranet\IntranetRepository;
use App\Repositories\Intranet\DocumentRepository;
use App\Repositories\Intranet\DocumentoRepository;
use App\Repositories\Intranet\NoticiaRepository;
use App\Repositories\Intranet\OrganigramaRepository;

class IntranetService
{
    private IntranetRepository $intranetRepo;
    private DocumentRepository $documentRepo;
    private OrganigramaRepository $organigramaRepo;

    public function __construct()
    {
        $this->intranetRepo = new IntranetRepository();
        $this->documentRepo = new DocumentRepository();
        $this->organigramaRepo = new OrganigramaRepository();
    }

    public function getHomeData(): array
    {
        $noticias = (new NoticiaRepository())->getPublished();
        foreach ($noticias as &$noticia) {
            $noticia['ImagenUrl'] = $noticia['Imagen'];
        }
        unset($noticia);

        $manuales = (new DocumentoRepository())->getByCategory('manuales');
        foreach ($manuales as &$manual) {
            $manual['Titulo'] = $manual['Nombre'];
        }
        unset($manual);

        return [
            'empresas' => $this->intranetRepo->getEmpresas(),
            'areas' => $this->intranetRepo->getAreas(),
            'categorias' => $this->intranetRepo->getCategorias(),
            'noticias' => $noticias,
            'manuales' => $manuales,
            'pildoras' => [],
            'links' => [],
        ];
    }

    public function getDirectorio(): array
    {
        return $this->intranetRepo->getDirectorio();
    }

    public function updateDirectorio(int $id, array $data): array
    {
        $limits = [
            'first_name' => 100,
            'last_name' => 120,
            'address' => 255,
            'phone' => 30,
            'email' => 150,
            'extension' => 30,
            'area_agencia' => 120,
            'cargo' => 120,
            'empresa' => 120,
        ];
        $values = [];
        $errors = [];

        foreach ($limits as $field => $limit) {
            $value = $data[$field] ?? null;
            if ($value !== null && !is_string($value)) {
                $errors[$field] = ['El valor debe ser texto.'];
                continue;
            }
            $value = is_string($value) ? trim($value) : '';
            if (($field === 'first_name' || $field === 'last_name') && $value === '') {
                $errors[$field] = ['Este campo es obligatorio.'];
            } elseif (mb_strlen($value) > $limit) {
                $errors[$field] = ["El campo no puede superar {$limit} caracteres."];
            }
            $values[$field] = $value === '' ? null : $value;
        }

        if (($values['email'] ?? null) !== null && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Ingresa un correo electrónico válido.'];
        }
        if ($errors) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Revisa los datos del contacto.'];
        }

        if (!$this->intranetRepo->directorioExists($id)) {
            return ['success' => false, 'message' => 'El contacto no existe.'];
        }
        if (!$this->intranetRepo->updateDirectorio($id, $values)) {
            return ['success' => false, 'message' => 'No fue posible actualizar el contacto.'];
        }

        return ['success' => true, 'message' => 'Contacto actualizado correctamente.'];
    }

    public function getDocumentos(?int $empresaId = null, ?int $areaId = null, ?int $categoriaId = null, ?string $search = null): array
    {
        return $this->documentRepo->getDocumentos($empresaId, $areaId, $categoriaId, $search);
    }

    public function createDocumento(array $data): array
    {
        $id = $this->documentRepo->create($data);
        return [
            'success' => true,
            'message' => 'Documento cargado exitosamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function updateDocumentoVersion(int $id, string $nuevaVersion, string $nuevaRuta): array
    {
        $updated = $this->documentRepo->updateVersion($id, $nuevaVersion, $nuevaRuta);
        return [
            'success' => $updated,
            'message' => $updated ? 'Versión de documento actualizada.' : 'No fue posible actualizar la versión.'
        ];
    }

    public function getOrganigrama(): array
    {
        return $this->organigramaRepo->getOrganigrama();
    }

    public function upsertOrganigramaNodo(array $data): array
    {
        $res = $this->organigramaRepo->upsertNodo($data);
        return [
            'success' => $res,
            'message' => $res ? 'Organigrama actualizado.' : 'No fue posible actualizar el organigrama.'
        ];
    }

    public function createNoticia(array $data): array
    {
        $id = $this->intranetRepo->createNoticia($data);
        return [
            'success' => true,
            'message' => 'Noticia publicada exitosamente.',
            'data' => ['Id' => $id]
        ];
    }
}
