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
