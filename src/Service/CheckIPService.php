<?php

namespace App\Service;

class CheckIPService
{

    function isIPInRange($ipToCheck, $ipRange): bool
    {

        if (!$ipRange) {
            return true;
        }
        // Aufteilen des Range-Strings in einzelne IPs und Ranges
        $rangeList = explode(',', $ipRange);

        foreach ($rangeList as $range) {
            // Zerlege die IP-Range in Netzwerk- und Subnetzmaske
            if (strpos($range, '/') !== false) {
                list($network, $subnetMask) = explode('/', $range);

                // Konvertiere die IP-Adressen und Subnetzmasken in binäre Darstellung
                $networkBinary = inet_pton($network);
                $ipToCheckBinary = inet_pton($ipToCheck);
                if (!$ipToCheckBinary || !$networkBinary) {
                    return false;
                }
                $subnetMaskBinary = pack('N', pow(2, 32) - pow(2, 32 - (int)$subnetMask));

                // Wende die Subnetzmaske an
                $networkBinaryMasked = $networkBinary & $subnetMaskBinary;
                $ipToCheckBinaryMasked = $ipToCheckBinary & $subnetMaskBinary;

                // Vergleiche die Netzwerkteile
                return $networkBinaryMasked === $ipToCheckBinaryMasked;
            } else {
                // Für einzelne IPs (Range ohne Subnetzmaske)
                $ipToCheckBinary = inet_pton($ipToCheck);
                $ipRangeBinary = inet_pton($range);

                return $ipToCheckBinary === $ipRangeBinary;
            }
        }
        return false;
    }

}