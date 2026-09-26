<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('responsible_parties as d')
            ->join('responsible_parties as k', function ($join): void {
                $join->on('k.region_code', '=', 'd.region_code')->on('k.type', '=', 'd.type')->on('k.id', '<', 'd.id');
            })
            ->groupBy('d.id')
            ->selectRaw('d.id as duplicate_id, min(k.id) as keep_id')
            ->get();

        foreach ($duplicates as $row) {
            DB::table('requests')->where('responsible_party_id', $row->duplicate_id)->update(['responsible_party_id' => $row->keep_id]);
            DB::table('requests')->where('redirected_party_id', $row->duplicate_id)->update(['redirected_party_id' => $row->keep_id]);
            DB::table('responsible_parties')->where('id', $row->duplicate_id)->delete();
        }

        Schema::table('responsible_parties', function (Blueprint $t) {
            $t->dropUnique(['region_code', 'type', 'name']);
            $t->unique(['region_code', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('responsible_parties', function (Blueprint $t) {
            $t->dropUnique(['region_code', 'type']);
            $t->unique(['region_code', 'type', 'name']);
        });
    }
};
