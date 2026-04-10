<?php

namespace AdminKit\Models;

use CodeIgniter\Model;

/**
 * Model base del kit: returnType object, timestamps, soft delete e audit
 * (created_by/updated_by/deleted_by da session('user_id')). I model dell'app e
 * dei pacchetti che vogliono la convenzione audit estendono questo.
 *
 * I model per dati statici o pivot possono disattivare timestamps/soft delete e
 * svuotare i callback (beforeInsert/Update/Delete = []).
 */
class BaseModel extends Model
{
    protected $returnType     = 'object';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;

    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $beforeInsert = ['setCreatedBy', 'setUpdatedBy'];
    protected $beforeUpdate = ['setUpdatedBy'];
    protected $beforeDelete = ['setDeletedBy'];

    protected function setCreatedBy(array $data): array
    {
        $userId = $this->getCurrentUserId();
        if ($userId !== null) {
            $data['data']['created_by'] = $userId;
        }
        return $data;
    }

    protected function setUpdatedBy(array $data): array
    {
        $userId = $this->getCurrentUserId();
        if ($userId !== null) {
            $data['data']['updated_by'] = $userId;
        }
        return $data;
    }

    protected function setDeletedBy(array $data): array
    {
        $userId = $this->getCurrentUserId();
        if ($this->useSoftDeletes && $userId !== null) {
            $this->builder()->set(['deleted_by' => $userId])->update();
        }
        return $data;
    }

    protected function getCurrentUserId(): ?int
    {
        $session = session();
        return $session->has('user_id') ? (int) $session->get('user_id') : null;
    }
}
