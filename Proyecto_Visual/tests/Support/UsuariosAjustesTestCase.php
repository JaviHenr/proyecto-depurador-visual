<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class UsuariosAjustesTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);

        Schema::create('usuario', function (Blueprint $table): void {
            $table->increments('id_usuario');
            $table->string('nombre_usuario', 100);
            $table->string('email', 200)->unique();
            $table->string('rol', 30)->default('estudiante');
            $table->string('contrasena');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }
}
