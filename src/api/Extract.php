<?php

namespace EcospoldExplorer;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
* Extract
* This class manages ecospold extraction
*/
class Extract extends Source
{

    protected $inputActivityKeywords = [];
    protected $inputReferenceFlowKeywords = [];
    protected $inputIntermediateExchangeKeywords = [];
    protected $outputName = true;
    protected $outputReferenceFlow = true;
    protected $outputGeography = true;
    protected $outputIntermediateExchanges = false;
    protected $outputImpactCategories = [];
    protected $outputElementaryFlows = [];

    /**
     * @param array $parameters as an array
     */
    public function __construct(array $parameters)
    {
        if (!isset($parameters['source_id'])) {
            throw new \Exception("No source database provided");
        }
        parent::__construct($parameters['source_id']);
        $this->setParameters($parameters);
    }

    /**
     * Method to generate extraction
     */
    public function extract(): string
    {
        // Scan LCIA files first
        $extract =  $this->scanFilesLCIA();

        // Scan LCI/System files if needed
        if (!empty($this->outputElementaryFlows)) {
            $extract = $this->scanFilesLCI($extract);
        }

        // Scan Unit files if need
        if (!empty($this->inputIntermediateExchangeKeywords) || in_array("detail", array_column($this->outputImpactCategories, 'rule')) || in_array("detail", array_column($this->outputElementaryFlows, 'rule'))) {
            $extract = $this->scanFiles($extract);
        }

        // Scan Geographies
        if ($this->outputGeography) {
            $extract = $this->scanGeographies($extract);
        }

        // Unset not wanted properties
        $remove = [];
        if (!$this->outputName) { $remove[] = "Name"; }
        if (!$this->outputGeography) { $remove[] = "Geography"; }
        if (!$this->outputReferenceFlow) { $remove[] = "ReferenceFlow"; }

        $extract = array_map(function($activity) use ($remove) {
            return array_diff_key($activity, $remove);
        }, $extract);

        // Sort or die
        if (!empty($extract)) {
            $extract = $this->sortOutput($extract);
        } else {
            Response::send(404, "No data matching the request parameters");
        }

        // Define save folder
        $dir = "download";
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
        }

        // Clear files
        $files = glob("$dir/*");
        foreach($files as $file){
            if(is_file($file)) {
                unlink($file);
            }
        }

        // Generate CSV
        $fileName = $this->generateRandomString();
        $this->convertArrayToCSV($extract, "{$dir}/{$fileName}.csv");

        // Convert CSV to XLSX
        $this->convertCsvToExcel("{$dir}/{$fileName}.csv");

        // Remove CSV
        unlink("{$dir}/{$fileName}.csv");

