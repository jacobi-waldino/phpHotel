<?php

function validateDate($date1, $date2) {
    if (empty($date1) || empty($date2)) {
        return false;
    }

    try {
        $format = 'Y-m-d';
        $date1Obj = DateTime::createFromFormat($format, $date1);
        $date2Obj = DateTime::createFromFormat($format, $date2);

        if (!$date1Obj || !$date2Obj) {
            return false; 
        }

        return ($date1Obj < $date2Obj);
    } catch (Exception $e) {
        error_log("Date validation error: " . $e->getMessage());
        return false;
    }
}

?>