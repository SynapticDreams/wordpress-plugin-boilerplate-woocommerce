<?php
use PHPUnit\Framework\TestCase;

class RegisterMethodTest extends TestCase
{
    private $auspostShipping;
    private $loaderMock;

    protected function setUp(): void
    {
        \WP_Mock::setUp();
        require_once __DIR__ . '/../auspost-shipping/includes/class-auspost-shipping-loader.php';
        require_once __DIR__ . '/../auspost-shipping/includes/class-auspost-shipping.php';

        $this->loaderMock = $this->createMock(Auspost_Shipping_Loader::class);

        $reflection = new ReflectionClass(Auspost_Shipping::class);
        $this->auspostShipping = $reflection->newInstanceWithoutConstructor();

        $loaderProp = $reflection->getProperty('loader');
        $loaderProp->setAccessible(true);
        $loaderProp->setValue($this->auspostShipping, $this->loaderMock);
    }

    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    public function test_register_shipping_method_adds_auspost()
    {
        $methods = ['existing_method' => 'Existing_Method'];

        $result = $this->auspostShipping->register_shipping_method($methods);

        $this->assertArrayHasKey('auspost_shipping', $result);
        $this->assertSame('Auspost_Shipping_Method', $result['auspost_shipping']);
    }
}
