<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        $usedSlugs = [];

        DB::table('wash_locations')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $location) use (&$usedSlugs): void {
                $baseSlug = Str::slug((string) $location->name) ?: 'lava-rapido';
                $slug = $baseSlug;
                $suffix = 2;

                while (in_array($slug, $usedSlugs, true)) {
                    $slug = $baseSlug.'-'.$suffix;
                    $suffix++;
                }

                $usedSlugs[] = $slug;

                DB::table('wash_locations')
                    ->where('id', $location->id)
                    ->update(['slug' => $slug]);
            });

        Schema::table('wash_locations', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
