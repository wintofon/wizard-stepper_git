<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Utils/CNCCalculator.php';

class CNCCalculatorTest extends TestCase
{
    public function testRpm(): void
    {
        $rpm = CNCCalculator::rpm(150.0, 10.0); // vc=150 m/min, D=10mm
        $expected = (150.0 * 1000.0) / (M_PI * 10.0);
        $this->assertEqualsWithDelta($expected, $rpm, 0.001);
    }

    public function testFeedValid(): void
    {
        $rpm = 5000.0;
        $fz  = 0.02;
        $Z   = 4;
        $this->assertSame(5000.0 * 0.02 * 4, CNCCalculator::feed($rpm, $fz, $Z));
    }

    public function testFeedInvalidParametersReturnZero(): void
    {
        $this->assertSame(0.0, CNCCalculator::feed(-1.0, 0.02, 4));
        $this->assertSame(0.0, CNCCalculator::feed(1000.0, -1.0, 4));
        $this->assertSame(0.0, CNCCalculator::feed(1000.0, 0.02, 0));
    }

    public function testPotencia(): void
    {
        $Fct = 1000.0; // N
        $vc  = 200.0;  // m/min
        [$W, $HP] = CNCCalculator::potencia($Fct, $vc, 1.0);
        $expectedW  = ($Fct * $vc) / (60.0 * 1.0);
        $expectedHP = (($expectedW / 1000.0) * 1.341);
        $this->assertSame((int)round($expectedW), $W);
        $this->assertEqualsWithDelta(round($expectedHP, 2), $HP, 0.001);
    }
}
