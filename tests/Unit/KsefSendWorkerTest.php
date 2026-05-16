<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\KsefSendQueueModel;
use PHPUnit\Framework\TestCase;

/**
 * Sanity tests dla KsefSendQueueModel — backoff/retry policy.
 *
 * Pelny test stanowy state machine (queued→signing→sending→completed)
 * wymaga DB + KSeF HTTP mock — to jest pokryte przez Integration suite
 * (przyszle). Tutaj testujemy:
 *   - poprawnosc tabeli RETRY_DELAYS_SECONDS (rosnaca, zgodna ze spec)
 *   - MAX_ATTEMPTS = 5
 */
class KsefSendWorkerTest extends TestCase
{
    public function testRetryDelayLadderIsSpecCompliant(): void
    {
        $expected = [60, 300, 1800, 7200, 43200];  // 1m, 5m, 30m, 2h, 12h
        $this->assertSame($expected, KsefSendQueueModel::RETRY_DELAYS_SECONDS);
    }

    public function testMaxAttemptsConstant(): void
    {
        $this->assertSame(5, KsefSendQueueModel::MAX_ATTEMPTS);
    }

    public function testRetryDelaysMonotonicallyIncreasing(): void
    {
        $delays = KsefSendQueueModel::RETRY_DELAYS_SECONDS;
        for ($i = 1; $i < count($delays); $i++) {
            $this->assertGreaterThan(
                $delays[$i - 1],
                $delays[$i],
                'Backoff musi byc rosnacy — w innym wypadku DoS na KSeF.'
            );
        }
    }

    public function testRetryDelaysAllPositive(): void
    {
        foreach (KsefSendQueueModel::RETRY_DELAYS_SECONDS as $d) {
            $this->assertGreaterThan(0, $d);
        }
    }
}
