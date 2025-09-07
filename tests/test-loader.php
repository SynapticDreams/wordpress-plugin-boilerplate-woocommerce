<?php
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    private Auspost_Shipping_Loader $loader;

    protected function setUp(): void
    {
        \WP_Mock::setUp();
        require_once __DIR__ . '/../auspost-shipping/includes/class-auspost-shipping-loader.php';
        $this->loader = new Auspost_Shipping_Loader();
    }

    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    public function test_stores_hooks_and_runs_them()
    {
        $component = new class {
            public function action_cb() {}
            public function filter_cb($value) { return $value; }
        };

        $this->loader->add_action('init', $component, 'action_cb', 20, 2);
        $this->loader->add_filter('the_content', $component, 'filter_cb', 15, 3);

        $reflection = new ReflectionClass($this->loader);
        $actionsProp = $reflection->getProperty('actions');
        $actionsProp->setAccessible(true);
        $filtersProp = $reflection->getProperty('filters');
        $filtersProp->setAccessible(true);

        $this->assertCount(1, $actionsProp->getValue($this->loader));
        $this->assertCount(1, $filtersProp->getValue($this->loader));

        \WP_Mock::userFunction('add_action', [
            'args'  => ['init', [$component, 'action_cb'], 20, 2],
            'times' => 1,
        ]);

        \WP_Mock::userFunction('add_filter', [
            'args'  => ['the_content', [$component, 'filter_cb'], 15, 3],
            'times' => 1,
        ]);

        $this->loader->run();

        $this->assertCount(1, $actionsProp->getValue($this->loader));
        $this->assertCount(1, $filtersProp->getValue($this->loader));
    }
}
