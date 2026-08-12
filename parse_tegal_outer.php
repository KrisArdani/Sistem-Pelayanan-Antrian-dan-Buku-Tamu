<?php
/**
 * Extract exact outer boundary of Kota Tegal from osm_tegal_full.json
 */

$jsonStr = file_get_contents(__DIR__ . '/osm_tegal_full.json');
$data = json_decode($jsonStr, true);

$nodes = [];
$ways = [];
$tegalRel = null;

foreach ($data['elements'] as $el) {
    if ($el['type'] === 'node') {
        $nodes[$el['id']] = [$el['lon'], $el['lat']];
    } elseif ($el['type'] === 'way') {
        $ways[$el['id']] = $el;
    } elseif ($el['type'] === 'relation' && $el['id'] == 9686828) {
        $tegalRel = $el;
    }
}

if ($tegalRel) {
    $outerWays = [];
    foreach ($tegalRel['members'] as $m) {
        if ($m['type'] === 'way' && ($m['role'] === 'outer' || $m['role'] === '')) {
            $outerWays[] = $m['ref'];
        }
    }
    
    $segments = [];
    foreach ($outerWays as $wId) {
        if (!isset($ways[$wId])) continue;
        $w = $ways[$wId];
        $pts = [];
        foreach ($w['nodes'] as $nId) {
            if (isset($nodes[$nId])) {
                $pts[] = $nodes[$nId];
            }
        }
        if (count($pts) > 1) {
            $segments[] = $pts;
        }
    }
    
    $ring = mergeSegs($segments);
    file_put_contents(__DIR__ . '/kota_tegal_outer_ring.json', json_encode($ring, JSON_PRETTY_PRINT));
    echo "Saved outer ring (" . count($ring) . " points) to kota_tegal_outer_ring.json\n";
}

function mergeSegs($segments) {
    if (empty($segments)) return [];
    $merged = array_shift($segments);
    $max = count($segments) * count($segments) + 50;
    $iter = 0;
    
    while (!empty($segments) && $iter < $max) {
        $iter++;
        $found = false;
        $last = end($merged);
        
        foreach ($segments as $i => $s) {
            if (pEq($last, $s[0])) {
                array_splice($merged, count($merged), 0, array_slice($s, 1));
                unset($segments[$i]); $segments = array_values($segments);
                $found = true; break;
            }
            if (pEq($last, end($s))) {
                $rev = array_reverse($s);
                array_splice($merged, count($merged), 0, array_slice($rev, 1));
                unset($segments[$i]); $segments = array_values($segments);
                $found = true; break;
            }
        }
        
        if (!$found) {
            $first = $merged[0];
            foreach ($segments as $i => $s) {
                if (pEq($first, end($s))) {
                    $merged = array_merge($s, array_slice($merged, 1));
                    unset($segments[$i]); $segments = array_values($segments);
                    $found = true; break;
                }
                if (pEq($first, $s[0])) {
                    $rev = array_reverse($s);
                    $merged = array_merge($rev, array_slice($merged, 1));
                    unset($segments[$i]); $segments = array_values($segments);
                    $found = true; break;
                }
            }
        }
        if (!$found) break;
    }
    return $merged;
}

function pEq($a, $b) {
    return abs($a[0] - $b[0]) < 0.000001 && abs($a[1] - $b[1]) < 0.000001;
}
