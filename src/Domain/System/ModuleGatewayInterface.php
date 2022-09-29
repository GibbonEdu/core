<?php

namespace Gibbon\Domain\System;

use Gibbon\Domain\QueryCriteria;

interface ModuleGatewayInterface
{

    /**
     * Queries the list for the Manage Modules page.
     *
     * @version v16
     * @since   v16
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryModules(QueryCriteria $criteria);

    /**
     * Gets an unfiltered list of all modules.
     *
     * @version v16
     * @since   v16
     *
     * @return string[]
     */
    public function getAllModuleNames();

    /**
     * The modules by role.
     *
     * @version v16
     * @since   v16
     *
     * @param string $gibbonRoleID
     *
     * @return array
     */
    public function selectModulesByRole($gibbonRoleID);

    /**
     * The module actions by role.
     *
     * @version v16
     * @since   v16
     *
     * @param string $gibbonRoleID
     * @param string $gibbonModuleID
     * @return array
     */
    public function selectModuleActionsByRole($gibbonRoleID, $gibbonModuleID);
}
