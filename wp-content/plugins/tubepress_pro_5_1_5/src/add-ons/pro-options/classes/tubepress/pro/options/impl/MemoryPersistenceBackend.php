<?php
/**
 * Copyright 2006 - 2016 TubePress LLC (http://tubepress.com/)
 *
 * This file is part of TubePress Pro.
 *
 * License summary
 *   - Can be used on 1 site, 1 server
 *   - Cannot be resold or distributed
 *   - Commercial use allowed
 *   - Can modify source-code but cannot distribute modifications (derivative works)
 *
 * Please see http://tubepress.com/license for details.
 */

/**
 * Implementation of tubepress_spi_options_storage_StorageManager that just keeps everything
 * in memory.
 */
class tubepress_pro_options_impl_MemoryPersistenceBackend implements tubepress_spi_options_PersistenceBackendInterface
{
    /**
     * @var array
     */
    private $_options;

    public function __construct(tubepress_api_boot_BootSettingsInterface $bsi)
    {
        $this->_options = $this->_readOptionsFromDatabaseJson($bsi);
    }

    /**
     * Creates multiple options in storage.
     *
     * @param array $optionNamesToValuesMap An associative array of option names to option values. For each
     *                                      element in the array, we will call createIfNotExists($name, $value)
     *
     * @return void
     */
    public function createEach(array $optionNamesToValuesMap)
    {
        $this->saveAll($optionNamesToValuesMap);
    }

    /**
     * @param array $optionNamesToValues An associative array of option names to values.
     *
     * @return null|string Null if the save succeeded and all queued options were saved, otherwise a string error message.
     */
    public function saveAll(array $optionNamesToValues)
    {
        $this->_options = array_merge($this->_options, $optionNamesToValues);
    }

    /**
     * @return array An associative array of all option names to values.
     */
    public function fetchAllCurrentlyKnownOptionNamesToValues()
    {
        return $this->_options;
    }

    private function _readOptionsFromDatabaseJson(tubepress_api_boot_BootSettingsInterface $bsi)
    {
        $databaseJsonPath = $bsi->getUserContentDirectory() . '/config/database.json';
        $readable         = is_readable($databaseJsonPath);

        if (!$readable) {

            return array();
        }

        $contents = file_get_contents($databaseJsonPath);

        if ($contents === false) {

            return array();
        }

        $decoded = json_decode($contents, true);

        if ($decoded === null) {

            return array();
        }

        $toReturn = array();

        foreach ($decoded as $key => $value) {

            if (!is_string($key) || !is_scalar($value)) {

                continue;
            }

            $toReturn[$key] = $value;
        }

        return $toReturn;
    }
}