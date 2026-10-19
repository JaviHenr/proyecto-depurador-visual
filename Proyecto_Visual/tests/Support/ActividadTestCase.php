<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class ActividadTestCase extends TestCase
{
    protected ?string $carpetaTrazas = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->withoutVite();
        EsquemaActividad::crear();

        $this->carpetaTrazas = sys_get_temp_dir().DIRECTORY_SEPARATOR.'actividad_'.Str::uuid();
        File::makeDirectory($this->carpetaTrazas, 0700, true);
        config(['depurador_json.directorio' => $this->carpetaTrazas]);
        File::copy(base_path('tests/Fixtures/actividad/traza_factorial_5.json'),
            $this->carpetaTrazas.DIRECTORY_SEPARATOR.'traza_factorial_5.json');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->carpetaTrazas !== null) {
                File::deleteDirectory($this->carpetaTrazas);
            }
        } finally {
            parent::tearDown();
        }
    }
}
