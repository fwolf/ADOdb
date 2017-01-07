<?php

/**
 * Class ADODB_pdo_sybase_ase
 */
class ADODB_pdo_sybase_ase extends ADODB_pdo
{
    /**
     * @var string
     */
    public $databaseType = 'sybase_ase';

    /**
     * @var string
     */
    public $metaColumnsSQL = "
        SELECT
            syscolumns.name AS field_name,
            systypes.name AS type,
            systypes.length AS width
        FROM sysobjects, syscolumns, systypes
        WHERE
            sysobjects.name='%s' AND
            syscolumns.id = sysobjects.id AND
            systypes.type=syscolumns.type
        ";

    /**
     * @var string
     */
    public $metaDatabasesSQL = "
        SELECT a.name
        FROM master.dbo.sysdatabases a, master.dbo.syslogins b
        WHERE
            a.suid = b.suid AND
            a.name LIKE '%' AND
            a.name != 'tempdb' AND
            a.status3 != 256
        ORDER BY 1
        ";

    /**
     * @var string
     */
    public $metaTablesSQL = "
        SELECT sysobjects.name
        FROM sysobjects, sysusers
        WHERE
            sysobjects.type='U' AND sysobjects.uid = sysusers.uid
        ";


    /**
     * @return bool
     */
    function BeginTrans()
    {
        if ($this->transOff) {
            return true;
        }
        $this->transCnt += 1;

        $this->Execute('BEGIN TRAN');

        return true;
    }


    /**
     * @param   bool $ok
     * @return  bool
     */
    function CommitTrans($ok = true)
    {
        if ($this->transOff) {
            return true;
        }

        if (!$ok) {
            return $this->RollbackTrans();
        }

        $this->transCnt -= 1;
        $this->Execute('COMMIT TRAN');

        return true;
    }


    /**
     * Split the Views, Tables and procedures
     *
     * @param   string $table
     * @param   bool   $upper
     * @return  array|bool
     */
    function MetaColumns($table, $upper = false)
    {
        $false = false;
        if (!empty($this->metaColumnsSQL)) {

            $rs = $this->Execute(sprintf($this->metaColumnsSQL, $table));
            if ($rs === false) {
                return $false;
            }

            $retarr = array();
            while (!$rs->EOF) {
                $fld = new ADOFieldObject();
                $fld->name = $rs->Fields('field_name');
                $fld->type = $rs->Fields('type');
                $fld->max_length = $rs->Fields('width');
                $retarr[strtoupper($fld->name)] = $fld;
                $rs->MoveNext();
            }
            $rs->Close();

            return $retarr;
        }

        return $false;
    }


    /**
     * @return  array|bool
     */
    function MetaDatabases()
    {
        $arr = array();
        if ($this->metaDatabasesSQL != '') {
            $rs = $this->Execute($this->metaDatabasesSQL);
            if ($rs && !$rs->EOF) {
                while (!$rs->EOF) {
                    $arr[] = $rs->Fields('name');
                    $rs->MoveNext();
                }

                return $arr;
            }
        }

        return false;
    }


    /**
     * Fix a bug which prevent the metaColumns query to be executed for Sybase
     * ASE
     *
     * @param   bool $ttype
     * @param   bool $showSchema
     * @param   bool $mask
     * @return  array|bool
     */
    function MetaTables($ttype = false, $showSchema = false, $mask = false)
    {
        $false = false;
        if ($this->metaTablesSQL) {
            // complicated state saving by the need for backward compat

            if ($ttype == 'VIEWS') {
                $sql = str_replace('U', 'V', $this->metaTablesSQL);
            } elseif (false === $ttype) {
                $sql = str_replace('U', "U' OR type='V", $this->metaTablesSQL);
            } else { // TABLES OR ANY OTHER
                $sql = $this->metaTablesSQL;
            }
            $rs = $this->Execute($sql);

            if ($rs === false || !method_exists($rs, 'GetArray')) {
                return $false;
            }
            $arr = $rs->GetArray();

            $arr2 = array();
            foreach ($arr as $key => $value) {
                $arr2[] = trim($value['name']);
            }

            return $arr2;
        }

        return $false;
    }


    /**
     * @return  bool
     */
    function RollbackTrans()
    {
        if ($this->transOff) {
            return true;
        }
        $this->transCnt -= 1;
        $this->Execute('ROLLBACK TRAN');

        return true;
    }


    /**
     * @param   string $parentDriver
     */
    public function _init($parentDriver)
    {
        $parentDriver->hasTransactions = true;
        $parentDriver->hasInsertID = true;
    }
}


/**
 * Class ADORecordSet_pdo_sybase_ase
 */
class ADORecordSet_pdo_sybase_ase extends ADORecordSet_pdo
{
    /**
     * @var string
     */
    public $databaseType = "sybase_ase";
}
