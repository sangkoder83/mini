<?php

declare(strict_types=1);

namespace App\Framework;

use PDO;
use Mini\Request;
use Mini\Database;
use Mini\Validator;


class Model extends Database
{
    protected $db;
    protected $table;
    protected $primaryKey;
    protected Request $request;
    protected Validator $validator;
    protected $columnSearch = [];
    protected $columnOrder = [];
    protected $orderDirection = 'DESC';
    protected $whereCondition = null;
    protected $rawSql = null;
    protected $insertRules = [];
    protected $updateRules = [];
    protected $validateRules = [];

    public function __construct()
    {
        $this->db = $this->connect();
        $this->request   = new Request;
        $this->validator = new Validator;
    }


    private function getTable(): string
    {
        if ($this->table !== null) {

            return $this->table;
        }

        $parts = explode("\\", $this::class);

        return strtolower(array_pop($parts));
    }


    private function getPrimaryKey(): string
    {
        if ($this->primaryKey !== null) {

            return $this->primaryKey;
        }

        return 'id';
    }

    public function lastInsertID(): string
    {
        return $this->db->lastInsertId();
    }


    public function findAll(): array
    {
        $sql  = "SELECT * FROM {$this->getTable()}";
        $stmt = $this->db->query($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find(string|int $id)
    {
        $sql  = "SELECT * FROM {$this->getTable()} WHERE {$this->getPrimaryKey()} = :{$this->getPrimaryKey()}";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":{$this->getPrimaryKey()}", $id, $this->getParamType($id));
        $stmt->execute();
        return $stmt->fetch() ?? false;
    }

    public function insert(array $data): bool
    {
        $columns      = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));

        $sql = "INSERT INTO {$this->getTable()} ($columns)
                VALUES ($placeholders)";

        $stmt = $this->db->prepare($sql);

        $i = 1;

        foreach ($data as $value) {
            $stmt->bindValue($i++, $value, $this->getParamType($value));
        }

