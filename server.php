<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json');

class Estimator {
    public function __construct(){
        $this->numberOfStudies = 0;
        $this->studyGrowth = 0;
        $this->monthsToForecast = 0;

        $this->ramReqPerThousandStudies = 0;
        $this->costPerGbRamPerHour = 0;
        $this->storageReqPerStudy = 0;
        $this->storageCostPerGbPerMonth = 0;
    }

    public function extractGetRequest(){
        if(isset($_GET['numberOfStudies'])){
            $this->numberOfStudies = $_GET['numberOfStudies'];
            $this->studyGrowth = $_GET['studyGrowth'];
            $this->monthsToForecast = $_GET['monthsToForecast'];
            return;
            // return "Number of studies : ".$this->numberOfStudies."<br/>Study Growth : ".$this->studyGrowth."<br/>Months to Forecast : ".$this->monthsToForecast;
        } else {
            return "Input error.";
        }
    }

    public function extractPostRequest(){
        $rest_json = file_get_contents("php://input");
        $_POST = json_decode($rest_json, true);        
        
        if(isset($_POST['numberOfStudies'])){
            $this->numberOfStudies = $_POST['numberOfStudies'];
            $this->studyGrowth = $_POST['studyGrowth'];
            $this->monthsToForecast = $_POST['monthsToForecast'];
            return;
            // return "Number of studies : ".$this->numberOfStudies."<br/>Study Growth : ".$this->studyGrowth."<br/>Months to Forecast : ".$this->monthsToForecast;
        } else {
            return "Input error.";
        }
    }

    public function setRamRates($ramReqPerThousandStudies, $costPerGbRamPerHour){
        if(is_numeric($ramReqPerThousandStudies) && (is_numeric($costPerGbRamPerHour))){
            $this->ramReqPerThousandStudies = $ramReqPerThousandStudies;
            $this->costPerGbRamPerHour = $costPerGbRamPerHour;
        } else {
            return "Input error.";
        }
    }

    public function setStorageRates($storageReqPerStudy, $storageCostPerGbPerMonth){
        if(is_numeric($storageReqPerStudy) && (is_numeric($storageCostPerGbPerMonth))){
            $this->storageReqPerStudy = $storageReqPerStudy;
            $this->storageCostPerGbPerMonth = $storageCostPerGbPerMonth;
        } else {
            return "Input error.";
        }
    }    

    public function computeRamCost($numberOfStudies, $numberOfDays){
        $ramReqPerThousandStudies = $this->ramReqPerThousandStudies;
        $costPerGbRamPerHour = $this->costPerGbRamPerHour;

        $ramReq = ($numberOfStudies/1000)*$ramReqPerThousandStudies;
        $ramCostUsdPerMonth = $ramReq*$costPerGbRamPerHour*24*$numberOfDays;
        return $ramCostUsdPerMonth;
    }

    public function computeStorageCost($numberOfStudies){
        $storageReqPerStudy = $this->storageReqPerStudy;
        $storageCostPerGbPerMonth = $this->storageCostPerGbPerMonth;

        $storageReq = $numberOfStudies*10;
        $storageCostUsdPerMonth = ($storageReq/1000)*$storageCostPerGbPerMonth;
        return $storageCostUsdPerMonth;        
    }

    public function computeEstimates(){
        $outputArray = array();
        $totalStorageCost = 0;
        $numberOfStudies = $this->numberOfStudies;
        $studyGrowth = $this->studyGrowth;
        $monthsToForecast = $this->monthsToForecast;

        $start = $month = strtotime("now");
        $endString = "+".$monthsToForecast." months";
        $end = strtotime($endString, $start);
        while($month < $end)
        {
            
            $monthYear = date('M Y', $month);
            
            $monthInt = date('n', strtotime($month));
            $yearInt = date('Y', strtotime($month));
            $daysThisMonth = cal_days_in_month(CAL_GREGORIAN, $monthInt, $yearInt);
            
            $month = strtotime("+1 month", $month);
            
            $monthRamCost = $this->computeRamCost($numberOfStudies, $daysThisMonth);
            $monthStorageCost = $this->computeStorageCost($numberOfStudies);
            $totalStorageCost = $totalStorageCost + $monthStorageCost;

            $costForecast = $monthRamCost + $totalStorageCost;

            $numberOfStudiesString = number_format($numberOfStudies, 0, '', ',');
            $costForecastString = "$".number_format($costForecast, 2, '.', ',');
            
            $elementArray = array(
                'monthYear' => $monthYear, 
                'numberOfStudies' => $numberOfStudiesString, 
                'costForecast' => $costForecastString
            );

            array_push($outputArray, $elementArray);
            $numberOfStudies = $numberOfStudies + ($numberOfStudies*($studyGrowth/100));
        }        

        return $outputArray = json_encode($outputArray);
    }

    public function returnInput(){
        return ("
        Number of studies : ".$this->numberOfStudies."<br/>
        Study Growth : ".$this->studyGrowth."<br/>
        Months to Forecast : ".$this->monthsToForecast."<br/>
        RAM Requirement per 1000 studies in MB: ".$this->ramReqPerThousandStudies."<br/>
        Cost per 1GB of RAM per hour in USD: ".$this->costPerGbRamPerHour."<br/>
        Storage Requirement per study in MB: ".$this->storageReqPerStudy."<br/>
        Cost per 1GB of Storage per month in USD: ".$this->storageCostPerGbPerMonth."<br/>
        ");
    }
}

$estimator = new Estimator();
// echo $estimator->extractGetRequest();
echo $estimator->extractPostRequest();
echo $estimator->setRamRates(500, 0.00553);
echo $estimator->setStorageRates(10, 0.10);
// echo $estimator->returnInput();
// echo $estimator->computeRamCost(35000, 31);
// echo $estimator->computeStorageCost(35000);
echo $estimator->computeEstimates();
