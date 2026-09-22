<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    // La transacción se abre aquí para que también se use al invocar up/down en pruebas.
    public $withinTransaction = false;

    public function up(): void
    {
        $this->exigirPostgres();
        DB::transaction(function () {
            // Bloquea cambios mientras se trasladan los vínculos del esquema anterior.
            DB::statement('LOCK TABLE seccion, actividad IN ACCESS EXCLUSIVE MODE');
            if (!Schema::hasColumn('seccion', 'id_actividad') || Schema::hasTable('seccion_actividad')) {
                throw new RuntimeException('Se esperaba seccion.id_actividad y ninguna tabla seccion_actividad. Revisa el esquema antes de migrar.');
            }
            Schema::create('seccion_actividad', function (Blueprint $table) {
                // INTEGER coincide con las claves que compartiste, sin timestamps adicionales.
                $table->integer('id_seccion');
                $table->integer('id_actividad');
                $table->primary(['id_seccion', 'id_actividad']);
                $table->index('id_actividad');
                $table->foreign('id_seccion', 'fk_seccion_actividad_seccion')
                    ->references('id_seccion')->on('seccion')->restrictOnDelete();
                $table->foreign('id_actividad', 'fk_seccion_actividad_actividad')
                    ->references('id_actividad')->on('actividad')->restrictOnDelete();
            });

            $anteriores = DB::table('seccion')->whereNotNull('id_actividad')->count();
            DB::table('seccion_actividad')->insertUsing(
                ['id_seccion', 'id_actividad'],
                DB::table('seccion')->select(['id_seccion', 'id_actividad'])->whereNotNull('id_actividad')
            );
            if (DB::table('seccion_actividad')->count() !== $anteriores) {
                throw new RuntimeException('No se copiaron todos los vínculos. La transacción se ha cancelado.');
            }
            // PostgreSQL retira también la FK local que depende de esta columna.
            // Sin CASCADE: si una vista externa depende de ella, falla y se revierte todo.
            Schema::table('seccion', fn (Blueprint $table) => $table->dropColumn('id_actividad'));
        });
    }

    public function down(): void
    {
        $this->exigirPostgres();
        DB::transaction(function () {
            DB::statement('LOCK TABLE seccion, actividad, seccion_actividad IN ACCESS EXCLUSIVE MODE');
            $multiples = DB::table('seccion_actividad')->select('id_seccion')
                ->groupBy('id_seccion')->havingRaw('COUNT(*) > 1')->exists();
            if ($multiples) {
                throw new RuntimeException('No es posible volver al esquema anterior: una sección tiene varias actividades. No se descartará ningún vínculo.');
            }
            Schema::table('seccion', function (Blueprint $table) {
                $table->integer('id_actividad')->nullable();
                $table->foreign('id_actividad', 'fk_seccion_actividad')
                    ->references('id_actividad')->on('actividad');
            });
            DB::statement('UPDATE seccion AS s SET id_actividad = sa.id_actividad FROM seccion_actividad AS sa WHERE s.id_seccion = sa.id_seccion');
            Schema::drop('seccion_actividad');
        });
    }

    private function exigirPostgres(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException('Esta migración está preparada para tu esquema PostgreSQL. No la ejecutes sobre SQLite o MySQL.');
        }
    }
};