        return $stmt->execute();
    }


    public function update(string|int $id, array $data): bool
    {
        $sql = "UPDATE {$this->getTable()} ";

        unset($data[$this->getPrimaryKey()]);

        $assignments = array_keys($data);

        array_walk($assignments, function (&$value) {
            $value = "$value = ?";
        });

        $sql .= " SET " . implode(", ", $assignments);

        $sql .= " WHERE {$this->getPrimaryKey()} = ?";

        $stmt = $this->db->prepare($sql);

        $i = 1;

        foreach ($data as $value) {
            $stmt->bindValue($i++, $value, $this->getParamType($value));
        }

        $stmt->bindValue($i, $id, $this->getParamType($id));

        return $stmt->execute();
    }

    public function delete(string|int $id): bool
    {
        $sql = "DELETE FROM {$this->getTable()}
            WHERE {$this->getPrimaryKey()} = :{$this->getPrimaryKey()}";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(":{$this->getPrimaryKey()}", $id, $this->getParamType($id));

        return $stmt->execute();
    }


    public function where(array $where_in = [], array $where_not_in = [], $data_type = 'object')
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE ";

        if (!empty($where_in)) {
            foreach ($where_in as $key => $val) {
                $sql .= $key . '=:' . $key . ' && ';
            }
        }

        if (!empty($where_not_in)) {
            foreach ($where_not_in as $key => $val) {
                $sql .= $key . '!= :' . $key . ' && ';
            }
        }

        $sql = trim($sql, ' && ');

        $data = [...$where_in, ...$where_not_in];

        $stmt = $this->db->prepare($sql);
        $res  = $stmt->execute($data);

        if ($res) {
            if ($data_type == 'object') {
                $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
            } else {
                $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
            }
        }

        return $rows;
    }

    public function first(array $where_in = [], array $where_not_in = [], $data_type = 'object')
    {
        $rows = $this->where($where_in, $where_not_in, $data_type);
        if (!empty($rows))
            return $rows[0];

        return false;
    }



    public function validateAndInsert()
    {
        $response = ['status' => false, 'messages' => []];
        $rules = $this->insertRules ?: $this->validateRules;

        $this->validator->setRules($rules);
        $this->validator->setErrorDelimiters('<p class="text-danger">', '</p>');


        if ($this->validator->run() === true) {
            $this->insert($this->request->getVar());
            $response['status'] = true;
            $response['messages'] = 'Berhasil Tambah Data';
        } else {
            $response['status'] = false;
            foreach ($this->request->getVar() as $key => $value) {
                $response['messages'][$key] = $this->validator->formError($key);
            }
        }

        echo json_encode($response);
    }

    public function showData($id)
    {
        $response =  $this->first([$this->getPrimaryKey() => $id]);
        echo json_encode($response);
    }

    public function deleteSelected($ids)
    {
        $successCount = 0;
        foreach ($ids as $id) {
            if ($this->delete($id)) {
                $successCount++;
            }
        }

        if ($successCount == count($ids)) {
            $response['status']   = true;
            $response['messages'] = count($ids) . " Data Berhasil di Hapus";
        } else {
            $response['status']   = false;
            $response['messages'] = "Gagal menghapus data";
        }

        echo json_encode($response);
    }




    public function validateAndUpdate($id)
    {
        $response = ['status' => false, 'messages' => []];
        $rules = $this->updateRules ?: $this->validateRules;

        $this->validator->setRules($rules);
        $this->validator->setErrorDelimiters('<p class="text-danger">', '</p>');

        if ($this->validator->run() === true) {
            $request = $this->request->getVar();

            if (array_key_exists('_method', $request)) {
                unset($request['_method']);
            }

            $this->update($id, $request);
            $response['status']   = true;
            $response['messages'] = "Berhasil Ubah Data";
        } else {
            $response['status'] = false;
            foreach ($this->request->getVar() as $key => $value) {
                $response['messages'][$key] = $this->validator->formError($key);
            }
        }
        echo json_encode($response);
    }

    public function setWhereCondition(...$conditions)
    {
        $this->whereCondition = implode(' AND ', $conditions);
    }

    public function rawQuery($sql)
    {
        $this->rawSql = $sql;
    }

    public function getData($formatRowCallback = null)
    {
        $columnSearch   = $this->columnSearch;
        $columnOrder    = $this->columnOrder;
        $orderDirection = $this->orderDirection;

        $isInitialLoad  = empty($_POST['order'][0]['column']);

        // Get data based on pagination and search query
        $search         = $_POST['search']['value'] ?? '';
        $limit          = $_POST['length'] ?? 6;
        $start          = $_POST['start'] ?? 0;

        // Build SQL query
        $sql = "SELECT * FROM {$this->getTable()}";

        // Add the where condition if not null
        if ($this->whereCondition !== null) {
            $sql .= " WHERE " . $this->whereCondition;
        }

        if (!empty($search)) {
            $escapedSearch = str_replace(array('%', '_'), array('\%', '\_'), $search);
            $searchConditions = [];
            foreach ($columnSearch as $column) {
                $searchConditions[] = "$column LIKE '%$escapedSearch%'";
            }
            $sql .= (empty($this->whereCondition) ? ' WHERE ' : ' AND ') . "(" . implode(" OR ", $searchConditions) . ")";
        }

        // Dynamically construct ORDER BY clause based on $columnOrder and $orderDirection
        if ($isInitialLoad && !empty($columnOrder)) {
            $sql .= " ORDER BY " . implode(",", $columnOrder) . " $orderDirection";
        } else {
            // If it's not the initial load or $columnOrder is empty, apply default order based on primary key
            $primaryKey       = $this->getPrimaryKey();
            $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
            $orderColumnName  = $columnOrder[$orderColumnIndex] ?? $primaryKey;
            $orderDirection   = $_POST['order'][0]['dir'] ?? $orderDirection ?? 'ASC'; // Default to ASC if not provided in POST
            $sql .= " ORDER BY $orderColumnName $orderDirection";
        }

        $sql .= " LIMIT $limit OFFSET $start";

        // Execute SQL query to fetch data
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total number of records
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->getTable()}");
        $stmt->execute();
        $totalRecords = $stmt->fetchColumn();

        // Get total number of filtered records
        $totalFilteredRecords = $totalRecords; // In this case, total records and filtered records are the same

        // Format data for DataTables
        $formattedData = [];
        foreach ($data as $row) {
            // Apply custom formatting if callback function is provided and callable
            if ($formatRowCallback !== null && is_callable($formatRowCallback)) {
                $formattedRow = call_user_func($formatRowCallback, $row);
                if ($formattedRow !== null) {
                    $formattedData[] = $formattedRow;
                }
            } else {
                $formattedData[] = $row; // No formatting, just use the original row
            }
        }

        // Prepare response
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalFilteredRecords, // Update recordsFiltered based on the filtered data
            "data" => $formattedData
        );

        // Output JSON response
        echo json_encode($output);
    }
}
