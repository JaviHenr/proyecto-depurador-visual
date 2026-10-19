<?php

namespace Tests\Browser;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\Client;

class E2EActividadTest extends TestCase
{
    private ?Client $navegador = null;

    private RemoteWebDriver $driver;

    private string $baseUri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUri = rtrim(
            getenv('PANTHER_EXTERNAL_BASE_URI') ?: 'http://127.0.0.1:8001',
            '/'
        );

        $rutaDriver = realpath(
            dirname(__DIR__, 2).'/drivers/chromedriver.exe'
        );

        if ($rutaDriver === false) {
            throw new \RuntimeException(
                'No se encontró drivers/chromedriver.exe en el proyecto.'
            );
        }

        $this->navegador = Client::createChromeClient($rutaDriver);
        $this->driver = $this->navegador->getWebDriver();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->navegador !== null) {
                $this->navegador->quit();
            }
        } finally {
            parent::tearDown();
        }
    }

    private function elemento(string $selector): WebDriverElement
    {
        return $this->driver->wait(15)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::cssSelector($selector)
            )
        );
    }

    public function test_inicio_de_sesion_con_credenciales_validas(): void
    {
        $this->driver->get($this->baseUri.'/login');

        $this->elemento('#email')
            ->sendKeys('e2e.actividad@example.test');

        $this->elemento('#password')
            ->sendKeys('SoloParaPruebas123!');

        $this->elemento('.login-form button[type="submit"]')->click();

        $this->driver->wait(15)->until(
            fn (RemoteWebDriver $driver): bool =>
                parse_url($driver->getCurrentURL(), PHP_URL_PATH) === '/dashboard'
        );

        $this->assertSame(
            '/dashboard',
            parse_url($this->driver->getCurrentURL(), PHP_URL_PATH),
            'El usuario debe acceder al dashboard después de iniciar sesión.'
        );
    }
}