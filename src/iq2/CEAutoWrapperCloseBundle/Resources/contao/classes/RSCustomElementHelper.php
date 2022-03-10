<?php


namespace iq2\CEAutoWrapperCloseBundle\Resources\contao\classes;


use Contao\Database;
use Contao\Input;
use DC_Table;
use MadeYourDay\RockSolidCustomElements\CustomElements;
use function json_encode;


class RSCustomElementHelper
{

    /**
     * Automatically creates a wrapper-stop element
     *
     * @param $objDC
     */
    public function createWrapperStop($objDC)
    {
        //get active record
        if (!$objDC->activeRecord) {
            $objDC->activeRecord = $this->getActiveRecord($objDC->table, $objDC->id);
        }

        //check if custom element has wrapper config
        $type = $objDC->activeRecord->type;
        $config = $this->getWrapperStartConfig($type);
        if ($config) {
            $wrapperClose = $config['wrapperClose'];

            //create wrapper stop if entry is new
            if ($objDC->activeRecord->rsce_data == null) {
                $this->createRsceDatabaseEntry($objDC, $wrapperClose);
            }
        }
    }

    /**
     * Creates a wrapper-stop element at copy-action of a single wrapper-start element
     *
     * @param $id
     * @param $objDC
     */
    public function createWrapperStopOnCopy($id, DC_Table $objDC)
    {
        if (Input::get('act') == "copy") {
            //get active record of copied entry
            $objDC->activeRecord = $this->getActiveRecord($objDC->table, $id);
            //check if custom element has wrapper config
            $type = $objDC->activeRecord->type;
            $config = $this->getWrapperStartConfig($type);
            if ($config) {
                $wrapperClose = $config['wrapperClose'];

                //create wrapper stop
                $this->createRsceDatabaseEntry($objDC, $wrapperClose);
            }
        }
    }

    /**
     * Returns the database entry of the active record
     *
     * @param $table
     * @param $id
     * @return Database\Result
     */
    private function getActiveRecord($table, $id)
    {
        return Database::getInstance()
            ->prepare("SELECT * FROM " . $table . " WHERE id=?")
            ->limit(1)->execute($id);
    }

    /**
     * Gets the config of a start wrapper, if it contains a wrapperClose definition
     *
     * @param $type
     * @return array|bool|null
     */
    private function getWrapperStartConfig($type)
    {
        //check if element is rocksolid custom element
        if (preg_match("/^rsce_/", $type)) {
            $config = CustomElements::getConfigByType($type);
            $wrapperType = $config['wrapper'];
            if ($wrapperType['type'] == 'start' && isset($config['wrapperClose'])) {
                return $config;
            }
        }
        return false;
    }

    /**
     * Creates a new database entry after the newly created element
     *
     * @param $objDC
     * @param $type
     * @param string $rsceData
     * @return Database\Result
     */
    private function createRsceDatabaseEntry($objDC, $type, $rsceData = "")
    {
        $objDB = Database::getInstance();

        //set sorting to value between current and next entry
        $startWrapperSorting = $objDC->activeRecord->sorting;
        $nextSorting = $objDB->prepare('SELECT sorting FROM tl_content WHERE sorting > ? AND pid = ? AND ptable = ? ORDER BY sorting LIMIT 1')
            ->execute($startWrapperSorting, $objDC->activeRecord->pid, $objDC->activeRecord->ptable)->fetchRow()[0];
        $newSorting = (($nextSorting - $startWrapperSorting) / 2 + $startWrapperSorting);

        //create database entry
        return Database::getInstance()
            ->prepare(
                "INSERT INTO " . $objDC->table . " (pid, ptable, tstamp, type, sorting, invisible, rsce_data) VALUES (?,?,?,'$type',?,?,?)"
            )
            ->execute(
                $objDC->activeRecord->pid,
                $objDC->activeRecord->ptable,
                time(),
                $newSorting,
                $objDC->activeRecord->invisible,
                json_encode($rsceData)
            );

    }
}