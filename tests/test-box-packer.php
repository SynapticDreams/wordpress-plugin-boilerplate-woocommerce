<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../auspost-shipping/includes/class-box-packer.php';

class BoxPackerTest extends TestCase
{
    public function test_overflow_unpacked_items()
    {
        $boxes = [
            ['length' => 10, 'width' => 10, 'height' => 10, 'max_weight' => 5, 'padding' => 0],
        ];
        $items = [
            ['length' => 20, 'width' => 20, 'height' => 20, 'weight' => 1, 'qty' => 1],
        ];

        $packer = new Box_Packer($boxes);
        $packages = $packer->pack($items);

        $this->assertCount(0, $packages);
        $this->assertCount(1, $packer->get_unpacked_items());
    }

    public function test_dimensional_weight_used()
    {
        $boxes = [
            ['length' => 100, 'width' => 100, 'height' => 100, 'max_weight' => 50, 'padding' => 0],
        ];
        $items = [
            ['length' => 50, 'width' => 50, 'height' => 50, 'weight' => 1, 'qty' => 1],
        ];

        $packer = new Box_Packer($boxes);
        $packages = $packer->pack($items);

        $this->assertCount(1, $packages);
        $pkg = $packages[0];
        $actual = 1; // actual weight
        $dim = (100*100*100)/5000; // 200 kg
        $this->assertEquals($dim, $pkg['weight']);
        $this->assertGreaterThan($actual, $pkg['weight']);
    }
}
