<?php
/**
 * Inspect exact coordinates of Tegal Timur and Tegal Selatan polygons
 */

$json = json_decode(file_get_contents(__DIR__ . '/js/geojson/kota_tegal_kecamatan.geojson'), true);

foreach ($json['features'] as $f) {
    $name = $f['properties']['nama_kec'];
    $coords = $f['geometry']['coordinates'][0];
    echo "Feature: $name (" . count($coords) . " points)\n";
    echo "  Start: [{$coords[0][0]}, {$coords[0][1]}]\n";
    echo "  Mid:   [{$coords[floor(count($coords)/2)][0]}, {$coords[floor(count($coords)/2)][1]}]\n";
    echo "  End:   [{$coords[count($coords)-1][0]}, {$coords[count($coords)-1][1]}]\n\n";
}
