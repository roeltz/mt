<?php

function lapseToString($seconds, $deep = 2) {

    if ($deep <= 0) {
        return "";
    }
    
    $magnitudes = array(1, 60, 60, 24, 30.41, 12, 12);
    $suffix = array("seg", "seg", "min", "h", "d", "m", "a");
    $suffixSng = array("segundo", "segundo", "minuto", "hora", "día", "mes", "año");
    $suffixPl = array("segundos", "segundos", "minutos", "horas", "días", "meses", "años");
    
    $tm = $seconds;
    
    for ($i = 0, $x = 1; $tm > $magnitudes[$i] && $i < count($magnitudes) - 1; $i++) {
        $tm /= $magnitudes[$i];
        $x *= $magnitudes[$i];
    }
    
    $remainder = $x * ($tm - floor($tm));
    $subcomponent = "";
    if ($remainder > 1) {
        $subcomponent = lapseToString($remainder, $deep - 1);
    }
    if ($subcomponent) {
        $subcomponent = "y ".$subcomponent;
    }
    
    $tm = floor($tm);
    $unit = $tm == 1 ? $suffixSng[$i] : $suffixPl[$i];
    $ret = "$tm $unit $subcomponent";
    
    return trim($ret);
}

function ago($refdate, $deep = 2) {
    return lapseToString(abs(time() - $refdate), $deep);
}
