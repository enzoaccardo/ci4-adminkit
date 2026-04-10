<?php

namespace AdminKit\Database;

use CodeIgniter\Database\Migration;

/**
 * Migration base del kit: createTable() inietta i 6 campi audit
 * (created_at/by, updated_at/by, deleted_at/by), coerenti con AdminKit\Models\BaseModel.
 * App e pacchetti che vogliono la convenzione audit estendono questa.
 */
abstract class BaseMigration extends Migration
{
    protected function rawQuery(string $sql): \CodeIgniter\Database\BaseResult|bool
    {
        return $this->db->query($sql);
    }

    protected function withAuditFields(array $fields): array
    {
        return array_merge($fields, $this->auditFields());
    }

    protected function createTable(string $table, array $fields, array $primaryKey = ['id']): void
    {
        $this->forge->addField($this->withAuditFields($fields));
        if (! empty($primaryKey)) {
            $this->forge->addPrimaryKey($primaryKey);
        }
        $this->forge->createTable($table, true);
    }

    protected function dropTable(string $table): void
    {
        $this->forge->dropTable($table, true);
    }

    private function auditFields(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'deleted_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
        ];
    }
}
