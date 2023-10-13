<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class CheckIPService
{
    public function __construct(
        private LoggerInterface $logger,
    )
    {
    }

    function isIPInRange($ipToCheck, $ipRange): bool
    {

        $this->logger->info($ipToCheck);
        if (!$ipRange) {
            return true;
        }
        // Aufteilen des Range-Strings in einzelne IPs und Ranges
        $rangeList = explode(',', $ipRange);
        $ipToCheckBinary = inet_pton($ipToCheck);
        foreach ($rangeList as $range) {
            // Zerlege die IP-Range in Netzwerk- und Subnetzmaske
            if (strpos($range, '/') !== false) {
                list($network, $subnetMask) = explode('/', $range);

                // Konvertiere die IP-Adressen und Subnetzmasken in binäre Darstellung
                $networkBinary = inet_pton($network);

                if (!$ipToCheckBinary || !$networkBinary) {
                    break;
                }
                $subnetMaskBinary = pack('N', pow(2, 32) - pow(2, 32 - (int)$subnetMask));

                // Wende die Subnetzmaske an
                $networkBinaryMasked = $networkBinary & $subnetMaskBinary;
                $ipToCheckBinaryMasked = $ipToCheckBinary & $subnetMaskBinary;

                // Vergleiche die Netzwerkteile
                if ($networkBinaryMasked === $ipToCheckBinaryMasked) {
                    return true;
                };
            } else {
                // Für einzelne IPs (Range ohne Subnetzmaske)
                $ipRangeBinary = inet_pton($range);
                if ($ipToCheckBinary === $ipRangeBinary) {
                    return true;
                }
            }
        }

        $this->logger->error('blocked IP found', ['ip' => $ipToCheck]);
        return false;
    }

}