        return "{$dir}/{$fileName}.xlsx";
    }

    /**
     * Method to scan files (Unit format)
     */
    private function scanFiles(array $extract): array
    {

        foreach ($extract as $uuid => $activity) {

            $unset = true;

            if (file_exists($this->source['path_to_unit_datasets_repository'].'/'.$uuid.'.spold')) {

                // Transform XML (ecospold) into object
                $xml = simplexml_load_file($this->source['path_to_unit_datasets_repository'].'/'.$uuid.'.spold');

                // Define the main node (could be activityDataset or childActivityDataset)
                $metaNode = $xml->activityDataset ?? $xml->childActivityDataset;

                // Get activity flow data
                $exchanges = $metaNode->flowData->children();

                foreach($exchanges as $exchangeData) {

                    $exchangeInputGroup = (int) $exchangeData->inputGroup ?? 0;
                    $exchangeAmount = (float) $exchangeData->attributes()->amount;
                    $exchangeAmount = abs($exchangeAmount);

                    // Check if flow data is an input from technosphere
                    if ($exchangeData->getName() == 'intermediateExchange' && $exchangeInputGroup == 5) {

                        $intermediateExchangeName = (string) $exchangeData->name;
                        $intermediateExchangeUnit = (string) $exchangeData->unitName;
                        $intermediateExchangeActivityLinkId = (string) $exchangeData->attributes()->activityLinkId;
                        $intermediateExchangeId = (string) $exchangeData->attributes()->intermediateExchangeId;

                        // Filter on intermediate exchange
                        foreach ($this->inputIntermediateExchangeKeywords as $keyword) {
                            if (preg_match("#{$keyword}#", $intermediateExchangeName)) {
                                if ($this->outputIntermediateExchanges) {
                                    $extract[$uuid]["{$intermediateExchangeName} [{$intermediateExchangeUnit}]"] = $exchangeAmount;
                                }
                                $unset = false;
                                break;
                            }
                        }

                        // Transform provider XML (ecospold) into object
                        if (file_exists($this->source['path_to_impact_datasets_repository'].'/'.$intermediateExchangeActivityLinkId.'_'.$intermediateExchangeId.'.spold')) {

                            $provider = simplexml_load_file($this->source['path_to_impact_datasets_repository'].'/'.$intermediateExchangeActivityLinkId.'_'.$intermediateExchangeId.'.spold');

                            // Define the main node of provider (could be activityDataset or childActivityDataset)
                            $providerMetaNode = $provider->activityDataset ?? $provider->childActivityDataset;

                            // Get provider name
                            $providerName = (string) $providerMetaNode->activityDescription->activity->activityName ?? 'Unknown data';

                            // Get provider flows
                            $providerExchanges = $providerMetaNode->flowData->children();

                            foreach($providerExchanges as $providerExchangeData) {

                                if ($providerExchangeData->getName() == 'impactIndicator') {

                                    $currentMethod = (string) $providerExchangeData->impactMethodName;
                                    $currentCategory = (string) $providerExchangeData->impactCategoryName;
                                    $currentName = (string) $providerExchangeData->name;

                                    foreach ($this->outputImpactCategories as $outputImpactCategory) {

                                        if ($outputImpactCategory['method'] == $currentMethod && $outputImpactCategory['category'] == $currentCategory && $outputImpactCategory['indicator'] == $currentName && $outputImpactCategory['rule'] == 'detail') {

                                            // Add impact contribution to extracted file
                                            $cf = (float) $providerExchangeData->attributes()->amount;
                                            $key = "{$currentMethod} | {$currentCategory} | {$currentName} - {$providerName}";
                                            $extract[$uuid][$key] = $exchangeAmount * $cf;

                                            break;

                                        }

                                    }

                                }

                            }

                            unset($provider);

                        }

                    }

                    // Check if flow data is an elementary flow
                    if ($exchangeData->getName() == 'elementaryExchange') {

                        $elementaryFlowName = (string) $exchangeData->name;
                        $elementaryFlowUnit = (string) $exchangeData->unitName;
                        $elementaryFlowCompartment = (string) $exchangeData->compartment->compartment;
                        $elementaryFlowSubcompartment = (string) $exchangeData->compartment->subcompartment;

                        foreach ($this->outputImpactCategories as $outputImpactCategory) {

                            $cf = $this->getCharacterizationFactor(
                                $outputImpactCategory['method'],
                                $outputImpactCategory['category'],
                                $outputImpactCategory['indicator'],
                                $elementaryFlowName,
                                $elementaryFlowCompartment,
                                $elementaryFlowSubcompartment
                            );

                            if ($cf != 0) {
                                $key = "{$outputImpactCategory['method']} | {$outputImpactCategory['category']} | {$outputImpactCategory['indicator']} - {$elementaryFlowName} ({$elementaryFlowCompartment} - {$elementaryFlowSubcompartment})";
                                $extract[$uuid][$key] = $exchangeAmount * $cf;
                            }

                        }

                        foreach ($this->outputElementaryFlows as $searchedElementaryFlow) {

                            if (strtolower($searchedElementaryFlow['name']) == strtolower($elementaryFlowName) && $searchedElementaryFlow['rule'] == 'detail') {

                                $key = "Direct {$elementaryFlowName} [{$elementaryFlowUnit}]";
                                $extract[$uuid][$key] = isset($extract[$uuid][$key]) ? $extract[$uuid][$key] + $exchangeAmount : $exchangeAmount;

                            }

                        }

                    }

                }

            }

            if ($unset && !empty($this->inputIntermediateExchangeKeywords)) {
                unset($extract[$uuid]);
            }

        }

        return $extract;

    }

    /**
     * Method to scan LCI files (System format)
     */
    private function scanFilesLCI(array $extract): array
    {

        foreach ($extract as $uuid => $activity) {

            if (file_exists($this->source['path_to_system_datasets_repository'].'/'.$uuid.'.spold')) {

                // Transform XML (ecospold) into object
                $xml = simplexml_load_file($this->source['path_to_system_datasets_repository'].'/'.$uuid.'.spold');

                // Define the main node (could be activityDataset or childActivityDataset)
                $metaNode = $xml->activityDataset ?? $xml->childActivityDataset;

                // Get activity flow data
                $exchanges = $metaNode->flowData->children();

                foreach($exchanges as $exchangeData) {

                    // Check if flow data is a searched elementary flow ...
                    if ($exchangeData->getName() == 'elementaryExchange') {

                        $elementaryFlowName = (string) $exchangeData->name;
                        $elementaryFlowUnit = (string) $exchangeData->unitName;

                        foreach ($this->outputElementaryFlows as $searchedElementaryFlow) {

                            if (strtolower($searchedElementaryFlow['name']) == strtolower($elementaryFlowName)) {

                                $emissions = (float) $exchangeData->attributes()->amount;
                                $key = "Total {$elementaryFlowName} [{$elementaryFlowUnit}]";
                                $extract[$uuid][$key] = isset($extract[$uuid][$key]) ? $extract[$uuid][$key] + $emissions : $emissions;

                                break;

                            }

                        }

                    }

                }

            }

        }

        return $extract;

    }

    /**
     * Method to scan LCIA files
     * These files are the lightest, they are parsed first any time
     */
    private function scanFilesLCIA(): array
    {

        $extract = [];

        $activityLCIAFiles = array_diff(scandir($this->source['path_to_impact_datasets_repository']), ['.','..']);

        foreach ($activityLCIAFiles as $file) {

            // Transform XML (ecospold) into object
            $xml = simplexml_load_file($this->source['path_to_impact_datasets_repository'].'/'.$file);

            // Define the main node (could be activityDataset or childActivityDataset)
            $metaNode = $xml->activityDataset ?? $xml->childActivityDataset;

            // Get activity name
            $activityName = (string) $metaNode->activityDescription->activity->activityName ?? 'Unknown data';

            // Check if activity is searched
            foreach ($this->inputActivityKeywords as $searchedName) {

                if (preg_match("#{$searchedName}#", $activityName)) {

                    // Save activity info
                    $uuid = str_replace('.spold', '', $file);
                    $extract[$uuid]['UUID'] = $uuid;
                    $extract[$uuid]["Name"] = $activityName;
                    $extract[$uuid]["Geography Code"] = (string) $metaNode->activityDescription->geography->shortname ?? '??';

                    // Get activity flow data
                    $exchanges = $metaNode->flowData->children();

                    // Loop flow data
                    foreach($exchanges as $exchangeData) {

                        // Get reference flow name
                        switch ($exchangeData->getName()) {

                            case 'intermediateExchange':

                                $referenceFlow = (string) $exchangeData->name;
                                $unit = (string) $exchangeData->unitName;
                                $extract[$uuid]["Reference flow"] = "{$referenceFlow} [{$unit}]";

                                break;

                            // Check if flow data is a searched impact indicator ...
                            case 'impactIndicator':

                                $currentMethod = (string) $exchangeData->impactMethodName;
                                $currentCategory = (string) $exchangeData->impactCategoryName;
                                $currentName = (string) $exchangeData->name;

                                foreach ($this->outputImpactCategories as $outputImpactCategory) {

                                    if ($outputImpactCategory['method'] == $currentMethod && $outputImpactCategory['category'] == $currentCategory && $outputImpactCategory['indicator'] == $currentName) {

                                        // Add impact to extracted file
                                        $unit = (string) $exchangeData->unitName;
                                        $amount = (float) $exchangeData->attributes()->amount;

                                        $extract[$uuid]["{$currentMethod} | {$currentCategory} | {$currentName} [{$unit}]"] = $amount;

                                        break;

                                    }

                                }
                        }

                    }

                    // Remove extracted data if reference flow is not searched
                    if (!empty($this->inputReferenceFlowKeywords)) {

                        foreach ($this->inputReferenceFlowKeywords as $searchedReferenceFlow) {

                            if (!preg_match("#{$searchedReferenceFlow}#", $extract[$uuid]["Reference flow"])) {

                                unset($extract[$uuid]);
                                break;

                            }

                        }

                    }

                    // A search activity was found
                    break;

                }

            }

        }

        return $extract;

    }

    /**
     * Method to scan Geographies file
     */
    private function scanGeographies(array $extract): array
    {

        if (file_exists($this->source['path_to_geographies_file'])) {

            $xml = simplexml_load_file($this->source['path_to_geographies_file']);
            $geographies = $xml->children();

            foreach ($extract as $uuid => $activity) {

                foreach ($geographies as $geography) {

                    if ($geography->getName() == 'geography' && $geography->shortname == $activity["Geography Code"]) {

                        $extract[$uuid]["Geography"] = (string) $geography->name;
                        break;

                    }

                }

            }

            unset($xml);

        }

        return $extract;

    }

    /**
     * Method to sort final output
     */
    private function sortOutput(array $extract): array
    {

        // First position keys
        $desiredOrder = ["UUID", "Name", "Reference flow", "Geography Code", "Geography"];

        if(!$this->outputGeography) {
            array_pop($desiredOrder);
        }

        foreach ($extract as &$activity) {
            // Extract and order the desired keys
            $sortedArr = [];
            foreach ($desiredOrder as $key) {
                if (isset($activity[$key])) {
                    $sortedArr[$key] = $activity[$key];
                }
            }

            // Remove the desired keys from the original array and sort the remaining keys
            $remainingArr = array_diff_key($activity, array_flip($desiredOrder));
            ksort($remainingArr);

            // Merge the arrays: desired keys first, then the remaining sorted keys
            $activity = array_merge($sortedArr, $remainingArr);
        }

        return $extract;

    }

    /**
     * Method to convert array into CSV
     */
    protected function convertArrayToCSV(array $array, string $fileName): string
    {

        if (count($array) == 0) {
            Response::send(404, "No data matching the request parameters");
        }

        file_put_contents($fileName, '');

        ob_start();
        $df = fopen($fileName, 'w');
        fprintf($df, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($df, array_keys(reset($array)));

        foreach ($array as $row) {
            fputcsv($df, $row);
        }

        fclose($df);
        return ob_get_clean();

    }

    /**
     * Method to convert CSV to Excel
     */
    protected function convertCsvToExcel(string $pathToCSV): void
    {
        // Load the CSV file
        $reader = IOFactory::createReader('Csv');
        $spreadsheet = $reader->load($pathToCSV);
        // Save it as an Excel file
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save(preg_replace('#\.csv$#', '.xlsx', $pathToCSV));

    }

    /**
     * Generate random string
     */
    public function generateRandomString($length = 16) {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;

    }

    /**
     * Unique setter
     * TODO: This should be split into one setter per property
     */
    private function setParameters(array $parameters): void
    {
        $this->inputActivityKeywords = $parameters['activity_keywords'] ?? [];
        $this->inputReferenceFlowKeywords = $parameters['reference_flow_keywords'] ?? [];
        $this->inputIntermediateExchangeKeywords = $parameters['intermediate_exchange_keywords'] ?? [];
        $this->outputName = $parameters['output_activity_name'] ?? true;
        $this->outputReferenceFlow = $parameters['output_reference_flow_name'] ?? true;
        $this->outputGeography = $parameters['output_geography'] ?? true;
        $this->outputIntermediateExchanges = $parameters['output_intermediate_exchanges'] ?? false;
        $this->outputImpactCategories = $parameters['impact_categories'] ?? [];
        $this->outputElementaryFlows = $parameters['elementary_flows'] ?? [];
    }

    // ------------------------------------------------------------------------------------

    /**
     * Method to format GET parameters recieved from client side
     * TODO: This formatting process should be executed on front side
     */
    public static function parseParameters(array $parameters): array
    {

        if (!isset($parameters['selects'], $parameters['inputs'])) {
            return [];
        }

        // Parse parameters
        $parameters = array_merge(
            json_decode($parameters['selects']),
            json_decode($parameters['inputs'])
        );

        foreach ($parameters as $parameter) {
            $data[$parameter->name] = $parameter->value;
        }

        // Format general parameters
        $response['source_id'] = $data['prm-source'];
        $response['output_activity_name'] = $data['output-activity-name'] ? 1 : 0;
        $response['output_reference_flow_name'] = $data['output-reference-flow-name'] ? 1 : 0;
        $response['output_geography'] = $data['output-geography'] ? 1 : 0;
        $response['output_intermediate_exchanges'] = $data['output-intermediate-exchanges'] ? 1 : 0;

        // Format keywords
        $response['activity_keywords'] = self::parseKeywords("activity-keyword", $data);
        $response['reference_flow_keywords'] = self::parseKeywords("reference-flow-keyword", $data);
        $response['intermediate_exchange_keywords'] = self::parseKeywords("intermediate-exchange-keyword", $data);

        // Format impact categories
        $i = 1;
        while ($i > 0) {
            if (isset($data["impact-category-rule-$i"], $data["impact-category-package-$i"], $data["impact-category-value-$i"])) {
                $indicator = explode(' | ', $data["impact-category-value-$i"]);
                $response['impact_categories'][] = [
                    'method' => $data["impact-category-package-$i"],
                    'category' => $indicator[0],
                    'indicator' => $indicator[1],
                    'rule' => $data["impact-category-rule-$i"]
                ];
                $i++;
            } else {
                $i = 0;
            }
        }

        // Format elementary flows
        $i = 1;
        while ($i > 0) {
            if (isset($data["elementary-flow-rule-$i"], $data["elementary-flow-value-$i"])) {
                $response['elementary_flows'][] = [
                    'name' => $data["elementary-flow-value-$i"],
                    'rule' => $data["elementary-flow-rule-$i"]
                ];
                $i++;
            } else {
                $i = 0;
            }
        }

        return $response;

    }

    /**
     * Method to format GET parameters recieved from client side, used by parseParameters()
     * TODO: This formatting process should be executed on front side
     */
    private static function parseKeywords(string $key, array $data)
    {
        $keywords = [];

        $i = 1;
        while ($i > 0) {
            if (isset($data["$key-rule-$i"], $data["$key-value-$i"])) {
                $keyword = preg_quote($data["$key-value-$i"], '#');
                switch($data["$key-rule-$i"]) {
                    case "begin":
                        $keyword = "^".$keyword;
                        break;
                    case "end":
                        $keyword = $keyword."$";
                }
                $keywords[] = $keyword;
                $i++;
            } else {
                $i = 0;
            }
        }

        return $keywords;
    }

}
