<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aligne la colonne payments.method sur la liste officielle :
     * Espèces (cash) + Mobile Money générique (mobile_money).
     */
    public function up(): void
    {
        // Table vide vérifiée le 2026-09-17 : conversion directe sans migration de données.
        DB::statement("ALTER TABLE `payments` MODIFY `method` ENUM('cash', 'mobile_money') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `payments` MODIFY `method` ENUM('cash', 'card', 'orange_money', 'wave') NOT NULL");
    }
};
