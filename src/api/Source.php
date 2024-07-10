<?php

namespace EcospoldExplorer;

/**
* Source
* This class provides methods to manage ecospold sources
*/
class Source
{

    protected array $source = [];

    public function __construct(int $id)
    {
        $source = Database::request("SELECT * FROM `source` WHERE `id` = ?", [$id])::fetch();

        if ($source) {
            $this->setSource($source);
        } else {
            Response::send(400, "The requested data source is not available");
        }
    }

    /**
     * Returns the list of declared sources in database
     *
     * @return array
     */
    public static function getList(): array
    {
        return Database::request("SELECT * FROM `source` ORDER BY `id` DESC")::fetchAll();
    }

    /**
     * Returns the list of available indicators in the source
     */
    public function getIndicators(): array
    {
        $indicators = Database::request(
            "SELECT DISTINCT CONCAT(`category`, ' | ', `indicator`) AS impact_category, method FROM `characterization_factor` WHERE `source_id` = ? ORDER BY `method`",
            [$this->source['id']]
        )::fetchAll();

        foreach ($indicators as $indicator) {
            $response[$indicator['method']][] = $indicator['impact_category'];
        }

        return $response;

    }

    /**
     * Returns the list of available elementary flows in the source
     */
    public function getElementaryFlows(): array
    {
        return Database::request("SELECT DISTINCT `name` FROM `characterization_factor` WHERE `source_id` = ? ORDER BY `name`", [$this->source['id']])::fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Returns characterization factor from DB
     */
    protected function getCharacterizationFactor(string $method, string $category, string $indicator, string $name, string $compartment, string $subcompartment): float
    {
        $cf = Database::request(
            "SELECT `characterization_factor` FROM `characterization_factor` WHERE `source_id` = ? AND `method` = ? AND `category` = ? AND `indicator` = ? AND `name` = ? AND `compartment` = ? AND `subcompartment` = ?",
            [$this->source['id'], $method, $category, $indicator, $name, $compartment, $subcompartment]
        )::fetch();

        return $cf['characterization_factor'] ?? 0;
    }

    /**
     * Returns list of activies based on keywords
     */
    protected function getActivities(array $keywords)
    {

        $clauses = [];
        $arguments = [$this->source['id']];

        foreach ($keywords as $keyword) {
            $clauses[] = "`name` LIKE ?";
            $arguments[] = $keyword;
        }

        $clauses = !empty($clauses) ? " AND ".implode(' OR ', $clauses) : '';

        return Database::request(
            "SELECT CONCAT(`uuid`, '.spold') AS `file` FROM `activity` WHERE `source_id` = ?$clauses",
            $arguments
        )::fetchAll(\PDO::FETCH_COLUMN);

    }

    /**
     * Checks if activities list is available
     */
    protected function isActivitiesListStored()
    {
        return Database::request("SELECT DISTINCT `source_id` FROM `activity` WHERE `source_id` = ?", [$this->source['id']])::fetchAll();
    }

    protected function setSource(array $source): void
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

}