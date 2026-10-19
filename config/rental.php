<?php

return [

    /*
    | Primer día de operación en el aplicativo. Ningún día anterior cuenta en
    | esperado, ingresos ni reportes, aunque existan contratos con fecha previa.
    */
    'fecha_salida_produccion' => env('RENTAL_FECHA_SALIDA_PRODUCCION', '2026-05-10'),

];
