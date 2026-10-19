<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fixture del esquema educativo, independiente de las migraciones existentes. */
class EsquemaActividad
{
    public static function crear(): void
    {
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
        Schema::create('actividad', function (Blueprint $table): void {
            $table->increments('id_actividad');
            $table->string('nombre_actividad', 150);
            $table->string('descripcion_actividad', 200)->nullable();
            $table->string('instrucciones', 200)->nullable();
            $table->timestamp('fecha_creacion_actividad')->nullable();
        });
        Schema::create('seccion', function (Blueprint $table): void {
            $table->increments('id_seccion');
            $table->unsignedInteger('id_usuario');
            $table->string('nombre_seccion', 200);
            $table->string('descripcion_seccion', 300)->nullable();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario');
        });
        Schema::create('seccion_estudiante', function (Blueprint $table): void {
            $table->unsignedInteger('id_seccion');
            $table->unsignedInteger('id_usuario');
            $table->timestamp('fecha_inscripcion')->nullable();
            $table->primary(['id_seccion', 'id_usuario']);
            $table->foreign('id_seccion')->references('id_seccion')->on('seccion')->cascadeOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->cascadeOnDelete();
        });
        Schema::create('seccion_actividad', function (Blueprint $table): void {
            $table->unsignedInteger('id_seccion');
            $table->unsignedInteger('id_actividad');
            $table->primary(['id_seccion', 'id_actividad']);
            $table->foreign('id_seccion')->references('id_seccion')->on('seccion');
            $table->foreign('id_actividad')->references('id_actividad')->on('actividad');
        });
        Schema::create('codigo', function (Blueprint $table): void {
            $table->increments('id_codigo');
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_actividad')->nullable();
            $table->string('nombre_codigo', 150);
            $table->string('nombre_archivo', 200);
            $table->string('formato', 20);
            $table->text('contenido_codigo');
            $table->date('fecha_creacion_codigo');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario');
            $table->foreign('id_actividad')->references('id_actividad')->on('actividad');
        });
    }
}
